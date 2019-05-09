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

namespace Espo\Core\Utils\Database\Schema;

use Espo\Core\Utils\Util;

class Utils
{
    public static function getIndexList(array $ormMeta, array $ignoreFlags = [])
    {
        $indexList = array();

        foreach ($ormMeta as $entityName => $entityParams) {
            foreach ($entityParams['fields'] as $fieldName => $fieldParams) {
                if (isset($fieldParams['notStorable']) && $fieldParams['notStorable']) {
                    continue;
                }

                $indexType = static::getIndexTypeByFieldDefs($fieldParams);
                if (!$indexType) {
                    continue;
                }

                if (!isset($indexList[$entityName])) {
                    $indexList[$entityName] = [];
                }

                $keyValue = $fieldParams[$indexType];
                $columnName = Util::toUnderScore($fieldName);

                if ($keyValue === true) {
                    $tableIndexName = static::generateIndexName($columnName, $indexType);
                    $indexList[$entityName][$tableIndexName]['type'] = $indexType;
                    $indexList[$entityName][$tableIndexName]['columns'] = array($columnName);
                } else if (is_string($keyValue)) {
                    $tableIndexName = static::generateIndexName($keyValue, $indexType);
                    $indexList[$entityName][$tableIndexName]['type'] = $indexType;
                    $indexList[$entityName][$tableIndexName]['columns'][] = $columnName;
                }
            }

            if (isset($entityParams['indexes']) && is_array($entityParams['indexes'])) {
                foreach ($entityParams['indexes'] as $indexName => $indexParams) {
                    $indexType = static::getIndexTypeByIndexDefs($indexParams);
                    $tableIndexName = static::generateIndexName($indexName, $indexType);

                    if (isset($indexParams['flags']) && is_array($indexParams['flags'])) {
                        $skipIndex = false;
                        foreach ($ignoreFlags as $ignoreFlag) {
                            if (($flagKey = array_search($ignoreFlag, $indexParams['flags'])) !== false) {
                                unset($indexParams['flags'][$flagKey]);
                                $skipIndex = true;
                            }
                        }

                        if ($skipIndex && empty($indexParams['flags'])) {
                            continue;
                        }

                        $indexList[$entityName][$tableIndexName]['flags'] = $indexParams['flags'];
                    }

                    if (is_array($indexParams['columns'])) {
                        $indexList[$entityName][$tableIndexName]['type'] = $indexType;
                        $indexList[$entityName][$tableIndexName]['columns'] = Util::toUnderScore($indexParams['columns']);
                    }
                }
            }
        }

        return $indexList;
    }

    public static function getIndexTypeByFieldDefs(array $fieldDefs)
    {
        if ($fieldDefs['type'] != 'id' && isset($fieldDefs['unique']) && $fieldDefs['unique']) {
            return 'unique';
        }

        if (isset($fieldDefs['index']) && $fieldDefs['index']) {
            return 'index';
        }
    }

    public static function getIndexTypeByIndexDefs(array $indexDefs)
    {
        if (isset($indexDefs['unique']) && $indexDefs['unique']) {
            return 'unique';
        }

        if (isset($indexDefs['flags']) && in_array('fulltext', $indexDefs['flags'])) {
            return 'fulltext';
        }

        return 'index';
    }

    public static function generateIndexName($name, $type = 'index', $maxLength = 60)
    {
        switch ($type) {
            case 'unique':
                $prefix = 'UNIQ';
                break;

            default:
                $prefix = 'IDX';
                break;
        }

        $nameList = [];
        $nameList[] = strtoupper($prefix);
        $nameList[] = strtoupper( Util::toUnderScore($name) );

        return substr(implode('_', $nameList), 0, $maxLength);
    }

    public static function getFieldListExceededIndexMaxLength(array $ormMeta, $indexMaxLength = 1000, array $indexList = null, $characterLength = 4)
    {
        $permittedFieldTypeList = [
            'varchar',
        ];

        $fields = array();

        if (!isset($indexList)) {
            $indexList = static::getIndexList($ormMeta, ['fulltext']);
        }

        foreach ($indexList as $entityName => $indexes) {
            foreach ($indexes as $indexName => $indexParams) {
                $columnList = $indexParams['columns'];

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
