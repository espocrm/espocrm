<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Core\Formula\Exceptions\BadArgumentValue;
use Espo\Core\Formula\Exceptions\Error as FormulaError;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;
use Espo\Core\Formula\Functions\RecordGroup\Util\FindQueryUtil;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Order;

class FindManyType implements Func
{
    public function __construct(
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory
    ) {}

    /**
     * @return string[]
     * @inheritDoc
     */
    public function process(EvaluatedArgumentList $arguments): array
    {
        if (count($arguments) < 4) {
            throw TooFewArguments::create(4);
        }

        $entityType = $arguments[0];
        $limit = $arguments[1];
        $orderBy = $arguments[2];
        $order = $arguments[3] ?? Order::ASC;

        if (!is_string($entityType)) {
            throw BadArgumentType::create(1, 'string');
        }

        if (!is_int($limit)) {
            throw BadArgumentType::create(2, 'int');
        }

        if ($orderBy !== null && !is_string($orderBy)) {
            throw BadArgumentType::create(3, 'string|null');
        }

        if (!is_bool($order) && !is_string($order)) {
            throw BadArgumentType::create(4, 'string|bool');
        }

        if (is_string($order)) {
            $order = strtoupper($order);

            if ($order !== Order::ASC && $order !== Order::DESC) {
                throw BadArgumentValue::create(4, 'Bad order value.');
            }
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($entityType);

        $whereClause = [];

        if (count($arguments) <= 5) {
            $filter = null;

            if (count($arguments) === 5) {
                $filter = $arguments[4];
            }

            (new FindQueryUtil())->applyFilter($builder, $filter, 5);
        } else {
            $i = 4;

            while ($i < count($arguments) - 1) {
                $key = $arguments[$i];
                $value = $arguments[$i + 1];

                $whereClause[] = [$key => $value];

                $i = $i + 2;
            }
        }

        try {
            $queryBuilder = $builder->buildQueryBuilder();
        } catch (BadRequest|Forbidden $e) {
            throw new FormulaError($e->getMessage(), $e->getCode(), $e);
        }

        if (!empty($whereClause)) {
            $queryBuilder->where($whereClause);
        }

        if ($orderBy) {
            $queryBuilder->order($orderBy, $order);
        }

        $queryBuilder
            ->select([Attribute::ID])
            ->limit(0, $limit);

        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($queryBuilder->build())
            ->find();

        return array_map(
            fn (Entity $entity) => $entity->getId(),
            iterator_to_array($collection)
        );
    }
}
