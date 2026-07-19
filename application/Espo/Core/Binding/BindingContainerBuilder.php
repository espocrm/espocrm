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
use Espo\Core\Binding\Key\QualifiedClassKey;

class BindingContainerBuilder
{
    private BindingData $data;
    private Binder $binder;

    public function __construct()
    {
        $this->data = new BindingData();
        $this->binder = new Binder($this->data);
    }

    /**
     * Bind an interface to an implementation.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T>|QualifiedClassKey<T> $key
     *     An interface, an interface with a parameter name or an interface with a qualifier.
     * @param class-string<T> $implementationClassName An implementation class name.
     */
    public function bindImplementation(string|NamedClassKey|QualifiedClassKey $key, string $implementationClassName): self
    {
        $this->binder->bindImplementation($key, $implementationClassName);

        return $this;
    }

    /**
     * Bind an interface to a specific service.
     *
     * @param class-string<object>|NamedClassKey<object>|QualifiedClassKey<object> $key
     *     An interface, an interface with a parameter name or an interface with a qualifier.
     * @param string $serviceName A service name.
     * @noinspection PhpUnused
     */
    public function bindService(string|NamedClassKey|QualifiedClassKey $key, string $serviceName): self
    {
        $this->binder->bindService($key, $serviceName);

        return $this;
    }

    /**
     * Bind an interface to a callback.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T>|QualifiedClassKey<T> $key
     *     An interface, an interface with a parameter name or an interface with a qualifier.
     * @param Closure $callback A callback that will resolve a dependency.
     * @todo Change to Closure(...): T Once https://github.com/phpstan/phpstan/issues/8214 is implemented.
     * @noinspection PhpUnused
     */
    public function bindCallback(string|NamedClassKey|QualifiedClassKey $key, Closure $callback): self
    {
        $this->binder->bindCallback($key, $callback);

        return $this;
    }

    /**
     * Bind an interface to a specific instance.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T>|QualifiedClassKey<T> $key
     *     An interface, an interface with a parameter name or an interface with a qualifier.
     * @param T $instance An instance.
     */
    public function bindInstance(string|NamedClassKey|QualifiedClassKey $key, object $instance): self
    {
        $this->binder->bindInstance($key, $instance);

        return $this;
    }

    /**
     * Bind an interface to a factory.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T>|QualifiedClassKey<T> $key
     *    An interface, an interface with a parameter name or an interface with a qualifier.
     * @param class-string<Factory<T>> $factoryClassName A factory class name.
     * @noinspection PhpUnused
     */
    public function bindFactory(string|NamedClassKey|QualifiedClassKey $key, string $factoryClassName): self
    {
        $this->binder->bindFactory($key, $factoryClassName);

        return $this;
    }

    /**
     * Creates a contextual binder and pass it as an argument of a callback.
     *
     * @param class-string<object> $className A context.
     * @param Closure(ContextualBinder): void $callback A callback with a `ContextualBinder` argument.
     */
    public function inContext(string $className, Closure $callback): self
    {
        $contextualBinder = new ContextualBinder($this->data, $className);

        $callback($contextualBinder);

        return $this;
    }

    /**
     * Build.
     */
    public function build(): BindingContainer
    {
        return new BindingContainer($this->data);
    }

    /**
     * Create an instance.
     */
    public static function create(): self
    {
        return new self();
    }
}
