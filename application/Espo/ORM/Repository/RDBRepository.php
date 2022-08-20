<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\ORM\EntityManager;
use Espo\ORM\EntityFactory;
use Espo\ORM\Collection;
use Espo\ORM\SthCollection;
use Espo\ORM\BaseEntity;
use Espo\ORM\Entity;
use Espo\ORM\Mapper\RDBMapper;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\Part\Selection;
use Espo\ORM\Query\Part\Join;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Mapper\BaseMapper;

use stdClass;
use RuntimeException;
use PDO;

/**
 * @template TEntity of Entity
 * @implements Repository<TEntity>
 */
class RDBRepository implements Repository
{
    protected string $entityType;

    protected EntityManager $entityManager;

    protected EntityFactory $entityFactory;

    protected HookMediator $hookMediator;

    protected RDBTransactionManager $transactionManager;

    public function __construct(
        string $entityType,
        EntityManager $entityManager,
        EntityFactory $entityFactory,
        ?HookMediator $hookMediator = null
    ) {
        $this->entityType = $entityType;
        $this->entityFactory = $entityFactory;
        $this->entityManager = $entityManager;
        $this->hookMediator = $hookMediator ?? (new EmptyHookMediator());
        $this->transactionManager = new RDBTransactionManager($entityManager->getTransactionManager());
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get a new entity.
     *
     * @return TEntity
     */
    public function getNew(): Entity
    {
        $entity = $this->entityFactory->create($this->entityType);

        if ($entity instanceof BaseEntity) {
            $entity->populateDefaults();
        }

        /** @var TEntity */
        return $entity;
    }

    /**
     * Fetch an entity by ID.
     *
     * @return ?TEntity
     */
    public function getById(string $id): ?Entity
    {
        $selectQuery = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from($this->entityType)
            ->where([
                'id' => $id,
            ])
            ->build();

        /** @var ?TEntity $entity */
        $entity = $this->getMapper()->selectOne($selectQuery);

        return $entity;
    }

    /**
     * Get an entity. If ID is NULL, a new entity is returned.
     *
     * @return ?TEntity
     *
     * @deprecated Use `getById` and `getNew`.
     */
    public function get(?string $id = null): ?Entity
    {
        if (is_null($id)) {
            return $this->getNew();
        }

        return $this->getById($id);
    }

    protected function processCheckEntity(Entity $entity): void
    {
        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("An entity type doesn't match the repository.");
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string,mixed> $options
     */
    public function save(Entity $entity, array $options = []): void
    {
        $this->processCheckEntity($entity);

        if ($entity instanceof BaseEntity) {
            $entity->setAsBeingSaved();
        }

        if (empty($options['skipBeforeSave']) && empty($options['skipAll'])) {
            $this->beforeSave($entity, $options);
        }

        $isSaved = false;

        if ($entity instanceof BaseEntity) {
            $isSaved = $entity->isSaved();
        }

        if ($entity->isNew() && !$isSaved) {
            $this->getMapper()->insert($entity);
        }
        else {
            $this->getMapper()->update($entity);
        }

        if ($entity instanceof BaseEntity) {
            $entity->setAsSaved();
        }

        if (empty($options['skipAfterSave']) && empty($options['skipAll'])) {
            $this->afterSave($entity, $options);
        }

        if ($entity->isNew()) {
            if (empty($options['keepNew'])) {
                $entity->setAsNotNew();

                $entity->updateFetchedValues();
            }
        }
        else {
            $entity->updateFetchedValues();
        }

        if ($entity instanceof BaseEntity) {
            $entity->setAsNotBeingSaved();
        }
    }

    /**
     * Restore a record flagged as deleted.
     */
    public function restoreDeleted(string $id): void
    {
        $mapper = $this->getMapper();

        if (!$mapper instanceof BaseMapper) {
            throw new RuntimeException("Not supported 'restoreDeleted'.");
        }

        $mapper->restoreDeleted($this->entityType, $id);
    }

    /**
     * Get an access point for a specific relation of a record.
     *
     * @param TEntity $entity
     */
    public function getRelation(Entity $entity, string $relationName): RDBRelation
    {
        return new RDBRelation($this->entityManager, $entity, $relationName, $this->hookMediator);
    }

    /**
     * Remove a record (mark as deleted).
     */
    public function remove(Entity $entity, array $options = []): void
    {
        $this->processCheckEntity($entity);
        $this->beforeRemove($entity, $options);
        $this->getMapper()->delete($entity);
        $this->afterRemove($entity, $options);
    }

    /**
     * @deprecated Use QueryBuilder instead.
     */
    public function deleteFromDb(string $id, bool $onlyDeleted = false): void
    {
        $mapper = $this->getMapper();

        if (!$mapper instanceof BaseMapper) {
            throw new RuntimeException("Not supported 'deleteFromDb'.");
        }

        $mapper->deleteFromDb($this->entityType, $id, $onlyDeleted);
    }

    /**
     * Find records.
     *
     * @param ?array<string,mixed> $params @deprecated
     * @return Collection<TEntity>
     */
    public function find(?array $params = []): Collection
    {
        return $this->createSelectBuilder()->find($params);
    }

    /**
     * Find one record.
     *
     * @param ?array<string,mixed> $params @deprecated
     */
    public function findOne(?array $params = []): ?Entity
    {
        $collection = $this->limit(0, 1)->find($params);

        foreach ($collection as $entity) {
            return $entity;
        }

        return null;
    }

    /**
     * Find records by a SQL query.
     *
     * @return SthCollection<TEntity>
     */
    public function findBySql(string $sql): SthCollection
    {
        $mapper = $this->getMapper();

        if (!$mapper instanceof BaseMapper) {
            throw new RuntimeException("Not supported 'findBySql'.");
        }

        /** @var SthCollection<TEntity> */
        return $mapper->selectBySql($this->entityType, $sql);
    }

    /**
     * @deprecated
     * @param ?array<string,mixed> $params
     * @return Collection<TEntity>|TEntity|null
     */
    public function findRelated(Entity $entity, string $relationName, ?array $params = null)
    {
        $params = $params ?? [];

        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("Not supported entity type.");
        }

        if (!$entity->hasId()) {
            return null;
        }

        $type = $entity->getRelationType($relationName);
        /** @phpstan-ignore-next-line */
        $entityType = $entity->getRelationParam($relationName, 'entity');

        $additionalColumns = $params['additionalColumns'] ?? [];
        unset($params['additionalColumns']);

        $additionalColumnsConditions = $params['additionalColumnsConditions'] ?? [];
        unset($params['additionalColumnsConditions']);

        $select = null;

        if ($entityType) {
            $params['from'] = $entityType;
            $select = Select::fromRaw($params);
        }

        if ($type === Entity::MANY_MANY && count($additionalColumns)) {
            if ($select === null) {
                throw new RuntimeException();
            }

            $select = $this->applyRelationAdditionalColumns($entity, $relationName, $additionalColumns, $select);
        }

        // @todo Get rid of 'additionalColumnsConditions' usage. Use 'whereClause' instead.
        if ($type === Entity::MANY_MANY && count($additionalColumnsConditions)) {
            if ($select === null) {
                throw new RuntimeException();
            }

            $select = $this->applyRelationAdditionalColumnsConditions(
                $entity,
                $relationName,
                $additionalColumnsConditions,
                $select
            );
        }

        /** @var Collection<TEntity>|TEntity|null $result */
        $result = $this->getMapper()->selectRelated($entity, $relationName, $select);

        if ($result instanceof SthCollection) {
            /** @var SthCollection<TEntity> */
            return $this->entityManager->getCollectionFactory()->createFromSthCollection($result);
        }

        return $result;
    }

    /**
     * @deprecated
     *
     * @param ?array<string,mixed> $params
     */
    public function countRelated(Entity $entity, string $relationName, ?array $params = null): int
    {
        $params = $params ?? [];

        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("Not supported entity type.");
        }

        if (!$entity->hasId()) {
            return 0;
        }

        $type = $entity->getRelationType($relationName);
        /** @phpstan-ignore-next-line */
        $entityType = $entity->getRelationParam($relationName, 'entity');

        $additionalColumnsConditions = $params['additionalColumnsConditions'] ?? [];
        unset($params['additionalColumnsConditions']);

        $select = null;

        if ($entityType) {
            $params['from'] = $entityType;

            $select = Select::fromRaw($params);
        }

        if ($type === Entity::MANY_MANY && count($additionalColumnsConditions)) {
            if ($select === null) {
                throw new RuntimeException();
            }

            $select = $this->applyRelationAdditionalColumnsConditions(
                $entity,
                $relationName,
                $additionalColumnsConditions,
                $select
            );
        }

        return (int) $this->getMapper()->countRelated($entity, $relationName, $select);
    }

    /**
     * @param string[] $columns
     */
    protected function applyRelationAdditionalColumns(
        Entity $entity,
        string $relationName,
        array $columns,
        Select $select
    ): Select {

        if (empty($columns)) {
            return $select;
        }

        /** @phpstan-ignore-next-line */
        $middleName = lcfirst($entity->getRelationParam($relationName, 'relationName'));

        $selectItemList = $select->getSelect();

        if ($selectItemList === []) {
            $selectItemList[] = '*';
        }

        foreach ($columns as $column => $alias) {
            $selectItemList[] = [
                $middleName . '.' . $column,
                $alias
            ];
        }

        return $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->clone($select)
            ->select($selectItemList)
            ->build();
    }

    /**
     * @param array<string,mixed> $conditions
     */
    protected function applyRelationAdditionalColumnsConditions(
        Entity $entity,
        string $relationName,
        array $conditions,
        Select $select
    ): Select {

        if (empty($conditions)) {
            return $select;
        }

        /** @phpstan-ignore-next-line */
        $middleName = lcfirst($entity->getRelationParam($relationName, 'relationName'));

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->clone($select);

        foreach ($conditions as $column => $value) {
            $builder->where(
                $middleName . '.' . $column,
                $value
            );
        }

        return $builder->build();
    }

    /**
     * @deprecated
     * @param TEntity|string $foreign
     */
    public function isRelated(Entity $entity, string $relationName, $foreign): bool
    {
        if (!$entity->hasId()) {
            return false;
        }

        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("Not supported entity type.");
        }

        /** @var mixed $foreign */

        if ($foreign instanceof Entity) {
            if (!$foreign->hasId()) {
                return false;
            }

            $id = $foreign->getId();
        }
        else if (is_string($foreign)) {
            $id = $foreign;
        }
        else {
            throw new RuntimeException("Bad 'foreign' value.");
        }

        if (!$id) {
            return false;
        }

        if (in_array($entity->getRelationType($relationName), [Entity::BELONGS_TO, Entity::BELONGS_TO_PARENT])) {
            if (!$entity->has($relationName . 'Id')) {
                $entity = $this->getById($entity->getId());
            }
        }

        /** @phpstan-var TEntity $entity */

        $relation = $this->getRelation($entity, $relationName);

        if ($foreign instanceof Entity) {
            return $relation->isRelated($foreign);
        }

        return (bool) $this->countRelated($entity, $relationName, [
            'whereClause' => [
                'id' => $id,
            ],
        ]);
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    public function relate(Entity $entity, string $relationName, $foreign, $columnData = null, array $options = [])
    {
        if (!$entity->hasId()) {
            throw new RuntimeException("Can't relate an entity w/o ID.");
        }

        if (!$foreign instanceof Entity && !is_string($foreign)) {
            throw new RuntimeException("Bad 'foreign' value.");
        }

        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("Not supported entity type.");
        }

        $this->beforeRelate($entity, $relationName, $foreign, $columnData, $options);

        $beforeMethodName = 'beforeRelate' . ucfirst($relationName);

        if (method_exists($this, $beforeMethodName)) {
            $this->$beforeMethodName($entity, $foreign, $columnData, $options);
        }

        $result = false;

        $methodName = 'relate' . ucfirst($relationName);

        if (method_exists($this, $methodName)) {
            $result = $this->$methodName($entity, $foreign, $columnData, $options);
        }
        else {
            $data = $columnData;

            if ($columnData instanceof stdClass) {
                $data = get_object_vars($columnData);
            }

            if ($foreign instanceof Entity) {
                $result = $this->getMapper()->relate($entity, $relationName, $foreign, $data);
            }
            else {
                $id = $foreign;

                $result = $this->getMapper()->relateById($entity, $relationName, $id, $data);
            }
        }

        if ($result) {
            $this->afterRelate($entity, $relationName, $foreign, $columnData, $options);

            $afterMethodName = 'afterRelate' . ucfirst($relationName);

            if (method_exists($this, $afterMethodName)) {
                $this->$afterMethodName($entity, $foreign, $columnData, $options);
            }
        }

        return $result;
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    public function unrelate(Entity $entity, string $relationName, $foreign, array $options = [])
    {
        if (!$entity->hasId()) {
            throw new RuntimeException("Can't unrelate an entity w/o ID.");
        }

        if (!$foreign instanceof Entity && !is_string($foreign)) {
            throw new RuntimeException("Bad foreign value.");
        }

        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("Not supported entity type.");
        }

        $this->beforeUnrelate($entity, $relationName, $foreign, $options);

        $beforeMethodName = 'beforeUnrelate' . ucfirst($relationName);

        if (method_exists($this, $beforeMethodName)) {
            $this->$beforeMethodName($entity, $foreign, $options);
        }

        $result = false;

        $methodName = 'unrelate' . ucfirst($relationName);

        if (method_exists($this, $methodName)) {
            $this->$methodName($entity, $foreign);
        }
        else {
            if ($foreign instanceof Entity) {
                $this->getMapper()->unrelate($entity, $relationName, $foreign);
            }
            else {
                $id = $foreign;

                $this->getMapper()->unrelateById($entity, $relationName, $id);
            }
        }

        $this->afterUnrelate($entity, $relationName, $foreign, $options);

        $afterMethodName = 'afterUnrelate' . ucfirst($relationName);

        if (method_exists($this, $afterMethodName)) {
            $this->$afterMethodName($entity, $foreign, $options);
        }

        return $result;
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    public function getRelationColumn(Entity $entity, string $relationName, string $foreignId, string $column)
    {
        return $this->getMapper()->getRelationColumn($entity, $relationName, $foreignId, $column);
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    protected function beforeRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    protected function afterRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    protected function beforeUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    protected function beforeMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    protected function afterMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    public function updateRelation(Entity $entity, string $relationName, $foreign, $columnData)
    {
        if (!$entity->hasId()) {
            throw new RuntimeException("Can't update a relation for an entity w/o ID.");
        }

        if (!$foreign instanceof Entity && !is_string($foreign)) {
            throw new RuntimeException("Bad foreign value.");
        }

        if ($columnData instanceof stdClass) {
            $columnData = get_object_vars($columnData);
        }

        if ($foreign instanceof Entity) {
            $id = $foreign->getId();
        } else {
            $id = $foreign;
        }

        if (!is_string($id)) {
            throw new RuntimeException("Bad foreign value.");
        }

        $this->getMapper()->updateRelationColumns($entity, $relationName, $id, $columnData);

        return true;
    }

    /**
     * @deprecated
     * @phpstan-ignore-next-line
     */
    public function massRelate(Entity $entity, string $relationName, array $params = [], array $options = [])
    {
        if (!$entity->hasId()) {
            throw new RuntimeException("Can't related an entity w/o ID.");
        }

        $this->beforeMassRelate($entity, $relationName, $params, $options);

        $select = Select::fromRaw($params);

        $this->getMapper()->massRelate($entity, $relationName, $select);

        $this->afterMassRelate($entity, $relationName, $params, $options);
    }

    /**
     * @param array<string,mixed> $params @deprecated Omit it.
     */
    public function count(array $params = []): int
    {
        return $this->createSelectBuilder()->count($params);
    }

    /**
     * Get a max value.
     *
     * @return int|float
     */
    public function max(string $attribute)
    {
        return $this->createSelectBuilder()->max($attribute);
    }

    /**
     * Get a min value.
     *
     * @return int|float
     */
    public function min(string $attribute)
    {
        return $this->createSelectBuilder()->min($attribute);
    }

    /**
     * Get a sum value.
     *
     * @return int|float
     */
    public function sum(string $attribute)
    {
        return $this->createSelectBuilder()->sum($attribute);
    }

    /**
     * Clone an existing query for a further modification and usage by 'find' or 'count' methods.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function clone(Select $query): RDBSelectBuilder
    {
        if ($this->entityType !== $query->getFrom()) {
            throw new RuntimeException("Can't clone a query of a different entity type.");
        }

        /** @var RDBSelectBuilder<TEntity> $builder */
        $builder = new RDBSelectBuilder($this->entityManager, $this->entityType, $query);

        return $builder;
    }

    /**
     * Add JOIN.
     *
     * @param Join|string $target
     * A relation name or table. A relation name should be in camelCase, a table in CamelCase.
     * @param string|null $alias An alias.
     * @param WhereItem|array<scalar,mixed>|null $conditions Join conditions.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function join($target, ?string $alias = null, $conditions = null): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->join($target, $alias, $conditions);
    }

    /**
     * Add LEFT JOIN.
     *
     * @param Join|string $target
     * A relation name or table. A relation name should be in camelCase, a table in CamelCase.
     * @param string|null $alias An alias.
     * @param WhereItem|array<scalar,mixed>|null $conditions Join conditions.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function leftJoin($target, ?string $alias = null, $conditions = null): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->leftJoin($target, $alias, $conditions);
    }

    /**
     * Set DISTINCT parameter.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function distinct(): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->distinct();
    }

    /**
     * Lock selected rows. To be used within a transaction.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function forUpdate(): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->forUpdate();
    }

    /**
     * Set to return STH collection. Recommended fetching large number of records.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function sth(): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->sth();
    }

    /**
     * Add a WHERE clause.
     *
     * Usage options:
     * * `where(WhereItem $clause)`
     * * `where(array $clause)`
     * * `where(string $key, string $value)`
     *
     * @param WhereItem|array<scalar,mixed>|string $clause A key or where clause.
     * @param mixed[]|scalar|null $value A value. Should be omitted if the first argument is not string.
     * @return RDBSelectBuilder<TEntity>
     */
    public function where($clause = [], $value = null): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->where($clause, $value);
    }

    /**
     * Add a HAVING clause.
     *
     * Usage options:
     * * `having(WhereItem $clause)`
     * * `having(array $clause)`
     * * `having(string $key, string $value)`
     *
     * @param WhereItem|array<scalar,mixed>|string $clause A key or where clause.
     * @param mixed[]|scalar|null $value A value. Should be omitted if the first argument is not string.
     * @return RDBSelectBuilder<TEntity>
     */
    public function having($clause = [], $value = null): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->having($clause, $value);
    }

    /**
     * Apply ORDER. Passing an array will override previously set items.
     * Passing non-array will append an item,
     *
     * Usage options:
     * * `order(Order $expression)
     * * `order([$expr1, $expr2, ...])
     * * `order(string $expression, string $direction)
     *
     * @param Order|Order[]|Expression|string $orderBy
     * An attribute to order by or an array or order items.
     * Passing an array will reset a previously set order.
     * @param string|bool|null $direction Select::ORDER_ASC|Select::ORDER_DESC.
     *
     * @phpstan-param Order|Order[]|Expression|string|array<int, string[]>|string[] $orderBy
     * @return RDBSelectBuilder<TEntity>
     */
    public function order($orderBy = 'id', $direction = null): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->order($orderBy, $direction);
    }

    /**
     * Apply OFFSET and LIMIT.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    public function limit(?int $offset = null, ?int $limit = null): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->limit($offset, $limit);
    }

    /**
     * Specify SELECT. Columns and expressions to be selected. If not called, then
     * all entity attributes will be selected. Passing an array will reset
     * previously set items. Passing a SelectExpression|Expression|string will append the item.
     *
     * Usage options:
     * * `select(SelectExpression $expression)`
     * * `select([$expr1, $expr2, ...])`
     * * `select(string $expression, string $alias)`
     *
     * @param Selection|Selection[]|Expression|Expression[]|string[]|string|array<int, string[]|string> $select
     * An array of expressions or one expression.
     * @param string|null $alias An alias. Actual if the first parameter is not an array.
     * @return RDBSelectBuilder<TEntity>
     */
    public function select($select = [], ?string $alias = null): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->select($select, $alias);
    }

    /**
     * Specify GROUP BY.
     * Passing an array will reset previously set items.
     * Passing a string|Expression will append an item.
     *
     * Usage options:
     * * `groupBy(Expression|string $expression)`
     * * `groupBy([$expr1, $expr2, ...])`
     *
     * @param Expression|Expression[]|string|string[] $groupBy
     * @return RDBSelectBuilder<TEntity>
     */
    public function group($groupBy): RDBSelectBuilder
    {
        return $this->createSelectBuilder()->group($groupBy);
    }

    /**
     * @deprecated Use `group` method.
     * @param Expression|Expression[]|string|string[] $groupBy
     * @return RDBSelectBuilder<TEntity>
     */
    public function groupBy($groupBy): RDBSelectBuilder
    {
        return $this->group($groupBy);
    }

    /**
     * @deprecated
     */
    protected function getPDO(): PDO
    {
        return $this->entityManager->getPDO();
    }

    /**
     * Create a select builder.
     *
     * @return RDBSelectBuilder<TEntity>
     */
    protected function createSelectBuilder(): RDBSelectBuilder
    {
        /** @var RDBSelectBuilder<TEntity> $builder */
        $builder = new RDBSelectBuilder($this->entityManager, $this->entityType);

        return $builder;
    }

    /**
     * @param array<string,mixed> $options
     * @return void
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        $this->hookMediator->beforeSave($entity, $options);
    }

    /**
     * @param array<string,mixed> $options
     * @return void
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        $this->hookMediator->afterSave($entity, $options);
    }

    /**
     * @param array<string,mixed> $options
     * @return void
     */
    protected function beforeRemove(Entity $entity, array $options = [])
    {
        $this->hookMediator->beforeRemove($entity, $options);
    }

    /**
     * @param array<string,mixed> $options
     * @return void
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        $this->hookMediator->afterRemove($entity, $options);
    }

    protected function getMapper(): RDBMapper
    {
        /** @var RDBMapper $mapper */
        $mapper = $this->entityManager->getMapper();

        return $mapper;
    }

    /**
     * @deprecated Use `$this->entityManager`.
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }
}
