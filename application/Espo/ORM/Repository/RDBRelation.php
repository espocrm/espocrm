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
    Collection,
    Entity,
    EntityManager,
    QueryParams\Select,
    QueryParams\SelectBuilder,
    Mapper\Mapper,
};

use RuntimeException;
use BadMethodCallException;

use StdClass;

/**
 * An access point for a specific relation of a record.
 */
class RDBRelation
{
    protected $entityManager;

    protected $hookMediator;

    protected $entity;

    protected $entityType;

    protected $foreignEntityType = null;

    protected $relationName;

    protected $relationType = null;

    protected $builder = null;

    protected $noBuilder = false;

    public function __construct(EntityManager $entityManager, Entity $entity, string $relationName, HookMediator $hookMediator)
    {
        $this->entityManager = $entityManager;
        $this->entity = $entity;
        $this->hookMediator = $hookMediator;

        if (!$entity->get('id')) {
            throw new RuntimeException("Can't use an entity w/o ID.");
        }

        if (!$entity->hasRelation($relationName)) {
            throw new RuntimeException("Entity does not have a relation '{$relationName}'.");
        }

        $this->relationName = $relationName;

        $this->relationType = $entity->getRelationType($relationName);

        $this->foreignEntityType = $entity->getRelationParam($relationName, 'entity');

        $this->entityType = $entity->getEntityType();

        if ($this->isBelongsToParentType()) {
            $this->noBuilder = true;
        }
    }

    protected function createBuilder(?Select $query = null) : RDBRelationSelectBuilder
    {
        if ($this->noBuilder) {
            throw new RuntimeException("Can't use query builder for the '{$this->relationType}' relation type.");
        }

        return new RDBRelationSelectBuilder($this->entityManager, $this->entity, $this->relationName, $query);
    }

    /**
     * Clone a query.
     */
    public function clone(Select $query) : RDBRelationSelectBuilder
    {
        if ($this->noBuilder) {
            throw new RuntimeException("Can't use clone for the '{$this->relationType}' relation type.");
        }

        if ($query->getFrom() !== $this->foreignEntityType) {
            throw new RuntimeException("Passed query doesn't match the entity type.");
        }

        return $this->createBuilder($query);
    }

    protected function isBelongsToParentType() : bool
    {
        return $this->relationType === Entity::BELONGS_TO_PARENT;
    }

    protected function getMapper() : Mapper
    {
        return $this->entityManager->getMapper();
    }

    /**
     * Find related records.
     */
    public function find() : Collection
    {
        if ($this->isBelongsToParentType()) {
            $collection = $this->entityManager->getCollectionFactory()->create();

            $entity = $this->getMapper()->selectRelated($this->entity, $this->relationName);

            if ($entity) {
                $collection[] = $entity;
            }

            $collection->setAsFetched();

            return $collection;
        }

        return $this->createBuilder()->find();
    }

    /**
     * Find a first record.
     */
    public function findOne() : ?Entity
    {
        if ($this->isBelongsToParentType()) {
            return $this->getMapper()->selectRelated($this->entity, $this->relationName);
        }

        $collection = $this->sth()->limit(0, 1)->find();

        foreach ($collection as $entity) {
            return $entity;
        }

        return null;
    }

    /**
     * Get a number of related records.
     */
    public function count() : int
    {
        return $this->createBuilder()->count();
    }

    /**
     * Add JOIN.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::join()
     */
    public function join(string $relationName, ?string $alias = null, ?array $conditions = null) : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->join($relationName, $alias, $conditions);
    }

    /**
     * Add LEFT JOIN.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::leftJoin()
     */
    public function leftJoin(string $relationName, ?string $alias = null, ?array $conditions = null) : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->leftJoin($relationName, $alias, $conditions);
    }

    /**
     * Set DISTINCT parameter.
     */
    public function distinct() : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->distinct();
    }

    /**
     * Set to return STH collection. Recommended for fetching large number of records.
     */
    public function sth() : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->sth();
    }

    /**
     * Add a WHERE clause.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::where()
     *
     * @param array|string $keyOrClause
     * @param ?array|string $value
     */
    public function where($keyOrClause = [], $value = null) : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->where($keyOrClause, $value);
    }

    /**
     * Add a HAVING clause.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::having()
     *
     * @param array|string $keyOrClause
     * @param ?array|string $value
     */
    public function having($keyOrClause = [], $value = null) : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->having($keyOrClause, $params2);
    }

    /**
     * Apply ORDER.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::order()
     *
     * @param string|int|array $orderBy
      * @param bool|string $direction
     */
    public function order($orderBy, $direction = 'ASC') : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->order($orderBy, $direction);
    }

    /**
     * Apply OFFSET and LIMIT.
     */
    public function limit(?int $offset = null, ?int $limit = null) : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->limit($offset, $limit);
    }

    /**
     * Specify SELECT. Which attributes to select. All attributes are selected by default.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::select()
     *
     * @param array|string $select
     */
    public function select($select, ?string $alias = null) : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->select($select, $alias);
    }

    /**
     * Specify GROUP BY.
     */
    public function groupBy(array $groupBy) : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->groupBy($groupBy);
    }

    /**
     * Apply middle table conditions for a many-to-many relationship.
     *
     * @see Espo\ORM\Repository\RDBRelationSelectBuilder::columnsWhere()
     */
    public function columnsWhere(array $where) : RDBRelationSelectBuilder
    {
        return $this->createBuilder()->columnsWhere($where);
    }

    protected function processCheckForeignEntity(Entity $entity)
    {
        if ($this->foreignEntityType && $this->foreignEntityType !== $entity->getEntityType()) {
            throw new RuntimeException("Entity type doesn't match an entity type of the relation.");
        }

        if (!$entity->id) {
            throw new RuntimeException("Can't use an entity w/o ID.");
        }
    }

    public function isRelated(Entity $entity) : bool
    {
        if (!$entity->id) {
            throw new RuntimeException("Can't use an entity w/o ID.");
        }

        if ($this->isBelongsToParentType()) {
            return $this->isRelatedBelongsToParent($entity);
        }

        if ($this->relationType === Entity::BELONGS_TO) {
            return $this->isRelatedBelongsTo($entity);
        }

        $this->processCheckForeignEntity($entity);

        return (bool) $this->createBuilder()
            ->select(['id'])
            ->where(['id' => $entity->id])
            ->findOne();
    }

    protected function isRelatedBelongsToParent(Entity $entity) : bool
    {
        $fromEntity = $this->entity;

        $idAttribute = $this->relationName . 'Id';
        $typeAttribute = $this->relationName . 'Type';

        if (!$fromEntity->has($idAttribute) || !$fromEntity->has($typeAttribute)) {
            $fromEntity = $this->entityManager->getEntity($fromEntity->getEntityType(), $fromEntity->id);
        }

        if (!$fromEntity) {
            return false;
        }

        return
            $fromEntity->get($idAttribute) === $entity->id
            &&
            $fromEntity->get($typeAttribute) === $entity->getEntityType();
    }

    protected function isRelatedBelongsTo(Entity $entity) : bool
    {
        $fromEntity = $this->entity;

        $idAttribute = $this->relationName . 'Id';

        if (!$fromEntity->has($idAttribute)) {
            $fromEntity = $this->entityManager->getEntity($fromEntity->getEntityType(), $fromEntity->id);
        }

        if (!$fromEntity) {
            return false;
        }

        return $fromEntity->get($idAttribute) === $entity->id;
    }

    /**
     * Relate with an entity by ID.
     */
    public function relateById(string $id, ?array $columnData = null, array $options = [])
    {
        if ($this->isBelongsToParentType()) {
            throw new RuntimeException("Can't relate 'belongToParent'.");
        }

        if ($id === '') {
            throw new RuntimeException();
        }

        $seed = $this->entityManager->getEntityFactory()->create($this->foreignEntityType);
        $seed->set('id', $id);

        $this->relate($seed, $columnData, $options);
    }

    /**
     * Unrelate from an entity by ID.
     */
    public function unrelateById(string $id, array $options = [])
    {
        if ($this->isBelongsToParentType()) {
            throw new RuntimeException("Can't unrelate 'belongToParent'.");
        }

        if ($id === '') {
            throw new RuntimeException();
        }

        $seed = $this->entityManager->getEntityFactory()->create($this->foreignEntityType);
        $seed->set('id', $id);

        $this->unrelate($seed, $options);
    }

    /**
     * Update relationship columns by ID. For many-to-many relationships.
     */
    public function updateColumnsById(string $id, array $columnData)
    {
        if ($this->isBelongsToParentType()) {
            throw new RuntimeException("Can't update columns by ID 'belongToParent'.");
        }

        if ($id === '') {
            throw new RuntimeException();
        }

        $seed = $this->entityManager->getEntityFactory()->create($this->foreignEntityType);
        $seed->set('id', $id);

        $this->updateColumns($seed, $columnData);
    }

    /**
     * Relate with an entity.
     */
    public function relate(Entity $entity, ?array $columnData = null, array $options = [])
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
     */
    public function unrelate(Entity $entity, array $options = [])
    {
        $this->processCheckForeignEntity($entity);

        $this->beforeUnrelate($entity, $options);

        $result = $this->getMapper()->unrelate($this->entity, $this->relationName, $entity);

        if (!$result) {
            return;
        }

        $this->afterUnrelate($entity, $options);
    }

    public function massRelate(Select $query, array $options = [])
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
     */
    public function updateColumns(Entity $entity, array $columnData)
    {
        $this->processCheckForeignEntity($entity);

        if ($this->relationType !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't update not many-to-many relation.");
        }

        $this->getMapper()->updateRelationColumns($this->entity, $this->relationName, $entity->id, $columnData);
    }

    /**
     * Get a relationship column value. For many-to-many relationships.
     *
     * @return mixed
     */
    public function getColumn(Entity $entity, string $column)
    {
        $this->processCheckForeignEntity($entity);

        if ($this->relationType !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't get a column of not many-to-many relation.");
        }

        return $this->getMapper()->getRelationColumn($this->entity, $this->relationName, $entity->id, $column);
    }

    protected function beforeRelate(Entity $entity, ?array $columnData, array $options)
    {
        $this->hookMediator->beforeRelate($this->entity, $this->relationName, $entity, $columnData, $options);
    }

    protected function afterRelate(Entity $entity, ?array $columnData, array $options)
    {
        $this->hookMediator->afterRelate($this->entity, $this->relationName, $entity, $columnData, $options);
    }

    protected function beforeUnrelate(Entity $entity, array $options)
    {
        $this->hookMediator->beforeUnrelate($this->entity, $this->relationName, $entity, $options);
    }

    protected function afterUnrelate(Entity $entity, array $options)
    {
        $this->hookMediator->afterUnrelate($this->entity, $this->relationName, $entity, $options);
    }

    protected function beforeMassRelate(Select $query, array $options)
    {
        $this->hookMediator->beforeMassRelate($this->entity, $this->relationName, $query, $options);
    }

    protected function afterMassRelate(Select $query, array $options)
    {
        $this->hookMediator->afterMassRelate($this->entity, $this->relationName, $query, $options);
    }
}
