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

use Espo\Core\Binding\Binding;
use Espo\Core\Container\Exceptions\NotFoundException;
use Espo\Core\Container\Exceptions\NotSettableException;
use Espo\Core\Container\Loader;
use Espo\Core\Container\Container as ContainerInterface;
use Espo\Core\Container\Configuration;
use Espo\Core\Binding\BindingContainer;

use Psr\Container\NotFoundExceptionInterface;

use ReflectionClass;
use ReflectionNamedType;
use LogicException;
use RuntimeException;

/**
 * DI container for services. Lazy initialization is used. Services are instantiated only once.
 * @see https://docs.espocrm.com/development/di/.
 */
class Container implements ContainerInterface
{
    private const ID_CONTAINER = 'container';
    private const ID_INJECTABLE_FACTORY = 'injectableFactory';

    /** @var array<string, object> */
    private array $data = [];
    /** @var array<string, ReflectionClass<object>> */
    private array $classCache = [];
    /** @var array<string, class-string<Loader>> */
    private array $loaderClassNames;

    private ?Configuration $configuration = null;
    private InjectableFactory $injectableFactory;

    /**
     * @param class-string<Configuration> $configurationClassName
     * @param array<string, class-string<Loader>> $loaderClassNames
     * @param array<string, object> $services
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        string $configurationClassName,
        private BindingContainer $bindingContainer,
        array $loaderClassNames = [],
        array $services = []
    ) {
        $this->loaderClassNames = $loaderClassNames;

        foreach ($services as $name => $service) {
            if (!is_string($name) || !is_object($service)) {
                throw new RuntimeException("Container: Bad service passed.");
            }

            $this->setForced($name, $service);
        }

        /** @var InjectableFactory $injectableFactory */
        $injectableFactory = $this->get(self::ID_INJECTABLE_FACTORY);
        $this->injectableFactory = $injectableFactory;

        $this->configuration = $this->injectableFactory->create($configurationClassName);
    }

    /**
     * @inheritDoc
     */
    public function get(string $id): object
    {
        if (!$this->isSet($id)) {
            $this->load($id);

            if (!$this->isSet($id)) {
                throw new NotFoundException("Could not load '$id' service.");
            }
        }

        return $this->data[$id];
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        if ($this->isSet($id)) {
            return true;
        }

        if (array_key_exists($id, $this->loaderClassNames)) {
            return true;
        }

        $loadMethodName = 'load' . ucfirst($id);

        if (method_exists($this, $loadMethodName)) {
            return true;
        }

        if (!$this->configuration) {
            return false;
        }

        if ($this->configuration->getLoaderClassName($id)) {
            return true;
        }

        if ($this->configuration->getServiceClassName($id)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     * @template T of object
     * @param class-string<T> $className A class name or interface name.
     * @return T A service instance.
     * @throws NotFoundExceptionInterface If not gettable.
     */
    public function getByClass(string $className): object
    {
        $binding = $this->bindingContainer->getByInterface($className);

        if ($binding->getType() !== Binding::CONTAINER_SERVICE) {
            throw new NotFoundException("No service bound to `$className`.");
        }

        $id = $binding->getValue();

        if (!is_string($id)) {
            throw new LogicException();
        }

        /** @var T */
        return $this->get($id);
    }

    private function isSet(string $id): bool
    {
        return isset($this->data[$id]);
    }

    private function initClass(string $id): void
    {
        if ($this->isSet($id)) {
            try {
                $object = $this->get($id);
            } catch (NotFoundExceptionInterface) {
                throw new LogicException();
            }

            $this->classCache[$id] = new ReflectionClass($object);

            return;
        }

        if ($id === self::ID_CONTAINER) {
            /** @var ReflectionClass<object> $object */
            $object = new ReflectionClass(Container::class);

            $this->classCache[$id] = $object;

            return;
        }

        if ($id === self::ID_INJECTABLE_FACTORY) {
            /** @var ReflectionClass<object> $object */
            $object = new ReflectionClass(InjectableFactory::class);

            $this->classCache[$id] = $object;

            return;
        }

        $loaderClassName = $this->getLoaderClassName($id);

        if ($loaderClassName) {
            $this->initClassByLoader($id, $loaderClassName);

            return;
        }

        assert($this->configuration !== null);

        $className = $this->configuration->getServiceClassName($id);

        if ($className === null) {
            throw new RuntimeException("No class-name for service '$id'.");
        }

        $this->classCache[$id] = new ReflectionClass($className);
    }

    /**
     * @param class-string<Loader> $loaderClassName
     * @throws RuntimeException
     */
    private function initClassByLoader(string $id, string $loaderClassName): void
    {
        $loaderClass = new ReflectionClass($loaderClassName);

        $loadMethod = $loaderClass->getMethod('load');

        if (!$loadMethod->hasReturnType()) {
            throw new RuntimeException("Loader method for service '$id' does not have a return type.");
        }

        $returnType = $loadMethod->getReturnType();

        if (!$returnType instanceof ReflectionNamedType) {
            throw new RuntimeException("Loader method for service '$id' does not have a named return type.");
        }

        /** @var class-string $className */
        $className = $returnType->getName();

        $this->classCache[$id] = new ReflectionClass($className);
    }

    /**
     * Get a class of a service.
     *
     * @return ReflectionClass<object>
     * @throws RuntimeException If not gettable.
     */
    public function getClass(string $id): ReflectionClass
    {
        if (!$this->has($id)) {
            throw new RuntimeException("Service '$id' does not exist.");
        }

        if (!isset($this->classCache[$id])) {
            $this->initClass($id);
        }

        return $this->classCache[$id];
    }

    /**
     * @inheritDoc
     */
    public function set(string $id, object $object): void
    {
        assert($this->configuration !== null);

        if (!$this->configuration->isSettable($id)) {
            throw new NotSettableException("Service '$id' is not settable.");
        }

        if ($this->isSet($id)) {
            throw new NotSettableException("Service '$id' is already set.");
        }

        $this->setForced($id, $object);
    }

    protected function setForced(string $id, object $object): void
    {
        $this->data[$id] = $object;
    }

    private function getLoader(string $name): ?Loader
    {
        $loaderClassName = $this->getLoaderClassName($name);

        if (!$loaderClassName) {
            return null;
        }

        return $this->injectableFactory->create($loaderClassName);
    }

    /**
     * @return ?class-string<Loader>
     */
    private function getLoaderClassName(string $id): ?string
    {
        $loader = $this->loaderClassNames[$id] ?? null;

        if ($loader) {
            return $loader;
        }

        if ($this->configuration === null) {
            throw new RuntimeException("Container configuration is not ready.");
        }

        return $this->configuration->getLoaderClassName($id);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    private function load(string $id): void
    {
        if ($id === self::ID_CONTAINER) {
            $this->setForced(self::ID_CONTAINER, $this->loadContainer());

            return;
        }

        if ($id === self::ID_INJECTABLE_FACTORY) {
            $this->setForced(self::ID_INJECTABLE_FACTORY, $this->loadInjectableFactory());

            return;
        }

        $loader = $this->getLoader($id);

        if ($loader) {
            $this->data[$id] = $loader->load();

            return;
        }

        assert($this->configuration !== null);

        $className = $this->configuration->getServiceClassName($id);

        if (!$className || !class_exists($className)) {
            throw new RuntimeException("Could not load '$id' service.");
        }

        $dependencyList = $this->configuration->getServiceDependencyList($id);

        if (!is_null($dependencyList)) {
            $dependencyObjectList = [];

            foreach ($dependencyList as $item) {
                $dependencyObjectList[] = $this->get($item);
            }

            $reflector = new ReflectionClass($className);

            $this->data[$id] = $reflector->newInstanceArgs($dependencyObjectList);

            return;
        }

        $this->data[$id] = $this->injectableFactory->create($className);
    }

    private function loadContainer(): Container
    {
        return $this;
    }

    private function loadInjectableFactory(): InjectableFactory
    {
        return new InjectableFactory($this, $this->bindingContainer);
    }
}
