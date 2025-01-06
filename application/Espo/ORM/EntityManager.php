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

namespace Espo\ORM;

use Espo\ORM\Defs\Defs;
use Espo\ORM\Executor\DefaultQueryExecutor;
use Espo\ORM\Executor\DefaultSqlExecutor;
use Espo\ORM\Executor\QueryExecutor;
use Espo\ORM\Executor\SqlExecutor;
use Espo\ORM\QueryComposer\QueryComposer;
use Espo\ORM\QueryComposer\QueryComposerFactory;
use Espo\ORM\QueryComposer\QueryComposerWrapper;
use Espo\ORM\Mapper\Mapper;
use Espo\ORM\Mapper\MapperFactory;
use Espo\ORM\Mapper\BaseMapper;
use Espo\ORM\Relation\RelationsMap;
use Espo\ORM\Repository\RDBRelation;
use Espo\ORM\Repository\RepositoryFactory;
use Espo\ORM\Repository\Repository;
use Espo\ORM\Repository\RDBRepository;
use Espo\ORM\Repository\Util as RepositoryUtil;
use Espo\ORM\Locker\Locker;
use Espo\ORM\Locker\BaseLocker;
use Espo\ORM\Locker\MysqlLocker;
use Espo\ORM\Value\ValueAccessorFactory;
use Espo\ORM\Value\ValueFactoryFactory;
use Espo\ORM\Value\AttributeExtractorFactory;
use Espo\ORM\PDO\PDOProvider;

use PDO;
use RuntimeException;
use stdClass;

/**
 * A central access point to ORM functionality.
 */
class EntityManager
{
    private CollectionFactory $collectionFactory;
    private QueryComposer $queryComposer;
    private QueryExecutor $queryExecutor;
    private QueryBuilder $queryBuilder;
    private SqlExecutor $sqlExecutor;
    private TransactionManager $transactionManager;
    private Locker $locker;

    private const RDB_MAPPER_NAME = 'RDB';

    /** @var array<string, Repository<Entity>> */
    private $repositoryHash = [];
    /** @var array<string, Mapper> */
    private $mappers = [];

    /**
     * @param AttributeExtractorFactory<object> $attributeExtractorFactory
     * @throws RuntimeException
     */
    public function __construct(
        private DatabaseParams $databaseParams,
        private Metadata $metadata,
        private RepositoryFactory $repositoryFactory,
        private EntityFactory $entityFactory,
        private QueryComposerFactory $queryComposerFactory,
        ValueFactoryFactory $valueFactoryFactory,
        AttributeExtractorFactory $attributeExtractorFactory,
        EventDispatcher $eventDispatcher,
        private PDOProvider $pdoProvider,
        private RelationsMap $relationsMap,
        private ?MapperFactory $mapperFactory = null,
        ?QueryExecutor $queryExecutor = null,
        ?SqlExecutor $sqlExecutor = null,
    ) {
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

        $this->sqlExecutor = $sqlExecutor ?? new DefaultSqlExecutor($this->pdoProvider);
        $this->queryExecutor = $queryExecutor ??
            new DefaultQueryExecutor($this->sqlExecutor, $this->getQueryComposer());
        $this->queryBuilder = new QueryBuilder();
        $this->collectionFactory = new CollectionFactory($this);
        $this->transactionManager = new TransactionManager($this->pdoProvider->get(), $this->queryComposer);

        $this->initLocker();
    }

    private function initQueryComposer(): void
    {
        $platform = $this->databaseParams->getPlatform() ?? '';

        $this->queryComposer = $this->queryComposerFactory->create($platform);
    }

    private function initLocker(): void
    {
        $platform = $this->databaseParams->getPlatform() ?? '';

        $className = BaseLocker::class;

        if ($platform === 'Mysql') {
            $className = MysqlLocker::class;
        }

        $this->locker = new $className($this->pdoProvider->get(), $this->queryComposer, $this->transactionManager);
    }

    /**
     * Get the query composer.
     */
    public function getQueryComposer(): QueryComposerWrapper
    {
        return new QueryComposerWrapper($this->queryComposer);
    }

    /**
     * Get the transaction manager.
     */
    public function getTransactionManager(): TransactionManager
    {
        return $this->transactionManager;
    }

    /**
     * Get the locker.
     */
    public function getLocker(): Locker
    {
        return $this->locker;
    }

    /**
     * Get a mapper.
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
            $mapper = new BaseMapper(
                $this->pdoProvider->get(),
                $this->entityFactory,
                $this->collectionFactory,
                $this->metadata,
                $this->queryExecutor
            );

            $this->mappers[$name] = $mapper;

            return;
        }

        if (!$this->mapperFactory) {
            throw new RuntimeException("Could not create mapper '$name'. No mapper factory.");
        }

        $this->mappers[$name] = $this->mapperFactory->create($name);
    }

    /**
     * Get an entity. If $id is null, a new entity instance is created.
     * If an entity with a specified ID does not exist, then NULL is returned.
     * @deprecated As of v9.0. Use getNewEntity and getEntityById instead.
     * @todo Remove in v11.0.
     */
    public function getEntity(string $entityType, ?string $id = null): ?Entity
    {
        if (!$this->hasRepository($entityType)) {
            throw new RuntimeException("ORM: Repository '$entityType' does not exist.");
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
        /**
         * @var Entity
         * @noinspection PhpDeprecationInspection
         */
        return $this->getEntity($entityType);
    }

    /**
     * Get an entity by ID. If an entity does not exist, NULL is returned.
     */
    public function getEntityById(string $entityType, string $id): ?Entity
    {
        /** @noinspection PhpDeprecationInspection */
        return $this->getEntity($entityType, $id);
    }

    /**
     * Store an entity.
     *
     * @param array<string, mixed> $options Options.
     */
    public function saveEntity(Entity $entity, array $options = []): void
    {
        $entityType = $entity->getEntityType();

        $this->getRepository($entityType)->save($entity, $options);
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

        if (!$entity->hasId()) {
            throw new RuntimeException("Can't refresh an entity w/o ID.");
        }

        $fetchedEntity = $this->getEntityById($entity->getEntityType(), $entity->getId());

        if (!$fetchedEntity) {
            throw new RuntimeException("Can't refresh a non-existent entity.");
        }

        $this->relationsMap->get($entity)?->resetAll();

        $prevMap = get_object_vars($entity->getValueMap());
        $fetchedMap = get_object_vars($fetchedEntity->getValueMap());

        foreach (array_keys($prevMap) as $attribute) {
            if (!array_key_exists($attribute, $fetchedMap)) {
                $entity->clear($attribute);
            }
        }

        $entity->set($fetchedMap);
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
        $entity = $this->getNewEntity($entityType);
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
     * @return Repository<Entity>
     */
    public function getRepository(string $entityType): Repository
    {
        if (!$this->hasRepository($entityType)) {
            throw new RuntimeException("Repository '$entityType' does not exist.");
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
            throw new RuntimeException("Repository '$entityType' is not RDB.");
        }

        return $repository;
    }

    /**
     * Get an RDB repository by an entity class name.
     *
     * @template T of Entity
     * @param class-string<T> $className An entity class name.
     * @return RDBRepository<T>
     */
    public function getRDBRepositoryByClass(string $className): RDBRepository
    {
        $entityType = RepositoryUtil::getEntityTypeByClass($className);

        /** @var RDBRepository<T> */
        return $this->getRDBRepository($entityType);
    }

    /**
     * Get a repository by an entity class name.
     *
     * @template T of Entity
     * @param class-string<T> $className An entity class name.
     * @return Repository<T>
     */
    public function getRepositoryByClass(string $className): Repository
    {
        $entityType = RepositoryUtil::getEntityTypeByClass($className);

        /** @var Repository<T> */
        return $this->getRepository($entityType);
    }

    /**
     * Get an access point for a specific relation of a record.
     *
     * @return RDBRelation<Entity>
     * @since 8.4.0
     */
    public function getRelation(Entity $entity, string $relationName): RDBRelation
    {
        return $this->getRDBRepository($entity->getEntityType())->getRelation($entity, $relationName);
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
     * Get the entity factory.
     */
    public function getEntityFactory(): EntityFactory
    {
        return $this->entityFactory;
    }

    /**
     * Get the collection factory.
     */
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

    /**
     * @deprecated As of v7.0. Use `getCollectionFactory`.
     * @param array<string, mixed> $data
     * @return EntityCollection<Entity>
     * @todo Remove in v10.0.
     */
    public function createCollection(?string $entityType = null, array $data = []): EntityCollection
    {
        return $this->collectionFactory->create($entityType, $data);
    }

    /**
     * @deprecated As of v7.0. Use the Query Builder instead. Otherwise, code will be not portable.
     */
    public function getPDO(): PDO
    {
        return $this->pdoProvider->get();
    }
}
