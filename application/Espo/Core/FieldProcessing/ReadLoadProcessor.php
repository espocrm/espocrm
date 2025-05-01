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

namespace Espo\Core\FieldProcessing;

use Espo\Core\Acl;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Entities\User;
use Espo\ORM\Defs;
use Espo\ORM\Entity;

use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;

/**
 * Processes loading special fields (before output).
 */
class ReadLoadProcessor
{
    /** @var array<string, Loader<Entity>[]> */
    private array $loaderListMapCache = [];

    private BindingContainer $bindingContainer;

    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        private Acl $acl,
        private User $user,
        private Defs $defs,
    ) {
        $this->bindingContainer = BindingContainerBuilder::create()
            ->bindInstance(User::class, $this->user)
            ->bindInstance(Acl::class, $this->acl)
            ->build();
    }

    public function process(Entity $entity, ?Params $params = null): void
    {
        if (!$params) {
            $params = new Params();
        }

        foreach ($this->getLoaderList($entity->getEntityType()) as $processor) {
            $processor->process($entity, $params);
        }
    }

    /**
     * @return Loader<Entity>[]
     */
    private function getLoaderList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->loaderListMapCache)) {
            return $this->loaderListMapCache[$entityType];
        }

        $list = [];

        foreach ($this->getLoaderClassNameList($entityType) as $className) {
            $list[] = $this->createLoader($className);
        }

        $this->loaderListMapCache[$entityType] = $list;

        return $list;
    }

    /**
     * @return class-string<Loader<Entity>>[]
     */
    private function getLoaderClassNameList(string $entityType): array
    {
        $entityLevelList = $this->getEntityLevelClassNameList($entityType);

        $list = $this->metadata
            ->get(['app', 'fieldProcessing', 'readLoaderClassNameList']) ?? [];

        $additionalList = $this->metadata
            ->get(['recordDefs', $entityType, 'readLoaderClassNameList']) ?? [];

        /** @var class-string<Loader<Entity>>[] $list */
        $list = array_merge($entityLevelList, $list, $additionalList);

        return array_values(array_unique($list));
    }

    /**
     * @param class-string<Loader<Entity>> $className
     * @return Loader<Entity>
     */
    private function createLoader(string $className): Loader
    {
        return $this->injectableFactory->createWithBinding($className, $this->bindingContainer);
    }

    /**
     * @return class-string<Loader<Entity>>[]
     */
    private function getEntityLevelClassNameList(string $entityType): array
    {
        $entityLevelList = [];

        $fieldList = $this->defs->getEntity($entityType)->getFieldList();

        foreach ($fieldList as $fieldDefs) {
            $className = $fieldDefs->getParam('loaderClassName');

            if (!$className || in_array($className, $entityLevelList)) {
                continue;
            }

            $entityLevelList[] = $className;
        }

        return $entityLevelList;
    }
}
