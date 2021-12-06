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

namespace Espo\ORM;

use Espo\ORM\Defs\Defs;

use Espo\ORM\QueryComposer\QueryComposer;

use Espo\ORM\Mapper\Mapper;
use Espo\ORM\Mapper\MapperFactory;

use Espo\ORM\Repository\RepositoryFactory;
use Espo\ORM\Repository\Repository;
use Espo\ORM\Repository\RDBRepository;

use Espo\ORM\Locker\Locker;
use Espo\ORM\Locker\BaseLocker;

use Espo\ORM\Value\ValueAccessorFactory;
use Espo\ORM\Value\ValueFactoryFactory;
use Espo\ORM\Value\AttributeExtractorFactory;

use Espo\ORM\PDO\PDOProvider;

use Espo\ORM\QueryComposer\Part\FunctionConverterFactory;

use PDO;
use RuntimeException;
use stdClass;

/**
 * A central access point to ORM functionality.
 */
class EntityManager
{
    private $entityFactory;

    private $collectionFactory;

    private $repositoryFactory;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    private $mapperFactory = null;

    private $functionConverterFactory = null;

    /** @var array<string, Mapper> */
    private $mappers = [];

    private $metadata;

    /** @var array<string, Repository<Entity>> */
    private $repositoryHash = [];

    /** @var DatabaseParams */
    private $databaseParams;

    /** @var QueryComposer */
    private $queryComposer;

    private $queryExecutor;

    private $queryBuilder;

    private $sqlExecutor;

    private $transactionManager;

    /** @var Locker */
    private $locker;

    private $pdoProvider;

    private const RDB_MAPPER_NAME = 'RDB';

    /**
     * @param AttributeExtractorFactory<object> $attributeExtractorFactory
     * @throws RuntimeException
     */
    public function __construct(
        DatabaseParams $databaseParams,
        Metadata $metadata,
        RepositoryFactory $repositoryFactory,
        EntityFactory $entityFactory,
        ValueFactoryFactory $valueFactoryFactory,
        AttributeExtractorFactory $attributeExtractorFactory,
        EventDispatcher $eventDispatcher,
        PDOProvider $pdoProvider,
        ?MapperFactory $mapperFactory = null,
        ?FunctionConverterFactory $functionConverterFactory = null
    ) {
        $this->databaseParams = $databaseParams;
        $this->metadata = $metadata;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityFactory = $entityFactory;
        $this->repositoryFactory = $repositoryFactory;
        $this->pdoProvider = $pdoProvider;
        $this->mapperFactory = $mapperFactory;
        $this->functionConverterFactory = $functionConverterFactory;

        if (!$this->databaseParams->getPlatform()) {
            throw new RuntimeException("No 'platform' parameter.");
        }

        $valueAccessorFactory = new ValueAccessorFactory(
            $valueFactoryFactory,
            $attributeExtractorFactory,
            $eventDispatcher
        );

        $this->entityFactory->setEntityManager($this);
        $this->entityFactory->setValueAccessorFactory($valueAccessorFactory);

        $this->initQueryComposer();

        $this->sqlExecutor = new SqlExecutor($this->pdoProvider->get());
        $this->queryExecutor = new QueryExecutor($this->sqlExecutor, $this->queryComposer);
        $this->queryBuilder = new QueryBuilder();
        $this->collectionFactory = new CollectionFactory($this);
        $this->transactionManager = new TransactionManager($this->pdoProvider->get(), $this->queryComposer);

        $this->initLocker();
    }

    private function initQueryComposer(): void
    {
        $platform = $this->databaseParams->getPlatform();

        $className = 'Espo\\ORM\\QueryComposer\\' . ucfirst($platform) . 'QueryComposer';

        if (!class_exists($className)) {
            throw new RuntimeException("Query composer for '{$platform}' platform does not exits.");
        }

        $this->queryComposer = new $className(
            $this->pdoProvider->get(),
            $this->entityFactory,
            $this->metadata,
            $this->functionConverterFactory
        );
    }

    private function initLocker(): void
    {
        $platform = $this->databaseParams->getPlatform();

        $className = 'Espo\\ORM\\Locker\\' . ucfirst($platform) . 'Locker';

        if (!class_exists($className)) {
            $className = BaseLocker::class;
        }

        $this->locker = new $className($this->pdoProvider->get(), $this->queryComposer, $this->transactionManager);
    }

    /**
     * @todo Remove in v7.0.
     * @deprecated
     */
    public function getQuery(): QueryComposer
    {
        return $this->queryComposer;
    }

    public function getQueryComposer(): QueryComposer
    {
        return $this->queryComposer;
    }

    public function getTransactionManager(): TransactionManager
    {
        return $this->transactionManager;
    }

    public function getLocker(): Locker
    {
        return $this->locker;
    }

    /**
     * Get a Mapper.
     */
    public function getMapper(string $name = self::RDB_MAPPER_NAME): Mapper
    {
        if (!array_key_exists($name, $this->mappers)) {
            $this->loadMapper($name);
        }

        return $this->mappers[$name];
    }

    private function loadMapper(string $name): void
    {
        if ($name === self::RDB_MAPPER_NAME) {
            $className = $this->getRDBMapperClassName();

            $this->mappers[$name] = new $className(
                $this->pdoProvider->get(),
                $this->entityFactory,
                $this->collectionFactory,
                $this->getQueryComposer(),
                $this->metadata,
                $this->sqlExecutor
            );

            return;
        }

        if (!$this->mapperFactory) {
            throw new RuntimeException("Could not create mapper '{$name}'. No mapper factory.");
        }

        $this->mappers[$name] = $this->mapperFactory->create($name);
    }

    private function getRDBMapperClassName(): string
    {
        $platform = $this->databaseParams->getPlatform();

        $className = 'Espo\\ORM\\Mapper\\' . ucfirst($platform) . 'Mapper';

        if (!class_exists($className)) {
            throw new RuntimeException("Mapper for '{$platform}' does not exist.");
        }

        return $className;
    }

    /**
     * Get an entity. If $id is null, a new entity instance is created.
     * If an entity with a specified ID does not exist, then NULL is returned.
     */
    public function getEntity(string $entityType, ?string $id = null): ?Entity
    {
        if (!$this->hasRepository($entityType)) {
            throw new RuntimeException("ORM: Repository '{$entityType}' does not exist.");
        }

        if ($id === null) {
            return $this->getRepository($entityType)->getNew();
        }

        return $this->getRepository($entityType)->getById($id);
    }

    /**
     * Create a new entity instance (w/o storing to DB).
     */
    public function getNewEntity(string $entityType): Entity
    {
        return $this->getEntity($entityType);
    }

    /**
     * Get an entity by ID. If an entity does not exist, NULL is returned.
     */
    public function getEntityById(string $entityType, string $id): ?Entity
    {
        return $this->getEntity($entityType, $id);
    }

    /**
     * Store an entity.
     *
     * @param array<string, mixed> $options Options.
     * @return void
     *
     * @todo Change return type to void in v7.1.
     */
    public function saveEntity(Entity $entity, array $options = [])
    {
        $entityType = $entity->getEntityType();

        $this->getRepository($entityType)->save($entity, $options);

        /** @phpstan-ignore-next-line */
        return $entity->getId();
    }

    /**
     * Mark an entity as deleted (in database).
     *
     * @param array<string, mixed> $options Options.
     */
    public function removeEntity(Entity $entity, array $options = []): void
    {
        $entityType = $entity->getEntityType();

        $this->getRepository($entityType)->remove($entity, $options);
    }

    /**
     * Refresh an entity from the database, overwriting made changes, if any.
     * Can be used to fetch attributes that were not fetched initially.
     *
     * @throws RuntimeException
     */
    public function refreshEntity(Entity $entity): void
    {
        if ($entity->isNew()) {
            throw new RuntimeException("Can't refresh a new entity.");
        }

        if ($entity->getId() === null) {
            throw new RuntimeException("Can't refresh an entity w/o ID.");
        }

        $fetchedEntity = $this->getEntity($entity->getEntityType(), $entity->getId());

        if (!$fetchedEntity) {
            throw new RuntimeException("Can't refresh a non-existent entity.");
        }

        $entity->set($fetchedEntity->getValueMap());
        $entity->setAsFetched();
    }

    /**
     * Create entity (and store to database).
     *
     * @param stdClass|array<string, mixed> $data Entity attributes.
     * @param array<string, mixed> $options Options.
     */
    public function createEntity(string $entityType, $data = [], array $options = []): Entity
    {
        $entity = $this->getEntity($entityType);

        $entity->set($data);

        $this->saveEntity($entity, $options);

        return $entity;
    }

    /**
     * Check whether a repository for a specific entity type exist.
     */
    public function hasRepository(string $entityType): bool
    {
        return $this->getMetadata()->has($entityType);
    }

    /**
     * Get a repository for a specific entity type.
     *
     * @return RDBRepository<Entity>
     */
    public function getRepository(string $entityType): Repository
    {
        if (!$this->hasRepository($entityType)) {
            throw new RuntimeException("Repository '{$entityType}' does not exist.");
        }

        if (!array_key_exists($entityType, $this->repositoryHash)) {
            $this->repositoryHash[$entityType] = $this->repositoryFactory->create($entityType);
        }

        return $this->repositoryHash[$entityType];
    }

    /**
     * Get an RDB repository for a specific entity type.
     *
     * @return RDBRepository<Entity>
     */
    public function getRDBRepository(string $entityType): RDBRepository
    {
        $repository = $this->getRepository($entityType);

        if (!$repository instanceof RDBRepository) {
            throw new RuntimeException("Repository '{$entityType}' is not RDB.");
        }

        return $repository;
    }

    /**
     * Get metadata definitions.
     */
    public function getDefs(): Defs
    {
        return $this->metadata->getDefs();
    }

    /**
     * Get a query builder.
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * Get metadata.
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * @deprecated
     */
    public function getPDO(): PDO
    {
        return $this->pdoProvider->get();
    }

    /**
     * @deprecated Use `getCollectionFactory`.
     * @param array<string, mixed> $data
     *
     * @return EntityCollection<Entity>
     */
    public function createCollection(?string $entityType = null, array $data = []): EntityCollection
    {
        return $this->collectionFactory->create($entityType, $data);
    }

    public function getEntityFactory(): EntityFactory
    {
        return $this->entityFactory;
    }

    public function getCollectionFactory(): CollectionFactory
    {
        return $this->collectionFactory;
    }

    /**
     * Get a Query Executor.
     */
    public function getQueryExecutor(): QueryExecutor
    {
        return $this->queryExecutor;
    }

    /**
     * Get SQL Executor.
     */
    public function getSqlExecutor(): SqlExecutor
    {
        return $this->sqlExecutor;
    }
}
