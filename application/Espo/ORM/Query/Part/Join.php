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

namespace Espo\ORM\Query\Part;

use Espo\ORM\Query\Select;
use LogicException;
use RuntimeException;

/**
 * A join item. Immutable.
 */
class Join
{
    /** A table join. */
    public const TYPE_TABLE = 0;
    /** A relation join. */
    public const TYPE_RELATION = 1;
    /** A sub-query join. */
    public const TYPE_SUB_QUERY = 3;

    private ?WhereItem $conditions = null;
    private bool $onlyMiddle = false;
    private bool $isLateral = false;

    private function __construct(
        private string|Select $target,
        private ?string $alias = null
    ) {
        if ($target === '' || $alias === '') {
            throw new RuntimeException("Bad join.");
        }
    }

    /**
     * Get a join target. A relation name, table or sub-query.
     * A relation name is in camelCase, a table is in CamelCase.
     */
    public function getTarget(): string|Select
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

    /**
     * Is a sub-query join.
     */
    public function isSubQuery(): bool
    {
        return !is_string($this->target);
    }

    /**
     * Is a table join.
     */
    public function isTable(): bool
    {
        return is_string($this->target) && $this->target[0] === ucfirst($this->target[0]);
    }

    /**
     * Is a relation join.
     */
    public function isRelation(): bool
    {
        return !$this->isSubQuery() && !$this->isTable();
    }

    /**
     * Get a join type.
     *
     * @return self::TYPE_TABLE|self::TYPE_RELATION|self::TYPE_SUB_QUERY
     */
    public function getType(): int
    {
        if ($this->isSubQuery()) {
            return self::TYPE_SUB_QUERY;
        }

        if ($this->isRelation()) {
            return self::TYPE_RELATION;
        }

        return self::TYPE_TABLE;
    }

    /**
     * Is only middle table to be joined.
     */
    public function isOnlyMiddle(): bool
    {
        return $this->onlyMiddle;
    }

    /**
     * Is LATERAL.
     *
     * @since 9.1.6
     */
    public function isLateral(): bool
    {
        return $this->isLateral;
    }

    /**
     * Create.
     *
     * @param string|Select $target
     * A relation name, table or sub-query. A relation name should be in camelCase, a table in CamelCase.
     * When joining a table or sub-query, conditions should be specified.
     * When joining a relation, conditions will be applied automatically, additional conditions can
     * be specified as well.
     * @param ?string $alias An alias.
     */
    public static function create(string|Select $target, ?string $alias = null): self
    {
        return new self($target, $alias);
    }

    /**
     * Create with a table target.
     *
     * @param string $table A table name. Should start with an upper case letter.
     * @param ?string $alias An alias.
     */
    public static function createWithTableTarget(string $table, ?string $alias = null): self
    {
        return self::create(ucfirst($table), $alias);
    }

    /**
     * Create with a relation target. Conditions will be applied automatically.
     *
     * @param string $relation A relation name. Should start with a lower case letter.
     * @param ?string $alias An alias.
     */
    public static function createWithRelationTarget(string $relation, ?string $alias = null): self
    {
        return self::create(lcfirst($relation), $alias);
    }

    /**
     * Create with a sub-query.
     *
     * @param Select $subQuery A sub-query.
     * @param string $alias An alias.
     */
    public static function createWithSubQuery(Select $subQuery, string $alias): self
    {
        return new self($subQuery, $alias);
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

    /**
     * Join only middle table. For many-to-many relationships.
     */
    public function withOnlyMiddle(bool $onlyMiddle = true): self
    {
        if (!$this->isRelation()) {
            throw new LogicException("Only-middle is compatible only with relation joins.");
        }

        $obj = clone $this;
        $obj->onlyMiddle = $onlyMiddle;

        return $obj;
    }

    /**
     * With LATERAL. Only for a sub-query join.
     *
     * @since 9.1.6
     */
    public function withLateral(bool $isLateral = true): self
    {
        if (!$this->isSubQuery()) {
            throw new LogicException("Lateral can be used only with sub-query joins.");
        }

        $obj = clone $this;
        $obj->isLateral = $isLateral;

        return $obj;
    }
}
