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

namespace Espo\ORM\Repository;

use Espo\ORM\{
    Entity,
    EntityManager,
    EntityFactory,
};

/**
 * An access point for fetching and storing records.
 */
abstract class Repository
{
    protected $entityFactory;

    protected $entityManager;

    protected $seed;

    protected $entityType;

    public function __construct(string $entityType, EntityManager $entityManager, EntityFactory $entityFactory)
    {
        $this->entityType = $entityType;

        $this->entityFactory = $entityFactory;
        $this->entityManager = $entityManager;

        $this->seed = $this->entityFactory->create($entityType);
    }

    protected function getEntityFactory() : EntityFactory
    {
        return $this->entityFactory;
    }

    protected function getEntityManager() : EntityManager
    {
        return $this->entityManager;
    }

    public function getEntityType() : string
    {
        return $this->entityType;
    }

    /**
     * Get an entity. If $id is NULL, a new entity is returned.
     */
    abstract public function get(?string $id = null) : ?Entity;

    /**
     * Store an entity.
     */
    abstract public function save(Entity $entity);
}
