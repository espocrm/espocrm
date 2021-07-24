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

use Espo\ORM\Query\Select;

use RuntimeException;

class OrderExpression
{
    private $expression;

    private $isDesc = false;

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
     * @return Select::ORDER_DESC|Select::ORDER_ASC
     */
    public function getDirection(): string
    {
        return $this->isDesc ? Select::ORDER_DESC : Select::ORDER_ASC;
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
     * Create with a position in SELECT.
     */
    public static function createWithPosition(int $positionInSelect): self
    {
        return self::fromString((string) $positionInSelect);
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
     * @params Select::ORDER_ASC|Select::ORDER_DESC $direction
     * @throws RuntimeException
     */
    public function withDirection(string $direction): self
    {
        $obj = clone $this;
        $obj->isDesc = strtoupper($direction) === Select::ORDER_DESC;

        if (!in_array(strtoupper($direction), [Select::ORDER_DESC, Select::ORDER_ASC])) {
            throw new RuntimeException("Bad order direction.");
        }

        return $obj;
    }
}
