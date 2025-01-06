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

namespace Espo\Core\Binding\Key;

/**
 * A key for a class-type-hinted constructor parameter with a parameter name.
 *
 * @template-covariant T of object
 */
class NamedClassKey
{
    /**
     * @param class-string<T> $className
     */
    private function __construct(private string $className, private string $parameterName)
    {}

    /**
     * Create.
     *
     * @template TC of object
     * @param class-string<TC> $className An interface.
     * @param string $parameterName A constructor parameter name (w/o '$').
     * @return self<TC>
     */
    public static function create(string $className, string $parameterName): self
    {
        return new self($className, $parameterName);
    }

    public function toString(): string
    {
        return $this->className . ' $' . $this->parameterName;
    }
}
