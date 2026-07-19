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

namespace Espo\Core\Binding;

use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;
use LogicException;

/**
 * Access point for bindings.
 */
class BindingContainer
{
    public function __construct(private BindingData $data)
    {}

    /**
     * Has binding by a reflection parameter.
     *
     * @param ?ReflectionClass<object> $class
     */
    public function hasByParam(?ReflectionClass $class, ReflectionParameter $param): bool
    {
        if ($this->getInternal($class, $param) === null) {
            return false;
        }

        return true;
    }

    /**
     * Get binding by a reflection parameter.
     *
     * @param ?ReflectionClass<object> $class
     */
    public function getByParam(?ReflectionClass $class, ReflectionParameter $param): Binding
    {
        if (!$this->hasByParam($class, $param)) {
            throw new LogicException("Cannot get not existing binding.");
        }

        return $this->getInternal($class, $param) ?? throw new LogicException();
    }

    /**
     * Has global binding by an interface.
     *
     * @param class-string $interfaceName
     */
    public function hasByInterface(string $interfaceName): bool
    {
        return $this->data->hasGlobal($interfaceName);
    }

    /**
     * Get global binding by an interface.
     *
     * @param class-string $interfaceName
     */
    public function getByInterface(string $interfaceName): Binding
    {
        if (!$this->hasByInterface($interfaceName)) {
            throw new LogicException("Binding for interface `$interfaceName` does not exist.");
        }

        if (!interface_exists($interfaceName) && !class_exists($interfaceName)) {
            throw new LogicException("Interface `$interfaceName` does not exist.");
        }

        return $this->data->getGlobal($interfaceName);
    }

    /**
     * @param ?ReflectionClass<object> $class
     */
    private function getInternal(?ReflectionClass $class, ReflectionParameter $param): ?Binding
    {
        if ($class) {
            $binding = $this->getInternalContextualNamed($class, $param);

            if ($binding) {
                return $binding;
            }
        }

        $paramClassName = $this->getClassNameFromParameterType($param->getType());

        if ($paramClassName === null) {
            return null;
        }

        $keyWithName = $paramClassName . ' $' . $param->getName();

        $binding = $this->getInternalByClassNameKey($class?->getName(), $keyWithName);

        if ($binding) {
            return $binding;
        }

        $key = $paramClassName;

        $binding = $this->getInternalByClassNameKey($class?->getName(), $key);

        if ($binding) {
            return $binding;
        }

        return null;
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function getInternalContextualNamed(ReflectionClass $class, ReflectionParameter $param): ?Binding
    {
        $key = '$' . $param->getName();

        if (!$this->data->hasContext($class->getName(), $key)) {
            return null;
        }

        $type = $param->getType();

        $binding = $this->data->getContext($class->getName(), $key);

        $notMatching =
            $type instanceof ReflectionNamedType &&
            !$type->isBuiltin() &&
            $binding->getType() === Binding::VALUE &&
            is_scalar($binding->getValue());

        if ($notMatching) {
            return null;
        }

        return $binding;
    }

    /**
     * @param ?class-string<object> $className
     */
    private function getInternalByClassNameKey(?string $className, string $key): ?Binding
    {
        if ($className && $this->data->hasContext($className, $key)) {
            return $this->data->getContext($className, $key);
        }

        if ($this->data->hasGlobal($key)) {
            return $this->data->getGlobal($key);
        }

        return null;
    }

    private function getClassNameFromParameterType(mixed $type): ?string
    {
        $dependencyClassName = null;

        if (
            $type instanceof ReflectionNamedType &&
            !$type->isBuiltin()
        ) {
            $dependencyClassName = $type->getName();
        }

        return $dependencyClassName;
    }
}
