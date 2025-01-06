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

namespace Espo\Core;

use Psr\Container\NotFoundExceptionInterface;

use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\Binding;
use Espo\Core\Binding\Factory;

use ReflectionClass;
use ReflectionParameter;
use ReflectionFunction;
use ReflectionNamedType;
use Throwable;
use RuntimeException;
use Closure;

/**
 * Creates an instance by a class name. Uses constructor param names and type hinting to detect which
 * dependencies are needed. Service dependencies are instantiated only once. Non-service dependencies
 * are instantiated every time along with a dependent class.
 *
 * Aware interfaces are also used to detect service dependencies.
 */
class InjectableFactory
{
    public function __construct(
        private Container $container,
        private ?BindingContainer $bindingContainer = null
    ) {}

    /**
     * Create an instance by a class name.
     *
     * @template T of object
     * @param class-string<T> $className An instantiatable class.
     * @return T
     */
    public function create(string $className): object
    {
        return $this->createInternal($className);
    }

    /**
     * Create an instance by a class name with specific constructor parameters
     * defined in an associative array. A key should match the parameter name.
     *
     * @template T of object
     * @param class-string<T> $className An instantiatable class.
     * @param array<string, mixed> $with Constructor parameter values.
     * @return T
     */
    public function createWith(string $className, array $with): object
    {
        return $this->createInternal($className, $with);
    }

    /**
     * Create an instance by a class name with a specific binding.
     *
     * @template T of object
     * @param class-string<T> $className An instantiatable class.
     * @param BindingContainer $bindingContainer A binding container.
     * @return T
     */
    public function createWithBinding(string $className, BindingContainer $bindingContainer): object
    {
        return $this->createInternal($className, null, $bindingContainer);
    }

    /**
     * Create an instance by an interface with an optional additional binding.
     * An interface will be resolved by the global binding. If a class is provided, it will be tried to
     * be resolved (if it's bound to an extended class). If the class is not bound, it will be instantiated
     * (with the same behavior as with the `createWithBinding` method).
     *
     * @template T of object
     * @param class-string<T> $interfaceName An interface or class.
     * @param ?BindingContainer $bindingContainer A binding container.
     * @return T
     */
    public function createResolved(string $interfaceName, ?BindingContainer $bindingContainer = null): object
    {
        $binding = $this->bindingContainer && $this->bindingContainer->hasByInterface($interfaceName) ?
            $this->bindingContainer->getByInterface($interfaceName) :
            null;

        if (!$binding) {
            $class = new ReflectionClass($interfaceName);

            if ($class->isInterface()) {
                throw new RuntimeException("Could not resolve interface `$interfaceName`.");
            }

            $obj = $this->createInternal($interfaceName, null, $bindingContainer);

            if (!$obj instanceof $interfaceName) {
                throw new RuntimeException("Class `$interfaceName` resolved to another type.");
            }

            return $obj;
        }

        $typeList = [
            Binding::IMPLEMENTATION_CLASS_NAME,
            Binding::FACTORY_CLASS_NAME,
            Binding::CALLBACK,
        ];

        if (!in_array($binding->getType(), $typeList)) {
            throw new RuntimeException("Bad resolution for interface `$interfaceName`.");
        }

        $obj = $this->resolveBinding($binding, $bindingContainer);

        if (!$obj instanceof $interfaceName) {
            throw new RuntimeException("Class `$interfaceName` resolved to another type.");
        }

        return $obj;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @param ?array<string, mixed> $with
     * @return T
     */
    private function createInternal(
        string $className,
        ?array $with = null,
        ?BindingContainer $bindingContainer = null
    ): object {

        if (!class_exists($className)) {
            throw new RuntimeException("InjectableFactory: Class '$className' does not exist.");
        }

        $class = new ReflectionClass($className);

        $injectionList = $this->getConstructorInjectionList($class, $with, $bindingContainer);

        $obj = $class->newInstanceArgs($injectionList);

        $this->applyAwareInjections($class, $obj);

        return $obj;
    }

    /**
     * @param ReflectionClass<object> $class
     * @param ?array<string, mixed> $with
     * @return mixed[]
     */
    private function getConstructorInjectionList(
        ReflectionClass $class,
        ?array $with = null,
        ?BindingContainer $bindingContainer = null
    ): array {

        $injectionList = [];

        $constructor = $class->getConstructor();

        if (!$constructor) {
            return $injectionList;
        }

        $params = $constructor->getParameters();

        foreach ($params as $param) {
            $injectionList[] = $this->getMethodParamInjection($class, $param, $with, $bindingContainer);
        }

        return $injectionList;
    }

    /**
     * @param ?ReflectionClass<object> $class
     * @param ?array<string, mixed> $with
     * @return mixed
     */
    private function getMethodParamInjection(
        ?ReflectionClass $class,
        ReflectionParameter $param,
        ?array $with = null,
        ?BindingContainer $bindingContainer = null
    ) {

        $name = $param->getName();

        if ($with && array_key_exists($name, $with)) {
            return $with[$name];
        }

        $dependencyClass = null;

        $type = $param->getType();

        if (
            $type &&
            $type instanceof ReflectionNamedType &&
            !$type->isBuiltin()
        ) {
            try {
                /** @var class-string $dependencyClassName */
                $dependencyClassName = $type->getName();

                $dependencyClass = new ReflectionClass($dependencyClassName);
            } catch (Throwable $e) {
                $badClassName = $type->getName();

                // This trick allows to log syntax errors.
                class_exists($badClassName);

                throw new RuntimeException("InjectableFactory: " . $e->getMessage());
            }
        }

        if ($bindingContainer && $bindingContainer->hasByParam($class, $param)) {
            $binding = $bindingContainer->getByParam($class, $param);

            return $this->resolveBinding($binding, $bindingContainer);
        }

        if ($this->bindingContainer && $this->bindingContainer->hasByParam($class, $param)) {
            $binding = $this->bindingContainer->getByParam($class, $param);

            return $this->resolveBinding($binding, $bindingContainer);
        }

        if (!$dependencyClass && $param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if (
            $dependencyClass && $this->container->has($name) &&
            $this->areDependencyClassesMatching($dependencyClass, $this->container->getClass($name))
        ) {
            return $this->container->get($name);
        }

        if ($dependencyClass && $param->allowsNull()) {
            return null;
        }

        if ($dependencyClass) {
            return $this->createInternal($dependencyClass->getName(), null, $bindingContainer);
        }

        if (!$class) {
            throw new RuntimeException(
                "InjectableFactory: Could not resolve the dependency '$name' for a callback."
            );
        }

        $className = $class->getName();

        throw new RuntimeException(
            "InjectableFactory: Could not create '$className', the dependency '$name' is not resolved."
        );
    }

    /**
     * @return mixed[]
     */
    private function getCallbackInjectionList(callable $callback): array
    {
        $injectionList = [];

        if (!$callback instanceof Closure) {
            $callback = Closure::fromCallable($callback);
        }

        $function = new ReflectionFunction($callback);

        foreach ($function->getParameters() as $param) {
            $injectionList[] = $this->getMethodParamInjection(null, $param);
        }

        return $injectionList;
    }

    private function resolveBinding(Binding $binding, ?BindingContainer $bindingContainer): mixed
    {
        $type = $binding->getType();
        $value = $binding->getValue();

        if ($type === Binding::CONTAINER_SERVICE) {
            try {
                return $this->container->get($value);
            } catch (NotFoundExceptionInterface $e) {
                throw new RuntimeException($e->getMessage());
            }
        }

        if ($type === Binding::IMPLEMENTATION_CLASS_NAME) {
            /** @var class-string $value */
            return $this->createInternal($value, null, $bindingContainer);
        }

        if ($type === Binding::VALUE) {
            return $value;
        }

        if ($type === Binding::CALLBACK) {
            $callback = $value;

            $dependencyList = $this->getCallbackInjectionList($callback);

            return $callback(...$dependencyList);
        }

        if ($type === Binding::FACTORY_CLASS_NAME) {
            /** @var class-string<object> $value */
            /** @var Factory<object> $factory */
            $factory = $this->createInternal($value, null, $bindingContainer);

            return $factory->create();
        }

        throw new RuntimeException("InjectableFactory: Bad binding.");
    }

    /**
     * @param ReflectionClass<object> $paramHintClass
     * @param ReflectionClass<object> $returnHintClass
     */
    private function areDependencyClassesMatching(
        ReflectionClass $paramHintClass,
        ReflectionClass $returnHintClass
    ): bool {

        if ($paramHintClass->getName() === $returnHintClass->getName()) {
            return true;
        }

        if ($returnHintClass->isSubclassOf($paramHintClass)) {
            return true;
        }

        return false;
    }

    /**
     * @param ReflectionClass<object> $class
     * @param string[] $ignoreList
     */
    private function applyAwareInjections(ReflectionClass $class, object $obj, array $ignoreList = []): void
    {
        foreach ($class->getInterfaces() as $interface) {
            $interfaceName = $interface->getShortName();

            if (!str_ends_with($interfaceName, 'Aware') || strlen($interfaceName) <= 5) {
                continue;
            }

            $name = lcfirst(substr($interfaceName, 0, -5));

            if (in_array($name, $ignoreList)) {
                continue;
            }

            if (!$this->classHasDependencySetter($class, $name, true)) {
                continue;
            }

            $injection = $this->container->get($name);

            $methodName = 'set' . ucfirst($name);

            $obj->$methodName($injection);
        }
    }

    /**
     * @param ReflectionClass<object> $class
     */
    private function classHasDependencySetter(
        ReflectionClass $class,
        string $name,
        bool $skipInstanceCheck = false
    ): bool {

        $methodName = 'set' . ucfirst($name);

        if (!$class->hasMethod($methodName) || !$class->getMethod($methodName)->isPublic()) {
            return false;
        }

        $params = $class->getMethod($methodName)->getParameters();

        if (!count($params)) {
            return false;
        }

        if ($skipInstanceCheck) {
            return true;
        }

        $injection = $this->container->get($name);

        $paramClass = null;

        $type = $params[0]->getType();

        if (
            $type &&
            $type instanceof ReflectionNamedType &&
            !$type->isBuiltin()
        ) {
            /** @var class-string $dependencyClassName */
            $dependencyClassName = $type->getName();

            $paramClass = new ReflectionClass($dependencyClassName);
        }

        if ($paramClass && $paramClass->isInstance($injection)) {
            return true;
        }

        return false;
    }
}
