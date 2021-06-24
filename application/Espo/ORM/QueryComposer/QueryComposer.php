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
    Query\Query as Query,
    Query\Select as SelectQuery,
    Query\Update as UpdateQuery,
    Query\Insert as InsertQuery,
    Query\Delete as DeleteQuery,
    Query\Union as UnionQuery,
    Query\LockTable as LockTableQuery,
};

interface QueryComposer
{
    /**
     * Compose a SQL query by a given query parameters.
     */
    public function compose(Query $query): string;

    public function composeSelect(SelectQuery $query): string;

    public function composeUpdate(UpdateQuery $query): string;

    public function composeDelete(DeleteQuery $query): string;

    public function composeInsert(InsertQuery $query): string;

    public function composeUnion(UnionQuery $query): string;

    public function composeLockTable(LockTableQuery $query): string;

    public function composeCreateSavepoint(string $savepointName): string;

    public function composeReleaseSavepoint(string $savepointName): string;

    public function composeRollbackToSavepoint(string $savepointName): string;
}
