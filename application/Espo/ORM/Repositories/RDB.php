<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\ORM\Repositories;

use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityFactory;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository;

class RDB extends
    Repository
{

    public static $mapperClassName = '\\Espo\\ORM\\DB\\MysqlMapper';

    /**
     * @var Object Mapper.
     */
    protected $mapper;

    /**
     * @var array Where clause array. To be used in further find operation.
     */
    protected $whereClause = array();

    /**
     * @var array Parameters to be used in further find operations.
     */
    protected $listParams = array();

    public function __construct($entityName, EntityManager $entityManager, EntityFactory $entityFactory)
    {
        $this->entityName = $entityName;
        $this->entityFactory = $entityFactory;
        $this->seed = $this->entityFactory->create($entityName);
        $this->entityClassName = get_class($this->seed);
        $this->entityManager = $entityManager;
    }

    public function get($id = null)
    {
        if (empty($id)) {
            return $this->getNewEntity();
        }
        return $this->getEntityById($id);
    }

    protected function getNewEntity()
    {
        $entity = $this->entityFactory->create($this->entityName);
        if ($entity) {
            $entity->setIsNew(true);
            $entity->populateDefaults();
            return $entity;
        }
    }

    protected function getEntityById($id)
    {
        $params = array();
        $this->handleSelectParams($params);
        $entity = $this->entityFactory->create($this->entityName);
        if ($entity) {
            if ($this->getMapper()->selectById($entity, $id, $params)) {
                $entity->setAsFetched();
                return $entity;
            }
        }
        return null;
    }

    public function handleSelectParams(&$params)
    {
    }

    protected function getMapper()
    {
        if (empty($this->mapper)) {
            $this->mapper = $this->getEntityManager()->getMapper(self::$mapperClassName);
        }
        return $this->mapper;
    }

    /**
     * @return \Espo\Core\ORM\EntityManager
     * @since 1.0
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    public function save(Entity $entity)
    {
        $this->beforeSave($entity);
        if ($entity->isNew()) {
            $result = $this->getMapper()->insert($entity);
            if ($result) {
                $entity->setIsNew(false);
            }
        } else {
            $result = $this->getMapper()->update($entity);
        }
        if ($result) {
            $this->afterSave($entity);
        }
        return $result;
    }

    protected function beforeSave(Entity $entity)
    {
    }

    protected function afterSave(Entity $entity)
    {
    }

    public function remove(Entity $entity)
    {
        $this->beforeRemove($entity);
        $result = $this->getMapper()->delete($entity);
        if ($result) {
            $this->afterRemove($entity);
        }
        return $result;
    }

    protected function beforeRemove(Entity $entity)
    {
    }

    protected function afterRemove(Entity $entity)
    {
    }

    public function deleteFromDb($id)
    {
        return $this->getMapper()->deleteFromDb($this->entityName, $id);
    }

    public function findOne(array $params = array())
    {
        $collection = $this->limit(0, 1)->find($params);
        if (count($collection)) {
            return $collection[0];
        }
        return null;
    }

    public function find(array $params = array())
    {
        $this->handleSelectParams($params);
        $params = $this->getSelectParams($params);
        $dataArr = $this->getMapper()->select($this->seed, $params);
        $collection = new EntityCollection($dataArr, $this->entityName, $this->entityFactory);
        $this->reset();
        return $collection;
    }

    protected function getSelectParams(array $params = array())
    {
        if (isset($params['whereClause'])) {
            $params['whereClause'] = $params['whereClause'] + $this->whereClause;
        } else {
            $params['whereClause'] = $this->whereClause;
        }
        $params = $params + $this->listParams;
        return $params;
    }

    public function reset()
    {
        $this->whereClause = array();
        $this->listParams = array();
    }

    public function limit($offset, $limit)
    {
        $this->listParams['offset'] = $offset;
        $this->listParams['limit'] = $limit;
        return $this;
    }

    public function findRelated(Entity $entity, $relationName, array $params = array())
    {
        $entityName = $entity->relations[$relationName]['entity'];
        $this->getEntityManager()->getRepository($entityName)->handleSelectParams($params);
        $result = $this->getMapper()->selectRelated($entity, $relationName, $params);
        if (is_array($result)) {
            $collection = new EntityCollection($result, $entityName, $this->entityFactory);
            return $collection;
        } else {
            return $result;
        }
    }

    public function countRelated(Entity $entity, $relationName, array $params = array())
    {
        $entityName = $entity->relations[$relationName]['entity'];
        $this->getEntityManager()->getRepository($entityName)->handleSelectParams($params);
        return $this->getMapper()->countRelated($entity, $relationName, $params);
    }

    public function relate(Entity $entity, $relationName, $foreign, $data)
    {
        if ($data instanceof \stdClass) {
            $data = get_object_vars($data);
        }
        if ($foreign instanceof Entity) {
            return $this->getMapper()->relate($entity, $relationName, $foreign, $data);
        }
        if (is_string($foreign)) {
            return $this->getMapper()->addRelation($entity, $relationName, $foreign, null, $data);
        }
        return false;
    }

    public function unrelate(Entity $entity, $relationName, $foreign)
    {
        if ($foreign instanceof Entity) {
            return $this->getMapper()->unrelate($entity, $relationName, $foreign);
        }
        if (is_string($foreign)) {
            return $this->getMapper()->removeRelation($entity, $relationName, $foreign);
        }
        if ($foreign === true) {
            return $this->getMapper()->removeAllRelations($entity, $relationName);
        }
        return false;
    }

    public function updateRelation(Entity $entity, $relationName, $foreign, $data)
    {
        if ($data instanceof \stdClass) {
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

    public function getAll()
    {
        $this->reset();
        return $this->find();
    }

    public function count(array $params = array())
    {
        $this->handleSelectParams($params);
        $params = $this->getSelectParams($params);
        return $this->getMapper()->count($this->seed, $params);
    }

    public function max($field)
    {
        $params = $this->getSelectParams();
        return $this->getMapper()->max($this->seed, $params, $field);
    }

    public function min($field)
    {
        $params = $this->getSelectParams();
        return $this->getMapper()->min($this->seed, $params, $field);
    }

    // @TODO use abstract class for list params
    // @TODO join conditions

    public function sum($field)
    {
        $params = $this->getSelectParams();
        return $this->getMapper()->sum($this->seed, $params, $field);
    }

    public function join()
    {
        $args = func_get_args();
        if (empty($this->listParams['joins'])) {
            $this->listParams['joins'] = array();
        }
        foreach ($args as &$param) {
            if (is_array($param)) {
                foreach ($param as $k => $v) {
                    $this->listParams['joins'][] = $v;
                }
            } else {
                $this->listParams['joins'][] = $param;
            }
        }
        return $this;
    }

    public function distinct()
    {
        $this->listParams['distinct'] = true;
        return $this;
    }

    /**
     * @param array|string $param1
     * @param null  $param2
     *
     * @return $this
     * @since 1.0
     */
    public function where($param1 = array(), $param2 = null)
    {
        if (is_array($param1)) {
            $this->whereClause = $param1 + $this->whereClause;
        } else {
            if (!is_null($param2)) {
                $this->whereClause[$param1] = $param2;
            }
        }
        return $this;
    }

    public function order($field = 'id', $direction = "ASC")
    {
        $this->listParams['orderBy'] = $field;
        $this->listParams['order'] = $direction;
        return $this;
    }

    public function getListParams()
    {
        return $this->listParams;
    }

    public function setListParams(array $params = array())
    {
        $this->listParams = $params;
    }

    protected function getEntityFactory()
    {
        return $this->entityFactory;
    }

    protected function getPDO()
    {
        return $this->getEntityManager()->getPDO();
    }
}

