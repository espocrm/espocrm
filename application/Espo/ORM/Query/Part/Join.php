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

namespace Espo\ORM\Query\Part;

use RuntimeException;

/**
 * A join item. Immutable.
 */
class Join
{
    private $target;

    private $alias = null;

    private $conditions = null;

    private function __construct(string $target, ?string $alias = null)
    {
        $this->target = $target;
        $this->alias = $alias;

        if ($target === '' || $alias === '') {
            throw new RuntimeException("Bad join.");
        }
    }

    /**
     * Get a join target. A relationName or table.
     * A relationName is in camelCase, a table is in CamelCase.
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Get an alias.
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Get join conditions.
     */
    public function getConditions(): ?WhereItem
    {
        return $this->conditions;
    }

    public function isTable(): bool
    {
        return $this->target[0] === ucfirst($this->target[0]);
    }

    public function isRelation(): bool
    {
        return !$this->isTable();
    }

    /**
     * Create.
     *
     * @param string $target
     * A relation name or table. A relationName should be in camelCase, a table in CamelCase.
     * When joining a table, conditions should be specified.
     * When joining a relation, conditions will be applied automatically.
     */
    public static function create(string $target, ?string $alias = null): self
    {
        return new self($target, $alias);
    }

    /**
     * Create with a table target.
     */
    public static function createWithTableTarget(string $table, ?string $alias = null): self
    {
        return self::create(ucfirst($table), $alias);
    }

    /**
     * Create with a relation target. Conditions will be applied automatically.
     */
    public static function createWithRelationTarget(string $relation, ?string $alias = null): self
    {
        return self::create(lcfirst($relation), $alias);
    }

    /**
     * Clone with an alias.
     */
    public function withAlias(?string $alias): self
    {
        $obj = clone $this;
        $obj->alias = $alias;

        return $obj;
    }

    /**
     * Clone with join conditions.
     */
    public function withConditions(?WhereItem $conditions): self
    {
        $obj = clone $this;
        $obj->conditions = $conditions;

        return $obj;
    }
}
