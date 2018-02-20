<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\Database\Schema;

use Espo\Core\Utils\Util;

class Utils
{
    public static function getIndexList(array $ormMeta)
    {
        $indexList = array();

        foreach ($ormMeta as $entityName => $entityParams) {
            foreach ($entityParams['fields'] as $fieldName => $fieldParams) {
                if (isset($fieldParams['notStorable']) && $fieldParams['notStorable']) {
                    continue;
                }

                if (isset($fieldParams['index'])) {
                    $keyValue = $fieldParams['index'];
                    $columnName = Util::toUnderScore($fieldName);

                    if (!isset($indexList[$entityName])) {
                        $indexList[$entityName] = [];
                    }

                    if ($keyValue === true) {
                        $tableIndexName = static::generateIndexName($columnName);
                        $indexList[$entityName][$tableIndexName] = array($columnName);
                    } else if (is_string($keyValue)) {
                        $tableIndexName = static::generateIndexName($keyValue);
                        $indexList[$entityName][$tableIndexName][] = $columnName;
                    }
                }
            }

            if (isset($entityParams['indexes']) && is_array($entityParams['indexes'])) {
                foreach ($entityParams['indexes'] as $indexName => $indexParams) {
                    if (is_array($indexParams['columns'])) {
                        $tableIndexName = static::generateIndexName($indexName);
                        $indexList[$entityName][$tableIndexName] = Util::toUnderScore($indexParams['columns']);
                    }
                }
            }
        }

        return $indexList;
    }

    public static function generateIndexName($name, $prefix = 'IDX', $maxLength = 30)
    {
        $nameList = [];
        $nameList[] = strtoupper($prefix);
        $nameList[] = strtoupper( Util::toUnderScore($name) );

        return substr(implode('_', $nameList), 0, $maxLength);
    }

    public static function getFieldListExceededIndexMaxLength(array $ormMeta, $indexMaxLength = 1000, $characterLength = 4)
    {
        $permittedFieldTypeList = [
            'varchar',
        ];

        $fields = array();

        $indexList = static::getIndexList($ormMeta);

        foreach ($indexList as $entityName => $indexes) {
            foreach ($indexes as $indexName => $columnList) {
                $indexLength = 0;
                foreach ($columnList as $columnName) {
                    $fieldName = Util::toCamelCase($columnName);

                    if (!isset($ormMeta[$entityName]['fields'][$fieldName])) {
                        continue;
                    }

                    $indexLength += static::getFieldLength($ormMeta[$entityName]['fields'][$fieldName], $characterLength);
                }

                if ($indexLength > $indexMaxLength) {
                    foreach ($columnList as $columnName) {
                        $fieldName = Util::toCamelCase($columnName);
                        if (!isset($ormMeta[$entityName]['fields'][$fieldName])) {
                            continue;
                        }

                        $fieldType = static::getFieldType($ormMeta[$entityName]['fields'][$fieldName]);

                        if (in_array($fieldType, $permittedFieldTypeList)) {
                            if (!isset($fields[$entityName]) || !in_array($fieldName, $fields[$entityName])) {
                                $fields[$entityName][] = $fieldName;
                            }
                        }
                    }
                }
            }
        }

        return $fields;
    }

    protected static function getFieldLength(array $ormFieldDefs, $characterLength = 4)
    {
        $length = 0;

        if (isset($ormFieldDefs['notStorable']) && $ormFieldDefs['notStorable']) {
            return $length;
        }

        $defaultLength = array(
            'datetime' => 8,
            'time' => 4,
            'int' => 4,
            'bool' => 1,
            'float' => 4,
            'varchar' => 255,
        );

        $type = static::getFieldType($ormFieldDefs);

        $length = isset($defaultLength[$type]) ? $defaultLength[$type] : $length;
        $length = isset($ormFieldDefs['len']) ? $ormFieldDefs['len'] : $length;

        switch ($type) {
            case 'varchar':
                $length = $length * $characterLength;
                break;
        }

        return $length;
    }

    protected static function getFieldType(array $ormFieldDefs)
    {
        return isset($ormFieldDefs['dbType']) ? $ormFieldDefs['dbType'] : $ormFieldDefs['type'];
    }
}
