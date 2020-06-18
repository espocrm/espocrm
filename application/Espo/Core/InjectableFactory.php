<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core;

use Espo\Core\Exceptions\Error;

/**
 * Creates instance by class name. Uses either Injectable interface or constructor param names to detect
 * which dependencies are needed. Only container services supported as dependencies.
 */
class InjectableFactory
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(string $className) : object
    {
        return $this->createByClassName($className);
    }

    public function createByClassName(string $className) : object
    {
        if (!class_exists($className)) {
            throw new Error("Class '{$className}' does not exist.");
        }

        $class = new \ReflectionClass($className);
        if ($class->implementsInterface('\\Espo\\Core\\Interfaces\\Injectable')) {
            return $this->createByClassNameInjectable($className);
        }

        return $this->createByClassNameByConstructorParams($className);
    }

    protected function createByClassNameByConstructorParams(string $className)
    {
        $class = new \ReflectionClass($className);

        $dependencyList = [];

        $constructor = $class->getConstructor();
        if (!is_null($constructor)) {
            $params = $constructor->getParameters();

            foreach ($params as $param) {
                $dependencyClassName = $param->getClass();
                if (is_null($dependencyClassName)) {
                    if ($param->isDefaultValueAvailable()) {
                        $dependencyList[] = $param->getDefaultValue();
                        continue;
                    }
                }

                $name = $param->getName();
                $dependency = $this->getContainer()->get($name);

                if (!$dependency) {
                    throw new Error("InjectableFactory: Could not create {$className}, dependency {$name} not found.");
                }

                $dependencyList[] = $dependency;
            }
        }

        return $class->newInstanceArgs($dependencyList);
    }

    protected function createByClassNameInjectable(string $className)
    {
        $obj = new $className();

        $dependencyList = $obj->getDependencyList();
        foreach ($dependencyList as $name) {
            $obj->inject($name, $this->container->get($name));
        }
        if (method_exists($obj, 'prepare')) {
            $obj->prepare();
        }

        return $obj;
    }

    protected function getContainer()
    {
        return $this->container;
    }
}
