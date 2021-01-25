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
     * @param $key An interface or interface with a parameter name (`Interface $name`).
     * @param $implementationClassName An implementation class name.
     */
    public function bindImplementation(string $key, string $implementationClassName) : self
    {
        if (!$key || $key[0] === '$') {
            throw new LogicException("Bad binding.");
        }

        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromImplementationClassName($implementationClassName)
        );

        return $this;
    }

    /**
     * Bind an inteface to a specific service.
     *
     * @param $key An interface, parameter name (`$name`) or interface with a parameter name (`Interface $name`).
     * @param $serviceName A service name.
     */
    public function bindService(string $key, string $serviceName) : self
    {
        if (!$key || $key[0] === '$') {
            throw new LogicException("Bad binding.");
        }

        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromServiceName($serviceName)
        );

        return $this;
    }

    /**
     * Bind an inteface or parameter name to a specific value.
     *
     * @param $key An interface, parameter name (`$name`) or interface with a parameter name (`Interface $name`).
     * @param $value A value of any type.
     */
    public function bindValue(string $key, $value) : self
    {
        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromValue($value)
        );

        return $this;
    }

    /**
     * Bind an inteface or parameter name to a callback.
     *
     * @param $key An interface, parameter name (`$name`) or interface with a parameter name (`Interface $name`).
     * @param $callback A callback that will resolve a dependency.
     */
    public function bindCallback(string $key, callable $callback) : self
    {
        $this->data->addContext(
            $this->className,
            $key,
            Binding::createFromCallback($callback)
        );

        return $this;
    }
}
