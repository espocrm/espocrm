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

namespace Espo\Core\Formula\Functions\EntityGroup;

use Espo\Core\Acl\SystemRestriction;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Formula\EvaluatedArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Core\Formula\Exceptions\NotPassedEntity;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;
use Espo\Core\Formula\Functions\RecordGroup\Util\FindQueryUtil;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @noinspection PhpUnused
 */
class CountRelatedType implements Func
{
    public function __construct(
        private SystemRestriction $systemRestriction,
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private FindQueryUtil $findQueryUtil,
        private ?Entity $entity = null,
    ) {}

    public function process(EvaluatedArgumentList $arguments): int
    {
        $entity = $this->entity ?? throw new NotPassedEntity();

        if (!$entity instanceof CoreEntity) {
            throw new Error("Non-core entity.");
        }

        if (count($arguments) < 1) {
            throw TooFewArguments::create(1);
        }

        $link = $arguments[0];

        if (!is_string($link)) {
            throw BadArgumentType::create(1, 'string');
        }

        $entityType = $entity->getEntityType();

        if (!$this->systemRestriction->checkLinkRead($entityType, $link)) {
            throw new NotAllowedUsage("Cannot read restricted field $entityType.$link.");
        }

        $filter = null;

        if (count($arguments) > 1) {
            $filter = $arguments[1];
        }

        $foreignEntityType = $entity->getRelationParam($link, RelationParam::ENTITY);

        if (empty($foreignEntityType)) {
            throw new Error("Not supported link $link.");
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($foreignEntityType);

        if ($filter) {
            $this->findQueryUtil->applyFilter($builder, $filter, 2);
        }

        try {
            $query = $builder->build();
        } catch (BadRequest|Forbidden $e) {
            throw new Error($e->getMessage());
        }

        return $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, $link)
            ->clone($query)
            ->count();
    }
}
