<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\ORM;

abstract class Repository
{

    /**
     * @var EntityFactory EntityFactory object.
     */
    protected $entityFactory;

    /**
     * @var EntityManager EntityManager object.
     */
    protected $entityManager;

    /**
     * @var iModel Seed entity.
     */
    protected $seed;

    /**
     * @var string Class Name of aggregate root.
     */
    protected $entityClassName;

    /**
     * @var string Model Name of aggregate root.
     */
    protected $entityName;

    public function __construct($entityName, EntityManager $entityManager, EntityFactory $entityFactory)
    {
        $this->entityName = $entityName;
        $this->entityFactory = $entityFactory;
        $this->seed = $this->entityFactory->create($entityName);
        $this->entityClassName = get_class($this->seed);
        $this->entityManager = $entityManager;
    }

    abstract public function get($id = null);

    abstract public function save(Entity $entity);

    abstract public function remove(Entity $entity);

    abstract public function find(array $params);

    abstract public function findOne(array $params);

    abstract public function getAll();

    abstract public function count(array $params);

    protected function getEntityFactory()
    {
        return $this->entityFactory;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }
}

