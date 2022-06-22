<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\ORM;

use Espo\Core\{
    Utils\ClassFinder,
    InjectableFactory,
    ORM\Entity as BaseEntity,
    Binding\BindingContainer,
    Binding\BindingData,
    Binding\Binder,
};

use Espo\ORM\{
    Entity,
    EntityManager,
    EntityFactory as EntityFactoryInterface,
    Value\ValueAccessorFactory,
};

use RuntimeException;

class EntityFactory implements EntityFactoryInterface
{
    private ClassFinder $classFinder;

    private Helper $helper;

    private InjectableFactory $injectableFactory;

    private ?EntityManager $entityManager = null;

    private ?ValueAccessorFactory $valueAccessorFactory = null;

    public function __construct(ClassFinder $classFinder, Helper $helper, InjectableFactory $injectableFactory)
    {
        $this->classFinder = $classFinder;
        $this->helper = $helper;
        $this->injectableFactory = $injectableFactory;
    }

    /**
     * @return ?class-string<\Espo\ORM\Entity>
     */
    private function getClassName(string $entityType): ?string
    {
        /** @var ?class-string<\Espo\ORM\Entity> */
        return $this->classFinder->find('Entities', $entityType);
    }

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
        $className = $this->getClassName($entityType);

        if (!$className) {
            $className = BaseEntity::class;
        }

        if (!$this->entityManager) {
            throw new RuntimeException();
        }

        $defs = $this->entityManager->getMetadata()->get($entityType);

        if (is_null($defs)) {
            throw new RuntimeException("Entity '{$entityType}' is not defined in metadata.");
        }

        $bindingContainer = $this->getBindingContainer($className, $entityType, $defs);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    /**
     * @param array<string,mixed> $defs
     */
    private function getBindingContainer(string $className, string $entityType, array $defs): BindingContainer
    {
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
            ->bindInstance(Helper::class, $this->helper);

        return new BindingContainer($data);
    }
}
