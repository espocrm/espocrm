<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class LinkMultiple extends Base
{
    protected function load($fieldName, $entityName)
    {
        $data = array(
            $entityName => array (
                'fields' => array(
                    $fieldName.'Ids' => array(
                        'type' => 'varchar',
                        'notStorable' => true,
                    ),
                    $fieldName.'Names' => array(
                        'type' => 'varchar',
                        'notStorable' => true,
                    ),
                ),
            ),
            'unset' => array(
                $entityName => array(
                    'fields.'.$fieldName,
                ),
            ),
        );

        $columns = $this->getMetadata()->get("entityDefs.{$entityName}.fields.{$fieldName}.columns");
        if (!empty($columns)) {
            $data[$entityName]['fields'][$fieldName . 'Columns'] = array(
                'type' => 'varchar',
                'notStorable' => true,
            );
        }

        return $data;
    }
}
