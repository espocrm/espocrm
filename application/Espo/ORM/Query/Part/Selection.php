<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

/**
 * A select item. Immutable.
 *
 * Immutable.
 */
class Selection
{
    private function __construct(
        private Expression $expression,
        private ?string $alias = null
    ) {}

    public function getExpression(): Expression
    {
        return $this->expression;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public static function create(Expression $expression, ?string $alias = null): self
    {
        return new self($expression, $alias);
    }

    public static function fromString(string $expression): self
    {
        return self::create(
            Expression::create($expression)
        );
    }

    /**
     * With an alias. With null, the field name will be used as an alias or an expression itself.
     * Use `withNoAlias` to prevent alias addition.
     */
    public function withAlias(?string $alias): self
    {
        $obj = clone $this;
        $obj->alias = $alias;

        return $obj;
    }

    /**
     * With on alias.
     *
     * @since 9.3.0
     */
    public function withNoAlias(): self
    {
        $obj = clone $this;
        $obj->alias = '';

        return $obj;
    }
}
