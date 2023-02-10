<?php

namespace Espo\Modules\Postgres\Core\Utils\Database\Schema\ColumnPreparators;

use Espo\Core\Utils\Database\Schema\Column;
use Espo\Core\Utils\Database\Schema\ColumnPreparator;
use Espo\Core\Utils\Util;
use Espo\ORM\Defs\AttributeDefs;
use Espo\ORM\Entity;
use Doctrine\DBAL\Types\Types;

class PostgresqlColumnPreparator implements ColumnPreparator
{

    private const PARAM_DB_TYPE = 'dbType';
    private const PARAM_DEFAULT = 'default';
    private const PARAM_NOT_NULL = 'notNull';
    private const PARAM_AUTOINCREMENT = 'autoincrement';
    private const PARAM_PRECISION = 'precision';
    private const PARAM_SCALE = 'scale';

    private array $columnTypeMap = [
        Entity::BOOL => Types::BOOLEAN,
        Entity::INT => Types::INTEGER,
        Entity::VARCHAR => Types::STRING,
        Entity::JSON_ARRAY => Types::JSON,
        Entity::JSON_OBJECT => Types::JSON,
    ];

    public function prepare(AttributeDefs $defs): Column
    {
        $espoType = $defs->getParam(self::PARAM_DB_TYPE) ?? $defs->getType();

        $columnType = $this->columnTypeMap[$espoType] ?? $espoType;

        $columnName = Util::toUnderScore($defs->getName());

        $column = Column::create($columnName, strtolower($columnType));

        $type = $defs->getType();
        $length = $defs->getLength();
        $default = $defs->getParam(self::PARAM_DEFAULT);
        $notNull = $defs->getParam(self::PARAM_NOT_NULL);
        $autoincrement = $defs->getParam(self::PARAM_AUTOINCREMENT);
        $precision = $defs->getParam(self::PARAM_PRECISION);
        $scale = $defs->getParam(self::PARAM_SCALE);

        if ($length !== null) {
            $column = $column->withLength($length);
        }

        if ($columnType !== 'json' && $defs->hasParam(self::PARAM_DEFAULT)) {
            $column = $column->withDefault($default);
        }

        if (!empty($notNull)) {
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

        if ($type !== Entity::ID && $autoincrement) {
            $column = $column
                ->withNotNull();
        }

        return $column;
    }
}
