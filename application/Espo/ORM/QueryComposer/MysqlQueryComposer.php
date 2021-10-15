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

namespace Espo\ORM\QueryComposer;

use Espo\ORM\{
    Query\LockTable as LockTableQuery,
};
use LogicException;

class MysqlQueryComposer extends BaseQueryComposer
{
    public function composeLockTable(LockTableQuery $queryParams): string
    {
        $params = $queryParams->getRaw();

        $table = $this->toDb($this->sanitize($params['table']));

        $mode = $params['mode'];

        if (empty($table)) {
            throw new LogicException();
        }

        if (!in_array($mode, [LockTableQuery::MODE_SHARE, LockTableQuery::MODE_EXCLUSIVE])) {
            throw new LogicException();
        }

        $sql = "LOCK TABLES `{$table}` ";

        $modeMap = [
            LockTableQuery::MODE_SHARE => 'READ',
            LockTableQuery::MODE_EXCLUSIVE => 'WRITE',
        ];

        $sql .= $modeMap[$mode];

        return $sql;
    }

    public function composeUnlockTables(): string
    {
        return "UNLOCK TABLES";
    }

    protected function limit(string $sql, ?int $offset = null, ?int $limit = null): string
    {
        if (!is_null($offset) && !is_null($limit)) {
            $offset = intval($offset);
            $limit = intval($limit);

            $sql .= " LIMIT {$offset}, {$limit}";

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
