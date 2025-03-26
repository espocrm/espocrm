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

namespace Espo\Core\Select\Text\FullTextSearch;

use Espo\ORM\Query\Part\Expression;

use InvalidArgumentException;

/**
 * Immutable.
 */
class Data
{
    /** @var string[] */
    private array $fieldList;
    /** @var string[] */
    private array $columnList;
    /** @var Mode::* $mode */
    private string $mode;

    /**
     * @param string[] $fieldList
     * @param string[] $columnList
     * @param Mode::* $mode
     */
    public function __construct(private Expression $expression, array $fieldList, array $columnList, string $mode)
    {
        $this->fieldList = $fieldList;
        $this->columnList = $columnList;
        $this->mode = $mode;

        if (!in_array($mode, [Mode::NATURAL_LANGUAGE, Mode::BOOLEAN])) {
            throw new InvalidArgumentException("Bad mode.");
        }
    }

    public function getExpression(): Expression
    {
        return $this->expression;
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
