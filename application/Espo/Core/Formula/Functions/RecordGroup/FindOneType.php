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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Formula\EvaluatedArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;
use Espo\Core\Formula\Functions\RecordGroup\Util\FindQueryUtil;
use Espo\Core\Select\Primary\Filters\All;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

/**
 * @noinspection PhpUnused
 */
class FindOneType  implements Func
{
    public function __construct(
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private FindQueryUtil $findQueryUtil,
    ) {}

    public function process(EvaluatedArgumentList $arguments): ?string
    {
        if (count($arguments) < 1) {
            throw TooFewArguments::create(1);
        }

        $entityType = $arguments[0];
        $orderBy = $arguments[1] ?? null;
        $order = $arguments[2] ?? null;

        if (!is_string($entityType)) {
            throw BadArgumentType::create(1, 'string');
        }

        if ($orderBy !== null && !is_string($orderBy)) {
            throw BadArgumentType::create(2, 'string|null');
        }

        if ($order !== null && !is_bool($order) && !is_string($order)) {
            throw BadArgumentType::create(3, 'string|bool|null');
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->withPrimaryFilter(All::NAME)
            ->from($entityType);

        $this->findQueryUtil->applyOrder($builder, $orderBy, $order, 3);

        $whereClause = [];

        if (count($arguments) <= 4) {
            $filter = null;

            if (count($arguments) === 4) {
                $filter = $arguments[3];
            }

            $this->findQueryUtil->applyFilter($builder, $filter, 4);
        } else {
            $i = 3;

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
            throw new NotAllowedUsage($e->getMessage(), $e->getCode(), $e);
        }

        if (!empty($whereClause)) {
            $queryBuilder->where($whereClause);
        }

        $queryBuilder->select([Attribute::ID]);

        $entity = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($queryBuilder->build())
            ->findOne();

        return $entity?->getId();
    }
}
