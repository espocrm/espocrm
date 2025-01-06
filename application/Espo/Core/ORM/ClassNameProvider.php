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

use Espo\Core\ORM\Entity as BaseEntity;
use Espo\Core\Repositories\Database as DatabaseRepository;
use Espo\Core\Utils\ClassFinder;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity as Entity;
use Espo\ORM\EventDispatcher;
use Espo\ORM\Repository\Repository as Repository;

class ClassNameProvider
{
    /** @var class-string<Entity> */
    private const DEFAULT_ENTITY_CLASS_NAME = BaseEntity::class;
    /** @var class-string<Repository<Entity>> */
    private const DEFAULT_REPOSITORY_CLASS_NAME = DatabaseRepository::class;

    /** @var array<string, class-string<Entity>> */
    private array $entityCache = [];

    /** @var array<string, class-string<Repository<Entity>>> */
    private array $repositoryCache = [];

    public function __construct(
        private Metadata $metadata,
        private ClassFinder $classFinder,
        EventDispatcher $eventDispatcher,
    ) {
        $eventDispatcher->subscribeToMetadataUpdate(function () {
            $this->entityCache = [];
            $this->repositoryCache = [];

            $this->classFinder->resetRuntimeCache();
        });
    }

    /**
     * @param string $entityType
     * @return class-string<Entity>
     */
    public function getEntityClassName(string $entityType): string
    {
        if (!array_key_exists($entityType, $this->entityCache)) {
            $this->entityCache[$entityType] = $this->findEntityClassName($entityType);
        }

        return $this->entityCache[$entityType];
    }

    /**
     * @param string $entityType
     * @return class-string<Repository<Entity>>
     */
    public function getRepositoryClassName(string $entityType): string
    {
        if (!array_key_exists($entityType, $this->entityCache)) {
            $this->repositoryCache[$entityType] = $this->findRepositoryClassName($entityType);
        }

        return $this->repositoryCache[$entityType];
    }

    /**
     * @param string $entityType
     * @return class-string<Entity>
     */
    private function findEntityClassName(string $entityType): string
    {
        /** @var ?class-string<Entity> $className */
        $className = $this->metadata->get("entityDefs.$entityType.entityClassName");

        if ($className) {
            return $className;
        }

        /** @var ?class-string<Entity> $className */
        $className = $this->classFinder->find('Entities', $entityType);

        if ($className) {
            return $className;
        }

        /** @var ?string $template */
        $template = $this->metadata->get(['scopes', $entityType, 'type']);

        if ($template) {
            /** @var ?class-string<Entity> $className */
            $className = $this->metadata->get(['app', 'entityTemplates', $template, 'entityClassName']);
        }

        if ($className) {
            return $className;
        }

        return self::DEFAULT_ENTITY_CLASS_NAME;
    }

    /**
     * @param string $entityType
     * @return class-string<Repository<Entity>>
     */
    private function findRepositoryClassName(string $entityType): string
    {
        /** @var ?class-string<Repository<Entity>> $className */
        $className = $this->metadata->get("entityDefs.$entityType.repositoryClassName");

        if ($className) {
            return $className;
        }

        /** @var ?class-string<Repository<Entity>> $className */
        $className = $this->classFinder->find('Repositories', $entityType);

        if ($className) {
            return $className;
        }

        /** @var ?string $template */
        $template = $this->metadata->get(['scopes', $entityType, 'type']);

        if ($template) {
            /** @var ?class-string<Repository<Entity>> $className */
            $className = $this->metadata->get(['app', 'entityTemplates', $template, 'repositoryClassName']);
        }

        if ($className) {
            return $className;
        }

        return self::DEFAULT_REPOSITORY_CLASS_NAME;
    }
}
