<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\ORM\Entity;
use \Espo\Core\Exceptions\Error;

class CountType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
        $this->addDependency('selectManagerFactory');
    }

    public function process(\StdClass $item)
    {
        if (!property_exists($item, 'value')) {
            throw new Error();
        }

        if (!is_array($item->value)) {
            throw new Error();
        }

        if (count($item->value) < 1) {
            throw new Error();
        }

        $entityType = $this->evaluate($item->value[0]);

        if (count($item->value) < 3) {
            $filter = null;
            if (count($item->value) == 2) {
                $filter = $this->evaluate($item->value[1]);
            }

            $selectManager = $this->getInjection('selectManagerFactory')->create($entityType);
            $selectParams = $selectManager->getEmptySelectParams();
            if ($filter) {
                $selectManager->applyFilter($filter, $selectParams);
            }

            return $this->getInjection('entityManager')->getRepository($entityType)->count($selectParams);
        }

        $whereClause = [];

        $i = 1;
        while ($i < count($item->value) - 1) {
            $key = $this->evaluate($item->value[$i]);
            $value = $this->evaluate($item->value[$i + 1]);
            $whereClause[$key] = $value;
            $i = $i + 2;
        }

        return $this->getInjection('entityManager')->getRepository($entityType)->where($whereClause)->count();
    }
}
