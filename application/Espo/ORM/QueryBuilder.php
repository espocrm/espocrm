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

namespace Espo\ORM;

use Espo\ORM\{
    Query\SelectBuilder,
    Query\UpdateBuilder,
    Query\DeleteBuilder,
    Query\InsertBuilder,
    Query\UnionBuilder,
    Query\Query,
    Query\Builder,
    Query\Part\Expression,
    Query\Part\Selection,
};

use ReflectionClass;
use RuntimeException;

/**
 * Creates query builders for specific query types.
 */
class QueryBuilder
{
    /**
     * Specify SELECT. Columns and expressions to be selected. If not called, then
     * all entity attributes will be selected. Passing an array will reset
     * previously set items. Passing a SelectExpression|Expression|string will append the item.
     *
     * Usage options:
     * * `select(SelectExpression $expression)`
     * * `select([$expr1, $expr2, ...])`
     * * `select(string $expression, string $alias)`
     *
     * @param Selection|Selection[]|Expression|string $select
     * An array of expressions or one expression.
     * @param string|null $alias An alias. Actual if the first parameter is not an array.
     */
    public function select($select = null, ?string $alias = null): SelectBuilder
    {
        $builder = new SelectBuilder();

        if ($select === null) {
            return $builder;
        }

        return $builder->select($select, $alias);
    }

    /**
     * Proceed with UPDATE builder.
     */
    public function update(): UpdateBuilder
    {
        return new UpdateBuilder();
    }

    /**
     * Proceed with DELETE builder.
     */
    public function delete(): DeleteBuilder
    {
        return new DeleteBuilder();
    }

    /**
     * Proceed with INSERT builder.
     */
    public function insert(): InsertBuilder
    {
        return new InsertBuilder();
    }

    /**
     * Proceed with UNION builder.
     */
    public function union(): UnionBuilder
    {
        return new UnionBuilder();
    }

    /**
     * Clone an existing query and proceed modifying it.
     *
     * @return SelectBuilder|UpdateBuilder|DeleteBuilder|InsertBuilder|UnionBuilder
     * @throws RuntimeException
     */
    public function clone(Query $query): Builder
    {
        $class = new ReflectionClass($query);

        $methodName = ucfirst($class->getShortName());

        if (!method_exists($this, $methodName)) {
            throw new RuntimeException("Can't clone an unsupported query.");
        }

        return $this->$methodName()->clone($query);
    }
}
