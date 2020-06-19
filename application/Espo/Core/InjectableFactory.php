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

        $injectionList = [];

        $constructor = $class->getConstructor();
        if (!is_null($constructor)) {
            $params = $constructor->getParameters();

            foreach ($params as $param) {
                $dependencyClassName = $param->getClass();
                if (is_null($dependencyClassName)) {
                    if ($param->isDefaultValueAvailable()) {
                        $injectionList[] = $param->getDefaultValue();
                        continue;
                    }
                }

                $name = $param->getName();
                $injection = $this->getContainer()->get($name);

                if (!$injection) {
                    throw new Error("InjectableFactory: Could not create {$className}, dependency {$name} not found.");
                }

                $injectionList[] = $injection;
            }
        }

        return $class->newInstanceArgs($injectionList);
    }

    protected function createByClassNameInjectable(string $className)
    {
        $obj = new $className();
        $class = new \ReflectionClass($className);

        $dependencyList = $obj->getDependencyList();
        foreach ($dependencyList as $name) {
            $injection = $this->container->get($name);
            if ($this->classHasDependencySetter($class, $name)) {
                $methodName = 'set' . ucfirst($name);
                $obj->$methodName($injection);
            }
            $obj->inject($name, $injection);
        }

        return $obj;
    }

    protected function classHasDependencySetter(\ReflectionClass $class, string $name) : bool
    {
        $methodName = 'set' . ucfirst($name);

        if (!$class->hasMethod($methodName) || !$class->getMethod($methodName)->isPublic()) {
            return false;
        }

        $params = $class->getMethod($methodName)->getParameters();
        if (!$params || !count($params)) {
            return false;
        }

        $injection = $this->container->get($name);

        $paramClass = $params[0]->getClass();

        if ($paramClass && $paramClass->isInstance($injection)) {
            return true;
        }

        return false;
    }

    protected function getContainer()
    {
        return $this->container;
    }
}
