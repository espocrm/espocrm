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

namespace Espo\Core\Select\Text\FullTextSearch;

use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Where\OrGroup;

use InvalidArgumentException;

class Data
{
    /**
     * @var Expression[]
     */
    private array $expressions;

    /**
     * @var string[]
     */
    private array $fieldList;

    /**
     * @var string[]
     */
    private array $columnList;

    /**
     * @var Mode::* $mode
     */
    private string $mode;

    /**
     * @param Expression[]|Expression $expressions
     * @param string[] $fieldList
     * @param string[] $columnList
     * @param Mode::* $mode
     */
    public function __construct($expressions, array $fieldList, array $columnList, string $mode)
    {
        // @todo Remove in v8.0
        if (!is_array($expressions)) {
            $expressions = [$expressions];
        }

        $this->expressions = $expressions;
        $this->fieldList = $fieldList;
        $this->columnList = $columnList;
        $this->mode = $mode;

        if (!in_array($mode, [Mode::NATURAL_LANGUAGE, Mode::BOOLEAN])) {
            throw new InvalidArgumentException("Bad mode.");
        }
    }

    /**
     * @deprecated Will be removed in v8.0. Use getExpressions() instead.
     * @return Expression
     */
    public function getExpression() : Expression {
        return $this->expressions[0];
    }

    /**
     * @return Expression[]
     */
    public function getExpressions(): array
    {
        return $this->expressions;
    }

    public function getOrGroup(): OrGroup
    {
        return OrGroup::create(...$this->expressions);
    }

    /**
     * @return string[]
     */
    public function getFieldList(): array
    {
        return $this->fieldList;
    }

    /**
     * @return string[]
     */
    public function getColumnList(): array
    {
        return $this->columnList;
    }

    /**
     * @return Mode::*
     */
    public function getMode(): string
    {
        return $this->mode;
    }
}
