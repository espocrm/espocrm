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

namespace Espo\Core\Utils\Database\Orm\Fields;

use Espo\Core\Utils\Util;

class PersonName extends Base
{
    protected function load($fieldName, $entityName)
    {
        $subList = ['first' . ucfirst($fieldName), ' ', 'last' . ucfirst($fieldName)];

        $tableName = Util::toUnderScore($entityName);

        $orderBy1Field = 'first' . ucfirst($fieldName);
        $orderBy2Field = 'last' . ucfirst($fieldName);

        $fullList = [];
        $fieldList = [];
        $like = [];
        $equal = [];

        foreach ($subList as $subFieldName) {
            $fieldNameTrimmed = trim($subFieldName);
            if (!empty($fieldNameTrimmed)) {
                $columnName = $tableName . '.' . Util::toUnderScore($fieldNameTrimmed);

                $fullList[] = $fieldList[] = $columnName;
                $like[] = $columnName." LIKE {value}";
                $equal[] = $columnName." = {value}";
            } else {
                $fullList[] = "'" . $subFieldName . "'";
            }
        }

        $fullListReverse = array_reverse($fullList);

        return [
            $entityName => [
                'fields' => [
                    $fieldName => [
                        'type' => 'varchar',
                        'select' => $this->getSelect($fullList),
                        'where' => [
                            'LIKE' => "(".implode(" OR ", $like)." OR CONCAT(".implode(", ", $fullList).") LIKE {value} OR CONCAT(".implode(", ", $fullListReverse).") LIKE {value})",
                            '=' => "(".implode(" OR ", $equal)." OR CONCAT(".implode(", ", $fullList).") = {value} OR CONCAT(".implode(", ", $fullListReverse).") = {value})",
                        ],
                        'orderBy' => "{$tableName}." . Util::toUnderScore($orderBy1Field) ." {direction}, {$tableName}." . Util::toUnderScore($orderBy2Field)
                    ]
                ]
            ]
        ];
    }

    protected function getSelect(array $fullList)
    {
        foreach ($fullList as &$item) {

            $rowItem = trim($item, " '");

            if (!empty($rowItem)) {
                $item = "IFNULL(".$item.", '')";
            }
        }

        $select = "TRIM(CONCAT(".implode(", ", $fullList)."))";

        return $select;
    }

}
