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

namespace Espo\ORM\Repositories;

use Espo\ORM\{
    EntityManager,
    EntityFactory,
    Collection,
    Entity,
    Repository,
    DB\Mapper,
    RDBSelectBuilder as RDBSelectBuilder,
};

class RDB extends Repository implements Findable, Relatable, Removable
{
    protected $mapper;

    private $isTableLocked = false;

    public function __construct(string $entityType, EntityManager $entityManager, EntityFactory $entityFactory)
    {
        $this->entityType = $entityType;
        $this->entityName = $entityType;

        $this->entityFactory = $entityFactory;
        $this->seed = $this->entityFactory->create($entityType);
        $this->entityClassName = get_class($this->seed);
        $this->entityManager = $entityManager;
    }

    protected function getMapper() : Mapper
    {
        if (empty($this->mapper)) {
            $this->mapper = $this->getEntityManager()->getMapper('RDB');
        }
        return $this->mapper;
    }

    /**
     * @deprecated
     */
    public function handleSelectParams(&$params)
    {
    }

    /**
     * Get a new entity.
     */
    public function getNew() : ?Entity
    {
        $entity = $this->entityFactory->create($this->entityType);

        if ($entity) {
            $entity->setIsNew(true);
            $entity->populateDefaults();
            return $entity;
        }

        return null;
    }

    /**
     * Fetch an entity by ID.
     */
    public function getById(string $id, array $params = []) : ?Entity
    {
        $entity = $this->entityFactory->create($this->entityType);
        if (!$entity) return null;

        if (empty($params['skipAdditionalSelectParams'])) {
            $this->handleSelectParams($params);
        }

        return $this->getMapper()->selectById($entity, $id, $params);
    }

    public function get(?string $id = null) : ?Entity
    {
        if (is_null($id)) {
            return $this->getNew();
        }
        return $this->getById($id);
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
    }

    protected function afterSave(Entity $entity, array $options = [])
    {
    }

    public function save(Entity $entity, array $options = [])
    {
        $entity->setAsBeingSaved();

        if (empty($options['skipBeforeSave']) && empty($options['skipAll'])) {
            $this->beforeSave($entity, $options);
        }
        if ($entity->isNew() && !$entity->isSaved()) {
            $this->getMapper()->insert($entity);
        } else {
            $this->getMapper()->update($entity);
        }

        $entity->setIsSaved(true);

        if (empty($options['skipAfterSave']) && empty($options['skipAll'])) {
            $this->afterSave($entity, $options);
        }

        if ($entity->isNew()) {
            if (empty($options['keepNew'])) {
                $entity->setIsNew(false);
            }
        } else {
            if ($entity->isFetched()) {
                $entity->updateFetchedValues();
            }
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

    protected function beforeRemove(Entity $entity, array $options = [])
    {
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
    }

    public function remove(Entity $entity, array $options = [])
    {
        $this->beforeRemove($entity, $options);
        $this->getMapper()->delete($entity);
        $this->afterRemove($entity, $options);
    }

    public function deleteFromDb(string $id, bool $onlyDeleted = false)
    {
        $this->getMapper()->deleteFromDb($this->entityType, $id, $onlyDeleted);
    }

    public function find(array $params = []) : Collection
    {
        if (empty($params['skipAdditionalSelectParams'])) {
            $this->handleSelectParams($params);
        }

        $collection = $this->getMapper()->select($this->seed, $params);

        return $collection;
    }

    public function findOne(array $params = []) : ?Entity
    {
        unset($params['returnSthCollection']);

        $collection = $this->limit(0, 1)->find($params);

        if (count($collection)) {
            return $collection[0];
        }

        return null;
    }

    public function findByQuery(string $sql, ?string $collectionType = null)
    {
        if (!$collectionType) {
            $collection = $this->getMapper()->selectByQuery($this->seed, $sql);
        } else if ($collectionType === EntityManager::STH_COLLECTION) {
            $collection = $this->getEntityManager()->createSthCollection($this->entityType);
            $collection->setQuery($sql);
        }

        return $collection;
    }

    public function findRelated(Entity $entity, string $relationName, array $params = [])
    {
        if (!$entity->id) {
            return null;
        }

        if ($entity->getRelationType($relationName) === Entity::BELONGS_TO_PARENT) {
            $entityType = $entity->get($relationName . 'Type');
        } else {
            $entityType = $entity->getRelationParam($relationName, 'entity');
        }

        if ($entityType && empty($params['skipAdditionalSelectParams'])) {
            $this->getEntityManager()->getRepository($entityType)->handleSelectParams($params);
        }

        $result = $this->getMapper()->selectRelated($entity, $relationName, $params);

        return $result;
    }

    public function countRelated(Entity $entity, string $relationName, array $params = []) : int
    {
        if (!$entity->id) {
            return 0;
        }

        $entityType =  $entity->getRelationParam($relationName, 'entity');

        if ($entityType && empty($params['skipAdditionalSelectParams'])) {
            $this->getEntityManager()->getRepository($entityType)->handleSelectParams($params);
        }

        return intval($this->getMapper()->countRelated($entity, $relationName, $params));
    }

    public function isRelated(Entity $entity, string $relationName, $foreign) : bool
    {
        if (!$entity->id) {
            return false;
        }

        if ($foreign instanceof Entity) {
            $id = $foreign->id;
        } else if (is_string($foreign)) {
            $id = $foreign;
        } else {
            return false;
        }

        if (!$id) return false;

        if ($entity->getRelationType($relationName) === Entity::BELONGS_TO) {
            $foreignEntityType = $entity->getRelationParam($relationName, 'entity');
            if (!$foreignEntityType) return false;

            $foreignId = $entity->get($relationName . 'Id');

            if (!$foreignId) {
                $e = $this->select([$relationName . 'Id'])->where(['id' => $entity->id])->findOne();
                if ($e) {
                    $foreignId = $e->get($relationName . 'Id');
                }
            }

            if (!$foreignId) return false;

            $foreignEntity = $this->getEntityManager()->getRepository($foreignEntityType)->select(['id'])->where([
                'id' => $foreignId,
            ])->findOne();

            if (!$foreignEntity) return false;

            return $foreignEntity->id === $id;
        }

        return (bool) $this->countRelated($entity, $relationName, [
            'whereClause' => [
                'id' => $id,
            ]
        ]);
    }

    public function relate(Entity $entity, string $relationName, $foreign, $data = null, array $options = [])
    {
        if (!$entity->id) {
            return false;
        }

        $this->beforeRelate($entity, $relationName, $foreign, $data, $options);
        $beforeMethodName = 'beforeRelate' . ucfirst($relationName);
        if (method_exists($this, $beforeMethodName)) {
            $this->$beforeMethodName($entity, $foreign, $data, $options);
        }

        $result = false;
        $methodName = 'relate' . ucfirst($relationName);
        if (method_exists($this, $methodName)) {
            $result = $this->$methodName($entity, $foreign, $data, $options);
        } else {
            $d = $data;
            if ($d instanceof \StdClass) {
                $d = get_object_vars($d);
            }
            if ($foreign instanceof Entity) {
                $result = $this->getMapper()->relate($entity, $relationName, $foreign, $d);
            }
            if (is_string($foreign)) {
                $result = $this->getMapper()->addRelation($entity, $relationName, $foreign, null, $d);
            }
        }

        if ($result) {
            $this->afterRelate($entity, $relationName, $foreign, $data, $options);
            $afterMethodName = 'afterRelate' . ucfirst($relationName);
            if (method_exists($this, $afterMethodName)) {
                $this->$afterMethodName($entity, $foreign, $data, $options);
            }
        }

        return $result;
    }

    public function unrelate(Entity $entity, string $relationName, $foreign, array $options = [])
    {
        if (!$entity->id) {
            return false;
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
            }
            if (is_string($foreign)) {
                $result = $this->getMapper()->removeRelation($entity, $relationName, $foreign);
            }
            if ($foreign === true) {
                $result = $this->getMapper()->removeAllRelations($entity, $relationName);
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
     * Update relationship columns.
     */
    public function updateRelation(Entity $entity, string $relationName, $foreign, $data)
    {
        if (!$entity->id) {
            return false;
        }
        if ($data instanceof \StdClass) {
            $data = get_object_vars($data);
        }
        if ($foreign instanceof Entity) {
            $id = $foreign->id;
        } else {
            $id = $foreign;
        }
        if (is_string($foreign)) {
            return $this->getMapper()->updateRelation($entity, $relationName, $id, $data);
        }

        return false;
    }

    public function massRelate(Entity $entity, string $relationName, array $params = [], array $options = [])
    {
        if (!$entity->id) {
            return false;
        }

        $this->beforeMassRelate($entity, $relationName, $params, $options);

        $this->getMapper()->massRelate($entity, $relationName, $params);

        $this->afterMassRelate($entity, $relationName, $params, $options);
    }

    public function count(array $params = []) : int
    {
        if (empty($params['skipAdditionalSelectParams'])) {
            $this->handleSelectParams($params);
        }

        $count = $this->getMapper()->count($this->seed, $params);

        return intval($count);
    }

    public function max(string $attribute, array $params = [])
    {
        return $this->getMapper()->max($this->seed, $params, $attribute);
    }

    public function min(string $attribute, array $params = [])
    {
        return $this->getMapper()->min($this->seed, $params, $attribute);
    }

    public function sum(string $attribute, array $params = [])
    {
        return $this->getMapper()->sum($this->seed, $params, $attribute);
    }

    /**
     * Add JOIN.
     *
     * @param string|array $relationName A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     *
     * Usage options:
     * * `join(string $relationName)`
     * * `join(array $joinDefinitionList)`
     *
     * Usage examples:
     * ```
     * ->join($relationName)
     * ->join($relationName, $alias, $conditions)
     * ->join([$relationName1, $relationName2, ...])
     * ->join([[$relationName, $alias], ...])
     * ->join([[$relationName, $alias, $conditions], ...])
     * ```
     */
    public function join($relationName, ?string $alias = null, ?array $conditions = null) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->join($relationName, $alias, $conditions);
    }

    /**
     * Add LEFT JOIN.
     *
     * @param string|array $relationName A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     *
     * This method works the same way as `join` method.
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
     * Set to return STH collection. Recommended fetching large number of records.
     */
    public function sth() : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->sth();
    }

    /**
     * Add a WHERE clause.
     *
     * Two usage options:
     * * `where(array $whereClause)`
     * * `where(string $key, string $value)`
     */
    public function where($param1 = [], $param2 = null) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->where($param1, $param2);
    }

    /**
     * Add a HAVING clause.
     *
     * Two usage options:
     * * `having(array $havingClause)`
     * * `having(string $key, string $value)`
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
     */
    public function select(array $select) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->select($select);
    }

    /**
     * Specify GROUP BY.
     */
    public function groupBy(array $groupBy) : RDBSelectBuilder
    {
        return $this->createSelectBuilder()->groupBy($groupBy);
    }

    protected function getPDO()
    {
        return $this->getEntityManager()->getPDO();
    }

    protected function lockTable()
    {
        $tableName = $this->getEntityManager()->getQuery()->toDb($this->entityType);

        $this->getPDO()->query("LOCK TABLES `{$tableName}` WRITE");
        $this->isTableLocked = true;
    }

    protected function unlockTable()
    {
        $this->getPDO()->query("UNLOCK TABLES");
        $this->isTableLocked = false;
    }

    protected function isTableLocked()
    {
        return $this->isTableLocked;
    }

    protected function createSelectBuilder() : RDBSelectBuilder
    {
        $builder = new RDBSelectBuilder($this->getEntityManager());
        $builder->from($this->getEntityType());
        return $builder;
    }
}
