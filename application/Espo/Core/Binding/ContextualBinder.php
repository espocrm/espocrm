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

use Closure;
use Espo\Core\Binding\Key\NamedClassKey;
use Espo\Core\Binding\Key\NamedKey;
use Espo\Core\Binding\Key\QualifiedClassKey;
use LogicException;

class ContextualBinder
{
    /**
     * @param class-string<object> $className
     */
    public function __construct(
        private BindingData $data,
        private string $className,
    ) {}

    /**
     * Bind an interface to an implementation.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T>|QualifiedClassKey<T> $key
     *     An interface, an interface with a parameter name or an interface with a qualifier.
     * @param class-string<T> $implementationClassName An implementation class name.
     */
    public function bindImplementation(
        string|NamedClassKey|QualifiedClassKey $key,
        string $implementationClassName,
    ): self {

        $key = self::keyToString($key);
        $this->validateBindingKeyNoName($key);

        $binding = Binding::createFromImplementationClassName($implementationClassName);

        $this->data->addContext($this->className, $key, $binding);

        return $this;
    }

    /**
     * Bind an interface to a specific service.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T>|QualifiedClassKey<T> $key
     *     An interface, an interface with a parameter name or an interface with a qualifier.
     * @param string $serviceName A service name.
     */
    public function bindService(string|NamedClassKey|QualifiedClassKey $key, string $serviceName): self
    {
        $key = self::keyToString($key);
        $this->validateBindingKeyNoName($key);

        $this->data->addContext($this->className, $key, Binding::createFromServiceName($serviceName));

        return $this;
    }

    /**
     * Bind an interface or parameter name to a specific value.
     *
     * @param string|NamedKey|NamedClassKey<object> $key
     *     A parameter name (`$name`) or an interface with a parameter name.
     * @param mixed $value A value of any type.
     */
    public function bindValue(string|NamedKey|NamedClassKey $key, $value): self
    {
        $key = self::keyToString($key);
        $this->validateBindingKeyParameterName($key);

        $this->data->addContext($this->className, $key, Binding::createFromValue($value));

        return $this;
    }

    /**
     * Bind an interface to a specific instance.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T>|QualifiedClassKey<T> $key
     *    An interface, an interface with a parameter name or an interface with a qualifier.
     * @param T $instance An instance.
     */
    public function bindInstance(string|NamedClassKey|QualifiedClassKey $key, object $instance): self
    {
        $key = self::keyToString($key);
        $this->validateBindingKeyNoName($key);

        $this->data->addContext($this->className, $key, Binding::createFromValue($instance));

        return $this;
    }

    /**
     * Bind an interface or parameter name to a callback.
     *
     * @param class-string<object>|NamedClassKey<object>|NamedKey|QualifiedClassKey<object> $key
     *     An interface, parameter name or both.
     * @param Closure $callback A callback that will resolve a dependency.
     * @todo Change to Closure(...): mixed Once https://github.com/phpstan/phpstan/issues/8214 is implemented.
     */
    public function bindCallback(string|NamedClassKey|NamedKey|QualifiedClassKey $key, Closure $callback): self
    {
        $key = self::keyToString($key);
        $this->validateBinding($key);

        $this->data->addContext($this->className, $key, Binding::createFromCallback($callback));

        return $this;
    }

    /**
     * Bind an interface to a factory.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T>|QualifiedClassKey<T> $key
     *     An interface, an interface with a parameter name or an interface with a qualifier.
     * @param class-string<Factory<T>> $factoryClassName A factory class name.
     */
    public function bindFactory(string|NamedClassKey|QualifiedClassKey $key, string $factoryClassName): self
    {
        $key = self::keyToString($key);
        $this->validateBindingKeyNoName($key);

        $this->data->addContext($this->className, $key, Binding::createFromFactoryClassName($factoryClassName));

        return $this;
    }

    private function validateBinding(string $key): void
    {
        if (!$key) {
            throw new LogicException("Bad binding.");
        }
    }

    private function validateBindingKeyNoName(string $key): void
    {
        $this->validateBinding($key);

        if ($key[0] === '$') {
            throw new LogicException("Can't bind a parameter name w/o an interface.");
        }

        if ($key[0] === '#') {
            throw new LogicException("Can't bind a qualification name w/o an interface.");
        }
    }

    private function validateBindingKeyParameterName(string $key): void
    {
        $this->validateBinding($key);

        if (!str_contains($key, '$')) {
            throw new LogicException("Can't bind w/o a parameter name.");
        }
    }

    /**
     * @param string|NamedKey|NamedClassKey<object>|QualifiedClassKey<object> $key
     */
    private static function keyToString(string|NamedKey|NamedClassKey|QualifiedClassKey $key): string
    {
        return is_string($key) ? $key : $key->toString();
    }
}
