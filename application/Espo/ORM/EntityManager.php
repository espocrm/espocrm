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

use Espo\Core\ORM\Repositories\RDB;
use Espo\Core\ORM\RepositoryFactory;

class EntityManager
{

    protected $pdo;

    protected $entityFactory;

    /**
     * @var RepositoryFactory

     */
    protected $repositoryFactory;

    protected $mappers = array();

    protected $metadata;

    protected $repositoryHash = array();

    protected $params = array();

    protected $query;

    public function __construct($params)
    {
        $this->params = $params;
        $this->metadata = new Metadata();
        if (!empty($params['metadata'])) {
            $this->setMetadata($params['metadata']);
        }
        $entityFactoryClassName = '\\Espo\\ORM\\EntityFactory';
        if (!empty($params['entityFactoryClassName'])) {
            $entityFactoryClassName = $params['entityFactoryClassName'];
        }
        $this->entityFactory = new $entityFactoryClassName($this, $this->metadata);
        $repositoryFactoryClassName = '\\Espo\\ORM\\RepositoryFactory';
        if (!empty($params['repositoryFactoryClassName'])) {
            $repositoryFactoryClassName = $params['repositoryFactoryClassName'];
        }
        $this->repositoryFactory = new $repositoryFactoryClassName($this, $this->entityFactory);
        $this->init();
    }

    protected function init()
    {
    }

    public function getMapper($className)
    {
        if (empty($this->mappers[$className])) {
            // TODO use factory
            $this->mappers[$className] = new $className($this->getPDO(), $this->entityFactory, $this->getQuery());
        }
        return $this->mappers[$className];
    }

    /**
     * @return \PDO

     */
    public function getPDO()
    {
        if (empty($this->pdo)) {
            $this->initPDO();
        }
        return $this->pdo;
    }

    protected function initPDO()
    {
        $params = $this->params;
        $port = empty($params['port']) ? '' : 'port=' . $params['port'] . ';';
        $this->pdo = new \PDO('mysql:host=' . $params['host'] . ';' . $port . 'dbname=' . $params['dbname'] . ';charset=utf8',
            $params['user'], $params['password']);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function getQuery()
    {
        if (empty($this->query)) {
            $this->query = new DB\Query($this->getPDO(), $this->entityFactory);
        }
        return $this->query;
    }

    public function saveEntity(Entity $entity)
    {
        $entityName = $entity->getEntityName();
        return $this->getRepository($entityName)->save($entity);
    }

    /**
     * @param $name
     *
     * @return RDB

     */
    public function getRepository($name)
    {
        if (empty($this->repositoryHash[$name])) {
            $this->repositoryHash[$name] = $this->repositoryFactory->create($name);
        }
        return $this->repositoryHash[$name];
    }

    public function removeEntity(Entity $entity)
    {
        $entityName = $entity->getEntityName();
        return $this->getRepository($entityName)->remove($entity);
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(array $data)
    {
        $this->metadata->setData($data);
    }

    public function normalizeRepositoryName($name)
    {
        return $name;
    }

    public function normalizeEntityName($name)
    {
        return $name;
    }

    public function createCollection($entityName, $data = array())
    {
        $seed = $this->getEntity($entityName);
        $collection = new EntityCollection($data, $seed, $this->entityFactory);
        return $collection;
    }

    /**
     * @param      $name
     * @param null $id
     *
     * @return Entity

     */
    public function getEntity($name, $id = null)
    {
        return $this->getRepository($name)->get($id);
    }
}

