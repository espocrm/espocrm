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
    EntityManager,
    EntityFactory,
    Collection,
    SthCollection,
    Entity,
    Mapper\Mapper,
    QueryParams\Select,
};

use StdClass;
use RuntimeException;

class RDBRepository extends Repository
{
    protected $mapper;

    protected $hookMediator;

    protected $transactionManager;

    public function __construct(
        string $entityType,
        EntityManager $entityManager,
        EntityFactory $entityFactory,
        ?HookMediator $hookMediator = null
    ) {
        $this->entityType = $entityType;
        $this->entityManager = $entityManager;
        $this->entityFactory = $entityFactory;

        $this->seed = $this->entityFactory->create($entityType);

        $this->hookMediator = $hookMediator ?? (new EmptyHookMediator());

        $this->transactionManager = new RDBTransactionManager($entityManager->getTransactionManager());
    }

    protected function getMapper() : Mapper
    {
        return $this->entityManager->getMapper();
    }

    /**
     * Get a new entity.
     */
    public function getNew() : ?Entity
    {
        $entity = $this->entityFactory->create($this->entityType);

        if ($entity) {
            $entity->populateDefaults();

            return $entity;
        }

        return null;
    }

    /**
     * Fetch an entity by ID.
     */
    public function getById(string $id) : ?Entity
    {
        $select = $this->entityManager->getQueryBuilder()
            ->select()
            ->from($this->entityType)
            ->where([
                'id' => $id,
            ])
            ->build();

        return $this->getMapper()->selectOne($select);
    }

    public function get(?string $id = null) : ?Entity
    {
        if (is_null($id)) {
            return $this->getNew();
        }

        return $this->getById($id);
    }

    protected function processCheckEntity(Entity $entity)
    {
        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("An entity type doesn't match the repository.");
        }
    }

    public function save(Entity $entity, array $options = [])
    {
        $this->processCheckEntity($entity);

        $entity->setAsBeingSaved();

        if (empty($options['skipBeforeSave']) && empty($options['skipAll'])) {
            $this->beforeSave($entity, $options);
        }

        if ($entity->isNew() && !$entity->isSaved()) {
            $this->getMapper()->insert($entity);
        }
        else {
            $this->getMapper()->update($entity);
        }

        $entity->setAsSaved();

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

        $entity->setAsNotBeingSaved();
    }

    /**
     * Restore a record flagged as deleted.
     */
    public function restoreDeleted(string $id)
    {
        return $this->getMapper()->restoreDeleted($this->entityType, $id);
    }

    /**
     * Get an access point for a specific relation of a record.
     */
    public function getRelation(Entity $entity, string $relationName) : RDBRelation
    {
        return new RDBRelation($this->entityManager, $entity, $relationName, $this->hookMediator);
    }

    /**
     * Remove a record (mark as deleted).
     */
    public function remove(Entity $entity, array $options = [])
    {
        $this->processCheckEntity($entity);

        $this->beforeRemove($entity, $options);

        $this->getMapper()->delete($entity);

        $this->afterRemove($entity, $options);
    }

    /**
     * @deprecated Use QueryBuilder instead.
     */
    public function deleteFromDb(string $id, bool $onlyDeleted = false)
    {
        $this->getMapper()->deleteFromDb($this->entityType, $id, $onlyDeleted);
    }

    /**
     * Find records.
     *
     * @param $params @deprecated Omit it.
     */
    public function find(?array $params = []) : Collection
    {
        return $this->createSelectBuilder()->find($params);
    }

    /**
     * Find one record.
     *
     * @param $params @deprecated Omit it.
     */
    public function findOne(?array $params = []) : ?Entity
    {
        $collection = $this->limit(0, 1)->find($params);

        foreach ($collection as $entity) {
            return $entity;
        }

        return null;
    }

    /**
     * Find records by a SQL query.
     */
    public function findBySql(string $sql) : SthCollection
    {
        return $this->getMapper()->selectBySql($this->entityType, $sql);
    }

    /**
     * @deprecated
     */
    public function findRelated(Entity $entity, string $relationName, ?array $params = null)
    {
        $params = $params ?? [];

        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("Not supported entity type.");
        }

        if (!$entity->id) {
            return null;
        }

        $type = $entity->getRelationType($relationName);
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
            $select = $this->applyRelationAdditionalColumns($entity, $relationName, $additionalColumns, $select);
        }

        // @todo Get rid of 'additionalColumnsConditions' usage. Use 'whereClause' instead.
        if ($type === Entity::MANY_MANY && count($additionalColumnsConditions)) {
            $select = $this->applyRelationAdditionalColumnsConditions(
                $entity, $relationName, $additionalColumnsConditions, $select
            );
        }

        $result = $this->getMapper()->selectRelated($entity, $relationName, $select);

        if ($result instanceof SthCollection) {
            return $this->entityManager->getCollectionFactory()->createFromSthCollection($result);
        }

        return $result;
    }

    /**
     * @deprecated
     */
    public function countRelated(Entity $entity, string $relationName, ?array $params = null) : int
    {
        $params = $params ?? [];

        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("Not supported entity type.");
        }

        if (!$entity->id) {
            return 0;
        }

        $type = $entity->getRelationType($relationName);
        $entityType = $entity->getRelationParam($relationName, 'entity');

        $additionalColumnsConditions = $params['additionalColumnsConditions'] ?? [];
        unset($params['additionalColumnsConditions']);

        $select = null;

        if ($entityType) {
            $params['from'] = $entityType;

            $select = Select::fromRaw($params);
        }

        if ($type === Entity::MANY_MANY && count($additionalColumnsConditions)) {
            $select = $this->applyRelationAdditionalColumnsConditions(
                $entity, $relationName, $additionalColumnsConditions, $select
            );
        }

        return (int) $this->getMapper()->countRelated($entity, $relationName, $select);
    }

    protected function applyRelationAdditionalColumns(
        Entity $entity, string $relationName, array $columns, Select $select
    ) : Select {

        if (empty($columns)) {
            return $select;
        }

        $middleName = lcfirst($entity->getRelationParam($relationName, 'relationName'));

        $selectItemList = $select->getSelect();

        if (empty($selectItemList)) {
            $selectItemList[] = '*';
        }

        foreach ($columns as $column => $alias) {
            $selectItemList[] = [
                $middleName . '.' . $column,
                $alias
            ];
        }

        $select = $this->entityManager->getQueryBuilder()
            ->clone($select)
            ->select($selectItemList)
            ->build();

        return $select;
    }

    protected function applyRelationAdditionalColumnsConditions(
        Entity $entity, string $relationName, array $conditions, Select $select
    ) : Select {
        if (empty($conditions)) {
            return $select;
        }

        $middleName = lcfirst($entity->getRelationParam($relationName, 'relationName'));

        $builder = $this->entityManager->getQueryBuilder()->clone($select);

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
     */
    public function isRelated(Entity $entity, string $relationName, $foreign) : bool
    {
        if (!$entity->id) {
            return false;
        }

        if ($entity->getEntityType() !== $this->entityType) {
            throw new RuntimeException("Not supported entity type.");
        }

        if ($foreign instanceof Entity) {
            $id = $foreign->id;
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
                $entity = $this->getById($entity->id);
            }
        }

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
     */
    public function relate(Entity $entity, string $relationName, $foreign, $columnData = null, array $options = [])
    {
        if (!$entity->id) {
            throw new RuntimeException("Can't relate an entity w/o ID.");
        }

        if (! $foreign instanceof Entity && !is_string($foreign)) {
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

            if ($columnData instanceof StdClass) {
                $data = get_object_vars($columnData);
            }

            if ($foreign instanceof Entity) {
                $result = $this->getMapper()->relate($entity, $relationName, $foreign, $data);
            } else {
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
     */
    public function unrelate(Entity $entity, string $relationName, $foreign, array $options = [])
    {
        if (!$entity->id) {
            throw new RuntimeException("Can't unrelate an entity w/o ID.");
        }

        if (! $foreign instanceof Entity && !is_string($foreign)) {
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
            $result = $this->$methodName($entity, $foreign);
        } else {
            if ($foreign instanceof Entity) {
                $result = $this->getMapper()->unrelate($entity, $relationName, $foreign);
            } else {
                $id = $foreign;
                $result = $this->getMapper()->unrelateById($entity, $relationName, $id);
            }
        }

        if ($result) {
            $this->afterUnrelate($entity, $relationName, $foreign, $options);

            $afterMethodName = 'afterUnrelate' . ucfirst($relationName);
            if (method_exists($this, $afterMethodName)) {
                $this->$afterMethodName($entity, $foreign, $options);
            }
        }

        return $result;
    }

    /**
     * @deprecated
     */
    public function getRelationColumn(Entity $entity, string $relationName, string $foreignId, string $column)
    {
        return $this->getMapper()->getRelationColumn($entity, $relationName, $foreignId, $column);
    }

    protected function beforeRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
    }

    protected function afterRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
    }

    protected function beforeUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
    }

    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
    }

    protected function beforeMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
    }

    protected function afterMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
    }

    /**
     * @deprecated
     */
    public function updateRelation(Entity $entity, string $relationName, $foreign, $columnData)
    {
        if (!$entity->id) {
            throw new RuntimeException("Can't update a relation for an entity w/o ID.");
        }

        if (! $foreign instanceof Entity && !is_string($foreign)) {
            throw new RuntimeException("Bad foreign value.");
        }

        if ($columnData instanceof StdClass) {
            $columnData = get_object_vars($columnData);
        }

        if ($foreign instanceof Entity) {
            $id = $foreign->id;
        } else {
            $id = $foreign;
        }

        if (!is_string($id)) {
            throw new RuntimeException("Bad foreign value.");
        }

        return $this->getMapper()->updateRelationColumns($entity, $relationName, $id, $columnData);
    }

    /**
     * @deprecated
     */
    public function massRelate(Entity $entity, string $relationName, array $params = [], array $options = [])
    {
        if (!$entity->id) {
            throw new RuntimeException("Can't related an entity w/o ID.");
        }

        $this->beforeMassRelate($entity, $relationName, $params, $options);

        $select = Select::fromRaw($params);

        $this->getMapper()->massRelate($entity, $relationName, $select);

        $this->afterMassRelate($entity, $relationName, $params, $options);
    }

    /**
     * @param $params @deprecated Omit it.
     */
    public function count(array $params = []) : int
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
     */
    public function clone(Select $query) : RDBSelectBuilder
    {
        if ($this->entityType !== $query->getFrom()) {
            throw new RuntimeException("Can't clone a query of a different entity type.");
        }

        $builder = new RDBSelectBuilder($this->entityManager, $this->entityType, $query);

        return $builder;
    }

    /**
     * Add JOIN.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::join()
     */
    public function join($relationName, ?string $alias = null, ?array $conditions = null) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->join($relationName, $alias, $conditions);
    }

    /**
     * Add LEFT JOIN.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::leftJoin()
     */
    public function leftJoin($relationName, ?string $alias = null, ?array $conditions = null) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->leftJoin($relationName, $alias, $conditions);
    }

    /**
     * Set DISTINCT parameter.
     */
    public function distinct() : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->distinct();
    }

    /**
     * Lock selected rows. To be used within a transaction.
     */
    public function forUpdate() : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->forUpdate();
    }

    /**
     * Set to return STH collection. Recommended fetching large number of records.
     *
     * @todo Remove.
     */
    public function sth() : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->sth();
    }

    /**
     * Add a WHERE clause.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::where()
     */
    public function where($param1 = [], $param2 = null) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->where($param1, $param2);
    }

    /**
     * Add a HAVING clause.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::having()
     */
    public function having($param1 = [], $param2 = null) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->having($param1, $param2);
    }

    /**
     * Apply ORDER.
     *
     * @param string|array $attribute An attribute to order by or order definitions as an array.
     * @param bool|string $direction TRUE for DESC order.
     */
    public function order($attribute = 'id', $direction = 'ASC') : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->order($attribute, $direction);
    }

    /**
     * Apply OFFSET and LIMIT.
     */
    public function limit(?int $offset = null, ?int $limit = null) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->limit($offset, $limit);
    }

    /**
     * Specify SELECT. Which attributes to select. All attributes are selected by default.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::select()
     *
     * @param array|string $select
     */
    public function select($select = [], ?string $alias = null) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->select($select, $alias);
    }

    /**
     * Specify GROUP BY.
     *
     * @see Espo\ORM\QueryParams\SelectBuilder::groupBy()
     */
    public function groupBy($groupBy) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->groupBy($groupBy);
    }

    protected function getPDO()
    {
        return $this->entityManager->getPDO();
    }

    protected function createSelectBuilder() : RDBSelectBuilder
    {
        $builder = new RDBSelectBuilder($this->entityManager, $this->entityType);

        return $builder;
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        $this->hookMediator->beforeSave($entity, $options);
    }

    protected function afterSave(Entity $entity, array $options = [])
    {
        $this->hookMediator->afterSave($entity, $options);
    }

    protected function beforeRemove(Entity $entity, array $options = [])
    {
        $this->hookMediator->beforeRemove($entity, $options);
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        $this->hookMediator->afterRemove($entity, $options);
    }
}
