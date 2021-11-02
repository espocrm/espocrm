<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Formula\Functions\RecordGroup;

use Espo\Core\Formula\{
    Functions\BaseFunction,
    ArgumentList,
};

use Espo\Core\Di;

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
        $order = $this->evaluate($args[2]) ?? 'ASC';

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($entityType);

        $whereClause = [];

        if (count($args) <= 4) {
            $filter = null;

            if (count($args) == 4) {
                $filter = $this->evaluate($args[3]);
            }

            if ($filter && !is_string($filter)) {
                $this->throwBadArgumentType(4, 'string');
            }

            if ($filter) {
                $builder->withPrimaryFilter($filter);
            }
        }
        else {
            $i = 3;

            while ($i < count($args) - 1) {
                $key = $this->evaluate($args[$i]);
                $value = $this->evaluate($args[$i + 1]);

                $whereClause[] = [$key => $value];

                $i = $i + 2;
            }
        }

        $queryBuilder = $builder->buildQueryBuilder();

        if (!empty($whereClause)) {
            $queryBuilder->where($whereClause);
        }

        if ($orderBy) {
            $queryBuilder->order($orderBy, $order);
        }

        $queryBuilder->select(['id']);

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
