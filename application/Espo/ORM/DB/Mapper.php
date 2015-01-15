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
 ************************************************************************/

namespace Espo\ORM\DB;

use Espo\ORM\Entity;
use Espo\ORM\IEntity;
use Espo\ORM\EntityFactory;
use PDO;

/**
 * Abstraction for DB.
 * Mapping of Entity to DB.
 * Should be used internally only.
 */
abstract class Mapper implements IMapper
{
    public $pdo;

    protected $entityFactroy;

    protected $query;

    protected $fieldsMapCache = array();
    protected $aliasesCache = array();

    protected $returnCollection = false;

    protected $collectionClass = "\\Espo\\ORM\\EntityCollection";

    protected static $sqlOperators = array(
        'OR',
        'AND',
    );

    protected static $comparisonOperators = array(
        '!=' => '<>',
        '*' => 'LIKE',
        '>=' => '>=',
        '<=' => '<=',
        '>' => '>',
        '<' => '<',
        '=' => '=',
    );

    protected static $selectParamList = array(
        'offset',
        'limit',
        'order',
        'orderBy',
        'customWhere',
        'customJoin',
        'joins',
        'leftJoins',
        'distinct',
        'joinConditions',
        'additionalColumnsConditions'
    );

    public function __construct(PDO $pdo, \Espo\ORM\EntityFactory $entityFactory, Query\Base $query) {
        $this->pdo = $pdo;
        $this->query = $query;
        $this->entityFactory = $entityFactory;
    }

    public function selectById(IEntity $entity, $id, $params = array())
    {
        if (!array_key_exists('whereClause', $params)) {
            $params['whereClause'] = array();
        }

        $params['whereClause']['id'] = $id;
        $params['whereClause']['deleted'] = 0;

        $sql = $this->query->createSelectQuery($entity->getEntityName(), $params);

        $ps = $this->pdo->query($sql);

        if ($ps) {
            foreach ($ps as $row) {
                $entity = $this->fromRow($entity, $row);
                return true;
            }
        }
        return false;
    }

    public function count(IEntity $entity, $params = array())
    {
        return $this->aggregate($entity, $params, 'COUNT', 'id');
    }

    public function max(IEntity $entity, $params = array(), $field, $deleted = false)
    {
        return $this->aggregate($entity, $params, 'MAX', $field, true);
    }

    public function min(IEntity $entity, $params = array(), $field, $deleted = false)
    {
        return $this->aggregate($entity, $params, 'MIN', $field, true);
    }

    public function sum(IEntity $entity, $params = array())
    {
        return $this->aggregate($entity, $params, 'SUM', 'id');
    }

    public function select(IEntity $entity, $params = array())
    {
        $sql = $this->query->createSelectQuery($entity->getEntityName(), $params);

        $dataArr = array();
        $ps = $this->pdo->query($sql);
        if ($ps) {
            $dataArr = $ps->fetchAll();
        }

        if ($this->returnCollection) {
            $collectionClass = $this->collectionClass;
            $entityArr = new $collectionClass($dataArr, $entity->getEntityName(), $this->entityFactory);
            return $entityArr;
        } else {
            return $dataArr;
        }
    }

    public function aggregate(IEntity $entity, $params = array(), $aggregation, $aggregationBy, $deleted = false)
    {
        if (empty($aggregation) || !isset($entity->fields[$aggregationBy])) {
            return false;
        }

        $params['aggregation'] = $aggregation;
        $params['aggregationBy'] = $aggregationBy;

        $sql = $this->query->createSelectQuery($entity->getEntityName(), $params, $deleted);

        $ps = $this->pdo->query($sql);

        if ($ps) {
            foreach ($ps as $row) {
                return $row['AggregateValue'];
            }
        }
        return false;
    }

    public function selectRelated(IEntity $entity, $relationName, $params = array(), $totalCount = false)
    {
        $relOpt = $entity->relations[$relationName];

        if (!isset($relOpt['entity']) || !isset($relOpt['type'])) {
            throw new \LogicException("Not appropriate defenition for relationship {$relationName} in " . $entity->getEntityName() . " entity");
        }

        $relEntityName = (!empty($relOpt['class'])) ? $relOpt['class'] : $relOpt['entity'];
        $relEntity = $this->entityFactory->create($relEntityName);

        if (!$relEntity) {
            return null;
        }

        if ($totalCount) {
            $params['aggregation'] = 'COUNT';
            $params['aggregationBy'] = 'id';
        }


        if (empty($params['whereClause'])) {
            $params['whereClause'] = array();
        }

        $relType = $relOpt['type'];

        $keySet = $this->query->getKeys($entity, $relationName);

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        switch ($relType) {

            case IEntity::BELONGS_TO:
                $params['whereClause'][$foreignKey] = $entity->get($key);
                $params['offset'] = 0;
                $params['limit'] = 1;

                $sql = $this->query->createSelectQuery($relEntity->getEntityName(), $params);

                $ps = $this->pdo->query($sql);

                if ($ps) {
                    foreach ($ps as $row) {
                        if (!$totalCount) {
                            $relEntity = $this->fromRow($relEntity, $row);
                            return $relEntity;
                        } else {
                            return $row['AggregateValue'];
                        }
                    }
                }
            break;

            case IEntity::HAS_MANY:
            case IEntity::HAS_CHILDREN:

                $params['whereClause'][$foreignKey] = $entity->get($key);

                if ($relType == IEntity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'];
                    $params['whereClause'][$foreignType] = $entity->getEntityName();
                }

                $dataArr = array();

                $sql = $this->query->createSelectQuery($relEntity->getEntityName(), $params);

                $ps = $this->pdo->query($sql);
                if ($ps) {
                    if (!$totalCount) {
                        $dataArr = $ps->fetchAll();

                    } else {
                        foreach ($ps as $row) {
                            return $row['AggregateValue'];
                        }
                    }
                }
                if ($this->returnCollection) {
                    $collectionClass = $this->collectionClass;
                    return new $collectionClass($dataArr, $relEntity->getEntityName(), $this->entityFactory);
                } else {
                    return $dataArr;
                }
            break;

            case IEntity::MANY_MANY:

                $additionalColumnsConditions = null;
                if (!empty($params['additionalColumnsConditions'])) {
                    $additionalColumnsConditions = $params['additionalColumnsConditions'];
                }

                $MMJoinPart = $this->getMMJoin($entity, $relationName, $keySet, $additionalColumnsConditions);

                if (empty($params['customJoin'])) {
                    $params['customJoin'] = '';
                } else {
                    $params['customJoin'] .= ' ';
                }
                $params['customJoin'] .= $MMJoinPart;


                $params['relationName'] = $relOpt['relationName'];

                // TODO total


                $sql = $this->query->createSelectQuery($relEntity->getEntityName(), $params);

                $dataArr = array();


                $ps = $this->pdo->query($sql);
                if ($ps) {
                    if (!$totalCount) {
                        $dataArr = $ps->fetchAll();

                    } else {
                        foreach ($ps as $row) {
                            return $row['AggregateValue'];
                        }
                    }
                }
                if ($this->returnCollection) {
                    $collectionClass = $this->collectionClass;
                    return new $collectionClass($dataArr, $relEntity->getEntityName(), $this->entityFactory);
                } else {
                    return $dataArr;
                }
            break;
        }

        return false;
    }


    public function countRelated(IEntity $entity, $relationName, $params = array())
    {
        return $this->selectRelated($entity, $relationName, $params, true);
    }

    public function relate(IEntity $entityFrom, $relationName, IEntity $entityTo, $data = null)
    {
        $this->addRelation($entityFrom, $relationName, null, $entityTo, $data);
    }

    public function unrelate(IEntity $entityFrom, $relationName, IEntity $entityTo)
    {
        $this->removeRelation($entityFrom, $relationName, null, false, $entityTo);
    }

    public function updateRelation(IEntity $entity, $relationName, $id = null, array $columnData)
    {
        if (empty($id) || empty($relationName)) {
            return false;
        }

        $relOpt = $entity->relations[$relationName];
        $keySet = $this->query->getKeys($entity, $relationName);

        $relType = $relOpt['type'];


        switch ($relType) {
            case IEntity::MANY_MANY:
                $relTable = $this->toDb($relOpt['relationName']);
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $setArr = array();
                foreach ($columnData as $column => $value) {
                    $setArr[] = "`".$this->toDb($column) . "` = " . $this->pdo->quote($value);
                }
                if (empty($setArr)) {
                    return true;
                }
                $setPart = implode(', ', $setArr);

                $wherePart =
                    $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id) . "
                    AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($id) . " AND deleted = 0
                    ";

                if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
                    foreach ($relOpt['conditions'] as $f => $v) {
                        $wherePart .= " AND " . $this->toDb($f) . " = " . $this->pdo->quote($v);
                    }
                }

                $sql = $this->composeUpdateQuery($relTable, $setPart, $wherePart);

                if ($this->pdo->query($sql)) {
                    return true;
                }
        }
    }

    public function addRelation(IEntity $entity, $relationName, $id = null, $relEntity = null, $data = null)
    {
        if (!is_null($relEntity)) {
            $id = $relEntity->id;
        }

        if (empty($id) || empty($relationName)) {
            return false;
        }

        $relOpt = $entity->relations[$relationName];

        if (!isset($relOpt['entity']) || !isset($relOpt['type'])) {
            throw new \LogicException("Not appropriate defenition for relationship {$relationName} in " . $entity->getEntityName() . " entity");
        }

        $relType = $relOpt['type'];

        $className = (!empty($relOpt['class'])) ? $relOpt['class'] : $relOpt['entity'];

        if (is_null($relEntity)) {
            $relEntity = $this->entityFactory->create($className);
            if (!$relEntity) {
                return null;
            }
            $relEntity->id = $id;
        }

        $keySet = $this->query->getKeys($entity, $relationName);

        switch ($relType) {
            case IEntity::BELONGS_TO:
            case IEntity::HAS_ONE:
                return false;
            break;

            case IEntity::HAS_CHILDREN:
            case IEntity::HAS_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];

                if ($this->count($relEntity, array('whereClause' => array('id' => $id))) > 0) {

                    $setPart = $this->toDb($foreignKey) . " = " . $this->pdo->quote($entity->get($key));

                    if ($relType == IEntity::HAS_CHILDREN) {
                        $foreignType = $keySet['foreignType'];
                        $setPart .= ", " . $this->toDb($foreignType) . " = " . $this->pdo->quote($entity->getEntityName());
                    }

                    $wherePart = $this->query->getWhere($relEntity, array('id' => $id, 'deleted' => 0));
                    $sql = $this->composeUpdateQuery($this->toDb($relEntity->getEntityName()), $setPart, $wherePart);

                    if ($this->pdo->query($sql)) {
                        return true;
                    }
                } else {
                    return false;
                }
            break;

            case IEntity::MANY_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                if ($this->count($relEntity, array('whereClause' => array('id' => $id))) > 0) {
                    $relTable = $this->toDb($relOpt['relationName']);

                    $wherePart =
                        $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id) . " ".
                        "AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($relEntity->id);
                    if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
                        foreach ($relOpt['conditions'] as $f => $v) {
                            $wherePart .= " AND " . $this->toDb($f) . " = " . $this->pdo->quote($v);
                        }
                    }

                    $sql = $this->query->composeSelectQuery($relTable, '*', '', $wherePart);

                    $ps = $this->pdo->query($sql);

                    if ($ps->rowCount() == 0) {
                        $fieldsPart = $this->toDb($nearKey) . ", " . $this->toDb($distantKey);
                        $valuesPart = $this->pdo->quote($entity->id) . ", " . $this->pdo->quote($relEntity->id);

                        if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
                            foreach ($relOpt['conditions'] as $f => $v) {
                                $fieldsPart .= ", " . $this->toDb($f);
                                $valuesPart .= ", " . $this->pdo->quote($v);
                            }
                        }

                        if (!empty($data) && is_array($data)) {
                            foreach ($data as $column => $columnValue) {
                                $fieldsPart .= ", " . $this->toDb($column);
                                $valuesPart .= ", " . $this->pdo->quote($columnValue);
                            }
                        }

                        $sql = $this->composeInsertQuery($relTable, $fieldsPart, $valuesPart);

                        if ($this->pdo->query($sql)) {
                            return true;
                        }
                    } else {
                        $setPart = 'deleted = 0';

                        if (!empty($data) && is_array($data)) {
                            $setArr = array();
                            foreach ($data as $column => $value) {
                                $setArr[] = $this->toDb($column) . " = " . $this->pdo->quote($value);
                            }
                            $setPart .= ', ' . implode(', ', $setArr);
                        }

                        $wherePart =
                            $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id) . "
                            AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($relEntity->id) . "
                            ";

                        if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
                            foreach ($relOpt['conditions'] as $f => $v) {
                                $wherePart .= " AND " . $this->toDb($f) . " = " . $this->pdo->quote($v);
                            }
                        }

                        $sql = $this->composeUpdateQuery($relTable, $setPart, $wherePart);
                        if ($this->pdo->query($sql)) {
                            return true;
                        }
                    }
                } else {
                    return false;
                }
            break;
        }
    }

    public function removeRelation(IEntity $entity, $relationName, $id = null, $all = false, IEntity $relEntity = null)
    {
        if (!is_null($relEntity)) {
            $id = $relEntity->id;
        }

        if (empty($id) && empty($all) || empty($relationName)) {
            return false;
        }

        $relOpt = $entity->relations[$relationName];

        if (!isset($relOpt['entity']) || !isset($relOpt['type'])) {
            throw new \LogicException("Not appropriate defenition for relationship {$relationName} in " . $entity->getEntityName() . " entity");
        }

        $relType = $relOpt['type'];

        $className = (!empty($relOpt['class'])) ? $relOpt['class'] : $relOpt['entity'];

        if (is_null($relEntity)) {
            $relEntity = $this->entityFactory->create($className);
            if (!$relEntity) {
                return null;
            }
            $relEntity->id = $id;
        }

        $keySet = $this->query->getKeys($entity, $relationName);

        switch ($relType) {

            case IEntity::BELONGS_TO:
                /*$foreignKey = $keySet['foreignKey'];
                $relEntity->$foreignKey = null;
                $this->
                break;*/

            case IEntity::HAS_ONE:
                return false;


            case IEntity::HAS_MANY:
            case IEntity::HAS_CHILDREN:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];

                $setPart = $this->toDb($foreignKey) . " = " . "NULL";

                $whereClause = array('deleted' => 0);
                if (empty($all)) {
                    $whereClause['id'] = $id;
                } else {
                    $whereClause[$foreignKey] = $entity->id;
                }

                if ($relType == IEntity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'];
                    $whereClause[$foreignType] = $entity->getEntityName();
                }

                $wherePart = $this->query->getWhere($relEntity, $whereClause);
                $sql = $this->composeUpdateQuery($this->toDb($relEntity->getEntityName()), $setPart, $wherePart);
                if ($this->pdo->query($sql)) {
                    return true;
                }
                break;

            case IEntity::MANY_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $relTable = $this->toDb($relOpt['relationName']);

                $setPart = 'deleted = 1';
                $wherePart = $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id);


                if (empty($all)) {
                    $wherePart .= " AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($id) . "";
                }

                if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
                    foreach ($relOpt['conditions'] as $f => $v) {
                        $wherePart .= " AND " . $this->toDb($f) . " = " . $this->pdo->quote($v);
                    }
                }

                $sql = $this->composeUpdateQuery($relTable, $setPart, $wherePart);

                if ($this->pdo->query($sql)) {
                    return true;
                }
                break;
        }
    }

    public function removeAllRelations(IEntity $entity, $relationName)
    {
        $this->removeRelation($entity, $relationName, null, true);
    }

    protected function quote($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } else {
            return $this->pdo->quote($value);
        }
    }

    public function insert(IEntity $entity)
    {
        $dataArr = $this->toArray($entity);

        $fieldArr = array();
        $valArr = array();
        foreach ($dataArr as $field => $value) {
            $fieldArr[] = $this->toDb($field);

            $type = $entity->fields[$field]['type'];

            $value = $this->prepareValueForInsert($type, $value);

            $valArr[] = $this->quote($value);
        }
        $fieldsPart = "`" . implode("`, `", $fieldArr) . "`";
        $valuesPart = implode(", ", $valArr);

        $sql = $this->composeInsertQuery($this->toDb($entity->getEntityName()), $fieldsPart, $valuesPart);

        if ($this->pdo->query($sql)) {
            return $entity->id;
        }

        return false;
    }

    public function update(IEntity $entity)
    {
        $dataArr = $this->toArray($entity);

        $setArr = array();
        foreach ($dataArr as $field => $value) {
            if ($field == 'id') {
                continue;
            }
            $type = $entity->fields[$field]['type'];

            if ($type == IEntity::FOREIGN) {
                continue;
            }

            if ($entity->getFetched($field) === $value && $type != IEntity::JSON_ARRAY && $type != IEntity::JSON_OBJECT) {
                continue;
            }

            $value = $this->prepareValueForInsert($type, $value);

            $setArr[] = "`" . $this->toDb($field) . "` = " . $this->quote($value);
        }
        if (count($setArr) == 0) {
            return $entity->id;
        }

        $setPart = implode(', ', $setArr);
        $wherePart = $this->query->getWhere($entity, array('id' => $entity->id, 'deleted' => 0));

        $sql = $this->composeUpdateQuery($this->toDb($entity->getEntityName()), $setPart, $wherePart);

        if ($this->pdo->query($sql)) {
            return $entity->id;
        }

        return false;
    }

    protected function prepareValueForInsert($type, $value) {
        if ($type == IEntity::JSON_ARRAY && is_array($value)) {
            $value = json_encode($value);
        } else if ($type == IEntity::JSON_OBJECT && (is_array($value) || $value instanceof \stdClass)) {
            $value = json_encode($value);
        }

        if (is_bool($value)) {
            $value = (int) $value;
        }
        return $value;
    }

    public function deleteFromDb($entityName, $id)
    {
        if (!empty($entityName) && !empty($id)) {
            $table = $this->toDb($entityName);
            $sql = "DELETE FROM `{$table}` WHERE id = " . $this->quote($id);
            if ($this->pdo->query($sql)) {
                return true;
            }
        }
    }

    public function delete(IEntity $entity)
    {
        $entity->set('deleted', true);
        return $this->update($entity);
    }

    protected function toArray(IEntity $entity, $onlyStorable = true)
    {
        $arr = array();
        foreach ($entity->fields as $field => $fieldDefs) {
            if ($entity->has($field)) {
                if ($onlyStorable) {
                    if (!empty($fieldDefs['notStorable']) || isset($fieldDefs['source']) && $fieldDefs['source'] != 'db')
                        continue;
                    if ($fieldDefs['type'] == IEntity::FOREIGN)
                        continue;
                }
                $arr[$field] = $entity->get($field);
            }
        }
        return $arr;
    }

    protected function fromRow(IEntity $entity, $data)
    {
        $entity->set($data);
        return $entity;
    }

    protected function getMMJoin(IEntity $entity, $relationName, $keySet = false, $conditions = array())
    {
        $relOpt = $entity->relations[$relationName];

        if (empty($keySet)) {
            $keySet = $this->query->getKeys($entity, $relationName);
        }

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];
        $nearKey = $keySet['nearKey'];
        $distantKey = $keySet['distantKey'];

        $relTable = $this->toDb($relOpt['relationName']);
        $distantTable = $this->toDb($relOpt['entity']);

        $join =
            "JOIN `{$relTable}` ON {$distantTable}." . $this->toDb($foreignKey) . " = {$relTable}." . $this->toDb($distantKey)
            . " AND "
            . "{$relTable}." . $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->get($key))
            . " AND "
            . "{$relTable}.deleted = " . $this->pdo->quote(0) . "";

        if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
            foreach ($relOpt['conditions'] as $f => $v) {
                $join .= " AND {$relTable}." . $this->toDb($f) . " = " . $this->pdo->quote($v);
            }
        }

        if (!empty($conditions) && is_array($conditions)) {
            foreach ($conditions as $f => $v) {
                $join .= " AND {$relTable}." . $this->toDb($f) . " = " . $this->pdo->quote($v);
            }
        }

        return $join;
    }


    protected function composeInsertQuery($table, $fields, $values)
    {
        $sql = "INSERT INTO `{$table}`";
        $sql .= " ({$fields})";
        if (!is_array($values)) {
            $sql .= " VALUES ({$values})";
        } else {
            $sql .= " VALUES (" . implode("), (", $values) . ")";
        }

        return $sql;
    }

    protected function composeUpdateQuery($table, $set, $where)
    {
        $sql = "UPDATE `{$table}` SET {$set} WHERE {$where}";

        return $sql;
    }

    abstract protected function toDb($field);

    public function setReturnCollection($returnCollection)
    {
        $this->returnCollection = $returnCollection;
    }

    public function setCollectionClass($collectionClass)
    {
        $this->collectionClass = $collectionClass;
    }
}


