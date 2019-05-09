<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\ORM;

use \Espo\Core\Exceptions\Error;

class EntityManager
{
    const STH_COLLECTION = 'sthCollection';

    protected $pdo;

    protected $entityFactory;

    protected $repositoryFactory;

    protected $mappers = [];

    protected $metadata;

    protected $repositoryHash = [];

    protected $params = [];

    protected $query;

    protected $driverPlatformMap = [
        'pdo_mysql' => 'Mysql',
        'mysqli' => 'Mysql',
    ];

    public function __construct($params)
    {
        $this->params = $params;

        $this->metadata = new Metadata();

        if (empty($this->params['platform'])) {
            if (empty($this->params['driver'])) {
                throw new \Exception('No database driver specified.');
            }
            $driver = $this->params['driver'];
            if (empty($this->driverPlatformMap[$driver])) {
                throw new \Exception("Database driver '{$driver}' is not supported.");
            }
            $this->params['platform'] = $this->driverPlatformMap[$this->params['driver']];
        }

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

    public function getQuery()
    {
        if (empty($this->query)) {
            $platform = $this->params['platform'];
            $className = '\\Espo\\ORM\\DB\\Query\\' . ucfirst($platform);
            $this->query = new $className($this->getPDO(), $this->entityFactory, $this->metadata);
        }
        return $this->query;
    }

    protected function getMapperClassName($name)
    {
        $className = null;

        switch ($name) {
            case 'RDB':
                $platform = $this->params['platform'];
                $className = '\\Espo\\ORM\\DB\\' . ucfirst($platform) . 'Mapper';
                break;
        }

        return $className;
    }

    public function getMapper($name)
    {
        if ($name{0} == '\\') {
            $className = $name;
        } else {
            $className = $this->getMapperClassName($name);
        }

        if (empty($this->mappers[$className])) {
            $this->mappers[$className] = new $className($this->getPDO(), $this->entityFactory, $this->getQuery(), $this->metadata);
        }
        return $this->mappers[$className];
    }

    protected function initPDO()
    {
        $params = $this->params;

        $port = empty($params['port']) ? '' : 'port=' . $params['port'] . ';';

        $platform = strtolower($params['platform']);

        $options = [];
        if (isset($params['sslCA'])) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $params['sslCA'];
        }
        if (isset($params['sslCert'])) {
            $options[\PDO::MYSQL_ATTR_SSL_CERT] = $params['sslCert'];
        }
        if (isset($params['sslKey'])) {
            $options[\PDO::MYSQL_ATTR_SSL_KEY] = $params['sslKey'];
        }
        if (isset($params['sslCAPath'])) {
            $options[\PDO::MYSQL_ATTR_SSL_CAPATH] = $params['sslCAPath'];
        }
        if (isset($params['sslCipher'])) {
            $options[\PDO::MYSQL_ATTR_SSL_CIPHER] = $params['sslCipher'];
        }

        $this->pdo = new \PDO($platform . ':host='.$params['host'].';'.$port.'dbname=' . $params['dbname'] . ';charset=' . $params['charset'], $params['user'], $params['password'], $options);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function getEntity($entityType, $id = null)
    {
        if (!$this->hasRepository($entityType)) {
            throw new Error("ORM: Repository '{$entityType}' does not exist.");
        }

        return $this->getRepository($entityType)->get($id);
    }

    public function saveEntity(Entity $entity, array $options = [])
    {
        $entityType = $entity->getEntityType();
        return $this->getRepository($entityType)->save($entity, $options);
    }

    public function removeEntity(Entity $entity, array $options = [])
    {
        $entityType = $entity->getEntityType();
        return $this->getRepository($entityType)->remove($entity, $options);
    }

    public function createEntity($entityType, $data, array $options = [])
    {
        $entity = $this->getEntity($entityType);
        $entity->set($data);
        $this->saveEntity($entity, $options);
        return $entity;
    }

    public function getRepository($entityType)
    {
        if (!$this->hasRepository($entityType)) {
            // TODO Throw error
        }

        if (empty($this->repositoryHash[$entityType])) {
            $this->repositoryHash[$entityType] = $this->repositoryFactory->create($entityType);
        }
        return $this->repositoryHash[$entityType];
    }

    public function setMetadata(array $data)
    {
        $this->metadata->setData($data);
    }

    public function hasRepository($entityType)
    {
        return $this->getMetadata()->has($entityType);
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getOrmMetadata()
    {
        return $this->getMetadata();
    }

    public function getPDO()
    {
        if (empty($this->pdo)) {
            $this->initPDO();
        }
        return $this->pdo;
    }

    public function normalizeRepositoryName($name)
    {
        return $name;
    }

    public function normalizeEntityName($name)
    {
        return $name;
    }

    public function createCollection($entityType, $data = [])
    {
        $collection = new EntityCollection($data, $entityType, $this->entityFactory);
        return $collection;
    }

    public function createSthCollection(string $entityType, array $selectParams = [])
    {
        return new SthCollection($entityType, $this, $selectParams);
    }

    public function getEntityFactory()
    {
        return $this->entityFactory;
    }

    public function runQuery($query, $rerunIfDeadlock = false)
    {
        try {
            return $this->getPDO()->query($query);
        } catch (\Exception $e) {
            if ($rerunIfDeadlock) {
                if ($e->errorInfo[0] == 40001 && $e->errorInfo[1] == 1213) {
                    return $this->getPDO()->query($query);
                } else {
                    throw $e;
                }
            }
        }
    }

    protected function init()
    {
    }
}
