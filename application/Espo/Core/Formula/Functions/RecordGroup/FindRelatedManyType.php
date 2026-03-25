<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Formula\Functions\RecordGroup;

use Espo\Core\Acl\SystemRestriction;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Formula\EvaluatedArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;
use Espo\Core\Formula\Functions\RecordGroup\Util\FindQueryUtil;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Select\Helpers\RandomStringGenerator;
use Espo\Core\Select\Primary\Filters\All;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Type\RelationType;

/**
 * @noinspection PhpUnused
 */
class FindRelatedManyType implements Func
{
    public function __construct(
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private FindQueryUtil $findQueryUtil,
        private RandomStringGenerator $randomStringGenerator,
        private SystemRestriction $systemRestriction,
    ) {}

    /**
     * @return string[]
     * @throws Error
     * @throws TooFewArguments
     * @throws BadArgumentType
     */
    public function process(EvaluatedArgumentList $arguments): array
    {
        if (count($arguments) < 4) {
            throw TooFewArguments::create(4);
        }

        $entityManager = $this->entityManager;

        $entityType = $arguments[0];
        $id = $arguments[1];
        $link = $arguments[2];
        $limit = $arguments[3];

        $orderBy = null;
        $order = null;

        if (count($arguments) > 4) {
            $orderBy = $arguments[4];
        }

        if (count($arguments) > 5) {
            $order = $arguments[5];
        }

        if (!$entityType || !is_string($entityType)) {
            throw BadArgumentType::create(1, 'string');
        }

        if (!is_string($id)) {
            throw BadArgumentType::create(2, 'string');
        }

        if (!$link || !is_string($link)) {
            throw BadArgumentType::create(3, 'string');
        }

        if (!is_int($limit)) {
            throw BadArgumentType::create(4, 'int');
        }

        if ($orderBy !== null && !is_string($orderBy)) {
            throw BadArgumentType::create(5, 'string|null');
        }

        if ($order !== null && !is_string($order) && !is_bool($order)) {
            throw BadArgumentType::create(6, 'string|bool|null');
        }

        $this->assertLinkRead($entityType, $link);

        $entity = $entityManager->getEntityById($entityType, $id);

        if (!$entity) {
            return [];
        }

        if (!$entity instanceof CoreEntity) {
            throw new Error("Non-core entity.");
        }

        $relationType = $entity->getRelationType($link);

        if (
            in_array($relationType, [
                RelationType::BELONGS_TO,
                RelationType::HAS_ONE,
                RelationType::BELONGS_TO_PARENT,
            ])
        ) {
            throw new NotAllowedUsage("Not supported link type '$relationType'.");
        }

        $foreignEntityType = $entity->getRelationParam($link, RelationParam::ENTITY);

        if (!$foreignEntityType) {
            throw new NotAllowedUsage("Bad or not supported link '$link'.");
        }

        $foreignLink = $entity->getRelationParam($link, RelationParam::FOREIGN);

        if (!$foreignLink) {
            throw new NotAllowedUsage("Not supported link '$link'.");
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->withPrimaryFilter(All::NAME)
            ->from($foreignEntityType);

        $this->findQueryUtil->applyOrder($builder, $orderBy, $order, 6);

        $whereClause = [];

        if (count($arguments) <= 7) {
            $filter = null;
            if (count($arguments) == 7) {
                $filter = $arguments[6];
            }

            $this->findQueryUtil->applyFilter($builder, $filter, 7);
        } else {
            $i = 6;

            while ($i < count($arguments) - 1) {
                $key = $arguments[$i];
                $value = $arguments[$i + 1];

                $this->findQueryUtil->assertWhereClauseKeyValid($entityType, $key);

                $whereClause[] = [$key => $value];

                $i = $i + 2;
            }
        }

        try {
            $queryBuilder = $builder->buildQueryBuilder();
        } catch (BadRequest|Forbidden $e) {
            throw new NotAllowedUsage($e->getMessage(), 0, $e);
        }

        if (!empty($whereClause)) {
            $queryBuilder->where($whereClause);
        }

        if ($relationType === RelationType::HAS_CHILDREN) {
            $queryBuilder->where([
                $foreignLink . 'Id' => $entity->getId(),
                $foreignLink . 'Type' => $entity->getEntityType(),
            ]);
        } else {
            $alias = $foreignLink . $this->generateRandomString();

            $queryBuilder
                ->join($foreignLink, $alias)
                ->where([
                    $alias . '.id' => $entity->getId(),
                ]);
        }

        $queryBuilder->limit(0, $limit);

        $collection = $entityManager
            ->getRDBRepository($foreignEntityType)
            ->clone($queryBuilder->build())
            ->select([Attribute::ID])
            ->find();

        $idList = [];

        foreach ($collection as $e) {
            $idList[] = $e->getId();
        }

        return $idList;
    }

    private function generateRandomString(): string
    {
        return $this->randomStringGenerator->generate();
    }

    /**
     * @throws NotAllowedUsage
     */
    private function assertLinkRead(string $entityType, string $link): void
    {
        if (!$this->systemRestriction->checkLinkRead($entityType, $link) ) {
            throw new NotAllowedUsage("Cannot read restricted link $entityType.$link.");
        }
    }
}
