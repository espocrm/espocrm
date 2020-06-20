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

use ReflectionClass;

/**
 * Creates instance by class name. Uses constructor param names to detect which
 * dependencies are needed. Only container services supported as dependencies.
 */
class InjectableFactory
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(string $className) : object
    {
        return $this->createByClassName($className);
    }

    /**
     * Allows passing specific constructor parameters. Defined in an associative  array. A key should match the parameter name.
     */
    public function createWith(string $className, array $with = []) : object
    {
        return $this->createByClassName($className, $with);
    }

    public function createByClassName(string $className, ?array $with = null) : object
    {
        if (!class_exists($className)) {
            throw new Error("Class '{$className}' does not exist.");
        }

        $class = new ReflectionClass($className);
        if ($class->implementsInterface('\\Espo\\Core\\Interfaces\\Injectable')) {
            return $this->createInjectable($className, $with);
        }

        return $this->createByConstructorParams($className, $with);
    }

    protected function createByConstructorParams(string $className, ?array $with = null)
    {
        $class = new ReflectionClass($className);

        $injectionList = [];

        $constructor = $class->getConstructor();
        if ($constructor) {
            $params = $constructor->getParameters();

            foreach ($params as $param) {
                $name = $param->getName();

                if ($with && array_key_exists($name, $with)) {
                    $injection = $with[$name];
                } else {
                    $dependencyClassName = $param->getClass();
                    if (is_null($dependencyClassName)) {
                        if ($param->isDefaultValueAvailable()) {
                            $injectionList[] = $param->getDefaultValue();
                            continue;
                        }
                    }
                    $injection = $this->container->get($name);

                    if (!$injection) {
                        throw new Error("InjectableFactory: Could not create {$className}, dependency {$name} not found.");
                    }
                }

                $injectionList[] = $injection;
            }
        }

        $obj = $class->newInstanceArgs($injectionList);

        $this->processAwareInjections($class, $obj);

        return $obj;
    }

    /** Deprecated */
    protected function createInjectable(string $className, ?array $with = null)
    {
        $class = new ReflectionClass($className);

        if ($with) {
            $args = [];
            $constructor = $class->getConstructor();
            if ($constructor) {
                $params = $constructor->getParameters();

                foreach ($params as $param) {
                    $name = $param->getName();

                    if (array_key_exists($name, $with)) {
                        $args[] = $with[$name];
                    } else {
                        $dependencyClassName = $param->getClass();
                        if (is_null($dependencyClassName)) {
                            if ($param->isDefaultValueAvailable()) {
                                $args[] = $param->getDefaultValue();
                                continue;
                            }
                        }
                        $args[] = null;
                    }
                }
            }
            $obj = $class->newInstanceArgs($args);
        } else {
            $obj = new $className();
        }

        $setList = [];

        $dependencyList = $obj->getDependencyList();
        foreach ($dependencyList as $name) {
            $injection = $this->container->get($name);
            if ($this->classHasDependencySetter($class, $name)) {
                $methodName = 'set' . ucfirst($name);
                $obj->$methodName($injection);
                $setList[] = $name;
            }
            $obj->inject($name, $injection);
        }

        $this->processAwareInjections($class, $obj, $setList);

        return $obj;
    }

    protected function processAwareInjections(ReflectionClass $class, object $obj, array $ignoreList = [])
    {
        foreach ($class->getInterfaces() as $interface) {
            $interfaceName = $interface->getShortName();

            if (substr($interfaceName, -5) !== 'Aware' || strlen($interfaceName) <= 5) continue;

            $name = lcfirst(substr($interfaceName, 0, -5));

            if (in_array($name, $ignoreList)) continue;

            if (!$this->classHasDependencySetter($class, $name, true)) continue;

            $injection = $this->container->get($name);

            $methodName = 'set' . ucfirst($name);
            $obj->$methodName($injection);
        }
    }

    protected function classHasDependencySetter(ReflectionClass $class, string $name, bool $skipInstanceCheck = false) : bool
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

        if ($skipInstanceCheck || $paramClass && $paramClass->isInstance($injection)) {
            return true;
        }

        return false;
    }
}
