<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\Database\Schema\ColumnPreparators;

use Doctrine\DBAL\Types\Types;
use Espo\Core\Utils\Database\Schema\Column;
use Espo\Core\Utils\Database\Schema\ColumnPreparator;
use Espo\Core\Utils\Util;
use Espo\ORM\Defs\AttributeDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Entity;

class PostgresqlColumnPreparator implements ColumnPreparator
{
    private const PARAM_DB_TYPE = AttributeParam::DB_TYPE;
    private const PARAM_DEFAULT = AttributeParam::DEFAULT;
    private const PARAM_NOT_NULL = AttributeParam::NOT_NULL;
    private const PARAM_AUTOINCREMENT = 'autoincrement';
    private const PARAM_PRECISION = 'precision';
    private const PARAM_SCALE = 'scale';

    /** @var string[] */
    private array $textTypeList = [
        Entity::TEXT,
        Entity::JSON_OBJECT,
        Entity::JSON_ARRAY,
    ];

    /** @var array<string, string> */
    private array $columnTypeMap = [
        Entity::BOOL => Types::BOOLEAN,
        Entity::INT => Types::INTEGER,
        Entity::VARCHAR => Types::STRING,
        // DBAL reverse engineers as blob.
        Types::BINARY => Types::BLOB,
    ];

    public function __construct() {}

    public function prepare(AttributeDefs $defs): Column
    {
        $dbType = $defs->getParam(self::PARAM_DB_TYPE);
        $type = $defs->getType();
        $length = $defs->getLength();
        $default = $defs->getParam(self::PARAM_DEFAULT);
        $notNull = $defs->getParam(self::PARAM_NOT_NULL);
        $autoincrement = $defs->getParam(self::PARAM_AUTOINCREMENT);
        $precision = $defs->getParam(self::PARAM_PRECISION);
        $scale = $defs->getParam(self::PARAM_SCALE);

        $columnType = $dbType ?? $type;

        if (in_array($type, $this->textTypeList) && !$dbType) {
            $columnType = Types::TEXT;
        }

        $columnType = $this->columnTypeMap[$columnType] ?? $columnType;

        $columnName = Util::toUnderScore($defs->getName());

        $column = Column::create($columnName, strtolower($columnType));

        if ($length !== null) {
            $column = $column->withLength($length);
        }

        if ($default !== null) {
            $column = $column->withDefault($default);
        }

        if ($notNull !== null) {
            $column = $column->withNotNull($notNull);
        }

        if ($autoincrement !== null) {
            $column = $column->withAutoincrement($autoincrement);
        }

        if ($precision !== null) {
            $column = $column->withPrecision($precision);
        }

        if ($scale !== null) {
            $column = $column->withScale($scale);
        }

        switch ($type) {
            case Entity::TEXT:
                $column = $column->withDefault(null);

                break;

            case Entity::JSON_ARRAY:
                $default = is_array($default) ? json_encode($default) : null;

                $column = $column->withDefault($default);

                break;

            case Entity::BOOL:
                $default = intval($default ?? false);

                $column = $column->withDefault($default);

                break;
        }

        if ($type !== Entity::ID && $autoincrement) {
            $column = $column
                ->withNotNull()
                ->withUnsigned();
        }

        return $column;

        // @todo Revise. Comparator would detect the column as changed if charset is set.
        /*if (
            !in_array($columnType, [
                Types::STRING,
                Types::TEXT,
            ])
        ) {
            return $column;
        }

        return $column->withCharset('UTF8');*/
    }
}
