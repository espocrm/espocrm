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

namespace Espo\Core\Binding;

use LogicException;
use stdClass;

class BindingData
{
    private stdClass $global;
    private stdClass $context;

    public function __construct()
    {
        $this->global = (object) [];
        $this->context = (object) [];
    }

    public function addContext(string $className, string $key, Binding $binding): void
    {
        if (!property_exists($this->context, $className)) {
            $this->context->$className = (object) [];
        }

        $this->context->$className->$key = $binding;
    }

    public function addGlobal(string $key, Binding $binding): void
    {
        $this->global->$key = $binding;
    }

    /**
     * @param class-string<object> $className
     */
    public function hasContext(string $className, string $key): bool
    {
        if (!property_exists($this->context, $className)) {
            return false;
        }

        if (!property_exists($this->context->$className, $key)) {
            return false;
        }

        return true;
    }

    /**
     * @param class-string<object> $className
     */
    public function getContext(string $className, string $key): Binding
    {
        if (!$this->hasContext($className, $key)) {
            throw new LogicException("No data.");
        }

        return $this->context->$className->$key;
    }

    public function hasGlobal(string $key): bool
    {
        if (!property_exists($this->global, $key)) {
            return false;
        }

        return true;
    }

    public function getGlobal(string $key): Binding
    {
        if (!$this->hasGlobal($key)) {
            throw new LogicException("No data.");
        }

        return $this->global->$key;
    }

    /**
     * @return string[]
     */
    public function getGlobalKeyList(): array
    {
        return array_keys(
            get_object_vars($this->global)
        );
    }

    /**
     * @return class-string<object>[]
     */
    public function getContextList(): array
    {
        /** @var class-string<object>[] */
        return array_keys(
            get_object_vars($this->context)
        );
    }

    /**
     * @return string[]
     */
    public function getContextKeyList(string $context): array
    {
        return array_keys(
            get_object_vars($this->context->$context ?? (object) [])
        );
    }
}
