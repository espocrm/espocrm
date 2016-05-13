<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\ORM\Repositories;

use \Espo\ORM\EntityManager;
use \Espo\ORM\EntityFactory;
use \Espo\ORM\EntityCollection;
use \Espo\ORM\Entity;
use \Espo\ORM\IEntity;


class RDB extends \Espo\ORM\Repository
{
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
        $this->init();
    }

    protected function init()
    {
    }

    protected function getMapper()
    {
        if (empty($this->mapper)) {
            $this->mapper = $this->getEntityManager()->getMapper('RDB');
        }
        return $this->mapper;
    }

    public function handleSelectParams(&$params)
    {
    }

    protected function getEntityFactory()
    {
        return $this->entityFactory;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    public function reset()
    {
        $this->whereClause = array();
        $this->listParams = array();
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

    public function get($id = null)
    {
        if (empty($id)) {
            return $this->getNewEntity();
        }
        return $this->getEntityById($id);
    }

    protected function beforeSave(Entity $entity, array $options = array())
    {
    }

    protected function afterSave(Entity $entity, array $options = array())
    {
    }

    public function save(Entity $entity, array $options = array())
    {
        $this->beforeSave($entity, $options);
        if ($entity->isNew() && !$entity->isSaved()) {
            $result = $this->getMapper()->insert($entity);
        } else {
            $result = $this->getMapper()->update($entity);
        }
        if ($result) {
            $entity->setIsSaved(true);
            $this->afterSave($entity, $options);
            if ($entity->isNew()) {
                $entity->setIsNew(false);
            } else {
                if ($entity->isFetched()) {
                    $entity->updateFetchedValues();
                }
            }
        }
        return $result;
    }

    protected function beforeRemove(Entity $entity, array $options = array())
    {
    }

    protected function afterRemove(Entity $entity, array $options = array())
    {
    }

    public function remove(Entity $entity, array $options = array())
    {
        $this->beforeRemove($entity, $options);
        $result = $this->getMapper()->delete($entity);
        if ($result) {
            $this->afterRemove($entity, $options);
        }
        return $result;
    }

    public function deleteFromDb($id)
    {
        return $this->getMapper()->deleteFromDb($this->entityName, $id);
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

    public function findOne(array $params = array())
    {
        $collection = $this->limit(0, 1)->find($params);
        if (count($collection)) {
            return $collection[0];
        }
        return null;
    }

    public function findByQuery($sql)
    {
        $dataArr = $this->getMapper()->selectByQuery($this->seed, $sql);

        $collection = new EntityCollection($dataArr, $this->entityName, $this->entityFactory);
        $this->reset();

        return $collection;
    }

    public function findRelated(Entity $entity, $relationName, array $params = array())
    {
        if ($entity->isNew()) {
            return [];
        }
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
        if (!$entity->id) {
            return;
        }
        $entityName = $entity->relations[$relationName]['entity'];
        $this->getEntityManager()->getRepository($entityName)->handleSelectParams($params);

        return intval($this->getMapper()->countRelated($entity, $relationName, $params));
    }

    public function isRelated(Entity $entity, $relationName, $foreign)
    {
        if (!$entity->id) {
            return;
        }

        if ($foreign instanceof Entity) {
            $id = $foreign->id;
        } else if (is_string($foreign)) {
            $id = $foreign;
        } else {
            return;
        }

        if (!$id) return;

        return !!$this->countRelated($entity, $relationName, array(
            'whereClause' => array(
                'id' => $id
            )
        ));
    }

    public function relate(Entity $entity, $relationName, $foreign, $data = null)
    {
        if (!$entity->id) {
            return;
        }

        $this->beforeRelate($entity, $relationName, $foreign, $data);
        $beforeMethodName = 'beforeRelate' . ucfirst($relationName);
        if (method_exists($this, $beforeMethodName)) {
            $this->$beforeMethodName($entity, $foreign, $data);
        }

        $result = false;
        $methodName = 'relate' . ucfirst($relationName);
        if (method_exists($this, $methodName)) {
            $result = $this->$methodName($entity, $foreign, $data);
        } else {
            $d = $data;
            if ($d instanceof \stdClass) {
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
            $this->afterRelate($entity, $relationName, $foreign, $data);
            $afterMethodName = 'afterRelate' . ucfirst($relationName);
            if (method_exists($this, $afterMethodName)) {
                $this->$afterMethodName($entity, $foreign, $data);
            }
        }

        return $result;
    }


    public function unrelate(Entity $entity, $relationName, $foreign)
    {
        if (!$entity->id) {
            return;
        }

        $this->beforeUnrelate($entity, $relationName, $foreign);
        $beforeMethodName = 'beforeUnrelate' . ucfirst($relationName);
        if (method_exists($this, $beforeMethodName)) {
            $this->$beforeMethodName($entity, $foreign);
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
            $this->afterUnrelate($entity, $relationName, $foreign);
            $afterMethodName = 'afterUnrelate' . ucfirst($relationName);
            if (method_exists($this, $afterMethodName)) {
                $this->$afterMethodName($entity, $foreign);
            }
        }

        return $result;
    }

    protected function beforeRelate(Entity $entity, $relationName, $foreign, $data = null)
    {

    }

    protected function afterRelate(Entity $entity, $relationName, $foreign, $data = null)
    {

    }

    protected function beforeUnrelate(Entity $entity, $relationName, $foreign)
    {

    }

    protected function afterUnrelate(Entity $entity, $relationName, $foreign)
    {

    }

    public function updateRelation(Entity $entity, $relationName, $foreign, $data)
    {
        if (!$entity->id) {
            return;
        }
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
        return null;
    }

    public function massRelate(Entity $entity, $relationName, array $params = array())
    {
        if (!$entity->id) {
            return;
        }
        return $this->getMapper()->massRelate($entity, $relationName, $params);
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
        $count = $this->getMapper()->count($this->seed, $params);
        $this->reset();
        return intval($count);
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

    public function sum($field)
    {
        $params = $this->getSelectParams();
        return $this->getMapper()->sum($this->seed, $params, $field);
    }

    public function join()
    {
        $args = func_get_args();

        if (empty($this->listParams['joins'])) {
            $this->listParams['joins'] = [];
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

    public function leftJoin()
    {
        $args = func_get_args();

        if (empty($this->listParams['leftJoins'])) {
            $this->listParams['leftJoins'] = [];
        }

        foreach ($args as &$param) {
            if (is_array($param)) {
                foreach ($param as $k => $v) {
                    $this->listParams['leftJoins'][] = $v;
                }
            } else {
                $this->listParams['leftJoins'][] = $param;
            }
        }

        return $this;
    }

    public function distinct()
    {
        $this->listParams['distinct'] = true;
        return $this;
    }

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

    public function limit($offset, $limit)
    {
        $this->listParams['offset'] = $offset;
        $this->listParams['limit'] = $limit;

        return $this;
    }

    public function setListParams(array $params = array())
    {
        $this->listParams = $params;
    }

    public function getListParams()
    {
        return $this->listParams;
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

    protected function getPDO()
    {
        return $this->getEntityManager()->getPDO();
    }
}

