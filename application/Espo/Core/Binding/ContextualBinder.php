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

use LogicException;

class ContextualBinder
{
    private $data;

    private $className;

    public function __construct(BindingData $data, string $className)
    {
        $this->data = $data;
        $this->className = $className;
    }

    /**
     * Bind an interface to an implementation.
     *
     * @param string $key An interface or interface with a parameter name (`Interface $name`).
     * @param string $implementationClassName An implementation class name.
     */
    public function bindImplementation(string $key, string $implementationClassName): self
    {
        $this->validateBindingKeyNoParameterName($key);

        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromImplementationClassName($implementationClassName)
        );

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
        $this->validateBindingKeyNoParameterName($key);

        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromServiceName($serviceName)
        );

        return $this;
    }

    /**
     * Bind an interface or parameter name to a specific value.
     *
     * @param string $key Parameter name (`$name`) or interface with a parameter name (`Interface $name`).
     * @param mixed $value A value of any type.
     */
    public function bindValue(string $key, $value): self
    {
        $this->validateBindingKeyParameterName($key);

        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromValue($value)
        );

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
        $this->validateBindingKeyNoParameterName($key);

        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromValue($instance)
        );

        return $this;
    }

    /**
     * Bind an interface or parameter name to a callback.
     *
     * @param string $key An interface, parameter name (`$name`) or
     * interface with a parameter name (`Interface $name`).
     * @param callable $callback A callback that will resolve a dependency.
     */
    public function bindCallback(string $key, callable $callback): self
    {
        $this->validateBinding($key);

        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromCallback($callback)
        );

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
        $this->validateBindingKeyNoParameterName($key);

        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromFactoryClassName($factoryClassName)
        );

        return $this;
    }

    private function validateBinding(string $key): void
    {
        if (!$key) {
            throw new LogicException("Bad binding.");
        }
    }

    private function validateBindingKeyNoParameterName(string $key): void
    {
        $this->validateBinding($key);

        if ($key[0] === '$') {
            throw new LogicException("Can't bind a parameter name w/o an interface.");
        }
    }

    private function validateBindingKeyParameterName(string $key): void
    {
        $this->validateBinding($key);

        if (strpos($key, '$') === false) {
            throw new LogicException("Can't bind w/o a parameter name.");
        }
    }
}
