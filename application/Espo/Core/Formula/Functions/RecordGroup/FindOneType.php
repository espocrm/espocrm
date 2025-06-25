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
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Exceptions\Error as FormulaError;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Di;
use Espo\Core\Formula\Functions\RecordGroup\Util\FindQueryUtil;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Order;

/**
 * @noinspection PhpUnused
 */
class FindOneType extends BaseFunction implements
    Di\EntityManagerAware,
    Di\SelectBuilderFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\SelectBuilderFactorySetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 3) {
            $this->throwTooFewArguments(3);
        }

        $entityType = $this->evaluate($args[0]);
        $orderBy = $this->evaluate($args[1]);
        $order = $this->evaluate($args[2]) ?? Order::ASC;

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($entityType);

        $whereClause = [];

        if (count($args) <= 4) {
            $filter = null;

            if (count($args) === 4) {
                $filter = $this->evaluate($args[3]);
            }

            (new FindQueryUtil())->applyFilter($builder, $filter, 4);
        } else {
            $i = 3;

            while ($i < count($args) - 1) {
                $key = $this->evaluate($args[$i]);
                $value = $this->evaluate($args[$i + 1]);

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

        $queryBuilder->select([Attribute::ID]);

        $entity = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($queryBuilder->build())
            ->findOne();

        if ($entity) {
            return $entity->getId();
        }

        return null;
    }
}
