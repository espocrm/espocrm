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

namespace Espo\Core\ORM;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Binding\ContextualBinder;

use Espo\Core\{
    InjectableFactory,
    Utils\ClassFinder,
    Repositories\Database as DatabaseRepository,
};

use Espo\ORM\{
    Repository\RepositoryFactory as RepositoryFactoryInterface,
    Repository\Repository,
    EntityFactory as EntityFactoryInteface,
};

class RepositoryFactory implements RepositoryFactoryInterface
{
    protected $defaultClassName = DatabaseRepository::class;

    /**
     * @var EntityFactoryInteface
     */
    protected $entityFactory;

    /**
     * @var InjectableFactory
     */
    protected $injectableFactory;

    /**
     * @var ClassFinder
     */
    protected $classFinder;

    public function __construct(
        EntityFactoryInteface $entityFactory,
        InjectableFactory $injectableFactory,
        ClassFinder $classFinder
    ) {
        $this->entityFactory = $entityFactory;

        $this->injectableFactory = $injectableFactory;
        $this->classFinder = $classFinder;
    }

    protected function getClassName(string $entityType): ?string
    {
        return $this->classFinder->find('Repositories', $entityType);
    }

    public function create(string $entityType): Repository
    {
        $className = $this->getClassName($entityType);

        if (!$className || !class_exists($className)) {
            $className = $this->defaultClassName;
        }

        return $this->injectableFactory->createWithBinding(
            $className,
            BindingContainerBuilder::create()
                ->bindInstance(EntityFactoryInteface::class, $this->entityFactory)
                ->bindInstance(EntityFactory::class, $this->entityFactory)
                ->inContext(
                    $className,
                    function (ContextualBinder $binder) use ($entityType) {
                        $binder->bindValue('$entityType', $entityType);
                    }
                )
                ->build()
        );
    }
}
