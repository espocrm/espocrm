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

namespace Espo\Core\Formula;

use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Parser\Ast\Attribute;
use Espo\Core\Formula\Parser\Ast\Node;
use Espo\Core\Formula\Parser\Ast\Value;
use Espo\Core\Formula\Parser\Ast\Variable;

/**
 * A function argument.
 */
class Argument implements Evaluatable
{
    public function __construct(private mixed $data)
    {}

    /**
     * Get an argument type (function name).
     *
     * @throws Error
     */
    public function getType(): string
    {
        if ($this->data instanceof Node) {
            return $this->data->getType();
        }

        if ($this->data instanceof Value) {
            return 'value';
        }

        if ($this->data instanceof Variable) {
            return 'variable';
        }

        if ($this->data instanceof Attribute) {
            return 'attribute';
        }

        throw new Error("Can't get type from scalar.");
    }

    /**
     * Get a nested argument list.
     *
     * @throws Error
     */
    public function getArgumentList(): ArgumentList
    {
        if ($this->data instanceof Node) {
            return new ArgumentList($this->data->getChildNodes());
        }

        if ($this->data instanceof Value) {
            return new ArgumentList([$this->data->getValue()]);
        }

        if ($this->data instanceof Variable) {
            $value = new Value($this->data->getName());

            return new ArgumentList([$value]);
        }

        if ($this->data instanceof Attribute) {
            return new ArgumentList([$this->data->getName()]);
        }

        throw new Error("Can't get argument list from a non-node item.");
    }

    /**
     * Get data.
     */
    public function getData(): mixed
    {
        return $this->data;
    }
}
