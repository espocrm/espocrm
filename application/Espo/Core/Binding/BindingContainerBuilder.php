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

namespace Espo\Core\Binding;

class BindingContainerBuilder
{
    private $data;

    private $binder;

    public function __construct()
    {
        $this->data = new BindingData();
        $this->binder = new Binder($this->data);
    }

    /**
     * Bind an interface to an implementation.
     *
     * @param string $key An interface or interface with a parameter name (`Interface $name`).
     * @param string $implementationClassName An implementation class name.
     */
    public function bindImplementation(string $key, string $implementationClassName): self
    {
        $this->binder->bindImplementation($key, $implementationClassName);

        return $this;
    }

    /**
     * Bind an interface to a specific service.
     *
     * @param string $key An interface or interface with a parameter name (`Interface $name`).
     * @param string $serviceName A service name.
     */
    public function bindService(string $key, string $serviceName): self
    {
        $this->binder->bindService($key, $serviceName);

        return $this;
    }

    /**
     * Bind an interface to a callback.
     *
     * @param string $key An interface or interface with a parameter name (`Interface $name`).
     * @param callable $callback A callback that will resolve a dependency.
     */
    public function bindCallback(string $key, callable $callback): self
    {
        $this->binder->bindCallback($key, $callback);

        return $this;
    }

    /**
     * Bind an interface to a specific instance.
     *
     * @param string $key An interface or interface with a parameter name (`Interface $name`).
     * @param object $instance An instance.
     */
    public function bindInstance(string $key, object $instance): self
    {
        $this->binder->bindInstance($key, $instance);

        return $this;
    }

    /**
     * Bind an interface to a factory.
     *
     * @param string $key An interface or interface with a parameter name (`Interface $name`).
     * @param string $factoryClassName A factory class name.
     */
    public function bindFactory(string $key, string $factoryClassName): self
    {
        $this->binder->bindFactory($key, $factoryClassName);

        return $this;
    }

    /**
     * Creates a contextual binder.
     *
     * @param string $className A context.
     * @param callable $callback A callback with a `ContextualBinder` argument.
     */
    public function inContext(string $className, callable $callback): self
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
