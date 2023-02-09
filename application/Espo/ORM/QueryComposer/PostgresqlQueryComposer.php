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

namespace Espo\ORM\QueryComposer;

use Espo\ORM\Query\LockTable as LockTableQuery;

use LogicException;

class PostgresqlQueryComposer extends BaseQueryComposer
{
    protected string $identifierQuoteCharacter = '"';
    protected bool $indexHints = false;

    protected function quoteColumn(string $column): string
    {
        $list = explode('.', $column);
        $list = array_map(fn ($item) => '"' . $item . '"', $list);

        return implode('.', $list);
    }

    /**
     * @todo Make protected.
     *
     * @param mixed $value
     */
    public function quote($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return strval($value);
        }

        if (is_float($value)) {
            return strval($value);
        }

        return $this->pdo->quote($value);
    }

    /**
     * @param string[] $argumentPartList
     * @param array<string, mixed> $params
     */
    protected function getFunctionPart(
        string $function,
        string $part,
        array $params,
        string $entityType,
        bool $distinct,
        array $argumentPartList = []
    ): string {
        if ($function === 'IFNULL') {
            $function = 'COALESCE';
        }

        return parent::getFunctionPart(
            $function,
            $part,
            $params,
            $entityType,
            $distinct,
            $argumentPartList
        );
    }

    public function composeLockTable(LockTableQuery $query): string
    {
        $params = $query->getRaw();

        $table = $this->toDb($this->sanitize($params['table']));

        $mode = $params['mode'];

        if (empty($table)) {
            throw new LogicException();
        }

        if (!in_array($mode, [LockTableQuery::MODE_SHARE, LockTableQuery::MODE_EXCLUSIVE])) {
            throw new LogicException();
        }

        $sql = "LOCK TABLE " . $this->quoteIdentifier($table) . " IN ";

        $modeMap = [
            LockTableQuery::MODE_SHARE => 'SHARE',
            LockTableQuery::MODE_EXCLUSIVE => 'EXCLUSIVE',
        ];

        $sql .= $modeMap[$mode] . " MODE";

        return $sql;
    }

    protected function limit(string $sql, ?int $offset = null, ?int $limit = null): string
    {
        if (!is_null($offset) && !is_null($limit)) {
            $offset = intval($offset);
            $limit = intval($limit);

            $sql .= " LIMIT {$limit} OFFSET {$offset}";

            return $sql;
        }

        if (!is_null($limit)) {
            $limit = intval($limit);

            $sql .= " LIMIT {$limit}";

            return $sql;
        }

        return $sql;
    }
}
