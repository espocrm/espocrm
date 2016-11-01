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

namespace Espo\ORM\DB\Query;

use Espo\ORM\Entity;
use Espo\ORM\IEntity;
use Espo\ORM\EntityFactory;
use PDO;

abstract class Base
{
    protected static $selectParamList = array(
        'select',
        'whereClause',
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
        'aggregation',
        'aggregationBy',
        'groupBy',
        'skipTextColumns',
        'maxTextColumnsLength'
    );

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

    protected $entityFactory;

    protected $pdo;

    protected $fieldsMapCache = array();

    protected $aliasesCache = array();

    protected $seedCache = array();

    public function __construct(PDO $pdo, EntityFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
        $this->pdo = $pdo;
    }

    protected function getSeed($entityName)
    {
        if (empty($this->seedCache[$entityName])) {
            $this->seedCache[$entityName] = $this->entityFactory->create($entityName);
        }
        return $this->seedCache[$entityName];
    }

    public function createSelectQuery($entityName, array $params = array(), $deleted = false)
    {
        $entity = $this->getSeed($entityName);

        foreach (self::$selectParamList as $k) {
            $params[$k] = array_key_exists($k, $params) ? $params[$k] : null;
        }

        $whereClause = $params['whereClause'];
        if (empty($whereClause)) {
            $whereClause = array();
        }

        if (!$deleted) {
            $whereClause = $whereClause + array('deleted' => 0);
        }

        if (empty($params['joins'])) {
            $params['joins'] = array();
        }
        if (empty($params['leftJoins'])) {
            $params['leftJoins'] = array();
        }
        if (empty($params['customJoin'])) {
            $params['customJoin'] = '';
        }

        $wherePart = $this->getWhere($entity, $whereClause, 'AND', $params);

        if (empty($params['aggregation'])) {
            $selectPart = $this->getSelect($entity, $params['select'], $params['distinct'], $params['skipTextColumns'], $params['maxTextColumnsLength']);
            $orderPart = $this->getOrder($entity, $params['orderBy'], $params['order']);

            if (!empty($params['additionalColumns']) && is_array($params['additionalColumns']) && !empty($params['relationName'])) {
                foreach ($params['additionalColumns'] as $column => $field) {
                    $selectPart .= ", `" . $this->toDb($this->sanitize($params['relationName'])) . "`." . $this->toDb($this->sanitize($column)) . " AS `{$field}`";
                }
            }

            if (!empty($params['additionalSelectColumns']) && is_array($params['additionalSelectColumns'])) {
                foreach ($params['additionalSelectColumns'] as $column => $field) {
                    $selectPart .= ", " . $column . " AS `{$field}`";
                }
            }

        } else {
            $aggDist = false;
            if ($params['distinct'] && $params['aggregation'] == 'COUNT') {
                $aggDist = true;
            }
            $selectPart = $this->getAggregationSelect($entity, $params['aggregation'], $params['aggregationBy'], $aggDist);
        }

        $joinsPart = $this->getBelongsToJoins($entity, $params['select'], array_merge($params['joins'], $params['leftJoins']));


        if (!empty($params['customWhere'])) {
            $wherePart .= ' ' . $params['customWhere'];
        }

        if (!empty($params['joins']) && is_array($params['joins'])) {
            // TODO array unique
            $joinsRelated = $this->getJoins($entity, $params['joins'], false, $params['joinConditions']);
            if (!empty($joinsRelated)) {
                if (!empty($joinsPart)) {
                    $joinsPart .= ' ';
                }
                $joinsPart .= $joinsRelated;
            }
        }

        if (!empty($params['leftJoins']) && is_array($params['leftJoins'])) {
            // TODO array unique
            $joinsRelated = $this->getJoins($entity, $params['leftJoins'], true, $params['joinConditions']);
            if (!empty($joinsRelated)) {
                if (!empty($joinsPart)) {
                    $joinsPart .= ' ';
                }
                $joinsPart .= $joinsRelated;
            }
        }

        if (!empty($params['customJoin'])) {
            if (!empty($joinsPart)) {
                $joinsPart .= ' ';
            }
            $joinsPart .= '' . $params['customJoin'] . '';
        }

        $groupByPart = null;
        if (!empty($params['groupBy']) && is_array($params['groupBy'])) {
            $arr = array();
            foreach ($params['groupBy'] as $field) {
                $arr[] = $this->convertComplexExpression($entity, $field);
            }
            $groupByPart = implode(', ', $arr);
        }

        if (empty($params['aggregation'])) {
            $sql = $this->composeSelectQuery($this->toDb($entity->getEntityType()), $selectPart, $joinsPart, $wherePart, $orderPart, $params['offset'], $params['limit'], $params['distinct'], null, $groupByPart);
        } else {
            $sql = $this->composeSelectQuery($this->toDb($entity->getEntityType()), $selectPart, $joinsPart, $wherePart, null, null, null, false, $params['aggregation']);
        }

        return $sql;
    }

    protected function getFunctionPart($function, $part, $entityName, $distinct = false)
    {
        switch ($function) {
            case 'MONTH':
                return "DATE_FORMAT({$part}, '%Y-%m')";
            case 'DAY':
                return "DATE_FORMAT({$part}, '%Y-%m-%d')";
        }
        if ($distinct) {
            $idPart = $this->toDb($entityName) . ".id";
            switch ($function) {
                case 'SUM':
                case 'COUNT':
                    return $function . "({$part}) * COUNT(DISTINCT {$idPart}) / COUNT({$idPart})";
            }
        }
        return $function . '(' . $part . ')';
    }


    protected function convertComplexExpression($entity, $field, $distinct = false)
    {
        $function = null;
        $relName = null;

        $entityType = $entity->getEntityType();

        if (strpos($field, ':')) {
            list($function, $field) = explode(':', $field);
        }
        if (strpos($field, '.')) {
            list($relName, $field) = explode('.', $field);
        }

        if (!empty($function)) {
            $function = preg_replace('/[^A-Za-z0-9_]+/', '', $function);
        }
        if (!empty($relName)) {
            $relName = preg_replace('/[^A-Za-z0-9_]+/', '', $relName);
        }
        if (!empty($field)) {
            $field = preg_replace('/[^A-Za-z0-9_]+/', '', $field);
        }

        $part = $this->toDb($field);
        if ($relName) {
            $part = $relName . '.' . $part;
        } else {
            if (!empty($entity->fields[$field]['select'])) {
                $part = $entity->fields[$field]['select'];
            } else {
                $part = $this->toDb($entityType) . '.' . $part;
            }
        }
        if ($function) {
            $part = $this->getFunctionPart(strtoupper($function), $part, $entityType, $distinct);
        }
        return $part;
    }

    protected function getSelect(IEntity $entity, $fields = null, $distinct = false, $skipTextColumns = false, $maxTextColumnsLength = null)
    {
        $select = "";
        $arr = array();
        $specifiedList = is_array($fields) ? true : false;

        if (empty($fields)) {
            $attributeList = array_keys($entity->fields);
        } else {
            $attributeList = $fields;
            foreach ($attributeList as $i => $attribute) {
                if (!is_array($attribute)) {
                    $attributeList[$i] = $this->sanitizeAlias($attribute);
                }
            }
        }

        foreach ($attributeList as $attribute) {
            $attributeType = null;
            if (is_string($attribute)) {
                $attributeType = $entity->getAttributeType($attribute);
            }
            if ($skipTextColumns) {
                if ($attributeType === $entity::TEXT) {
                    continue;
                }
            }

            if (is_array($attribute) && count($attribute) == 2) {
                if (stripos($attribute[0], 'VALUE:') === 0) {
                    $part = substr($attribute[0], 6);
                    $part = $this->quote($part);
                } else {
                    if (!array_key_exists($attribute[0], $entity->fields)) {
                        $part = $this->convertComplexExpression($entity, $attribute[0], $distinct);
                    } else {
                        $fieldDefs = $entity->fields[$attribute[0]];
                        if (!empty($fieldDefs['select'])) {
                            $part = $fieldDefs['select'];
                        } else {
                            if (!empty($fieldDefs['notStorable'])) {
                                continue;
                            }
                            $part = $this->getFieldPath($entity, $attribute[0]);
                        }
                    }
                }

                $arr[] = $part . ' AS `' . $this->sanitizeAlias($attribute[1]) . '`';
                continue;
            }

            if (array_key_exists($attribute, $entity->fields)) {
                $fieldDefs = $entity->fields[$attribute];
            } else {
                $part = $this->convertComplexExpression($entity, $attribute, $distinct);
                $arr[] = $part . ' AS `' . $attribute . '`';
                continue;
            }

            if (!empty($fieldDefs['select'])) {
                $fieldPath = $fieldDefs['select'];
            } else {
                if (!empty($fieldDefs['notStorable'])) {
                    continue;
                }
                $fieldPath = $this->getFieldPath($entity, $attribute);
                if ($attributeType === $entity::TEXT && $maxTextColumnsLength !== null) {
                    $fieldPath = 'LEFT(' . $fieldPath . ', '. intval($maxTextColumnsLength) . ')';
                }
            }

            $arr[] = $fieldPath . ' AS `' . $attribute . '`';
        }

        $select = implode(', ', $arr);

        return $select;
    }

    protected function getBelongsToJoin(IEntity $entity, $relationName, $r = null)
    {
        if (empty($r)) {
            $r = $entity->relations[$relationName];
        }

        $keySet = $this->getKeys($entity, $relationName);
        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        $alias = $this->getAlias($entity, $relationName);

        if ($alias) {
            return "JOIN `" . $this->toDb($r['entity']) . "` AS `" . $alias . "` ON ".
                   $this->toDb($entity->getEntityType()) . "." . $this->toDb($key) . " = " . $alias . "." . $this->toDb($foreignKey);
        }
    }

    protected function getBelongsToJoins(IEntity $entity, $select = null, $skipList = array())
    {
        $joinsArr = array();

        $relationsToJoin = array();
        if (is_array($select)) {
            foreach ($select as $item) {
                $field = $item;
                if (is_array($item)) {
                    if (count($field) == 0) continue;
                    $field = $item[0];
                }
                if ($entity->getAttributeType($field) == 'foreign' && $entity->getAttributeParam($field, 'relation')) {
                    $relationsToJoin[] = $entity->getAttributeParam($field, 'relation');
                }
            }
        }

        foreach ($entity->relations as $relationName => $r) {
            if ($r['type'] == IEntity::BELONGS_TO) {
                if (!empty($r['noJoin'])) {
                    continue;
                }
                if (in_array($relationName, $skipList)) {
                    continue;
                }

                if (!empty($select)) {
                    if (!in_array($relationName, $relationsToJoin)) {
                        continue;
                    }
                }

                $join = $this->getBelongsToJoin($entity, $relationName, $r);
                if ($join) {
                    $joinsArr[] = 'LEFT ' . $join;
                }
            }
        }

        return implode(' ', $joinsArr);
    }

    protected function getOrderPart(IEntity $entity, $orderBy = null, $order = null) {

        if (!is_null($orderBy)) {
            if (is_array($orderBy)) {
                $arr = array();

                foreach ($orderBy as $item) {
                    if (is_array($item)) {
                        $orderByInternal = $item[0];
                        $orderInternal = null;
                        if (!empty($item[1])) {
                            $orderInternal = $item[1];
                        }
                        $arr[] = $this->getOrderPart($entity, $orderByInternal, $orderInternal);
                    }
                }
                return implode(", ", $arr);
            }

            if (strpos($orderBy, 'LIST:') === 0) {
                list($l, $field, $list) = explode(':', $orderBy);
                $fieldPath = $this->getFieldPathForOrderBy($entity, $field);
                $part = "FIELD(" . $fieldPath . ", '" . implode("', '", array_reverse(explode(",", $list))) . "') DESC";
                return $part;
            }

            if (!is_null($order)) {
                if (is_bool($order)) {
                    $order = $order ? 'DESC' : 'ASC';
                }
                $order = strtoupper($order);
                if (!in_array($order, ['ASC', 'DESC'])) {
                    $order = 'ASC';
                }
            } else {
                $order = 'ASC';
            }

            if (is_integer($orderBy)) {
                return "{$orderBy} " . $order;
            }

            if (!empty($entity->fields[$orderBy])) {
                $fieldDefs = $entity->fields[$orderBy];
            }
            if (!empty($fieldDefs) && !empty($fieldDefs['orderBy'])) {
                $orderPart = str_replace('{direction}', $order, $fieldDefs['orderBy']);
                return "{$orderPart}";
            } else {
                $fieldPath = $this->getFieldPathForOrderBy($entity, $orderBy);

                return "{$fieldPath} " . $order;
            }
        }
    }

    protected function getOrder(IEntity $entity, $orderBy = null, $order = null)
    {
        $orderPart = $this->getOrderPart($entity, $orderBy, $order);
        if ($orderPart) {
            return "ORDER BY " . $orderPart;
        }

    }

    protected function getFieldPathForOrderBy($entity, $orderBy)
    {
        if (strpos($orderBy, '.') !== false) {
            list($alias, $field) = explode('.', $orderBy);
            $fieldPath = $this->sanitize($alias) . '.' . $this->toDb($this->sanitize($field));
        } else {
            $fieldPath = $this->getFieldPath($entity, $orderBy);
        }
        return $fieldPath;
    }

    protected function getAggregationSelect(IEntity $entity, $aggregation, $aggregationBy, $distinct = false)
    {
        if (!isset($entity->fields[$aggregationBy])) {
            return false;
        }

        $aggregation = strtoupper($aggregation);

        $distinctPart = '';
        if ($distinct) {
            $distinctPart = 'DISTINCT ';
        }

        $selectPart = "{$aggregation}({$distinctPart}" . $this->toDb($entity->getEntityType()) . "." . $this->toDb($this->sanitize($aggregationBy)) . ") AS AggregateValue";
        return $selectPart;
    }

    public function quote($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } else {
            return $this->pdo->quote($value);
        }
    }

    public function toDb($field)
    {
        if (array_key_exists($field, $this->fieldsMapCache)) {
            return $this->fieldsMapCache[$field];

        } else {
            $field[0] = strtolower($field[0]);
            $dbField = preg_replace_callback('/([A-Z])/', array($this, 'toDbConversion'), $field);

            $this->fieldsMapCache[$field] = $dbField;
            return $dbField;
        }
    }

    protected function toDbConversion($matches)
    {
        return "_" . strtolower($matches[1]);
    }

    protected function getAlias(IEntity $entity, $relationName)
    {
        if (!isset($this->aliasesCache[$entity->getEntityType()])) {
            $this->aliasesCache[$entity->getEntityType()] = $this->getTableAliases($entity);
        }

        if (isset($this->aliasesCache[$entity->getEntityType()][$relationName])) {
            return $this->aliasesCache[$entity->getEntityType()][$relationName];
        } else {
            return false;
        }
    }

    protected function getTableAliases(IEntity $entity)
    {
        $aliases = array();
        $c = 0;

        $occuranceHash = array();

        foreach ($entity->relations as $name => $r) {
            if ($r['type'] == IEntity::BELONGS_TO) {

                if (!array_key_exists($name, $aliases)) {
                    if (array_key_exists($name, $occuranceHash)) {
                        $occuranceHash[$name]++;
                    } else {
                        $occuranceHash[$name] = 0;
                    }
                    $suffix = '';
                    if ($occuranceHash[$name] > 0) {
                        $suffix .= '_' . $occuranceHash[$name];
                    }

                    $aliases[$name] = $name . $suffix;
                }
            }
        }

        return $aliases;
    }

    protected function getFieldPath(IEntity $entity, $field)
    {
        if (isset($entity->fields[$field])) {
            $f = $entity->fields[$field];

            if (isset($f['source'])) {
                if ($f['source'] != 'db') {
                    return false;
                }
            }

            if (!empty($f['notStorable'])) {
                return false;
            }

            $fieldPath = '';

            switch ($f['type']) {
                case 'foreign':
                    if (isset($f['relation'])) {
                        $relationName = $f['relation'];

                        $foreigh = $f['foreign'];

                        if (is_array($foreigh)) {
                            foreach ($foreigh as $i => $value) {
                                if ($value == ' ') {
                                    $foreigh[$i] = '\' \'';
                                } else {
                                    $foreigh[$i] = $this->getAlias($entity, $relationName) . '.' . $this->toDb($value);
                                }
                            }
                            $fieldPath = 'TRIM(CONCAT(' . implode(', ', $foreigh). '))';
                        } else {
                            $fieldPath = $this->getAlias($entity, $relationName) . '.' . $this->toDb($foreigh);
                        }
                    }
                    break;
                default:
                    $fieldPath = $this->toDb($entity->getEntityType()) . '.' . $this->toDb($this->sanitize($field));
            }

            return $fieldPath;
        }

        return false;
    }

    public function getWhere(IEntity $entity, $whereClause, $sqlOp = 'AND', &$params = array())
    {
        $whereParts = array();

        foreach ($whereClause as $field => $value) {

            if (is_int($field)) {
                $field = 'AND';
            }


            if (!in_array($field, self::$sqlOperators)) {
                $isComplex = false;

                $operator = '=';

                $leftPart = null;

                if (!preg_match('/^[a-z0-9]+$/i', $field)) {
                    foreach (self::$comparisonOperators as $op => $opDb) {
                        if (strpos($field, $op) !== false) {
                            $field = trim(str_replace($op, '', $field));
                            $operator = $opDb;
                            break;
                        }
                    }
                }

                if (strpos($field, '.') !== false || strpos($field, ':') !== false) {
                    $leftPart = $this->convertComplexExpression($entity, $field);
                    $isComplex = true;
                }


                if (empty($isComplex)) {

                    if (!isset($entity->fields[$field])) {
                        continue;
                    }

                    $fieldDefs = $entity->fields[$field];

                    $operatorModified = $operator;
                    if (is_array($value)) {
                        if ($operator == '=') {
                            $operatorModified = 'IN';
                        } else if ($operator == '<>') {
                            $operatorModified = 'NOT IN';
                        }
                    } else if (is_null($value)) {
                        if ($operator == '=') {
                            $operatorModified = 'IS NULL';
                        } else if ($operator == '<>') {
                            $operatorModified = 'IS NOT NULL';
                        }
                    }

                    if (!empty($fieldDefs['where']) && !empty($fieldDefs['where'][$operatorModified])) {
                        $whereSqlPart = '';
                        if (is_string($fieldDefs['where'][$operatorModified])) {
                            $whereSqlPart = $fieldDefs['where'][$operatorModified];
                        } else {
                            if (!empty($fieldDefs['where'][$operatorModified]['sql'])) {
                                $whereSqlPart = $fieldDefs['where'][$operatorModified]['sql'];
                            }
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['leftJoins'])) {
                            foreach ($fieldDefs['where'][$operatorModified]['leftJoins'] as $j) {
                                $jAlias = $this->obtainJoinAlias($j);
                                foreach ($params['leftJoins'] as $jE) {
                                    $jEAlias = $this->obtainJoinAlias($jE);
                                    if ($jEAlias === $jAlias) {
                                        continue 2;
                                    }
                                }
                                $params['leftJoins'][] = $j;
                            }
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['joins'])) {
                            foreach ($fieldDefs['where'][$operatorModified]['joins'] as $j) {
                                $jAlias = $this->obtainJoinAlias($j);
                                foreach ($params['joins'] as $jE) {
                                    $jEAlias = $this->obtainJoinAlias($jE);
                                    if ($jEAlias === $jAlias) {
                                        continue 2;
                                    }
                                }
                                $params['joins'][] = $j;
                            }
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['customJoin'])) {
                            $params['customJoin'] .= ' ' . $fieldDefs['where'][$operatorModified]['customJoin'];
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['distinct'])) {
                            $params['distinct'] = true;
                        }
                        $whereParts[] = str_replace('{value}', $this->stringifyValue($value), $whereSqlPart);
                    } else {
                        if ($fieldDefs['type'] == IEntity::FOREIGN) {
                            $leftPart = '';
                            if (isset($fieldDefs['relation'])) {
                                $relationName = $fieldDefs['relation'];
                                if (isset($entity->relations[$relationName])) {

                                    $alias = $this->getAlias($entity, $relationName);
                                    if ($alias) {
                                        $leftPart = $alias . '.' . $this->toDb($fieldDefs['foreign']);
                                    }
                                }
                            }
                        } else {
                            $leftPart = $this->toDb($entity->getEntityType()) . '.' . $this->toDb($this->sanitize($field));
                        }
                    }
                }

                if (!empty($leftPart)) {
                    if (!is_array($value)) {
                        if (!is_null($value)) {
                            $whereParts[] = $leftPart . " " . $operator . " " . $this->pdo->quote($value);
                        } else {
                            if ($operator == '=') {
                                $whereParts[] = $leftPart . " IS NULL";
                            } else if ($operator == '<>') {
                                $whereParts[] = $leftPart . " IS NOT NULL";
                            }
                        }

                    } else {
                        $valArr = $value;
                        foreach ($valArr as $k => $v) {
                            $valArr[$k] = $this->pdo->quote($valArr[$k]);
                        }
                        $oppose = '';
                        $emptyValue = '0';
                        if ($operator == '<>') {
                            $oppose = 'NOT ';
                            $emptyValue = '1';
                        }
                        if (!empty($valArr)) {
                            $whereParts[] = $leftPart . " {$oppose}IN " . "(" . implode(',', $valArr) . ")";
                        } else {
                            $whereParts[] = "" . $emptyValue;
                        }
                    }
                }
            } else {
                $internalPart = $this->getWhere($entity, $value, $field, $params);
                if ($internalPart) {
                    $whereParts[] = "(" . $internalPart . ")";
                }
            }
        }
        return implode(" " . $sqlOp . " ", $whereParts);
    }

    public function obtainJoinAlias($j)
    {
        if (is_array($j)) {
            if (count($j)) {
                $joinAlias = $j[1];
            } else {
                $joinAlias = $j[0];
            }
        } else {
            $joinAlias = $j;
        }
        return $joinAlias;
    }

    public function stringifyValue($value)
    {
        if (is_array($value)) {
            $arr = [];
            foreach ($value as $v) {
                $arr[] = $this->pdo->quote($v);
            }
            $stringValue = '(' . implode(', ', $arr) . ')';
        } else {
            $stringValue = $this->pdo->quote($value);
        }
        return $stringValue;
    }

    public function sanitize($string)
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string);
    }

    public function sanitizeAlias($string)
    {
        return preg_replace('/[^A-Za-z0-9_:.]+/', '', $string);
    }

    protected function getJoins(IEntity $entity, array $joins, $left = false, $joinConditions = array())
    {
        $joinsArr = array();
        foreach ($joins as $relationName) {
            if (is_array($relationName)) {
                $arr = $relationName;
                $relationName = $arr[0];
                if (count($arr) > 1) {
                    $joinAlias = $arr[1];
                } else {
                    $joinAlias = $relationName;
                }
            } else {
                $joinAlias = $relationName;
            }
            $conditions = array();
            if (!empty($joinConditions[$joinAlias])) {
                $conditions = $joinConditions[$joinAlias];
            }
            if ($joinRelated = $this->getJoinRelated($entity, $relationName, $left, $conditions, $joinAlias)) {
                $joinsArr[] = $joinRelated;
            }
        }
        return implode(' ', $joinsArr);
    }

    protected function getJoinRelated(IEntity $entity, $relationName, $left = false, $conditions = array(), $joinAlias = null)
    {
        $relOpt = $entity->relations[$relationName];
        $keySet = $this->getKeys($entity, $relationName);

        if (!$joinAlias) {
            $joinAlias = $relationName;
        }

        $joinAlias = $this->sanitize($joinAlias);

        $pre = ($left) ? 'LEFT ' : '';

        $type = $relOpt['type'];

        switch ($type) {
            case IEntity::MANY_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $relTable = $this->toDb($relOpt['relationName']);
                $midAlias = lcfirst($this->sanitize($relOpt['relationName']));

                $distantTable = $this->toDb($relOpt['entity']);

                $alias = $joinAlias;

                $midAlias = $alias . 'Middle';

                $join =
                    "{$pre}JOIN `{$relTable}` AS `{$midAlias}` ON {$this->toDb($entity->getEntityType())}." . $this->toDb($key) . " = {$midAlias}." . $this->toDb($nearKey)
                    . " AND "
                    . "{$midAlias}.deleted = " . $this->pdo->quote(0);

                if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
                    $conditions = array_merge($conditions, $relOpt['conditions']);
                }
                foreach ($conditions as $f => $v) {
                    $join .= " AND {$midAlias}." . $this->toDb($this->sanitize($f)) . " = " . $this->pdo->quote($v);
                }

                $join .= " {$pre}JOIN `{$distantTable}` AS `{$alias}` ON {$alias}." . $this->toDb($foreignKey) . " = {$midAlias}." . $this->toDb($distantKey)
                    . " AND "
                    . "{$alias}.deleted = " . $this->pdo->quote(0) . "";

                return $join;

            case IEntity::HAS_MANY:
            case IEntity::HAS_ONE:
                $foreignKey = $keySet['foreignKey'];
                $distantTable = $this->toDb($relOpt['entity']);

                $alias = $joinAlias;

                // TODO conditions

                $join =
                    "{$pre}JOIN `{$distantTable}` AS `{$alias}` ON {$this->toDb($entity->getEntityType())}." . $this->toDb('id') . " = {$alias}." . $this->toDb($foreignKey)
                    . " AND "
                    . "{$alias}.deleted = " . $this->pdo->quote(0) . "";

                return $join;

            case IEntity::HAS_CHILDREN:
                $foreignKey = $keySet['foreignKey'];
                $foreignType = $keySet['foreignType'];
                $distantTable = $this->toDb($relOpt['entity']);

                $alias = $joinAlias;

                $join =
                    "{$pre}JOIN `{$distantTable}` AS `{$alias}` ON " . $this->toDb($entity->getEntityType()) . "." . $this->toDb('id') . " = {$alias}." . $this->toDb($foreignKey)
                    . " AND "
                    . "{$alias}." . $this->toDb($foreignType) . " = " . $this->pdo->quote($entity->getEntityType())
                    . " AND "
                    . "{$alias}.deleted = " . $this->pdo->quote(0) . "";

                return $join;

            case IEntity::BELONGS_TO:
                return $pre . $this->getBelongsToJoin($entity, $relationName);
        }

        return false;
    }

    public function composeSelectQuery($table, $select, $joins = '', $where = '', $order = '', $offset = null, $limit = null, $distinct = null, $aggregation = false, $groupBy = null)
    {
        $sql = "SELECT";

        if (!empty($distinct) && empty($groupBy)) {
            $sql .= " DISTINCT";
        }

        $sql .= " {$select} FROM `{$table}`";

        if (!empty($joins)) {
            $sql .= " {$joins}";
        }

        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        if (!empty($groupBy)) {
            $sql .= " GROUP BY {$groupBy}";
        }

        if (!empty($order)) {
            $sql .= " {$order}";
        }

        if (is_null($offset) && !is_null($limit)) {
            $offset = 0;
        }

        $sql = $this->limit($sql, $offset, $limit);

        return $sql;
    }

    abstract public function limit($sql, $offset, $limit);

    public function getKeys(IEntity $entity, $relationName)
    {
        $relOpt = $entity->relations[$relationName];
        $relType = $relOpt['type'];

        switch ($relType) {
            case IEntity::BELONGS_TO:
                $key = $this->toDb($entity->getEntityType()) . 'Id';
                if (isset($relOpt['key'])) {
                    $key = $relOpt['key'];
                }
                $foreignKey = 'id';
                if(isset($relOpt['foreignKey'])){
                    $foreignKey = $relOpt['foreignKey'];
                }
                return array(
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                );

            case IEntity::HAS_MANY:
            case IEntity::HAS_ONE:
                $key = 'id';
                if (isset($relOpt['key'])){
                    $key = $relOpt['key'];
                }
                $foreignKey = $this->toDb($entity->getEntityType()) . 'Id';
                if (isset($relOpt['foreignKey'])) {
                    $foreignKey = $relOpt['foreignKey'];
                }
                return array(
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                );

            case IEntity::HAS_CHILDREN:
                $key = 'id';
                if (isset($relOpt['key'])){
                    $key = $relOpt['key'];
                }
                $foreignKey = 'parentId';
                if (isset($relOpt['foreignKey'])) {
                    $foreignKey = $relOpt['foreignKey'];
                }
                $foreignType = 'parentType';
                if (isset($relOpt['foreignType'])) {
                    $foreignType = $relOpt['foreignType'];
                }
                return array(
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                    'foreignType' => $foreignType,
                );

            case IEntity::MANY_MANY:
                $key = 'id';
                if(isset($relOpt['key'])){
                    $key = $relOpt['key'];
                }
                $foreignKey = 'id';
                if(isset($relOpt['foreignKey'])){
                    $foreignKey = $relOpt['foreignKey'];
                }
                $nearKey = $this->toDb($entity->getEntityType()) . 'Id';
                $distantKey = $this->toDb($relOpt['entity']) . 'Id';
                if (isset($relOpt['midKeys']) && is_array($relOpt['midKeys'])){
                    $nearKey = $relOpt['midKeys'][0];
                    $distantKey = $relOpt['midKeys'][1];
                }
                return array(
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                    'nearKey' => $nearKey,
                    'distantKey' => $distantKey
                );
            case IEntity::BELONGS_TO_PARENT:
                $key = $relationName . 'Id';
                $typeKey = $relationName . 'Type';
                return array(
                    'key' => $key,
                    'typeKey' => $typeKey,
                    'foreignKey' => 'id'
                );
        }
    }

}

