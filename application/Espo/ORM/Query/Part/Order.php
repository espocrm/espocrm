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

use RuntimeException;

/**
 * An order item. Immutable.
 *
 * Immutable.
 */
class Order
{
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    private Expression $expression;
    private bool $isDesc = false;

    private function __construct(Expression $expression)
    {
        $this->expression = $expression;
    }

    /**
     * Get an expression.
     */
    public function getExpression(): Expression
    {
        return $this->expression;
    }

    public function isDesc(): bool
    {
        return $this->isDesc;
    }

    /**
     * Get a direction.
     *
     * @return self::DESC|self::ASC
     */
    public function getDirection(): string
    {
        return $this->isDesc ? self::DESC : self::ASC;
    }

    /**
     * Create.
     */
    public static function create(Expression $expression): self
    {
        return new self($expression);
    }

    /**
     * Create from a string expression.
     */
    public static function fromString(string $expression): self
    {
        return self::create(
            Expression::create($expression)
        );
    }

    /**
     * Create an order by position in list.
     * Note: Reverses the list and applies DESC order.
     *
     * @param string[]|int[]|float[] $list
     */
    public static function createByPositionInList(Expression $expression, array $list): self
    {
        $orderExpression = Expression::positionInList($expression, array_reverse($list));

        return self::create($orderExpression)->withDesc();
    }

    /**
     * Clone with an ascending direction.
     */
    public function withAsc(): self
    {
        $obj = clone $this;
        $obj->isDesc = false;

        return $obj;
    }

    /**
     * Clone with a descending direction.
     */
    public function withDesc(): self
    {
        $obj = clone $this;
        $obj->isDesc = true;

        return $obj;
    }

    /**
     * Clone with a direction.
     *
     * @params self::ASC|self::DESC $direction
     * @throws RuntimeException
     */
    public function withDirection(string $direction): self
    {
        $obj = clone $this;
        $obj->isDesc = strtoupper($direction) === self::DESC;

        if (!in_array(strtoupper($direction), [self::DESC, self::ASC])) {
            throw new RuntimeException("Bad order direction.");
        }

        return $obj;
    }

    /**
     * Clone with a reverse direction.
     */
    public function withReverseDirection(): self
    {
        $obj = clone $this;
        $obj->isDesc = !$this->isDesc;

        return $obj;
    }
}
