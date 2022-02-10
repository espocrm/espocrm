<?php
/************************************************************************
* This file is part of EspoCRM.
*
* EspoCRM - Open Source CRM application.
* Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Utils\Database\DBAL\Schema;

use Doctrine\DBAL\Schema\MySQLSchemaManager as OriginalComparator;

// Espo: requires for the issue https://github.com/doctrine/dbal/issues/4496

//use Doctrine\DBAL\Platforms\MariaDb1027Platform;
//use Doctrine\DBAL\Platforms\MySQLPlatform;
use Espo\Core\Utils\Database\DBAL\Platforms\{
    MariaDb1027Platform,
    MySQLPlatform,
};

use Doctrine\DBAL\Schema\Column;
// Espo: end

use Doctrine\DBAL\Types\Type;

use function array_change_key_case;
use function array_shift;
use function array_values;
use function assert;
use function explode;
use function is_string;
use function preg_match;
use function strpos;
use function strtok;
use function strtolower;
use function strtr;

use const CASE_LOWER;

class MySQLSchemaManager extends OriginalComparator
{
    private const MARIADB_ESCAPE_SEQUENCES = [
        '\\0' => "\0",
        "\\'" => "'",
        '\\"' => '"',
        '\\b' => "\b",
        '\\n' => "\n",
        '\\r' => "\r",
        '\\t' => "\t",
        '\\Z' => "\x1a",
        '\\\\' => '\\',
        '\\%' => '%',
        '\\_' => '_',

        // Internally, MariaDB escapes single quotes using the standard syntax
        "''" => "'",
    ];

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $dbType = strtolower($tableColumn['type']);
        $dbType = strtok($dbType, '(), ');
        assert(is_string($dbType));

        $length = $tableColumn['length'] ?? strtok('(), ');

        $fixed = null;

        if (! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

        $scale     = null;
        $precision = null;

        $type = $this->_platform->getDoctrineTypeMapping($dbType);

        // In cases where not connected to a database DESCRIBE $table does not return 'Comment'
        if (isset($tableColumn['comment'])) {
            $type                   = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type);
            $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type);
        }

        switch ($dbType) {
            case 'char':
            case 'binary':
                $fixed = true;
                break;

            case 'float':
            case 'double':
            case 'real':
            case 'numeric':
            case 'decimal':
                if (
                    preg_match(
                        '([A-Za-z]+\(([0-9]+),([0-9]+)\))',
                        $tableColumn['type'],
                        $match
                    ) === 1
                ) {
                    $precision = $match[1];
                    $scale     = $match[2];
                    $length    = null;
                }

                break;

            case 'tinytext':
                $length = MySQLPlatform::LENGTH_LIMIT_TINYTEXT;
                break;

            case 'text':
                $length = MySQLPlatform::LENGTH_LIMIT_TEXT;
                break;

            case 'mediumtext':
                $length = MySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT;
                break;

            case 'tinyblob':
                $length = MySQLPlatform::LENGTH_LIMIT_TINYBLOB;
                break;

            case 'blob':
                $length = MySQLPlatform::LENGTH_LIMIT_BLOB;
                break;

            case 'mediumblob':
                $length = MySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB;
                break;

            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
            case 'year':
                $length = null;
                break;
        }

        if ($this->_platform instanceof MariaDb1027Platform) {
            $columnDefault = $this->getMariaDb1027ColumnDefault($this->_platform, $tableColumn['default']);
        } else {
            $columnDefault = $tableColumn['default'];
        }

        $options = [
            'length'        => $length !== null ? (int) $length : null,
            'unsigned'      => strpos($tableColumn['type'], 'unsigned') !== false,
            'fixed'         => (bool) $fixed,
            'default'       => $columnDefault,
            'notnull'       => $tableColumn['null'] !== 'YES',
            'scale'         => null,
            'precision'     => null,
            'autoincrement' => strpos($tableColumn['extra'], 'auto_increment') !== false,
            'comment'       => isset($tableColumn['comment']) && $tableColumn['comment'] !== ''
                ? $tableColumn['comment']
                : null,
        ];

        if ($scale !== null && $precision !== null) {
            $options['scale']     = (int) $scale;
            $options['precision'] = (int) $precision;
        }

        $column = new Column($tableColumn['field'], Type::getType($type), $options);

        if (isset($tableColumn['characterset'])) {
            $column->setPlatformOption('charset', $tableColumn['characterset']);
        }

        if (isset($tableColumn['collation'])) {
            $column->setPlatformOption('collation', $tableColumn['collation']);
        }

        return $column;
    }

    private function getMariaDb1027ColumnDefault(MariaDb1027Platform $platform, ?string $columnDefault): ?string
    {
        if ($columnDefault === 'NULL' || $columnDefault === null) {
            return null;
        }

        if (preg_match('/^\'(.*)\'$/', $columnDefault, $matches) === 1) {
            return strtr($matches[1], self::MARIADB_ESCAPE_SEQUENCES);
        }

        switch ($columnDefault) {
            case 'current_timestamp()':
                return $platform->getCurrentTimestampSQL();

            case 'curdate()':
                return $platform->getCurrentDateSQL();

            case 'curtime()':
                return $platform->getCurrentTimeSQL();
        }

        return $columnDefault;
    }
}
