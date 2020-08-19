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

use Espo\ORM\{
    Mapper\Mapper,
    QueryComposer\QueryComposer,
    Repository\RepositoryFactory,
    Repository\Repository,
};

use PDO;
use PDOStatement;
use Exception;
use RuntimeException;

/**
 * A central access point to ORM functionality.
 */
class EntityManager
{
    protected $pdo;

    protected $entityFactory;

    protected $collectionFactory;

    protected $repositoryFactory;

    protected $mappers = [];

    protected $metadata;

    protected $repositoryHash = [];

    protected $params = [];

    protected $queryComposer;

    protected $queryExecutor;

    protected $sqlExecutor;

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
                throw new Exception('No database driver specified.');
            }
            $driver = $this->params['driver'];
            if (empty($this->driverPlatformMap[$driver])) {
                throw new Exception("Database driver '{$driver}' is not supported.");
            }
            $this->params['platform'] = $this->driverPlatformMap[$this->params['driver']];
        }

        if (!empty($params['metadata'])) {
            $this->setMetadata($params['metadata']);
        }

        $this->entityFactory = $entityFactory;
        $this->entityFactory->setEntityManager($this);

        $this->repositoryFactory = $repositoryFactory;

        $this->initQueryComposer();

        $this->sqlExecutor = new SqlExecutor($this->getPDO());

        $this->queryExecutor = new QueryExecutor($this->sqlExecutor, $this->queryComposer);

        $this->queryBuilder = new QueryBuilder();

        $this->collectionFactory = new CollectionFactory($this);
    }

    protected function initQueryComposer()
    {
        $className = $this->params['queryComposerClassName'] ?? null;

        if (!$className) {
            $platform = $this->params['platform'];
            $className = 'Espo\\ORM\\QueryComposer\\' . ucfirst($platform) . 'QueryComposer';
        }

        if (!$className || !class_exists($className)) {
            throw new RuntimeException("Query composer {$name} could not be created.");
        }

        $this->queryComposer = new $className($this->getPDO(), $this->entityFactory, $this->metadata);
    }

    /**
     * @todo Remove in v7.0.
     * @deprecated
     */
    public function getQuery() : QueryComposer
    {
        return $this->queryComposer;
    }

    public function getQueryComposer() : QueryComposer
    {
        return $this->queryComposer;
    }

    protected function getMapperClassName(string $name) : string
    {
        $className = null;

        switch ($name) {
            case 'RDB':
                $platform = $this->params['platform'];
                $className = 'Espo\\ORM\\Mapper\\' . ucfirst($platform) . 'Mapper';
                break;
        }

        if (!$className || !class_exists($className)) {
            throw new RuntimeException("Mapper '{$name}' does not exist.");
        }

        return $className;
    }

    /**
     * Get a Mapper.
     */
    public function getMapper(?string $name = null) : Mapper
    {
        $name = $name ?? $this->defaultMapperName;

        $className = $this->getMapperClassName($name);

        if (empty($this->mappers[$className])) {
            $this->mappers[$className] = new $className(
                $this->getPDO(),
                $this->entityFactory,
                $this->collectionFactory,
                $this->getQueryComposer(),
                $this->metadata,
                $this->sqlExecutor
            );
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
            $options[PDO::MYSQL_ATTR_SSL_CA] = $params['sslCA'];
        }
        if (isset($params['sslCert'])) {
            $options[PDO::MYSQL_ATTR_SSL_CERT] = $params['sslCert'];
        }
        if (isset($params['sslKey'])) {
            $options[PDO::MYSQL_ATTR_SSL_KEY] = $params['sslKey'];
        }
        if (isset($params['sslCAPath'])) {
            $options[PDO::MYSQL_ATTR_SSL_CAPATH] = $params['sslCAPath'];
        }
        if (isset($params['sslCipher'])) {
            $options[PDO::MYSQL_ATTR_SSL_CIPHER] = $params['sslCipher'];
        }

        $this->pdo = new PDO(
            $platform . ':host=' . $params['host'] . ';'. $port.'dbname=' . $params['dbname'] . ';charset=' . $params['charset'],
            $params['user'], $params['password'], $options
        );

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Get an entity. If $id is null, a new entity instance is created.
     * If an entity with a specified $id does not exist, then NULL is returned.
     */
    public function getEntity(string $entityType, ?string $id = null) : ?Entity
    {
        if (!$this->hasRepository($entityType)) {
            throw new RuntimeException("ORM: Repository '{$entityType}' does not exist.");
        }

        return $this->getRepository($entityType)->get($id);
    }

    /**
     * Store an entity (in database).
     */
    public function saveEntity(Entity $entity, array $options = [])
    {
        $entityType = $entity->getEntityType();

        $this->getRepository($entityType)->save($entity, $options);
    }

    /**
     * Mark an entity as deleted (in database).
     */
    public function removeEntity(Entity $entity, array $options = [])
    {
        $entityType = $entity->getEntityType();

        $this->getRepository($entityType)->remove($entity, $options);
    }

    /**
     * Create entity (store it in a database).
     *
     * @param StdClass|array $data Entity attributes.
     */
    public function createEntity(string $entityType, $data, array $options = []) : Entity
    {
        $entity = $this->getEntity($entityType);
        $entity->set($data);

        $this->saveEntity($entity, $options);

        return $entity;
    }

    /**
     * Fetch an entity (from a database).
     */
    public function fetchEntity(string $entityType, string $id) : ?Entity
    {
        if (empty($id)) {
            return null;
        }

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
            throw new RuntimeException("Repository '{$entityType}' does not exist.");
        }

        if (empty($this->repositoryHash[$entityType])) {
            $this->repositoryHash[$entityType] = $this->repositoryFactory->create($entityType);
        }

        return $this->repositoryHash[$entityType] ?? null;
    }

    /**
     * Get a query builder.
     */
    public function getQueryBuilder() : QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @deprecated
     * @todo Remove.
     */
    public function setMetadata(array $data)
    {
        $this->metadata->setData($data);
    }

    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Get a PDO instance.
     */
    public function getPDO() : PDO
    {
        if (empty($this->pdo)) {
            $this->initPDO();
        }

        return $this->pdo;
    }

    /**
     * Create a collection. An entity type can be omitted.
     */
    public function createCollection(?string $entityType = null, array $data = []) : EntityCollection
    {
        return $this->collectionFactory->create($entityType, $data);
    }

    public function getEntityFactory() : EntityFactory
    {
        return $this->entityFactory;
    }

    public function getCollectionFactory() : CollectionFactory
    {
        return $this->collectionFactory;
    }

    /**
     * Get a Query Executor.
     */
    public function getQueryExecutor() : QueryExecutor
    {
        return $this->queryExecutor;
    }

    /**
     * Get SQL Executor.
     */
    public function getSqlExecutor() : SqlExecutor
    {
        return $this->sqlExecutor;
    }
}
