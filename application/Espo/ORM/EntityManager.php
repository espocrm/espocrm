<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\Error;

use Espo\ORM\DB\{
    IMapper,
    Query\Base as Query,
};


/**
 * A central access point to ORM functionality.
 */
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

    protected $defaultMapperName = 'RDB';

    protected $driverPlatformMap = [
        'pdo_mysql' => 'Mysql',
        'mysqli' => 'Mysql',
    ];

    public function __construct(array $params, RepositoryFactory $repositoryFactory, EntityFactory $entityFactory)
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

        $this->entityFactory = $entityFactory;
        $this->entityFactory->setEntityManager($this);

        $this->repositoryFactory = $repositoryFactory;

        $this->init();
    }

    /**
     * Get Query.
     */
    public function getQuery() : Query
    {
        if (empty($this->query)) {
            $platform = $this->params['platform'];
            $className = 'Espo\\ORM\\DB\\Query\\' . ucfirst($platform);
            $this->query = new $className($this->getPDO(), $this->entityFactory, $this->metadata);
        }
        return $this->query;
    }

    protected function getMapperClassName(string $name)
    {
        $className = null;

        switch ($name) {
            case 'RDB':
                $platform = $this->params['platform'];
                $className = 'Espo\\ORM\\DB\\' . ucfirst($platform) . 'Mapper';
                break;
        }

        if (!class_exists($className)) {
            throw new Error("Mapper {$name} does not exist.");
        }

        return $className;
    }

    /**
     * Get Mapper.
     */
    public function getMapper(?string $name = null) : IMapper
    {
        $name = $name ?? $this->defaultMapperName;

        if ($name{0} == '\\') {
            $className = $name;
        } else {
            $className = $this->getMapperClassName($name);
        }

        if (empty($this->mappers[$className])) {
            $this->mappers[$className] = new $className(
                $this->getPDO(), $this->entityFactory, $this->getQuery(), $this->metadata);
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

        $this->pdo = new \PDO(
            $platform . ':host='.$params['host'].';'.$port.'dbname=' . $params['dbname'] . ';charset=' . $params['charset'],
            $params['user'], $params['password'], $options
        );
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Get entity. If $id is null, a new entity instance is created.
     * If entity with a specified $id does not exist, then NULL is returned.
     */
    public function getEntity(string $entityType, ?string $id = null) : ?Entity
    {
        if (!$this->hasRepository($entityType)) {
            throw new Error("ORM: Repository '{$entityType}' does not exist.");
        }

        return $this->getRepository($entityType)->get($id);
    }

    /**
     * Store entity (in database).
     */
    public function saveEntity(Entity $entity, array $options = [])
    {
        $entityType = $entity->getEntityType();
        return $this->getRepository($entityType)->save($entity, $options);
    }

    /**
     * Mark entity as deleted (in database).
     */
    public function removeEntity(Entity $entity, array $options = [])
    {
        $entityType = $entity->getEntityType();
        return $this->getRepository($entityType)->remove($entity, $options);
    }

    /**
     * Create entity (store it in database).
     *
     * @param \StdClass|array $data Entity attributes.
     */
    public function createEntity(string $entityType, $data, array $options = []) : Entity
    {
        $entity = $this->getEntity($entityType);
        $entity->set($data);
        $this->saveEntity($entity, $options);
        return $entity;
    }

    /**
     * Fetch entity (from database).
     */
    public function fetchEntity(string $entityType, string $id) : ?Entity
    {
        if (empty($id)) return null;
        return $this->getEntity($entityType, $id);
    }

    /**
     * Check whether a repository for a specific entity type exist.
     */
    public function hasRepository(string $entityType) : bool
    {
        return $this->getMetadata()->has($entityType);
    }

    /**
     * Get a repository for a specific entity type.
     */
    public function getRepository(string $entityType) : ?Repository
    {
        if (!$this->hasRepository($entityType)) {
            // TODO Throw error
        }

        if (empty($this->repositoryHash[$entityType])) {
            $this->repositoryHash[$entityType] = $this->repositoryFactory->create($entityType);
        }
        return $this->repositoryHash[$entityType] ?? null;
    }

    public function setMetadata(array $data)
    {
        $this->metadata->setData($data);
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    public function getOrmMetadata()
    {
        return $this->getMetadata();
    }

    /**
     * Get an instance of PDO.
     */
    public function getPDO() : \PDO
    {
        if (empty($this->pdo)) {
            $this->initPDO();
        }
        return $this->pdo;
    }

    /**
     * Create a Collection.
     * Entity type can be omitted.
     */
    public function createCollection(?string $entityType = null, array $data = [])
    {
        $collection = new EntityCollection($data, $entityType, $this->entityFactory);
        return $collection;
    }

    /**
     * Create an Sth Collection. Sth collection is preferable when a select query returns a large number of rows.
     */
    public function createSthCollection(string $entityType, array $selectParams = [])
    {
        return new SthCollection($entityType, $this, $selectParams);
    }

    public function getEntityFactory() : object
    {
        return $this->entityFactory;
    }

    /**
     * Run a query. Returns a result.
     *
     * @param $rerunIfDeadlock Query will be re-run if a deadlock occurs.
     */
    public function runQuery(string $query, bool $rerunIfDeadlock = false)
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
