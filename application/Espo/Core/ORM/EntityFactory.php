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

namespace Espo\Core\ORM;

use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Binding\BindingData;
use Espo\Core\InjectableFactory;
use Espo\ORM\DataLoader\Loader;
use Espo\ORM\DataLoader\RDBLoader;
use Espo\ORM\Entity;
use Espo\ORM\EntityFactory as EntityFactoryInterface;
use Espo\ORM\EntityManager;
use Espo\ORM\Relation\RDBRelations;
use Espo\ORM\Relation\Relations;
use Espo\ORM\Relation\RelationsMap;
use Espo\ORM\Value\ValueAccessorFactory;

use RuntimeException;

class EntityFactory implements EntityFactoryInterface
{
    private ?EntityManager $entityManager = null;
    private ?ValueAccessorFactory $valueAccessorFactory = null;

    public function __construct(
        private ClassNameProvider $classNameProvider,
        private Helper $helper,
        private InjectableFactory $injectableFactory,
        private RelationsMap $relationsMap,
    ) {}

    public function setEntityManager(EntityManager $entityManager): void
    {
        if ($this->entityManager) {
            throw new RuntimeException("EntityManager can be set only once.");
        }

        $this->entityManager = $entityManager;
    }

    public function setValueAccessorFactory(ValueAccessorFactory $valueAccessorFactory): void
    {
        if ($this->valueAccessorFactory) {
            throw new RuntimeException("ValueAccessorFactory can be set only once.");
        }

        $this->valueAccessorFactory = $valueAccessorFactory;
    }

    public function create(string $entityType): Entity
    {
        return $this->createInternal($entityType);
    }

    /**
     * @param ?array<string, mixed> $attributeDefs
     */
    private function createInternal(string $entityType, ?array $attributeDefs = null): Entity
    {
        $className = $this->getClassName($entityType);

        if (!$this->entityManager) {
            throw new RuntimeException("No entityManager.");
        }

        $defs = $this->entityManager->getMetadata()->get($entityType);

        if (is_null($defs)) {
            throw new RuntimeException("Entity '$entityType' is not defined in metadata.");
        }

        if ($attributeDefs) {
            $defs['attributes'] = array_merge($defs['attributes'] ?? [], $attributeDefs);
        }

        $relations = $this->injectableFactory->createWithBinding(
            RDBRelations::class,
            BindingContainerBuilder::create()
                ->bindInstance(EntityManager::class, $this->entityManager)
                ->build()
        );

        $loader = $this->injectableFactory->createWithBinding(
            RDBLoader::class,
            BindingContainerBuilder::create()
                ->bindInstance(EntityManager::class, $this->entityManager)
                ->build()
        );

        $bindingContainer = $this->getBindingContainer(
            $className,
            $entityType,
            $defs,
            $relations,
            $loader,
        );

        $entity = $this->injectableFactory->createWithBinding($className, $bindingContainer);

        if ($relations instanceof RDBRelations) {
            $relations->setEntity($entity);
        }

        $this->relationsMap->set($entity, $relations);

        return $entity;
    }

    /**
     * @param array<string, mixed> $attributeDefs
     * @internal
     * @noinspection PhpUnused
     */
    public function createWithAdditionalAttributes(string $entityType, array $attributeDefs): Entity
    {
        return $this->createInternal($entityType, $attributeDefs);
    }

    /**
     * @return class-string<Entity>
     */
    private function getClassName(string $entityType): string
    {
        /** @var class-string<Entity> */
        return $this->classNameProvider->getEntityClassName($entityType);
    }

    /**
     * @param class-string<Entity> $className
     * @param array<string, mixed> $defs
     */
    private function getBindingContainer(
        string $className,
        string $entityType,
        array $defs,
        Relations $relations,
        Loader $loader,
    ): BindingContainer {

        if (!$this->entityManager || !$this->valueAccessorFactory) {
            throw new RuntimeException();
        }

        $data = new BindingData();
        $binder = new Binder($data);

        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType)
            ->bindValue('$defs', $defs)
            ->bindInstance(EntityManager::class, $this->entityManager)
            ->bindInstance(ValueAccessorFactory::class, $this->valueAccessorFactory)
            ->bindInstance(Helper::class, $this->helper)
            ->bindInstance(Relations::class, $relations)
            ->bindInstance(Loader::class, $loader);

        return new BindingContainer($data);
    }
}
