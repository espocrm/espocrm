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

namespace Espo\Core\Utils\Database\Schema;

use Espo\Core\Utils\Util;

class Utils
{
    public static function getIndexes(array $ormMeta, array $ignoreFlags = [])
    {
        $indexList = [];

        foreach ($ormMeta as $entityName => $entityParams) {

            /* add indexes for additionalTables */
            $entityIndexList = static::getEntityIndexListByFieldsDefs($entityParams['fields']);

            foreach ($entityIndexList as $indexName => $indexParams) {
                if (!isset($entityParams['indexes'][$indexName])) {
                    $entityParams['indexes'][$indexName] = $indexParams;
                }
            }

            if (isset($entityParams['indexes']) && is_array($entityParams['indexes'])) {
                foreach ($entityParams['indexes'] as $indexName => $indexParams) {
                    $indexType = static::getIndexTypeByIndexDefs($indexParams);

                    $tableIndexName = isset($indexParams['key']) ?
                        $indexParams['key'] :
                        static::generateIndexName($indexName, $indexType);

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

                        $indexList[$entityName][$tableIndexName]['columns'] =
                            Util::toUnderScore($indexParams['columns']);
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

    public static function getIndexNameByFieldDefs($fieldName, array $fieldDefs, $default = null)
    {
        $indexType = static::getIndexTypeByFieldDefs($fieldDefs);

        if ($indexType) {
            $keyValue = $fieldDefs[$indexType];

            if ($keyValue === true) {
                return $fieldName;
            }

            if (is_string($keyValue)) {
                return $keyValue;
            }
        }

        return $default;
    }

    public static function getEntityIndexListByFieldsDefs(array $fieldsDefs, $isTableColumnNames = false)
    {
        $indexList = [];

        foreach ($fieldsDefs as $fieldName => $fieldParams) {
            if (isset($fieldParams['notStorable']) && $fieldParams['notStorable']) {
                continue;
            }

            $indexType = static::getIndexTypeByFieldDefs($fieldParams);
            $indexName = static::getIndexNameByFieldDefs($fieldName, $fieldParams);

            if (!$indexType || !$indexName) {
                continue;
            }

            $keyValue = $fieldParams[$indexType];

            $columnName = $isTableColumnNames ? Util::toUnderScore($fieldName) : $fieldName;

            if ($keyValue === true) {
                $indexList[$indexName]['type'] = $indexType;
                $indexList[$indexName]['columns'] = [$columnName];
            }
            else if (is_string($keyValue)) {
                $indexList[$indexName]['type'] = $indexType;
                $indexList[$indexName]['columns'][] = $columnName;
            }
        }

        return $indexList;
    }

    public static function getIndexTypeByIndexDefs(array $indexDefs)
    {
        if (
            (isset($indexDefs['type']) && $indexDefs['type'] == 'unique') ||
            (isset($indexDefs['unique']) && $indexDefs['unique'])
        ) {
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
        $nameList[] = strtoupper(Util::toUnderScore($name));

        return substr(implode('_', $nameList), 0, $maxLength);
    }

    public static function getFieldListExceededIndexMaxLength(
        array $ormMeta,
        $indexMaxLength = 1000,
        array $indexList = null,
        $characterLength = 4
    ) {

        $permittedFieldTypeList = [
            'varchar',
        ];

        $fields = [];

        if (!isset($indexList)) {
            $indexList = static::getIndexes($ormMeta, ['fulltext']);
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

                    $indexLength += static::getFieldLength(
                        $ormMeta[$entityName]['fields'][$fieldName],
                        $characterLength
                    );
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

        $defaultLength = [
            'datetime' => 8,
            'time' => 4,
            'int' => 4,
            'bool' => 1,
            'float' => 4,
            'varchar' => 255,
        ];

        $type = static::getDbFieldType($ormFieldDefs);

        $length = isset($defaultLength[$type]) ? $defaultLength[$type] : $length;
        $length = isset($ormFieldDefs['len']) ? $ormFieldDefs['len'] : $length;

        switch ($type) {
            case 'varchar':
                $length = $length * $characterLength;
                break;
        }

        return $length;
    }

    protected static function getDbFieldType(array $ormFieldDefs)
    {
        return isset($ormFieldDefs['dbType']) ? $ormFieldDefs['dbType'] : $ormFieldDefs['type'];
    }

    protected static function getFieldType(array $ormFieldDefs)
    {
        return isset($ormFieldDefs['type']) ? $ormFieldDefs['type'] : static::getDbFieldType($ormFieldDefs);
    }
}
