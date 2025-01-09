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

namespace Espo\ORM\Repository;

use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\ORM\BaseEntity;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\Part\Selection;
use Espo\ORM\Query\Part\Join;
use Espo\ORM\Mapper\RDBMapper;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Repository\RDBRelationSelectBuilder as Builder;

use Espo\ORM\SthCollection;
use LogicException;
use RuntimeException;

/**
 * An access point for a specific relation of a record.
 *
 * @template TEntity of Entity
 */
class RDBRelation
{
    private string $entityType;
    private ?string $foreignEntityType = null;
    private string $relationName;
    private ?string $relationType = null;
    private bool $noBuilder = false;

    public function __construct(
        private EntityManager $entityManager,
        private Entity $entity,
        string $relationName,
        private HookMediator $hookMediator
    ) {

        if (!$entity->hasId()) {
            throw new RuntimeException("Can't use an entity w/o ID.");
        }

        if (!$entity->hasRelation($relationName)) {
            throw new RuntimeException("Entity does not have a relation '$relationName'.");
        }

        $this->relationName = $relationName;
        $this->relationType = $entity->getRelationType($relationName);
        $this->entityType = $entity->getEntityType();

        if ($entity instanceof BaseEntity) {
            $this->foreignEntityType = $entity->getRelationParam($relationName, RelationParam::ENTITY);
        } else {
            $this->foreignEntityType = $this->entityManager
                ->getDefs()
                ->getEntity($this->entityType)
                ->getRelation($relationName)
                ->getForeignEntityType();
        }

        if ($this->isBelongsToParentType()) {
            $this->noBuilder = true;
        }
    }

    /**
     * Create a select builder.
     *
     * @return Builder<TEntity>
     */
    private function createSelectBuilder(?Select $query = null): Builder
    {
        if ($this->noBuilder) {
            throw new RuntimeException("Can't use query builder for the '$this->relationType' relation type.");
        }

        /** @var Builder<TEntity> */
        return new Builder($this->entityManager, $this->entity, $this->relationName, $query);
    }

    /**
     * Clone a query.
     *
     * @return Builder<TEntity>
     */
    public function clone(Select $query): Builder
    {
        if ($this->noBuilder) {
            throw new RuntimeException("Can't use clone for the '$this->relationType' relation type.");
        }

        if ($query->getFrom() !== $this->foreignEntityType) {
            throw new RuntimeException("Passed query doesn't match the entity type.");
        }

        /** @var Builder<TEntity> */
        return $this->createSelectBuilder($query);
    }

    private function isBelongsToParentType(): bool
    {
        return $this->relationType === Entity::BELONGS_TO_PARENT;
    }

    private function getMapper(): RDBMapper
    {
        $mapper = $this->entityManager->getMapper();

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!$mapper instanceof RDBMapper) {
            throw new LogicException();
        }

        return $mapper;
    }

    /**
     * Find related records.
     *
     * @return EntityCollection<TEntity>|SthCollection<TEntity>
     */
    public function find(): EntityCollection|SthCollection
    {
        if ($this->isBelongsToParentType()) {
            /** @var EntityCollection<TEntity> $collection */
            $collection = $this->entityManager->getCollectionFactory()->create();

            $entity = $this->getMapper()->selectRelated($this->entity, $this->relationName);

            if ($entity) {
                $collection[] = $entity;
            }

            $collection->setAsFetched();

            return $collection;
        }

        return $this->createSelectBuilder()->find();
    }

    /**
     * Find a first record.
     *
     * @return TEntity
     */
    public function findOne(): ?Entity
    {
        if ($this->isBelongsToParentType()) {
            $entity = $this->getMapper()->selectRelated($this->entity, $this->relationName);

            if ($entity && !$entity instanceof Entity) {
                throw new LogicException();
            }

            /** @var TEntity */
            return $entity;
        }

        $collection = $this
            ->sth()
            ->limit(0, 1)
            ->find();

        foreach ($collection as $entity) {
            return $entity;
        }

        return null;
    }

    /**
     * Get a number of related records.
     */
    public function count(): int
    {
        return $this->createSelectBuilder()->count();
    }

    /**
     * Add JOIN.
     *
     * @param Join|string $target
     * A relation name or table. A relation name should be in camelCase, a table in CamelCase.
     * @param string|null $alias An alias.
     * @param WhereItem|array<string|int, mixed>|null $conditions Join conditions.
     * @return Builder<TEntity>
     */
    public function join($target, ?string $alias = null, $conditions = null): Builder
    {
        return $this->createSelectBuilder()->join($target, $alias, $conditions);
    }

    /**
     * Add LEFT JOIN.
     *
     * @param Join|string $target
     * A relation name or table. A relation name should be in camelCase, a table in CamelCase.
     * @param string|null $alias An alias.
     * @param WhereItem|array<string|int, mixed>|null $conditions Join conditions.
     * @return Builder<TEntity>
     */
    public function leftJoin($target, ?string $alias = null, $conditions = null): Builder
    {
        return $this->createSelectBuilder()->leftJoin($target, $alias, $conditions);
    }

    /**
     * Set DISTINCT parameter.
     *
     * @return Builder<TEntity>
     */
    public function distinct(): Builder
    {
        return $this->createSelectBuilder()->distinct();
    }

    /**
     * Set to return STH collection. Recommended for fetching large number of records.
     *
     * @return Builder<TEntity>
     */
    public function sth(): Builder
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
     * @param WhereItem|array<string|int, mixed>|string $clause A key or where clause.
     * @param array<int, mixed>|scalar|null $value A value. Should be omitted if the first argument is not string.
     * @return Builder<TEntity>
     */
    public function where($clause = [], $value = null): Builder
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
     * @param WhereItem|array<string|int, mixed>|string $clause A key or where clause.
     * @param array<int, mixed>|string|null $value A value. Should be omitted if the first argument is not string.
     * @return Builder<TEntity>
     */
    public function having($clause = [], $value = null): Builder
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
     * @param Order|Order[]|Expression|string|array<int, string[]>|string[] $orderBy
     *   An attribute to order by or an array or order items.
     *   Passing an array will reset a previously set order.
     * @param (Order::ASC|Order::DESC)|bool|null $direction A direction.
     * @return Builder<TEntity>
     */
    public function order($orderBy = Attribute::ID, $direction = null): Builder
    {
        return $this->createSelectBuilder()->order($orderBy, $direction);
    }

    /**
     * Apply OFFSET and LIMIT.
     *
     * @return Builder<TEntity>
     */
    public function limit(?int $offset = null, ?int $limit = null): Builder
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
     *   An array of expressions or one expression.
     * @param string|null $alias An alias. Actual if the first parameter is not an array.
     * @return Builder<TEntity>
     */
    public function select($select = [], ?string $alias = null): Builder
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
     * @return Builder<TEntity>
     */
    public function group($groupBy): Builder
    {
        return $this->createSelectBuilder()->group($groupBy);
    }

    /**
     * @deprecated Use `group` method.
     * @param Expression|Expression[]|string|string[] $groupBy
     * @return Builder<TEntity>
     */
    public function groupBy($groupBy): Builder
    {
        return $this->group($groupBy);
    }

    /**
     * Apply middle table conditions for a many-to-many relationship.
     *
     * Usage example:
     * `->columnsWhere(['column' => $value])`
     *
     * @param WhereItem|array<string|int, mixed> $clause Where clause.
     * @return Builder<TEntity>
     */
    public function columnsWhere($clause): Builder
    {
        return $this->createSelectBuilder()->columnsWhere($clause);
    }

    private function processCheckForeignEntity(Entity $entity): void
    {
        if ($this->foreignEntityType && $this->foreignEntityType !== $entity->getEntityType()) {
            throw new RuntimeException("Entity type doesn't match an entity type of the relation.");
        }

        if (!$entity->hasId()) {
            throw new RuntimeException("Can't use an entity w/o ID.");
        }
    }

    /**
     * Whether related with an entity.
     *
     * @throws RuntimeException
     */
    public function isRelated(Entity $entity): bool
    {
        if (!$entity->hasId()) {
            throw new RuntimeException("Can't use an entity w/o ID.");
        }

        if ($this->isBelongsToParentType()) {
            return $this->isRelatedBelongsToParent($entity);
        }

        if ($this->relationType === Entity::BELONGS_TO) {
            return $this->isRelatedBelongsTo($entity);
        }

        $this->processCheckForeignEntity($entity);

        return (bool) $this->createSelectBuilder()
            ->select([Attribute::ID])
            ->where([Attribute::ID => $entity->getId()])
            ->findOne();
    }

    /**
     * Whether related with another entity. An entity is specified by an ID.
     * Does not work with 'belongsToParent' relations.
     */
    public function isRelatedById(string $id): bool
    {
        if ($this->isBelongsToParentType()) {
            throw new LogicException("Can't use isRelatedById for 'belongsToParent'.");
        }

        return (bool) $this->createSelectBuilder()
            ->select([Attribute::ID])
            ->where([Attribute::ID => $id])
            ->findOne();
    }

    private function isRelatedBelongsToParent(Entity $entity): bool
    {
        $fromEntity = $this->entity;

        $idAttribute = $this->relationName . 'Id';
        $typeAttribute = $this->relationName . 'Type';

        if (!$fromEntity->has($idAttribute) || !$fromEntity->has($typeAttribute)) {
            $fromEntity = $this->entityManager->getEntityById($fromEntity->getEntityType(), $fromEntity->getId());
        }

        if (!$fromEntity) {
            return false;
        }

        return
            $fromEntity->get($idAttribute) === $entity->getId() &&
            $fromEntity->get($typeAttribute) === $entity->getEntityType();
    }

    private function isRelatedBelongsTo(Entity $entity): bool
    {
        $fromEntity = $this->entity;

        $idAttribute = $this->relationName . 'Id';

        if (!$fromEntity->has($idAttribute)) {
            $fromEntity = $this->entityManager->getEntityById($fromEntity->getEntityType(), $fromEntity->getId());
        }

        if (!$fromEntity) {
            return false;
        }

        return $fromEntity->get($idAttribute) === $entity->getId();
    }

    /**
     * Relate with an entity by ID.
     *
     * @param array<string, mixed>|null $columnData Role values.
     * @param array<string, mixed> $options
     */
    public function relateById(string $id, ?array $columnData = null, array $options = []): void
    {
        if ($this->isBelongsToParentType()) {
            throw new RuntimeException("Can't relate 'belongToParent'.");
        }

        if ($id === '') {
            throw new RuntimeException();
        }

        /** @var string $foreignEntityType */
        $foreignEntityType = $this->foreignEntityType;

        $seed = $this->entityManager->getEntityFactory()->create($foreignEntityType);

        $seed->set(Attribute::ID, $id);
        $seed->setAsFetched();

        if ($seed instanceof BaseEntity) {
            $seed->setAsPartiallyLoaded();
        }

        $this->relate($seed, $columnData, $options);
    }

    /**
     * Unrelate from an entity by ID.
     *
     * @param array<string, mixed> $options
     */
    public function unrelateById(string $id, array $options = []): void
    {
        if ($this->isBelongsToParentType()) {
            throw new RuntimeException("Can't unrelate 'belongToParent'.");
        }

        if ($id === '') {
            throw new RuntimeException();
        }

        /** @var string $foreignEntityType */
        $foreignEntityType = $this->foreignEntityType;

        $seed = $this->entityManager->getEntityFactory()->create($foreignEntityType);

        $seed->set(Attribute::ID, $id);
        $seed->setAsFetched();

        if ($seed instanceof BaseEntity) {
            $seed->setAsPartiallyLoaded();
        }

        $this->unrelate($seed, $options);
    }

    /**
     * Update relationship columns by ID. For many-to-many relationships.
     *
     * @param array<string, mixed> $columnData Role values.
     */
    public function updateColumnsById(string $id, array $columnData): void
    {
        if ($this->isBelongsToParentType()) {
            throw new RuntimeException("Can't update columns by ID 'belongToParent'.");
        }

        if ($id === '') {
            throw new RuntimeException();
        }

        /** @var string $foreignEntityType */
        $foreignEntityType = $this->foreignEntityType;

        $seed = $this->entityManager->getEntityFactory()->create($foreignEntityType);

        $seed->set(Attribute::ID, $id);
        $seed->setAsFetched();

        if ($seed instanceof BaseEntity) {
            $seed->setAsPartiallyLoaded();
        }

        $this->updateColumns($seed, $columnData);
    }

    /**
     * Relate with an entity.
     *
     * @param array<string, mixed>|null $columnData Role values.
     * @param array<string, mixed> $options
     */
    public function relate(Entity $entity, ?array $columnData = null, array $options = []): void
    {
        $this->processCheckForeignEntity($entity);
        $this->beforeRelate($entity, $columnData, $options);

        $result = $this->getMapper()->relate($this->entity, $this->relationName, $entity, $columnData);

        if (!$result) {
            return;
        }

        $this->afterRelate($entity, $columnData, $options);
    }

    /**
     * Unrelate from an entity.
     *
     * @param array<string, mixed> $options
     */
    public function unrelate(Entity $entity, array $options = []): void
    {
        $this->processCheckForeignEntity($entity);
        $this->beforeUnrelate($entity, $options);
        $this->getMapper()->unrelate($this->entity, $this->relationName, $entity);
        $this->afterUnrelate($entity, $options);
    }

    /**
     * Mass-relate.
     *
     * @param array<string, mixed> $options
     */
    public function massRelate(Select $query, array $options = []): void
    {
        if ($this->isBelongsToParentType()) {
            throw new RuntimeException("Can't mass relate 'belongToParent'.");
        }

        if ($query->getFrom() !== $this->foreignEntityType) {
            throw new RuntimeException("Passed query doesn't match foreign entity type.");
        }

        $this->beforeMassRelate($query, $options);
        $this->getMapper()->massRelate($this->entity, $this->relationName, $query);
        $this->afterMassRelate($query, $options);
    }

    /**
     * Update relationship columns. For many-to-many relationships.
     *
     * @param array<string, mixed> $columnData Role values.
     */
    public function updateColumns(Entity $entity, array $columnData): void
    {
        $this->processCheckForeignEntity($entity);

        if ($this->relationType !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't update not many-to-many relation.");
        }

        if (!$entity->hasId()) {
            throw new RuntimeException("Entity w/o ID.");
        }

        $id = $entity->getId();

        $this->getMapper()->updateRelationColumns($this->entity, $this->relationName, $id, $columnData);
    }

    /**
     * Get a relationship column value. For many-to-many relationships.
     *
     * @return string|int|float|bool|null
     */
    public function getColumn(Entity $entity, string $column)
    {
        $this->processCheckForeignEntity($entity);

        if ($this->relationType !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't get a column of not many-to-many relation.");
        }

        if (!$entity->hasId()) {
            throw new RuntimeException("Entity w/o ID.");
        }

        $id = $entity->getId();

        return $this->getMapper()->getRelationColumn($this->entity, $this->relationName, $id, $column);
    }

    /**
     * Get a relationship column value by a foreign record ID. For many-to-many relationships.
     */
    public function getColumnById(string $id, string $column): string|int|float|bool|null
    {
        if ($this->relationType !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't get a column of not many-to-many relation.");
        }

        return $this->getMapper()->getRelationColumn($this->entity, $this->relationName, $id, $column);
    }

    /**
     * @param array<string, mixed>|null $columnData Role values.
     * @param array<string, mixed> $options
     */
    private function beforeRelate(Entity $entity, ?array $columnData, array $options): void
    {
        $this->hookMediator->beforeRelate($this->entity, $this->relationName, $entity, $columnData, $options);
    }

    /**
     * @param array<string, mixed>|null $columnData Role values.
     * @param array<string, mixed> $options
     */
    private function afterRelate(Entity $entity, ?array $columnData, array $options): void
    {
        $this->hookMediator->afterRelate($this->entity, $this->relationName, $entity, $columnData, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function beforeUnrelate(Entity $entity, array $options): void
    {
        $this->hookMediator->beforeUnrelate($this->entity, $this->relationName, $entity, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function afterUnrelate(Entity $entity, array $options): void
    {
        $this->hookMediator->afterUnrelate($this->entity, $this->relationName, $entity, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function beforeMassRelate(Select $query, array $options): void
    {
        $this->hookMediator->beforeMassRelate($this->entity, $this->relationName, $query, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function afterMassRelate(Select $query, array $options): void
    {
        $this->hookMediator->afterMassRelate($this->entity, $this->relationName, $query, $options);
    }
}
