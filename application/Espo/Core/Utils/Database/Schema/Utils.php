<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\ORM\Defs\IndexDefs;

class Utils
{
    /**
     * Get indexes in specific format.
     *
     * @param array<string, mixed> $defs
     * @param string[] $ignoreFlags @todo Remove parameter?
     * @return array<string, array<string, mixed>>
     */
    public static function getIndexes(array $defs, array $ignoreFlags = []): array
    {
        $indexList = [];

        foreach ($defs as $entityType => $entityParams) {
            $entityIndexList = self::getEntityIndexListByFieldsDefs($entityParams['fields'] ?? []);

            foreach ($entityIndexList as $indexName => $indexParams) {
                if (!isset($entityParams['indexes'][$indexName])) {
                    $entityParams['indexes'][$indexName] = $indexParams;
                }
            }

            if (isset($entityParams['indexes']) && is_array($entityParams['indexes'])) {
                foreach ($entityParams['indexes'] as $indexName => $indexParams) {
                    $indexDefs = IndexDefs::fromRaw($indexParams, $indexName);

                    $tableIndexName = $indexParams['key'] ?? self::generateIndexName($indexDefs, $entityType);

                    $columns = $indexDefs->getColumnList();
                    $flags = $indexDefs->getFlagList();

                    if ($flags !== []) {
                        $skipIndex = false;

                        foreach ($ignoreFlags as $ignoreFlag) {
                            if (($flagKey = array_search($ignoreFlag, $flags)) !== false) {
                                unset($flags[$flagKey]);

                                $skipIndex = true;
                            }
                        }

                        if ($skipIndex && empty($flags)) {
                            continue;
                        }

                        $indexList[$entityType][$tableIndexName]['flags'] = $flags;
                    }

                    if ($columns !== []) {
                        $indexType = self::getIndexTypeByIndexDefs($indexDefs);

                        // @todo Revise, may to be removed.
                        $indexList[$entityType][$tableIndexName]['type'] = $indexType;

                        $indexList[$entityType][$tableIndexName]['columns'] = array_map(
                            fn ($item) => Util::toUnderScore($item),
                            $columns
                        );
                    }
                }
            }
        }

        /** @var array<string, array<string, mixed>> */
        return $indexList;
    }

    /**
     * @param array<string, mixed> $fieldDefs
     */
    private static function getIndexTypeByFieldDefs(array $fieldDefs): ?string
    {
        if ($fieldDefs['type'] != 'id' && isset($fieldDefs['unique']) && $fieldDefs['unique']) {
            return 'unique';
        }

        if (isset($fieldDefs['index']) && $fieldDefs['index']) {
            return 'index';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $fieldDefs
     */
    private static function getIndexNameByFieldDefs(string $fieldName, array $fieldDefs): ?string
    {
        $indexType = self::getIndexTypeByFieldDefs($fieldDefs);

        if ($indexType) {
            $keyValue = $fieldDefs[$indexType];

            if ($keyValue === true) {
                return $fieldName;
            }

            if (is_string($keyValue)) {
                return $keyValue;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $fieldsDefs
     * @return array<string, mixed>
     */
    public static function getEntityIndexListByFieldsDefs(array $fieldsDefs, bool $isTableColumnNames = false): array
    {
        $indexList = [];

        foreach ($fieldsDefs as $fieldName => $fieldParams) {
            if (isset($fieldParams['notStorable']) && $fieldParams['notStorable']) {
                continue;
            }

            $indexType = self::getIndexTypeByFieldDefs($fieldParams);
            $indexName = self::getIndexNameByFieldDefs($fieldName, $fieldParams);

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

        /** @var array<string,mixed> */
        return $indexList;
    }

    private static function getIndexTypeByIndexDefs(IndexDefs $indexDefs): string
    {
        if ($indexDefs->isUnique()) {
            return 'unique';
        }

        if (in_array('fulltext', $indexDefs->getFlagList())) {
            return 'fulltext';
        }

        return 'index';
    }

    /**
     * @todo Move to IndexHelper interface.
     */
    public static function generateIndexName(IndexDefs $defs, string $entityType): string
    {
        $maxLength = 60;

        $name = $defs->getName();
        $prefix = $defs->isUnique() ? 'UNIQ' : 'IDX';

        $parts = [$prefix, strtoupper(Util::toUnderScore($name))];

        $key = implode('_', $parts);

        return substr($key, 0, $maxLength);
    }

    /**
     * @deprecated
     *
     * @param array<string,mixed> $ormMeta
     * @param int $indexMaxLength
     * @param ?array<string,mixed> $indexList
     * @param int $characterLength
     * @return array<string,mixed>
     */
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
            $indexList = self::getIndexes($ormMeta, ['fulltext']);
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

                    $indexLength += self::getFieldLength(
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

                        $fieldType = self::getFieldType($ormMeta[$entityName]['fields'][$fieldName]);

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

    /**
     * @param array<string, mixed> $ormFieldDefs
     * @param int $characterLength
     * @return int
     */
    private static function getFieldLength(array $ormFieldDefs, $characterLength = 4)
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

        $type = self::getDbFieldType($ormFieldDefs);

        $length = $defaultLength[$type] ?? $length;
        //$length = isset($ormFieldDefs['len']) ? $ormFieldDefs['len'] : $length;

        switch ($type) {
            case 'varchar':
                $length = $length * $characterLength;

                break;
        }

        return $length;
    }

    /**
     * @param array<string, mixed> $ormFieldDefs
     * @return string
     */
    private static function getDbFieldType(array $ormFieldDefs)
    {
        return $ormFieldDefs['dbType'] ?? $ormFieldDefs['type'];
    }

    /**
     * @param array<string, mixed> $ormFieldDefs
     */
    private static function getFieldType(array $ormFieldDefs): string
    {
        return $ormFieldDefs['type'] ?? self::getDbFieldType($ormFieldDefs);
    }
}
