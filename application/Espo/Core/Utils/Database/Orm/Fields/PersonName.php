<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
        $format = $this->config->get('personNameFormat');

        switch ($format) {
            case 'lastFirst':
                $subList = ['last' . ucfirst($fieldName), ' ', 'first' . ucfirst($fieldName)];
                break;

            case 'lastFirstMiddle':
                $subList = [
                    'last' . ucfirst($fieldName), ' ', 'first' . ucfirst($fieldName), ' ', 'middle' . ucfirst($fieldName)
                ];
                break;

            case 'firstMiddleLast':
                $subList = [
                    'first' . ucfirst($fieldName), ' ', 'middle' . ucfirst($fieldName), ' ', 'last' . ucfirst($fieldName)
                ];
                break;

            default:
                $subList = ['first' . ucfirst($fieldName), ' ', 'last' . ucfirst($fieldName)];
        }

        $tableName = Util::toUnderScore($entityName);

        if ($format === 'lastFirstMiddle' || $format === 'lastFirst') {
            $orderBy1Field = 'last' . ucfirst($fieldName);
            $orderBy2Field = 'first' . ucfirst($fieldName);
        } else {
            $orderBy1Field = 'first' . ucfirst($fieldName);
            $orderBy2Field = 'last' . ucfirst($fieldName);
        }

        $uname = ucfirst($fieldName);

        $fullList = [];
        $fieldList = [];

        $parts = [];

        foreach ($subList as $subFieldName) {
            $fieldNameTrimmed = trim($subFieldName);
            if (!empty($fieldNameTrimmed)) {
                $columnName = $tableName . '.' . Util::toUnderScore($fieldNameTrimmed);

                $fullList[] = $fieldList[] = $columnName;
                $parts[] = $columnName." {operator} {value}";
            } else {
                $fullList[] = "'" . $subFieldName . "'";
            }
        }

        $firstColumn = $tableName . '.' . Util::toUnderScore('first' . $uname);
        $lastColumn = $tableName . '.' . Util::toUnderScore('last' . $uname);
        $middleColumn = $tableName . '.' . Util::toUnderScore('middle' . $uname);

        $whereString = "".implode(" OR ", $parts);

        if ($format === 'firstMiddleLast') {
            $whereString .=
                " OR CONCAT({$firstColumn}, ' ', {$middleColumn}, ' ', {$lastColumn}) {operator} {value}" .
                " OR CONCAT({$firstColumn}, ' ', {$lastColumn}) {operator} {value}" .
                " OR CONCAT({$lastColumn}, ' ', {$firstColumn}) {operator} {value}";
        } else if ($format === 'lastFirstMiddle') {
            $whereString .=
                " OR CONCAT({$lastColumn}, ' ', {$firstColumn}, ' ', {$middleColumn}) {operator} {value}" .
                " OR CONCAT({$firstColumn}, ' ', {$lastColumn}) {operator} {value}" .
                " OR CONCAT({$lastColumn}, ' ', {$firstColumn}) {operator} {value}";
        } else {
            $whereString .= " OR CONCAT({$firstColumn}, ' ', {$lastColumn}) {operator} {value}";
            $whereString .= " OR CONCAT({$lastColumn}, ' ', {$firstColumn}) {operator} {value}";
        }

        $selectString = $this->getSelect($fullList);

        if ($format === 'firstMiddleLast' || $format === 'lastFirstMiddle') {
            $selectString = "REPLACE({$selectString}, '  ', ' ')";
        }

        return [
            $entityName => [
                'fields' => [
                    $fieldName => [
                        'type' => 'varchar',
                        'select' => $selectString,
                        'where' => [
                            'LIKE' => str_replace('{operator}', 'LIKE', $whereString),
                            '=' => str_replace('{operator}', '=', $whereString),
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
