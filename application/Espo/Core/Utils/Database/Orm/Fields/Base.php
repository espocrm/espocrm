<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

namespace Espo\Core\Utils\Database\Orm\Fields;

class Base extends \Espo\Core\Utils\Database\Orm\Base
{
    /**
     * Start process Orm converting for fields
     *
     * @param  string $itemName    Field name
     * @param  string $entityName
     * @return array
     */
    public function process($itemName, $entityName)
    {
        $inputs = array(
            'itemName' => $itemName,
            'entityName' => $entityName,
        );
        $this->setMethods($inputs);

        $convertedDefs = $this->load($itemName, $entityName);

        $inputs = $this->setArrayValue(null, $inputs);
        $this->setMethods($inputs);

        return $convertedDefs;
    }
}