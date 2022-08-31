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

namespace Espo\ORM;

use Espo\ORM\QueryComposer\QueryComposer;
use Espo\ORM\Query\Query;
use Espo\ORM\Query\Delete as DeleteQuery;
use Espo\ORM\Query\Insert as InsertQuery;
use Espo\ORM\Query\LockTable as LockTableQuery;
use Espo\ORM\Query\Select as SelectQuery;
use Espo\ORM\Query\Union as UnionQuery;
use Espo\ORM\Query\Update as UpdateQuery;

use RuntimeException;
use PDOStatement;

/**
 * Executes queries by given query params instances.
 */
class QueryExecutor
{
    private SqlExecutor $sqlExecutor;
    private QueryComposer $queryComposer;

    public function __construct(SqlExecutor $sqlExecutor, QueryComposer $queryComposer)
    {
        $this->sqlExecutor = $sqlExecutor;
        $this->queryComposer = $queryComposer;
    }

    /**
     * Execute a query.
     */
    public function execute(Query $query): PDOStatement
    {
        $sql = $this->compose($query);

        return $this->sqlExecutor->execute($sql, true);
    }

    private function compose(Query $query): string
    {
        if ($query instanceof SelectQuery) {
            return $this->queryComposer->composeSelect($query);
        }

        if ($query instanceof UpdateQuery) {
            return $this->queryComposer->composeUpdate($query);
        }

        if ($query instanceof InsertQuery) {
            return $this->queryComposer->composeInsert($query);
        }

        if ($query instanceof DeleteQuery) {
            return $this->queryComposer->composeDelete($query);
        }

        if ($query instanceof UnionQuery) {
            return $this->queryComposer->composeUnion($query);
        }

        if ($query instanceof LockTableQuery) {
            return $this->queryComposer->composeLockTable($query);
        }

        throw new RuntimeException("ORM Query: Unknown query type passed.");
    }
}
