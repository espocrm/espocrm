<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\ORM\DB;

use Espo\ORM\Entity;
use Espo\ORM\IEntity;
use Espo\ORM\EntityFactory;
use Espo\ORM\Metadata;
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

    protected $metadata;

    protected $fieldsMapCache = [];

    protected $aliasesCache = [];

    protected $returnCollection = false;

    protected $collectionClass = "\\Espo\\ORM\\EntityCollection";

    protected $sthColletctionClass = "\\Espo\\ORM\\Sth2Collection";

    public function __construct(PDO $pdo, \Espo\ORM\EntityFactory $entityFactory, Query\Base $query, Metadata $metadata) {
        $this->pdo = $pdo;
        $this->query = $query;
        $this->entityFactory = $entityFactory;
        $this->metadata = $metadata;
    }

    public function selectById(IEntity $entity, $id, ?array $params = null) : ?IEntity
    {
        $params = $params ?? [];

        if (!array_key_exists('whereClause', $params)) {
            $params['whereClause'] = [];
        }

        $params['whereClause']['id'] = $id;

        $sql = $this->query->createSelectQuery($entity->getEntityType(), $params);

        $ps = $this->pdo->query($sql);

        if ($ps) {
            foreach ($ps as $row) {
                $entity = $this->fromRow($entity, $row);
                $entity->setAsFetched();
                return $entity;
            }
        }
        return null;
    }

    public function count(IEntity $entity, ?array $params = null)
    {
        return $this->aggregate($entity, $params, 'COUNT', 'id');
    }

    public function max(IEntity $entity, ?array $params, string $attribute)
    {
        return $this->aggregate($entity, $params, 'MAX', $attribute);
    }

    public function min(IEntity $entity, ?array $params, string $attribute)
    {
        return $this->aggregate($entity, $params, 'MIN', $attribute);
    }

    public function sum(IEntity $entity, ?array $params, string $attribute)
    {
        return $this->aggregate($entity, $params, 'SUM', $attribute);
    }

    public function select(IEntity $entity, ?array $params = null)
    {
        $sql = $this->query->createSelectQuery($entity->getEntityType(), $params);

        return $this->selectByQuery($entity, $sql, $params);
    }

    public function selectByQuery(IEntity $entity, $sql, ?array $params = null)
    {
        $params = $params ?? [];

        if ($params['returnSthCollection'] ?? false) {
            $collection = $this->createSthCollection($entity->getEntityType());
            $collection->setQuery($sql);
            return $collection;
        }

        $dataArr = [];
        $ps = $this->pdo->query($sql);
        if ($ps) {
            $dataArr = $ps->fetchAll();
        }

        if ($this->returnCollection) {
            $collectionClass = $this->collectionClass;
            $collection = new $collectionClass($dataArr, $entity->getEntityType(), $this->entityFactory);
            $collection->setAsFetched();
            return $collection;
        } else {
            return $dataArr;
        }
    }

    protected function createSthCollection(string $entityType)
    {
        return new $this->sthColletctionClass($entityType, $this->entityFactory, $this->query, $this->pdo);;
    }

    public function aggregate(IEntity $entity, ?array $params, string $aggregation, string $aggregationBy)
    {
        if (empty($aggregation) || !$entity->hasAttribute($aggregationBy)) {
            return false;
        }

        $params = $params ?? [];

        $params['aggregation'] = $aggregation;
        $params['aggregationBy'] = $aggregationBy;

        $sql = $this->query->createSelectQuery($entity->getEntityType(), $params);

        $ps = $this->pdo->query($sql);

        if ($ps) {
            foreach ($ps as $row) {
                return $row['AggregateValue'];
            }
        }
        return false;
    }

    public function selectRelated(IEntity $entity, $relationName, $params = [], $returnTotalCount = false)
    {
        $relDefs = $entity->relations[$relationName];

        if (!isset($relDefs['type'])) {
            throw new \LogicException("Missing 'type' in definition for relationship {$relationName} in " . $entity->getEntityType() . " entity");
        }

        if ($relDefs['type'] !== IEntity::BELONGS_TO_PARENT) {
            if (!isset($relDefs['entity'])) {
                throw new \LogicException("Missing 'entity' in definition for relationship {$relationName} in " . $entity->getEntityType() . " entity");
            }

            $relEntityName = (!empty($relDefs['class'])) ? $relDefs['class'] : $relDefs['entity'];
            $relEntity = $this->entityFactory->create($relEntityName);

            if (!$relEntity) {
                return null;
            }
        }

        if ($returnTotalCount) {
            $params['aggregation'] = 'COUNT';
            $params['aggregationBy'] = 'id';
        }

        if (empty($params['whereClause'])) {
            $params['whereClause'] = [];
        }

        $relType = $relDefs['type'];

        $keySet = $this->query->getKeys($entity, $relationName);

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        switch ($relType) {
            case IEntity::BELONGS_TO:
                $params['whereClause'][$foreignKey] = $entity->get($key);
                $params['offset'] = 0;
                $params['limit'] = 1;

                $sql = $this->query->createSelectQuery($relEntity->getEntityType(), $params);

                $ps = $this->pdo->query($sql);

                if ($ps) {
                    foreach ($ps as $row) {
                        if (!$returnTotalCount) {
                            $relEntity = $this->fromRow($relEntity, $row);
                            $relEntity->setAsFetched();
                            return $relEntity;
                        } else {
                            return $row['AggregateValue'];
                        }
                    }
                }
                return null;

            case IEntity::HAS_MANY:
            case IEntity::HAS_CHILDREN:
            case IEntity::HAS_ONE:
                $params['whereClause'][$foreignKey] = $entity->get($key);

                if ($relType == IEntity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'];
                    $params['whereClause'][$foreignType] = $entity->getEntityType();
                }

                if ($relType == IEntity::HAS_ONE) {
                    $params['offset'] = 0;
                    $params['limit'] = 1;
                }

                if (!empty($relDefs['conditions']) && is_array($relDefs['conditions'])) {
                    $params['whereClause'][] = $relDefs['conditions'];
                }

                $resultDataList = [];

                $sql = $this->query->createSelectQuery($relEntity->getEntityType(), $params);

                if (!$returnTotalCount) {
                    if (!empty($params['returnSthCollection']) && $relType !== IEntity::HAS_ONE) {
                        $collection = $this->createSthCollection($relEntity->getEntityType());
                        $collection->setQuery($sql);
                        return $collection;
                    }
                }

                $ps = $this->pdo->query($sql);

                if ($ps) {
                    if (!$returnTotalCount) {
                        $resultDataList = $ps->fetchAll();
                    } else {
                        foreach ($ps as $row) {
                            return $row['AggregateValue'];
                        }
                    }
                }

                if ($relType == IEntity::HAS_ONE) {
                    if (count($resultDataList)) {
                        $relEntity = $this->fromRow($relEntity, $resultDataList[0]);
                        $relEntity->setAsFetched();
                        return $relEntity;
                    }
                    return null;
                } else {
                    if ($this->returnCollection) {
                        $collectionClass = $this->collectionClass;
                        $collection = new $collectionClass($resultDataList, $relEntity->getEntityType(), $this->entityFactory);
                        $collection->setAsFetched();
                        return $collection;
                    } else {
                        return $resultDataList;
                    }
                }

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

                $params['relationName'] = $relDefs['relationName'];

                $sql = $this->query->createSelectQuery($relEntity->getEntityType(), $params);

                $resultDataList = [];

                if (!$returnTotalCount) {
                    if (!empty($params['returnSthCollection'])) {
                        $collection = $this->createSthCollection($relEntity->getEntityType());
                        $collection->setQuery($sql);
                        return $collection;
                    }
                }

                $ps = $this->pdo->query($sql);

                if ($ps) {
                    if (!$returnTotalCount) {
                        $resultDataList = $ps->fetchAll();
                    } else {
                        foreach ($ps as $row) {
                            return $row['AggregateValue'];
                        }
                    }
                }
                if ($this->returnCollection) {
                    $collectionClass = $this->collectionClass;
                    $collection = new $collectionClass($resultDataList, $relEntity->getEntityType(), $this->entityFactory);
                    $collection->setAsFetched();
                    return $collection;
                } else {
                    return $resultDataList;
                }
            case IEntity::BELONGS_TO_PARENT:
                $foreignEntityType = $entity->get($keySet['typeKey']);
                $foreignEntityId = $entity->get($key);
                if (!$foreignEntityType || !$foreignEntityId) {
                    return null;
                }
                $params['whereClause'][$foreignKey] = $foreignEntityId;
                $params['offset'] = 0;
                $params['limit'] = 1;

                $relEntity = $this->entityFactory->create($foreignEntityType);

                $sql = $this->query->createSelectQuery($foreignEntityType, $params);

                $ps = $this->pdo->query($sql);

                if ($ps) {
                    foreach ($ps as $row) {
                        if (!$returnTotalCount) {
                            $relEntity = $this->fromRow($relEntity, $row);
                            return $relEntity;
                        } else {
                            return $row['AggregateValue'];
                        }
                    }
                }
                return null;
        }

        return false;
    }


    public function countRelated(IEntity $entity, $relationName, $params = [])
    {
        return $this->selectRelated($entity, $relationName, $params, true);
    }

    public function relate(IEntity $entityFrom, $relationName, IEntity $entityTo, $data = null)
    {
        return $this->addRelation($entityFrom, $relationName, null, $entityTo, $data);
    }

    public function unrelate(IEntity $entityFrom, $relationName, IEntity $entityTo)
    {
        return $this->removeRelation($entityFrom, $relationName, null, false, $entityTo);
    }

    public function updateRelation(IEntity $entity, $relationName, $id = null, $columnData)
    {
        if (empty($id) || empty($relationName)) {
            return false;
        }

        if (empty($columnData)) return;

        $relDefs = $entity->relations[$relationName];
        $keySet = $this->query->getKeys($entity, $relationName);

        $relType = $relDefs['type'];

        switch ($relType) {
            case IEntity::MANY_MANY:
                $relTable = $this->toDb($relDefs['relationName']);
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $setArr = [];
                foreach ($columnData as $column => $value) {
                    $setArr[] = "`".$this->toDb($column) . "` = " . $this->quote($value);
                }
                if (empty($setArr)) {
                    return true;
                }
                $setPart = implode(', ', $setArr);

                $wherePart =
                    $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id) . "
                    AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($id) . " AND deleted = 0
                    ";

                if (!empty($relDefs['conditions']) && is_array($relDefs['conditions'])) {
                    foreach ($relDefs['conditions'] as $f => $v) {
                        $wherePart .= " AND " . $this->toDb($f) . " = " . $this->pdo->quote($v);
                    }
                }

                $sql = $this->composeUpdateQuery($relTable, $setPart, $wherePart);

                if ($this->pdo->query($sql)) {
                    return true;
                }
        }
    }

    public function massRelate(IEntity $entity, $relationName, array $params = [])
    {
        if (!$entity) {
            return false;
        }
        $id = $entity->id;

        if (empty($id) || empty($relationName)) {
            return false;
        }

        $relDefs = $entity->relations[$relationName];

        if (!isset($relDefs['entity']) || !isset($relDefs['type'])) {
            throw new \LogicException("Not appropriate definition for relationship {$relationName} in " . $entity->getEntityType() . " entity");
        }

        $relType = $relDefs['type'];

        $className = (!empty($relDefs['class'])) ? $relDefs['class'] : $relDefs['entity'];
        $relEntity = $this->entityFactory->create($className);
        $foreignEntityType = $relEntity->getEntityType();

        $keySet = $this->query->getKeys($entity, $relationName);

        switch ($relType) {
            case IEntity::MANY_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $relTable = $this->toDb($relDefs['relationName']);

                $fieldsPart = $this->toDb($nearKey);
                $valuesPart = $this->pdo->quote($entity->id);

                $valueList = [];
                $valueList[] = $entity->id;

                if (!empty($relDefs['conditions']) && is_array($relDefs['conditions'])) {
                    foreach ($relDefs['conditions'] as $f => $v) {
                        $fieldsPart .= ", " . $this->toDb($f);
                        $valuesPart .= ", " . $this->pdo->quote($v);
                        $valueList[] = $v;
                    }
                }
                $fieldsPart .= ", " . $this->toDb($distantKey);

                $params['select'] = [];
                foreach ($valueList as $value) {
                   $params['select'][] = ['VALUE:' . $value, $value];
                }

                $params['select'][] = 'id';

                $subSql = $this->query->createSelectQuery($foreignEntityType, $params);

                $sql = "INSERT INTO `".$relTable."` (".$fieldsPart.") (".$subSql.") ON DUPLICATE KEY UPDATE deleted = '0'";

                if ($this->runQuery($sql, true)) {
                    return true;
                }

                break;
        }
    }

    public function runQuery($query, $rerunIfDeadlock = false)
    {
        try {
            return $this->pdo->query($query);
        } catch (\Exception $e) {
            if ($rerunIfDeadlock) {
                if ($e->errorInfo[0] == 40001 && $e->errorInfo[1] == 1213) {
                    return $this->pdo->query($query);
                } else {
                    throw $e;
                }
            }
        }
    }

    public function addRelation(IEntity $entity, string $relationName, $id = null, $relEntity = null, $data = null)
    {
        if (!is_null($relEntity)) {
            $id = $relEntity->id;
        }

        if (empty($id) || empty($relationName)) {
            return false;
        }

        if (!$entity->hasRelation($relationName)) return false;

        $relDefs = $entity->relations[$relationName];

        $relType = $entity->getRelationType($relationName);
        $foreignEntityType = $entity->getRelationParam($relationName, 'entity');

        if (!$relType || !$foreignEntityType && $relType !== IEntity::BELONGS_TO_PARENT) {
            throw new \LogicException("Not appropriate definition for relationship {$relationName} in " . $entity->getEntityType() . " entity");
        }

        $className = (!empty($relDefs['class'])) ? $relDefs['class'] : $foreignEntityType;

        if (is_null($relEntity)) {
            $relEntity = $this->entityFactory->create($className);
            if (!$relEntity) {
                return false;
            }
            $relEntity->id = $id;
        }

        $keySet = $this->query->getKeys($entity, $relationName);

        switch ($relType) {
            case IEntity::BELONGS_TO:
                $key = $relationName . 'Id';
                $setPart = $this->toDb($key) . " = " . $this->pdo->quote($relEntity->id);
                $wherePart = $this->query->getWhere($entity, ['id' => $id, 'deleted' => 0]);

                $entity->set([
                    $key => $relEntity->id
                ]);

                $sql = $this->composeUpdateQuery(
                    $this->toDb($entity->getEntityType()),
                    $setPart,
                    $wherePart
                );

                if ($this->pdo->query($sql)) {
                    return true;
                }
                return false;

            case IEntity::BELONGS_TO_PARENT:
                $key = $relationName . 'Id';
                $typeKey = $relationName . 'Type';

                $entity->set([
                    $key => $relEntity->id,
                    $typeKey => $relEntity->getEntityType()
                ]);

                $setPart =
                    $this->toDb($key) . " = " . $this->pdo->quote($relEntity->id) . ', ' .
                    $this->toDb($typeKey) . " = " . $this->pdo->quote($relEntity->getEntityType());
                $wherePart = $this->query->getWhere($entity, ['id' => $id, 'deleted' => 0]);

                $sql = $this->composeUpdateQuery(
                    $this->toDb($entity->getEntityType()),
                    $setPart,
                    $wherePart
                );

                if ($this->pdo->query($sql)) {
                    return true;
                }
                return false;

            case IEntity::HAS_ONE:
                return false;

            case IEntity::HAS_CHILDREN:
            case IEntity::HAS_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];

                if ($this->count($relEntity, ['whereClause' => ['id' => $id]]) > 0) {

                    $setPart = $this->toDb($foreignKey) . " = " . $this->pdo->quote($entity->get($key));

                    if ($relType == IEntity::HAS_CHILDREN) {
                        $foreignType = $keySet['foreignType'];
                        $setPart .= ", " . $this->toDb($foreignType) . " = " . $this->pdo->quote($entity->getEntityType());
                    }

                    $wherePart = $this->query->getWhere($relEntity, ['id' => $id, 'deleted' => 0]);
                    $sql = $this->composeUpdateQuery($this->toDb($relEntity->getEntityType()), $setPart, $wherePart);

                    if ($this->pdo->query($sql)) {
                        return true;
                    }
                    return false;
                } else {
                    return false;
                }
                break;

            case IEntity::MANY_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                if ($this->count($relEntity, ['whereClause' => ['id' => $id]]) > 0) {
                    $relTable = $this->toDb($relDefs['relationName']);

                    $wherePart =
                        $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id) . " ".
                        "AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($relEntity->id);
                    if (!empty($relDefs['conditions']) && is_array($relDefs['conditions'])) {
                        foreach ($relDefs['conditions'] as $f => $v) {
                            $wherePart .= " AND " . $this->toDb($f) . " = " . $this->pdo->quote($v);
                        }
                    }

                    $sql = $this->query->composeSelectQuery($relTable, '*', '', $wherePart);

                    $ps = $this->pdo->query($sql);

                    if ($ps->rowCount() == 0) {
                        $fieldsPart = $this->toDb($nearKey) . ", " . $this->toDb($distantKey);
                        $valuesPart = $this->pdo->quote($entity->id) . ", " . $this->pdo->quote($relEntity->id);

                        if (!empty($relDefs['conditions']) && is_array($relDefs['conditions'])) {
                            foreach ($relDefs['conditions'] as $f => $v) {
                                $fieldsPart .= ", " . $this->toDb($f);
                                $valuesPart .= ", " . $this->quote($v);
                            }
                        }

                        if (!empty($data) && is_array($data)) {
                            foreach ($data as $column => $columnValue) {
                                $fieldsPart .= ", " . $this->toDb($column);
                                $valuesPart .= ", " . $this->quote($columnValue);
                            }
                        }

                        $sql = $this->composeInsertQuery($relTable, $fieldsPart, $valuesPart);

                        $sql .= " ON DUPLICATE KEY UPDATE deleted = '0'";

                        if (!empty($data) && is_array($data)) {
                            $setArr = [];
                            foreach ($data as $column => $value) {
                                $setArr[] = $this->toDb($column) . " = " . $this->quote($value);
                            }
                            $sql .= ', ' . implode(', ', $setArr);
                        }

                        if ($this->runQuery($sql, true)) {
                            return true;
                        }

                    } else {
                        $setPart = 'deleted = 0';

                        if (!empty($data) && is_array($data)) {
                            $setArr = [];
                            foreach ($data as $column => $value) {
                                $setArr[] = $this->toDb($column) . " = " . $this->quote($value);
                            }
                            $setPart .= ', ' . implode(', ', $setArr);
                        }

                        $wherePart =
                            $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id) . "
                            AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($relEntity->id) . "
                            ";

                        if (!empty($relDefs['conditions']) && is_array($relDefs['conditions'])) {
                            foreach ($relDefs['conditions'] as $f => $v) {
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

        return false;
    }

    public function removeRelation(IEntity $entity, string $relationName, $id = null, $all = false, IEntity $relEntity = null)
    {
        if (!is_null($relEntity)) {
            $id = $relEntity->id;
        }

        if (empty($id) && empty($all) || empty($relationName)) {
            return false;
        }

        if (!$entity->hasRelation($relationName)) return false;

        $relDefs = $entity->relations[$relationName];

        $relType = $entity->getRelationType($relationName);
        $foreignEntityType = $entity->getRelationParam($relationName, 'entity');

        if (!$relType || !$foreignEntityType && $relType !== IEntity::BELONGS_TO_PARENT) {
            throw new \LogicException("Not appropriate definition for relationship {$relationName} in " . $entity->getEntityType() . " entity");
        }

        $className = (!empty($relDefs['class'])) ? $relDefs['class'] : $foreignEntityType;

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
                $key = $relationName . 'Id';
                $setPart = $this->toDb($key) . " = " . $this->quote(null);
                $wherePart = $this->query->getWhere($entity, ['id' => $id, 'deleted' => 0]);

                $entity->set([
                    $key => null
                ]);

                $sql = $this->composeUpdateQuery(
                    $this->toDb($entity->getEntityType()),
                    $setPart,
                    $wherePart
                );

                if ($this->pdo->query($sql)) {
                    return true;
                }
                return false;

            case IEntity::BELONGS_TO_PARENT:
                $key = $relationName . 'Id';
                $typeKey = $relationName . 'Type';

                $entity->set([
                    $key => null,
                    $typeKey => null
                ]);

                $setPart =
                    $this->toDb($key) . " = " . $this->quote(null) . ', ' .
                    $this->toDb($typeKey) . " = " . $this->quote(null);
                $wherePart = $this->query->getWhere($entity, ['id' => $id, 'deleted' => 0]);

                $sql = $this->composeUpdateQuery(
                    $this->toDb($entity->getEntityType()),
                    $setPart,
                    $wherePart
                );

                if ($this->pdo->query($sql)) {
                    return true;
                }
                return false;

            case IEntity::HAS_ONE:
                return false;

            case IEntity::HAS_MANY:
            case IEntity::HAS_CHILDREN:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];

                $setPart = $this->toDb($foreignKey) . " = " . "NULL";

                $whereClause = ['deleted' => 0];
                if (empty($all)) {
                    $whereClause['id'] = $id;
                } else {
                    $whereClause[$foreignKey] = $entity->id;
                }

                if ($relType == IEntity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'];
                    $whereClause[$foreignType] = $entity->getEntityType();
                }

                $wherePart = $this->query->getWhere($relEntity, $whereClause);
                $sql = $this->composeUpdateQuery($this->toDb($relEntity->getEntityType()), $setPart, $wherePart);
                if ($this->pdo->query($sql)) {
                    return true;
                }
                break;

            case IEntity::MANY_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $relTable = $this->toDb($relDefs['relationName']);

                $setPart = 'deleted = 1';
                $wherePart = $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->id);


                if (empty($all)) {
                    $wherePart .= " AND " . $this->toDb($distantKey) . " = " . $this->pdo->quote($id) . "";
                }

                if (!empty($relDefs['conditions']) && is_array($relDefs['conditions'])) {
                    foreach ($relDefs['conditions'] as $f => $v) {
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

    public function removeAllRelations(IEntity $entity, string $relationName)
    {
        $this->removeRelation($entity, $relationName, null, true);
    }

    protected function quote($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } else if (is_bool($value)) {
            return $value ? '1' : '0';
        } else {
            return $this->pdo->quote($value);
        }
    }

    public function insert(IEntity $entity)
    {
        $dataArr = $this->toValueMap($entity);

        $fieldArr = [];
        $valArr = [];
        foreach ($dataArr as $field => $value) {
            $fieldArr[] = $this->toDb($field);

            $type = $entity->fields[$field]['type'];

            $value = $this->prepareValueForInsert($type, $value);

            $valArr[] = $this->quote($value);
        }
        $fieldsPart = "`" . implode("`, `", $fieldArr) . "`";
        $valuesPart = implode(", ", $valArr);

        $sql = $this->composeInsertQuery($this->toDb($entity->getEntityType()), $fieldsPart, $valuesPart);

        if ($this->pdo->query($sql)) {
            return $entity->id;
        }

        return false;
    }

    public function update(IEntity $entity)
    {
        $valueMap = $this->toValueMap($entity);

        $setArr = [];

        foreach ($valueMap as $attribute => $value) {
            if ($attribute == 'id') {
                continue;
            }
            $type = $entity->getAttributeType($attribute);

            if ($type == IEntity::FOREIGN) {
                continue;
            }

            if (!$entity->isAttributeChanged($attribute) && $type !== IEntity::JSON_OBJECT) {
                continue;
            }

            $value = $this->prepareValueForInsert($type, $value);

            $setArr[] = "`" . $this->toDb($attribute) . "` = " . $this->quote($value);
        }

        if (count($setArr) == 0) {
            return $entity->id;
        }

        $setPart = implode(', ', $setArr);
        $wherePart = $this->query->getWhere($entity, ['id' => $entity->id, 'deleted' => 0]);

        $sql = $this->composeUpdateQuery($this->toDb($entity->getEntityType()), $setPart, $wherePart);

        if ($this->pdo->query($sql)) {
            return $entity->id;
        }

        return false;
    }

    protected function prepareValueForInsert($type, $value) {
        if ($type == IEntity::JSON_ARRAY && is_array($value)) {
            $value = json_encode($value, \JSON_UNESCAPED_UNICODE);
        } else if ($type == IEntity::JSON_OBJECT && (is_array($value) || $value instanceof \stdClass)) {
            $value = json_encode($value, \JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value)) {
            $value = (int) $value;
        }
        return $value;
    }

    public function deleteFromDb(string $entityType, $id, $onlyDeleted = false)
    {
        if (empty($entityType) || empty($id)) return false;

        $table = $this->toDb($entityType);
        $sql = "DELETE FROM `{$table}` WHERE id = " . $this->quote($id);
        if ($onlyDeleted) {
            $sql .= " AND deleted = 1";
        }
        if ($this->pdo->query($sql)) {
            return true;
        }

        return false;
    }

    public function restoreDeleted(string $entityType, $id)
    {
        if (empty($entityType) || empty($id)) return false;

        $table = $this->toDb($entityType);
        $sql = "UPDATE `{$table}` SET `deleted` = 0 WHERE id = " . $this->quote($id);
        if ($this->pdo->query($sql)) {
            return true;
        }

        return false;
    }

    public function delete(IEntity $entity)
    {
        $entity->set('deleted', true);
        return $this->update($entity);
    }

    protected function toValueMap(IEntity $entity, $onlyStorable = true)
    {
        $data = [];
        foreach ($entity->getAttributes() as $attribute => $defs) {
            if ($entity->has($attribute)) {
                if ($onlyStorable) {
                    if (
                        !empty($defs['notStorable'])
                        ||
                        !empty($defs['autoincrement'])
                        ||
                        isset($defs['source']) && $defs['source'] != 'db'
                    ) continue;
                    if ($defs['type'] == IEntity::FOREIGN) continue;
                }
                $data[$attribute] = $entity->get($attribute);
            }
        }
        return $data;
    }

    protected function fromRow(IEntity $entity, $data)
    {
        $entity->set($data);
        return $entity;
    }

    protected function getMMJoin(IEntity $entity, $relationName, $keySet = false, $conditions = [])
    {
        $relDefs = $entity->relations[$relationName];

        if (empty($keySet)) {
            $keySet = $this->query->getKeys($entity, $relationName);
        }

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];
        $nearKey = $keySet['nearKey'];
        $distantKey = $keySet['distantKey'];

        $relTable = $this->toDb($relDefs['relationName']);
        $distantTable = $this->toDb($relDefs['entity']);

        $join =
            "JOIN `{$relTable}` ON {$distantTable}." . $this->toDb($foreignKey) . " = {$relTable}." . $this->toDb($distantKey)
            . " AND "
            . "{$relTable}." . $this->toDb($nearKey) . " = " . $this->pdo->quote($entity->get($key))
            . " AND "
            . "{$relTable}.deleted = " . $this->pdo->quote(0) . "";

        $conditions = $conditions ?? [];
        if (!empty($relDefs['conditions']) && is_array($relDefs['conditions'])) {
            $conditions = array_merge($conditions, $relDefs['conditions']);
        }

        if (!empty($conditions)) {
            $conditionsSql = $this->query->buildJoinConditionsStatement($entity, $relTable, $conditions);
            $join .= " AND " . $conditionsSql;
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

    abstract protected function toDb($attribute);

    public function setReturnCollection($returnCollection)
    {
        $this->returnCollection = $returnCollection;
    }

    public function setCollectionClass(string $collectionClass)
    {
        $this->collectionClass = $collectionClass;
    }
}
