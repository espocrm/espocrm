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

namespace Espo\ORM\DB\Query;


use Espo\ORM\{
    Entity,
    EntityFactory,
    Metadata,
    DB\Helper,
};

use PDO;
use RuntimeException;

/**
 * Composes SQL queries.
 */
abstract class BaseQuery
{
    protected static $paramList = [
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
        'havingClause',
        'customHaving',
        'skipTextColumns',
        'maxTextColumnsLength',
        'useIndex',
        'withDeleted',
        'update',
    ];

    protected static $sqlOperators = [
        'OR',
        'AND',
    ];

    protected static $comparisonOperators = [
        '!=s' => 'NOT IN',
        '=s' => 'IN',
        '!=' => '<>',
        '!*' => 'NOT LIKE',
        '*' => 'LIKE',
        '>=' => '>=',
        '<=' => '<=',
        '>' => '>',
        '<' => '<',
        '=' => '=',
    ];

    protected $functionList = [
        'COUNT',
        'SUM',
        'AVG',
        'MAX',
        'MIN',
        'DATE',
        'MONTH',
        'DAY',
        'YEAR',
        'WEEK',
        'WEEK_0',
        'WEEK_1',
        'QUARTER',
        'DAYOFMONTH',
        'DAYOFWEEK',
        'DAYOFWEEK_NUMBER',
        'MONTH_NUMBER',
        'DATE_NUMBER',
        'YEAR_NUMBER',
        'HOUR_NUMBER',
        'HOUR',
        'MINUTE_NUMBER',
        'MINUTE',
        'QUARTER_NUMBER',
        'WEEK_NUMBER',
        'WEEK_NUMBER_0',
        'WEEK_NUMBER_1',
        'LOWER',
        'UPPER',
        'TRIM',
        'LENGTH',
        'CHAR_LENGTH',
        'YEAR_0',
        'YEAR_1',
        'YEAR_2',
        'YEAR_3',
        'YEAR_4',
        'YEAR_5',
        'YEAR_6',
        'YEAR_7',
        'YEAR_8',
        'YEAR_9',
        'YEAR_10',
        'YEAR_11',
        'QUARTER_0',
        'QUARTER_1',
        'QUARTER_2',
        'QUARTER_3',
        'QUARTER_4',
        'QUARTER_5',
        'QUARTER_6',
        'QUARTER_7',
        'QUARTER_8',
        'QUARTER_9',
        'QUARTER_10',
        'QUARTER_11',
        'CONCAT',
        'TZ',
        'NOW',
        'ADD',
        'SUB',
        'MUL',
        'DIV',
        'MOD',
        'FLOOR',
        'CEIL',
        'ROUND',
        'COALESCE',
        'IF',
        'LIKE',
        'NOT_LIKE',
        'EQUAL',
        'NOT_EQUAL',
        'GREATER_THAN',
        'LESS_THAN',
        'GREATER_THAN_OR_EQUAL',
        'LESS_THAN_OR_EQUAL',
        'IS_NULL',
        'IS_NOT_NULL',
        'OR',
        'AND',
        'NOT',
        'IN',
        'NOT_IN',
        'IFNULL',
        'NULLIF',
        'BINARY',
        'UNIX_TIMESTAMP',
        'TIMESTAMPDIFF_DAY',
        'TIMESTAMPDIFF_MONTH',
        'TIMESTAMPDIFF_YEAR',
        'TIMESTAMPDIFF_WEEK',
        'TIMESTAMPDIFF_HOUR',
        'TIMESTAMPDIFF_MINUTE',
    ];

    protected $multipleArgumentsFunctionList = [
        'CONCAT',
        'TZ',
        'ROUND',
        'COALESCE',
        'IF',
        'LIKE',
        'NOT_LIKE',
        'EQUAL',
        'NOT_EQUAL',
        'GREATER_THAN',
        'LESS_THAN',
        'GREATER_THAN_OR_EQUAL',
        'LESS_THAN_OR_EQUAL',
        'OR',
        'AND',
        'IN',
        'NOT_IN',
        'ADD',
        'SUB',
        'MUL',
        'DIV',
        'MOD',
        'IFNULL',
        'NULLIF',
        'TIMESTAMPDIFF_DAY',
        'TIMESTAMPDIFF_MONTH',
        'TIMESTAMPDIFF_YEAR',
        'TIMESTAMPDIFF_WEEK',
        'TIMESTAMPDIFF_HOUR',
        'TIMESTAMPDIFF_MINUTE',
    ];

    protected $comparisonFunctionList = [
        'LIKE',
        'NOT_LIKE',
        'EQUAL',
        'NOT_EQUAL',
        'GREATER_THAN',
        'LESS_THAN',
        'GREATER_THAN_OR_EQUAL',
        'LESS_THAN_OR_EQUAL',
    ];

    protected $comparisonFunctionOperatorMap = [
        'LIKE' => 'LIKE',
        'NOT_LIKE' => 'NOT LIKE',
        'EQUAL' => '=',
        'NOT_EQUAL' => '<>',
        'GREATER_THAN' => '>',
        'LESS_THAN' => '<',
        'GREATER_THAN_OR_EQUAL' => '>=',
        'LESS_THAN_OR_EQUAL' => '<=',
        'IS_NULL' => 'IS NULL',
        'IS_NOT_NULL' => 'IS NOT NULL',
        'IN' => 'IN',
        'NOT_IN' => 'NOT IN',
    ];

    protected $mathFunctionOperatorMap = [
        'ADD' => '+',
        'SUB' => '-',
        'MUL' => '*',
        'DIV' => '/',
        'MOD' => '%',
    ];

    protected $mathOperationFunctionList = [
        'ADD',
        'SUB',
        'MUL',
        'DIV',
        'MOD',
    ];

    protected $matchFunctionList = ['MATCH_BOOLEAN', 'MATCH_NATURAL_LANGUAGE', 'MATCH_QUERY_EXPANSION'];

    protected $matchFunctionMap = [
        'MATCH_BOOLEAN' => 'IN BOOLEAN MODE',
        'MATCH_NATURAL_LANGUAGE' => 'IN NATURAL LANGUAGE MODE',
        'MATCH_QUERY_EXPANSION' => 'WITH QUERY EXPANSION',
    ];

    const SELECT_METHOD = 'SELECT';
    const DELETE_METHOD = 'DELETE';
    const UPDATE_METHOD = 'UPDATE';
    const INSERT_METHOD = 'INESRT';

    protected $entityFactory;

    protected $pdo;

    protected $metadata;

    protected $helper;

    protected $attributeDbMapCache = [];

    protected $aliasesCache = [];

    protected $seedCache = [];

    public function __construct(PDO $pdo, EntityFactory $entityFactory, Metadata $metadata)
    {
        $this->entityFactory = $entityFactory;
        $this->pdo = $pdo;
        $this->metadata = $metadata;

        $this->helper = new Helper($metadata);
    }

    protected function getSeed(string $entityType) : Entity
    {
        if (empty($this->seedCache[$entityType])) {
            $this->seedCache[$entityType] = $this->entityFactory->create($entityType);
        }
        return $this->seedCache[$entityType];
    }

    /**
     * Compose a SELECT query.
     */
    public function createSelectQuery(string $entityType, ?array $params = nul) : string
    {
        return $this->createSelectQueryInternal($entityType, $params);
    }

    /**
     * Compose a DELETE query.
     */
    public function createDeleteQuery(string $entityType, ?array $params = null) : string
    {
        $params = $this->normilizeParams(self::DELETE_METHOD, $params);

        $entity = $this->getSeed($entityType);

        $wherePart = $this->getWherePart($entity, $params['whereClause'], 'AND', $params);
        $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order'], $params);
        $joinsPart = $this->getJoinsPart($entity, $params);

        $sql = $this->composeDeleteQuery(
            $this->toDb($entityType),
            $wherePart,
            $joinsPart,
            $orderPart,
            $params['limit']
        );

        return $sql;
    }

    /**
     * Compose an UPDATE query.
     */
    public function createUpdateQuery(string $entityType, ?array $params = null) : string
    {
        $params = $this->normilizeParams(self::UPDATE_METHOD, $params);

        $values = $params['update'];

        $entity = $this->getSeed($entityType);

        $wherePart = $this->getWherePart($entity, $params['whereClause'], 'AND', $params);
        $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order'], $params);
        $joinsPart = $this->getJoinsPart($entity, $params);

        $setPart = $this->getSetPart($entity, $values);

        $sql = $this->composeUpdateQuery(
            $this->toDb($entityType),
            $setPart,
            $wherePart,
            $joinsPart,
            $orderPart,
            $params['limit']
        );

        return $sql;
    }

    public function createInsertQuery(string $entityType, array $params) : string
    {
        $params = $this->normilizeInsertParams($params);

        $columns = $params['columns'];
        $values = $params['values'];
        $update = $params['update'];
        $valuesSelectParams = $params['valuesSelectParams'];

        $columnsPart = $this->getInsertColumnsPart($columns);

        $valuesPart = $this->getInsertValuesPart($entityType, $params);

        $updatePart = null;

        if ($update) {
            $updatePart = $this->getInsertUpdatePart($update);
        }

        return $this->composeInsertQuery($this->toDb($entityType), $columnsPart, $valuesPart, $updatePart);
    }

    protected function getInsertValuesPart(string $entityType, array $params)
    {
        $isMass = $params['isMass'];
        $isBySelect = $params['isBySelect'];

        $columns = $params['columns'];
        $values = $params['values'];

        if ($isBySelect) {
            return '('. $this->createSelectQuery($params['valuesSelectParams']['from'], $params['valuesSelectParams']) . ')';
        }

        if ($isMass) {
            $list = [];
            foreach ($values as $item) {
                $list[] = '(' . $this->getInsertValuesItemPart($columns, $item) . ')';
            }
            return 'VALUES ' . implode(', ', $list);
        }

        return 'VALUES (' . $this->getInsertValuesItemPart($columns, $values) . ')';
    }

    protected function normilizeInsertParams(array $params) : array
    {
        $columns = $params['columns'] ?? null;

        if (empty($columns) || !is_array($columns)) {
            throw new RuntimeException("ORM Query: 'columns' is empty for INSERT.");
        }

        $values = $params['values'] = $params['values'] ?? null;

        $valuesSelectParams = $params['valuesSelectParams'] = $params['valuesSelectParams'] ?? null;

        $isBySelect = false;

        if ($valuesSelectParams) {
            $isBySelect = true;
        }

        if (!$isBySelect) {
            if (empty($values) || !is_array($values)) {
                throw new RuntimeException("ORM Query: 'values' is empty for INSERT.");
            }
        }

        if ($isBySelect) {
            if (!is_array($valuesSelectParams)) {
                throw new RuntimeException("ORM Query: Bad 'valuesSelectParams' parameter.");
            }
            if (!isset($valuesSelectParams['from'])) {
                throw new RuntimeException("ORM Query: Missing 'from' in 'valuesSelectParams'.");
            }
        }

        $params['isBySelect'] = $isBySelect;

        $isMass = !$isBySelect && array_keys($values)[0] === 0;

        $params['isMass'] = $isMass;

        if (!$isBySelect) {
            if (!$isMass) {
                foreach ($columns as $item) {
                    if (!array_key_exists($item, $values)) {
                        throw new RuntimeException("ORM Query: 'values' should contain all items listed in 'columns'.");
                    }
                }
            } else {
                foreach ($values as $valuesItem) {
                    foreach ($columns as $item) {
                        if (!array_key_exists($item, $valuesItem)) {
                            throw new RuntimeException("ORM Query: 'values' should contain all items listed in 'columns'.");
                        }
                    }
                }
            }
        }

        $update = $params['update'] = $params['update'] ?? null;

        if ($update && !is_array($update)) {
            throw new RuntimeException("ORM Query: Bad 'update' param.");
        }

        return $params;
    }

    protected function normilizeParams(string $method, ?array $params) : array
    {
        $params = $params ?? [];

        foreach (self::$paramList as $k) {
            $params[$k] = array_key_exists($k, $params) ? $params[$k] : null;
        }

        $params['distinct'] = $params['distinct'] ?? false;
        $params['skipTextColumns'] = $params['skipTextColumns'] ?? false;

        $params['joins'] = $params['joins'] ?? [];
        $params['leftJoins'] = $params['leftJoins'] ?? [];
        $params['additionalSelect'] = $params['additionalSelect'] ?? [];

        if ($method !== self::SELECT_METHOD) {
            if (isset($params['aggregation'])) {
                throw new RuntimeException("ORM Query: Param 'aggregation' is not allowed for '{$method}'.");
            }

            if (isset($params['offset'])) {
                throw new RuntimeException("ORM Query: Param 'offset' is not allowed for '{$method}'.");
            }
        }

        if ($method !== self::UPDATE_METHOD && $method !== self::INSERT_METHOD) {
            if (isset($params['update'])) {
                 throw new RuntimeException("ORM Query: Param 'update' is not allowed for '{$method}'.");
            }
        }

        if (isset($params['update']) && !is_array($params['update'])) {
            throw new RuntimeException("ORM Query: Param 'update' should be an array.");
        }

        return $params;
    }

    protected function createSelectQueryInternal(string $entityType, ?array $params = null) : string
    {
        $entity = $this->getSeed($entityType);

        $params = $this->normilizeParams(self::SELECT_METHOD, $params);

        $isAggregation = (bool) ($params['aggregation'] ?? null);

        $whereClause = $params['whereClause'] ?? [];
        $havingClause = $params['havingClause'] ?? [];

        if (!$params['withDeleted'] && $entity->hasAttribute('deleted')) {
            $whereClause = $whereClause + ['deleted' => false];
        }

        $selectPart = null;
        $joinsPart = null;
        $orderPart = null;
        $havingPart = null;
        $groupByPart = null;

        $wherePart = $this->getWherePart($entity, $whereClause, 'AND', $params);

        if (!empty($havingClause)) {
            $havingPart = $this->getWherePart($entity, $havingClause, 'AND', $params);
        }

        if (!$isAggregation) {
            $selectPart = $this->getSelectPart(
                $entity, $params['select'], $params['distinct'], $params['skipTextColumns'], $params['maxTextColumnsLength'], $params
            );

            $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order'], $params);

            $additionalSelectPart = $this->getAdditionalSelect($entity, $params);
            if ($additionalSelectPart) {
                $selectPart .= $additionalSelectPart;
            }
        }

        if ($isAggregation) {
            $aggregationDistinct = false;
            if ($params['distinct'] && $params['aggregation'] == 'COUNT') {
                $aggregationDistinct = true;
            }
            $params['select'] = [];
            $selectPart = $this->getAggregationSelectPart($entity, $params['aggregation'], $params['aggregationBy'], $aggregationDistinct);
        }

        // @todo remove 'customWhere' support
        if (!empty($params['customWhere'])) {
            if ($wherePart) {
                $wherePart .= ' ';
            }
            $wherePart .= $params['customWhere'];
        }

        // @todo remove 'customHaving' support
        if (!empty($params['customHaving'])) {
            if (!empty($havingPart)) {
                $havingPart .= ' ';
            }
            $havingPart .= $params['customHaving'];
        }

        $joinsPart = $this->getJoinsPart($entity, $params, true);
        $groupByPart = $this->getGroupByPart($entity, $params);

        $indexKeyList = $this->getIndexKeyList($entityType, $params);

        if ($isAggregation) {
            $sql = $this->composeSelectQuery(
                $this->toDb($entityType),
                $selectPart,
                $joinsPart,
                $wherePart,
                null,
                null,
                null,
                false,
                $groupByPart,
                $havingPart,
                $indexKeyList
            );

            if ($params['aggregation'] === 'COUNT' && $groupByPart && $havingPart) {
                $sql = "SELECT COUNT(*) AS `value` FROM ({$sql}) AS `countAlias`";
            }

            return $sql;
        }

        $sql = $this->composeSelectQuery(
            $this->toDb($entityType),
            $selectPart,
            $joinsPart,
            $wherePart,
            $orderPart,
            $params['offset'],
            $params['limit'],
            $params['distinct'],
            $groupByPart,
            $havingPart,
            $indexKeyList
        );

        return $sql;
    }

    protected function getIndexKeyList(string $entityType, array $params) : ?array
    {
        $indexKeyList = [];

        $indexList = $params['useIndex'] ?? null;

        if (empty($indexList)) {
            return null;
        }

        if (is_string($indexList)) {
            $indexList = [$indexList];
        }

        foreach ($indexList as $indexName) {
            $indexKey = $this->metadata->get($entityType, ['indexes', $indexName, 'key']);
            if ($indexKey) {
                $indexKeyList[] = $indexKey;
            }
        }

        return $indexKeyList;
    }

    protected function getJoinsPart(Entity $entity, array $params, bool $includeBelongsTo = false) : string
    {
        $joinsPart = '';

        if ($includeBelongsTo) {
            $joinsPart = $this->getBelongsToJoinsPart(
                $entity, $params['select'], array_merge($params['joins'], $params['leftJoins'])
            );
        }

        if (!empty($params['joins']) && is_array($params['joins'])) {
            // @todo array unique
            $joinsItemPart = $this->getJoinsTypePart($entity, $params['joins'], false, $params['joinConditions']);
            if (!empty($joinsItemPart)) {
                if (!empty($joinsPart)) {
                    $joinsPart .= ' ';
                }
                $joinsPart .= $joinsItemPart;
            }
        }

        if (!empty($params['leftJoins']) && is_array($params['leftJoins'])) {
            // @todo array unique
            $joinsItemPart = $this->getJoinsTypePart($entity, $params['leftJoins'], true, $params['joinConditions']);
            if (!empty($joinsItemPart)) {
                if (!empty($joinsPart)) {
                    $joinsPart .= ' ';
                }
                $joinsPart .= $joinsItemPart;
            }
        }

        // @todo remove custom join
        if (!empty($params['customJoin'])) {
            if (!empty($joinsPart)) {
                $joinsPart .= ' ';
            }
            $joinsPart .= $params['customJoin'];
        }

        return $joinsPart;
    }

    protected function getGroupByPart(Entity $entity, array $params) : ?string
    {
        if (empty($params['groupBy'])) {
            return null;
        }

        $list = [];
        foreach ($params['groupBy'] as $field) {
            $list[] = $this->convertComplexExpression($entity, $field, false, $params);
        }

        return implode(', ', $list);
    }

    protected function getAdditionalSelect(Entity $entity, array $params) : ?string
    {
        $selectPart = '';

        if (!empty($params['extraAdditionalSelect'])) {
            $extraSelect = [];
            foreach ($params['extraAdditionalSelect'] as $item) {
                if (!in_array($item, $params['select']) && !in_array($item, $params['additionalSelect'])) {
                    $extraSelect[] = $item;
                }
            }
            if (count($extraSelect)) {
                $extraSelectPart = $this->getSelectPart(
                    $entity, $extraSelect, false
                );
                if ($extraSelectPart) {
                    $selectPart .= ', ' . $extraSelectPart;
                }
            }
        }

        if (
            !empty($params['additionalColumns']) && is_array($params['additionalColumns']) && !empty($params['relationName'])
        ) {
            $alias = $this->sanitizeSelectAlias(lcfirst($params['relationName']));

            foreach ($params['additionalColumns'] as $column => $field) {
                $itemAlias = $this->sanitizeSelectAlias($field);
                $selectPart .= ", " . $alias . "." . $this->toDb($this->sanitize($column)) . " AS `{$itemAlias}`";
            }
        }

        if (!empty($params['additionalSelectColumns']) && is_array($params['additionalSelectColumns'])) {
            foreach ($params['additionalSelectColumns'] as $column => $field) {
                $itemAlias = $this->sanitizeSelectAlias($field);
                $selectPart .= ", " . $column . " AS `{$itemAlias}`";
            }
        }

        if ($selectPart === '') {
            return null;
        }

        return $selectPart;
    }

    protected function getFunctionPart(
        string $function, string $part, string $entityType, bool $distinct = false, ?array $argumentPartList = null
    ) : string {
        if (!in_array($function, $this->functionList)) {
            throw new RuntimeException("ORM Query: Not allowed function '{$function}'.");
        }

        if (strpos($function, 'YEAR_') === 0 && $function !== 'YEAR_NUMBER') {
            $fiscalShift = substr($function, 5);
            if (is_numeric($fiscalShift)) {
                $fiscalShift = intval($fiscalShift);
                $fiscalFirstMonth = $fiscalShift + 1;

                return
                    "CASE WHEN MONTH({$part}) >= {$fiscalFirstMonth} THEN ".
                    "YEAR({$part}) ".
                    "ELSE YEAR({$part}) - 1 END";
            }
        }

        if (strpos($function, 'QUARTER_') === 0 && $function !== 'QUARTER_NUMBER') {
            $fiscalShift = substr($function, 8);
            if (is_numeric($fiscalShift)) {
                $fiscalShift = intval($fiscalShift);
                $fiscalFirstMonth = $fiscalShift + 1;
                $fiscalDistractedMonth = 12 - $fiscalFirstMonth;

                return
                    "CASE WHEN MONTH({$part}) >= {$fiscalFirstMonth} THEN ".
                    "CONCAT(YEAR({$part}), '_', FLOOR((MONTH({$part}) - {$fiscalFirstMonth}) / 3) + 1) ".
                    "ELSE CONCAT(YEAR({$part}) - 1, '_', CEIL((MONTH({$part}) + {$fiscalDistractedMonth}) / 3)) END";
            }
        }

        if ($function === 'TZ') {
            return $this->getFunctionPartTZ($entityType, $argumentPartList);
        }

        if (in_array($function, $this->comparisonFunctionList)) {
            if (count($argumentPartList) < 2) {
                throw new RuntimeException("ORM Query: Not enough arguments for function '{$function}'.");
            }
            $operator = $this->comparisonFunctionOperatorMap[$function];
            return $argumentPartList[0] . ' ' . $operator . ' ' . $argumentPartList[1];
        }

        if (in_array($function, $this->mathOperationFunctionList)) {
            if (count($argumentPartList) < 2) {
                throw new RuntimeException("ORM Query: Not enough arguments for function '{$function}'.");
            }
            $operator = $this->mathFunctionOperatorMap[$function];
            return '(' . implode(' ' . $operator . ' ', $argumentPartList) . ')';
        }

        if (in_array($function, ['IN', 'NOT_IN'])) {
            $operator = $this->comparisonFunctionOperatorMap[$function];

            if (count($argumentPartList) < 2) {
                throw new RuntimeException("ORM Query: Not enough arguments for function '{$function}'.");
            }
            $operatorArgumentList = $argumentPartList;
            array_shift($operatorArgumentList);

            return $argumentPartList[0] .  ' ' . $operator . ' (' . implode(', ', $operatorArgumentList) . ')';
        }

        if (in_array($function, ['IS_NULL', 'IS_NOT_NULL'])) {
            $operator = $this->comparisonFunctionOperatorMap[$function];
            return $part . ' ' . $operator;
        }

        if (in_array($function, ['OR', 'AND'])) {
            return implode(' ' . $function . ' ', $argumentPartList);
        }

        switch ($function) {
            case 'MONTH':
                return "DATE_FORMAT({$part}, '%Y-%m')";
            case 'DAY':
                return "DATE_FORMAT({$part}, '%Y-%m-%d')";
            case 'WEEK_0':
                return "CONCAT(SUBSTRING(YEARWEEK({$part}, 6), 1, 4), '/', TRIM(LEADING '0' FROM SUBSTRING(YEARWEEK({$part}, 6), 5, 2)))";
            case 'WEEK':
            case 'WEEK_1':
                return "CONCAT(SUBSTRING(YEARWEEK({$part}, 3), 1, 4), '/', TRIM(LEADING '0' FROM SUBSTRING(YEARWEEK({$part}, 3), 5, 2)))";
            case 'QUARTER':
                return "CONCAT(YEAR({$part}), '_', QUARTER({$part}))";
            case 'MONTH_NUMBER':
                $function = 'MONTH';
                break;
            case 'DATE_NUMBER':
                $function = 'DAYOFMONTH';
                break;
            case 'YEAR_NUMBER':
                $function = 'YEAR';
                break;
            case 'WEEK_NUMBER_0':
                return "WEEK({$part}, 6)";
            case 'WEEK_NUMBER':
            case 'WEEK_NUMBER_1':
                return "WEEK({$part}, 3)";
            case 'HOUR_NUMBER':
                $function = 'HOUR';
                break;
            case 'MINUTE_NUMBER':
                $function = 'MINUTE';
                break;
            case 'QUARTER_NUMBER':
                $function = 'QUARTER';
                break;
            case 'DAYOFWEEK_NUMBER':
                $function = 'DAYOFWEEK';
                break;
            case 'NOT':
                return 'NOT ' . $part;
            case 'TIMESTAMPDIFF_YEAR':
                return 'TIMESTAMPDIFF(YEAR, ' . implode(', ', $argumentPartList) . ')';
            case 'TIMESTAMPDIFF_MONTH':
                return 'TIMESTAMPDIFF(MONTH, ' . implode(', ', $argumentPartList) . ')';
            case 'TIMESTAMPDIFF_WEEK':
                return 'TIMESTAMPDIFF(WEEK, ' . implode(', ', $argumentPartList) . ')';
            case 'TIMESTAMPDIFF_DAY':
                return 'TIMESTAMPDIFF(DAY, ' . implode(', ', $argumentPartList) . ')';
            case 'TIMESTAMPDIFF_HOUR':
                return 'TIMESTAMPDIFF(HOUR, ' . implode(', ', $argumentPartList) . ')';
            case 'TIMESTAMPDIFF_MINUTE':
                return 'TIMESTAMPDIFF(MINUTE, ' . implode(', ', $argumentPartList) . ')';
        }

        if ($distinct) {
            $idPart = $this->toDb($entityType) . ".id";
            switch ($function) {
                case 'COUNT':
                    return $function . "({$part}) * COUNT(DISTINCT {$idPart}) / COUNT({$idPart})";
            }
        }

        return $function . '(' . $part . ')';
    }

    protected function getFunctionPartTZ(string $entityType, ?array $argumentPartList = null)
    {
        if (!$argumentPartList || count($argumentPartList) < 2) {
            throw new RuntimeException("ORM Query: Not enough arguments for function TZ.");
        }
        $offsetHoursString = $argumentPartList[1];
        if (substr($offsetHoursString, 0, 1) === '\'' && substr($offsetHoursString, -1) === '\'') {
            $offsetHoursString = substr($offsetHoursString, 1, -1);
        }
        $offset = floatval($offsetHoursString);
        $offsetHours = intval(floor(abs($offset)));
        $offsetMinutes = (abs($offset) - $offsetHours) * 60;
        $offsetString =
            str_pad((string) $offsetHours, 2, '0', \STR_PAD_LEFT) .
            ':' .
            str_pad((string) $offsetMinutes, 2, '0', \STR_PAD_LEFT);
        if ($offset < 0) {
            $offsetString = '-' . $offsetString;
        } else {
            $offsetString = '+' . $offsetString;
        }

        return "CONVERT_TZ(". $argumentPartList[0]. ", '+00:00', " . $this->quote($offsetString) . ")";
    }

    protected function convertMatchExpression($entity, $expression)
    {
        $delimiterPosition = strpos($expression, ':');
        if ($delimiterPosition === false) {
            throw new RuntimeException("ORM Query: Bad MATCH usage.");
        }

        $function = substr($expression, 0, $delimiterPosition);
        $rest = substr($expression, $delimiterPosition + 1);

        if (empty($rest)) {
            throw new RuntimeException("ORM Query: Empty MATCH parameters.");
        }

        if (substr($rest, 0, 1) === '(' && substr($rest, -1) === ')') {
            $rest = substr($rest, 1, -1);

            $argumentList = self::parseArgumentListFromFunctionContent($rest);
            if (count($argumentList) < 2) {
                throw new RuntimeException("ORM Query: Bad MATCH usage.");
            }

            $columnList = [];
            for ($i = 0; $i < count($argumentList) - 1; $i++) {
                $columnList[] = $argumentList[$i];
            }
            $query = $argumentList[count($argumentList) - 1];
        } else {
            $delimiterPosition = strpos($rest, ':');
            if ($delimiterPosition === false) {
                throw new RuntimeException("ORM Query: Bad MATCH usage.");
            }

            $columns = substr($rest, 0, $delimiterPosition);
            $query = mb_substr($rest, $delimiterPosition + 1);

            $columnList = explode(',', $columns);
        }

        $tableName = $this->toDb($entity->getEntityType());

        foreach ($columnList as $i => $column) {
            $columnList[$i] = $tableName . '.' . $this->sanitize($column);
        }

        $query = $this->quote($query);

        if (!in_array($function, $this->matchFunctionList)) return;
        $modePart = ' ' . $this->matchFunctionMap[$function];

        $result = "MATCH (" . implode(',', $columnList) . ") AGAINST (" . $query . "" . $modePart . ")";

        return $result;
    }

    protected function convertComplexExpression(Entity $entity, string $attribute, bool $distinct = false, ?array &$params = null) : string
    {
        $function = null;

        $entityType = $entity->getEntityType();

        if (strpos($attribute, ':')) {
            $dilimeterPosition = strpos($attribute, ':');
            $function = substr($attribute, 0, $dilimeterPosition);

            if (in_array($function, $this->matchFunctionList)) {
                return $this->convertMatchExpression($entity, $attribute);
            }

            $attribute = substr($attribute, $dilimeterPosition + 1);

            if (substr($attribute, 0, 1) === '(' && substr($attribute, -1) === ')') {
                $attribute = substr($attribute, 1, -1);
            }
        }
        if (!empty($function)) {
            $function = strtoupper($this->sanitize($function));
        }

        $argumentPartList = null;

        if ($function && in_array($function, $this->multipleArgumentsFunctionList)) {
            $arguments = $attribute;

            $argumentList = $this->parseArgumentListFromFunctionContent($arguments);

            $argumentPartList = [];
            foreach ($argumentList as $argument) {
                $argumentPartList[] = $this->getFunctionArgumentPart($entity, $argument, $distinct, $params);
            }
            $part = implode(', ', $argumentPartList);

        } else {
            $part = $this->getFunctionArgumentPart($entity, $attribute, $distinct, $params);
        }

        if ($function) {
            $part = $this->getFunctionPart($function, $part, $entityType, $distinct, $argumentPartList);
        }

        return $part;
    }

    public static function getAllAttributesFromComplexExpression(string $expression) : array
    {
        return self::getAllAttributesFromComplexExpressionImplementation($expression);
    }

    protected static function getAllAttributesFromComplexExpressionImplementation(string $expression, ?array &$list = null) : array
    {
        if (!$list) $list = [];

        $arguments = $expression;

        if (strpos($expression, ':')) {
            $dilimeterPosition = strpos($expression, ':');
            $function = substr($expression, 0, $dilimeterPosition);
            $arguments = substr($expression, $dilimeterPosition + 1);
            if (substr($arguments, 0, 1) === '(' && substr($arguments, -1) === ')') {
                $arguments = substr($arguments, 1, -1);
            }
        } else {
            if (
                !self::isArgumentString($expression) &&
                !self::isArgumentNumeric($expression) &&
                !self::isArgumentBoolOrNull($expression)
            ) {
                $list[] = $expression;
            }
            return $list;
        }

        $argumentList = self::parseArgumentListFromFunctionContent($arguments);

        foreach ($argumentList as $argument) {
            self::getAllAttributesFromComplexExpressionImplementation($argument, $list);
        }

        return $list;
    }

    static protected function parseArgumentListFromFunctionContent(string $functionContent)
    {
        $functionContent = trim($functionContent);

        $isString = false;
        $isSingleQuote = false;

        if ($functionContent === '') {
            return [];
        }

        $commaIndexList = [];
        $braceCounter = 0;
        for ($i = 0; $i < strlen($functionContent); $i++) {
            if ($functionContent[$i] === "'" && ($i === 0 || $functionContent[$i - 1] !== "\\")) {
                if (!$isString) {
                    $isString = true;
                    $isSingleQuote = true;
                } else {
                    if ($isSingleQuote) {
                        $isString = false;
                    }
                }
            } else if ($functionContent[$i] === "\"" && ($i === 0 || $functionContent[$i - 1] !== "\\")) {
                if (!$isString) {
                    $isString = true;
                    $isSingleQuote = false;
                } else {
                    if (!$isSingleQuote) {
                        $isString = false;
                    }
                }
            }

            if (!$isString) {
                if ($functionContent[$i] === '(') {
                    $braceCounter++;
                } else if ($functionContent[$i] === ')') {
                    $braceCounter--;
                }
            }

            if ($braceCounter === 0 && !$isString && $functionContent[$i] === ',') {
                $commaIndexList[] = $i;
            }
        }

        $commaIndexList[] = strlen($functionContent);

        $argumentList = [];
        for ($i = 0; $i < count($commaIndexList); $i++) {
            if ($i > 0) {
                $previousCommaIndex = $commaIndexList[$i - 1] + 1;
            } else {
                $previousCommaIndex = 0;
            }
            $argument = trim(substr($functionContent, $previousCommaIndex, $commaIndexList[$i] - $previousCommaIndex));
            $argumentList[] = $argument;
        }

        return $argumentList;
    }

    protected static function isArgumentString(string $argument)
    {
        return
            substr($argument, 0, 1) === '\'' && substr($argument, -1) === '\''
            ||
            substr($argument, 0, 1) === '"' && substr($argument, -1) === '"';
    }

    protected static function isArgumentNumeric(string $argument)
    {
        return is_numeric($argument);
    }

    protected static function isArgumentBoolOrNull(string $argument)
    {
        return in_array(strtoupper($argument), ['NULL', 'TRUE', 'FALSE']);
    }

    protected function getFunctionArgumentPart($entity, $attribute, $distinct = false, &$params = null)
    {
        $argument = $attribute;

        if (self::isArgumentString($argument)) {
            $string = substr($argument, 1, -1);
            $string = $this->quote($string);
            return $string;
        } else if (self::isArgumentNumeric($argument)) {
            $string = $this->quote($argument);
            return $string;
        } else if (self::isArgumentBoolOrNull($argument)) {
            return strtoupper($argument);
        }

        if (strpos($argument, ':')) {
            return $this->convertComplexExpression($entity, $argument, $distinct, $params);
        }

        $relName = null;
        $entityType = $entity->getEntityType();

        if (strpos($argument, '.')) {
            list($relName, $attribute) = explode('.', $argument);
        }

        if (!empty($relName)) {
            $relName = $this->sanitize($relName);
        }
        if (!empty($attribute)) {
            $attribute = $this->sanitize($attribute);
        }

        if ($attribute !== '') {
            $part = $this->toDb($attribute);
        } else {
            $part = '';
        }

        if ($relName) {
            $part = $relName . '.' . $part;

            $foreignEntityType = $entity->getRelationParam($relName, 'entity');
            if ($foreignEntityType) {
                $foreignSeed = $this->getSeed($foreignEntityType);
                if ($foreignSeed) {
                    $selectForeign = $foreignSeed->getAttributeParam($attribute, 'selectForeign');
                    if (is_array($selectForeign)) {
                        $part = $this->getAttributeSql($foreignSeed, $attribute, 'selectForeign', $params, $relName);
                    }
                }
            }
        } else {
            if (!empty($entity->getAttributes()[$attribute]['select'])) {
                $part = $this->getAttributeSql($entity, $attribute, 'select', $params);
            } else {
                if ($part !== '') {
                    $part = $this->toDb($entityType) . '.' . $part;
                }
            }
        }

        return $part;
    }

    protected function getAttributeSql(Entity $entity, string $attribute, string $type, ?array &$params = null, ?string $alias = null) : string
    {
        $fieldDefs = $entity->getAttributes()[$attribute];

        if (is_string($fieldDefs[$type])) {
            $part = $fieldDefs[$type];
        } else {
            if (!empty($fieldDefs[$type]['sql'])) {
                $part = $fieldDefs[$type]['sql'];
                if ($alias) {
                    $part = str_replace('{alias}', $alias, $part);
                }
            } else {
                $part = $this->toDb($entity->getEntityType()) . '.' . $this->toDb($this->sanitize($attribute));
                if ($type === 'orderBy') {
                    $part .= ' {direction}';
                }
            }
        }

        if ($params) {
            if (!empty($fieldDefs[$type]['leftJoins'])) {
                foreach ($fieldDefs[$type]['leftJoins'] as $j) {
                    $jAlias = $this->obtainJoinAlias($j);
                    if ($alias) $jAlias = str_replace('{alias}', $alias, $jAlias);
                    if (isset($j[1])) $j[1] = $jAlias;
                    foreach ($params['leftJoins'] as $jE) {
                        $jEAlias = $this->obtainJoinAlias($jE);
                        if ($jEAlias === $jAlias) {
                            continue 2;
                        }
                    }
                    if ($alias) {
                        if (count($j) >= 3) {
                            $conditions = [];
                            foreach ($j[2] as $k => $value) {
                                $value = str_replace('{alias}', $alias, $value);
                                $left = $k;
                                $left = str_replace('{alias}', $alias, $left);
                                $conditions[$left] = $value;
                            }
                            $j[2] = $conditions;
                        }
                    }

                    $params['leftJoins'][] = $j;
                }
            }
            if (!empty($fieldDefs[$type]['joins'])) {
                foreach ($fieldDefs[$type]['joins'] as $j) {
                    $jAlias = $this->obtainJoinAlias($j);
                    $jAlias = str_replace('{alias}', $alias, $jAlias);
                    if (isset($j[1])) $j[1] = $jAlias;
                    foreach ($params['joins'] as $jE) {
                        $jEAlias = $this->obtainJoinAlias($jE);
                        if ($jEAlias === $jAlias) {
                            continue 2;
                        }
                    }
                    $params['joins'][] = $j;
                }
            }

            if (!empty($fieldDefs[$type]['additionalSelect'])) {
                $params['extraAdditionalSelect'] = $params['extraAdditionalSelect'] ?? [];
                foreach ($fieldDefs[$type]['additionalSelect'] as $value) {
                    $value = str_replace('{alias}', $alias, $value);
                    $value = str_replace('{attribute}', $attribute, $value);
                    if (!in_array($value, $params['extraAdditionalSelect'])) {
                        $params['extraAdditionalSelect'][] = $value;
                    }
                }
            }
        }

        return $part;
    }

    protected function getSelectPart(
        Entity $entity,
        ?array $itemList = null,
        bool $distinct = false,
        bool $skipTextColumns = false,
        ?int $maxTextColumnsLength = null,
        ?array &$params = null
    ) : string {
        $select = '';
        $arr = [];
        $specifiedList = is_array($itemList) ? true : false;

        if (empty($itemList)) {
            $attributeList = $entity->getAttributeList();
        } else {
            $attributeList = $itemList;
        }

        if ($params && isset($params['additionalSelect'])) {
            foreach ($params['additionalSelect'] as $item) {
                $attributeList[] = $item;
            }
        }

        foreach ($attributeList as $i => $attribute) {
            if (is_string($attribute)) {
                if (strpos($attribute, ':')) {
                    $attributeList[$i] = [
                        $attribute,
                        $attribute
                    ];
                    continue;
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
                    if ($part !== false) {
                        $part = $this->quote($part);
                    } else {
                        $part = $this->quote('');
                    }
                } else {
                    if (!$entity->hasAttribute($attribute[0])) {
                        $part = $this->convertComplexExpression($entity, $attribute[0], $distinct, $params);
                    } else {
                        $fieldDefs = $entity->getAttributes()[$attribute[0]];
                        if (!empty($fieldDefs['select'])) {
                            $part = $this->getAttributeSql($entity, $attribute[0], 'select', $params);
                        } else {
                            if (!empty($fieldDefs['noSelect'])) {
                                continue;
                            }
                            if (!empty($fieldDefs['notStorable'])) {
                                continue;
                            }
                            $part = $this->getFieldPath($entity, $attribute[0], $params);
                        }
                    }
                }

                $arr[] = $part . ' AS `' . $this->sanitizeSelectAlias($attribute[1]) . '`';
                continue;
            }

            $attribute = $this->sanitizeSelectItem($attribute);

            if ($entity->hasAttribute($attribute)) {
                $fieldDefs = $entity->getAttributes()[$attribute];
            } else {
                $part = $this->convertComplexExpression($entity, $attribute, $distinct, $params);
                $arr[] = $part . ' AS `' . $this->sanitizeSelectAlias($attribute) . '`';
                continue;
            }

            if (!empty($fieldDefs['select'])) {
                $fieldPath = $this->getAttributeSql($entity, $attribute, 'select', $params);
            } else {
                if (!empty($fieldDefs['notStorable']) && ($fieldDefs['type'] ?? null) !== 'foreign') {
                    continue;
                }
                if ($attributeType === null) {
                    continue;
                }
                $fieldPath = $this->getFieldPath($entity, $attribute, $params);
                if ($attributeType === $entity::TEXT && $maxTextColumnsLength !== null) {
                    $fieldPath = 'LEFT(' . $fieldPath . ', '. intval($maxTextColumnsLength) . ')';
                }
            }

            $arr[] = $fieldPath . ' AS `' . $attribute . '`';
        }

        $select = implode(', ', $arr);

        return $select;
    }

    protected function getBelongsToJoinItemPart(Entity $entity, $relationName, $r = null, $alias = null)
    {
        if (empty($r)) {
            $r = $entity->relations[$relationName];
        }

        $keySet = $this->helper->getRelationKeys($entity, $relationName);
        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        if (!$alias) {
            $alias = $this->getAlias($entity, $relationName);
        } else {
            $alias = $this->sanitizeSelectAlias($alias);
        }

        if ($alias) {
            return "JOIN `" . $this->toDb($r['entity']) . "` AS `" . $alias . "` ON ".
                   $this->toDb($entity->getEntityType()) . "." . $this->toDb($key) . " = " . $alias . "." . $this->toDb($foreignKey);
        }
    }

    protected function getBelongsToJoinsPart(Entity $entity, ?array $select = null, array $skipList = []) : string
    {
        $joinsArr = [];

        $relationsToJoin = [];

        if (is_array($select)) {
            foreach ($select as $item) {
                $field = $item;
                if (is_array($item)) {
                    if (count($field) == 0) continue;
                    $field = $item[0];
                }
                if ($entity->getAttributeType($field) == 'foreign' && $entity->getAttributeParam($field, 'relation')) {
                    $relationsToJoin[] = $entity->getAttributeParam($field, 'relation');
                } else if (
                    $entity->getAttributeParam($field, 'fieldType') == 'linkOne' && $entity->getAttributeParam($field, 'relation')
                ) {
                    $relationsToJoin[] = $entity->getAttributeParam($field, 'relation');
                }
            }
        }

        foreach ($entity->getRelations() as $relationName => $r) {
            $type = $r['type'] ?? null;
            if ($type == Entity::BELONGS_TO || $type == Entity::HAS_ONE) {
                if (!empty($r['noJoin'])) continue;

                if (in_array($relationName, $skipList)) continue;

                foreach ($skipList as $sItem) {
                    if (is_array($sItem) && count($sItem) > 1) {
                        if ($sItem[1] === $relationName) {
                            continue 2;
                        }
                    }
                }

                if (is_array($select) && !in_array($relationName, $relationsToJoin)) continue;

                if ($type == Entity::BELONGS_TO) {
                    $join = $this->getBelongsToJoinItemPart($entity, $relationName, $r);
                    if (!$join) continue;
                    $joinsArr[] = 'LEFT ' . $join;
                } else if ($type == Entity::HAS_ONE) {
                    $join =  $this->getJoinItemPart($entity, $relationName, true);
                    $joinsArr[] = $join;
                }
            }
        }

        return implode(' ', $joinsArr);
    }

    protected function getOrderExpressionPart(
        Entity $entity, $orderBy = null, $order = null, $useColumnAlias = false, &$params = null
    ) : ?string {
        if (is_null($orderBy)) {
            return null;
        }

        if (is_array($orderBy)) {
            $arr = [];

            foreach ($orderBy as $item) {
                if (is_array($item)) {
                    $orderByInternal = $item[0];
                    $orderInternal = null;
                    if (!empty($item[1])) {
                        $orderInternal = $item[1];
                    }
                    $arr[] = $this->getOrderExpressionPart($entity, $orderByInternal, $orderInternal, $useColumnAlias, $params);
                }
            }
            return implode(", ", $arr);
        }

        if (strpos($orderBy, 'LIST:') === 0) {
            list($l, $field, $list) = explode(':', $orderBy);
            if ($useColumnAlias) {
                $fieldPath = '`'. $this->sanitizeSelectAlias($field) . '`';
            } else {
                $fieldPath = $this->getFieldPathForOrderBy($entity, $field, $params);
            }
            $listQuoted = [];
            $list = array_reverse(explode(',', $list));
            foreach ($list as $i => $listItem) {
                $listItem = str_replace('_COMMA_', ',', $listItem);
                $listQuoted[] = $this->quote($listItem);
            }
            $part = "FIELD(" . $fieldPath . ", " . implode(", ", $listQuoted) . ") DESC";
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

        if (!empty($entity->getAttributes()[$orderBy])) {
            $fieldDefs = $entity->getAttributes()[$orderBy];
        }

        if (!empty($fieldDefs) && !empty($fieldDefs['orderBy'])) {
            $orderPart = $this->getAttributeSql($entity, $orderBy, 'orderBy', $params);
            $orderPart = str_replace('{direction}', $order, $orderPart);
            return "{$orderPart}";
        }

        if ($useColumnAlias) {
            $fieldPath = '`'. $this->sanitizeSelectAlias($orderBy) . '`';
        } else {
            $fieldPath = $this->getFieldPathForOrderBy($entity, $orderBy, $params);
        }

        return "{$fieldPath} " . $order;
    }

    protected function getOrderPart(Entity $entity, $orderBy = null, $order = null, &$params = null) : ?string
    {
        return $this->getOrderExpressionPart($entity, $orderBy, $order, false, $params);
    }

    public function order(string $sql, Entity $entity, $orderBy = null, $order = null, bool $useColumnAlias = false) : string
    {
        $orderPart = $this->getOrderExpressionPart($entity, $orderBy, $order, $useColumnAlias);
        if ($orderPart) {
            $sql .= " ORDER BY " . $orderPart;
        }
        return $sql;
    }

    protected function getFieldPathForOrderBy(Entity $entity, string $orderBy, array $params) : ?string
    {
        if (strpos($orderBy, '.') !== false || strpos($orderBy, ':') !== false) {
            return $this->convertComplexExpression(
                $entity,
                $orderBy,
                false,
                $params
            );
        }

        return $this->getFieldPath($entity, $orderBy, $params);
    }

    protected function getAggregationSelectPart(Entity $entity, string $aggregation, string $aggregationBy, bool $distinct = false) : ?string
    {
        if (!isset($entity->getAttributes()[$aggregationBy])) {
            return null;
        }

        $aggregation = strtoupper($aggregation);

        $distinctPart = '';
        if ($distinct) {
            $distinctPart = 'DISTINCT ';
        }

        $selectPart = "{$aggregation}({$distinctPart}" . $this->toDb($entity->getEntityType()) . "." .
            $this->toDb($this->sanitize($aggregationBy)) . ") AS `value`";

        return $selectPart;
    }

    /**
     * Quote a value.
     */
    public function quote($value) : string
    {
        if (is_null($value)) {
            return 'NULL';
        } else if (is_bool($value)) {
            return $value ? '1' : '0';
        } else {
            return $this->pdo->quote($value);
        }
    }

    /**
     * Convert a camelCase string to a corresponding representation for DB.
     */
    public function toDb(string $attribute) : string
    {
        if (!array_key_exists($attribute, $this->attributeDbMapCache)) {
            $attribute[0] = strtolower($attribute[0]);
            $this->attributeDbMapCache[$attribute] = preg_replace_callback('/([A-Z])/', [$this, 'toDbMatchConversion'], $attribute);
        }

        return $this->attributeDbMapCache[$attribute];
    }

    protected function toDbMatchConversion(array $matches) : string
    {
        return '_' . strtolower($matches[1]);
    }

    protected function getAlias(Entity $entity, string $relationName) : ?string
    {
        if (!isset($this->aliasesCache[$entity->getEntityType()])) {
            $this->aliasesCache[$entity->getEntityType()] = $this->getTableAliases($entity);
        }

        if (isset($this->aliasesCache[$entity->getEntityType()][$relationName])) {
            return $this->aliasesCache[$entity->getEntityType()][$relationName];
        } else {
            return null;
        }
    }

    protected function getTableAliases(Entity $entity) : array
    {
        $aliases = [];
        $c = 0;

        $occuranceHash = [];

        foreach ($entity->getRelations() as $name => $r) {
            if ($r['type'] == Entity::BELONGS_TO || $r['type'] == Entity::HAS_ONE) {

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

    protected function getFieldPath(Entity $entity, $field, &$params = null) : ?string
    {
        if (!isset($entity->getAttributes()[$field])) {
            return null;
        }

        $f = $entity->getAttributes()[$field];

        $relationType = $f['type'];

        if (isset($f['source'])) {
            if ($f['source'] != 'db') {
                return null;
            }
        }

        if (!empty($f['notStorable']) && $relationType !== 'foreign') {
            return null;
        }

        $fieldPath = '';

        switch ($relationType) {
            case 'foreign':
                if (isset($f['relation'])) {
                    $relationName = $f['relation'];

                    $foreign = $f['foreign'];

                    if (is_array($foreign)) {
                        $wsCount = 0;
                        foreach ($foreign as $i => $value) {
                            if ($value == ' ') {
                                $foreign[$i] = '\' \'';
                                $wsCount ++;
                            } else {
                                $item =  $this->getAlias($entity, $relationName) . '.' . $this->toDb($value);

                                $foreign[$i] = "IFNULL({$item}, '')";
                            }
                        }

                        $fieldPath = 'TRIM(CONCAT(' . implode(', ', $foreign). '))';

                        if ($wsCount > 1) {
                            $fieldPath = "REPLACE({$fieldPath}, '  ', ' ')";
                        }

                        $fieldPath = "NULLIF({$fieldPath}, '')";
                    } else {
                        $expression = $this->getAlias($entity, $relationName) . '.' . $foreign;
                        $fieldPath = $this->convertComplexExpression($entity, $expression, false, $params);
                    }
                }
                break;
            default:
                $fieldPath = $this->toDb($entity->getEntityType()) . '.' . $this->toDb($this->sanitize($field));
        }

        return $fieldPath;
    }

    protected function getWherePart(
        Entity $entity, ?array $whereClause = null, string $sqlOp = 'AND', array &$params = [], int $level = 0
    ) : string {
        $wherePartList = [];

        if (!$whereClause) $whereClause = [];

        foreach ($whereClause as $field => $value) {
            if (is_int($field)) {
                if (is_string($value)) {
                    if (strpos($value, 'MATCH_') === 0) {
                        $rightPart = $this->convertMatchExpression($entity, $value);
                        $wherePartList[] = $rightPart;
                        continue;
                    }
                }
                $field = 'AND';
            }

            if ($field === 'NOT') {
                if ($level > 1) break;

                $field = 'id!=s';
                $value = [
                    'selectParams' => [
                        'select' => ['id'],
                        'whereClause' => $value
                    ]
                ];
                if (!empty($params['joins'])) {
                    $value['selectParams']['joins'] = $params['joins'];
                }
                if (!empty($params['leftJoins'])) {
                    $value['selectParams']['leftJoins'] = $params['leftJoins'];
                }
                if (!empty($params['customJoin'])) {
                    $value['selectParams']['customJoin'] = $params['customJoin'];
                }
            }

            if (in_array($field, self::$sqlOperators)) {
                $internalPart = $this->getWherePart($entity, $value, $field, $params, $level + 1);
                if ($internalPart || $internalPart === '0') {
                    $wherePartList[] = "(" . $internalPart . ")";
                }
                continue;
            }

            $isComplex = false;

            $operator = '=';
            $operatorOrm = '=';

            $leftPart = null;

            $isNotValue = false;
            if (substr($field, -1) === ':') {
                $field = substr($field, 0, strlen($field) - 1);
                $isNotValue = true;
            }

            if (!preg_match('/^[a-z0-9]+$/i', $field)) {
                foreach (self::$comparisonOperators as $op => $opDb) {
                    if (substr($field, -strlen($op)) === $op) {
                        $field = trim(substr($field, 0, -strlen($op)));
                        $operatorOrm = $op;
                        $operator = $opDb;
                        break;
                    }
                }
            }

            if (strpos($field, '.') !== false || strpos($field, ':') !== false) {
                $leftPart = $this->convertComplexExpression($entity, $field, false, $params);
                $isComplex = true;
            }

            if (empty($isComplex)) {
                if (!isset($entity->getAttributes()[$field])) {
                    $wherePartList[] = '0';
                    continue;
                }

                $fieldDefs = $entity->getAttributes()[$field];

                $operatorModified = $operator;

                $attributeType = null;
                if (!empty($fieldDefs['type'])) {
                    $attributeType = $fieldDefs['type'];
                }

                if (
                    is_bool($value)
                    &&
                    in_array($operator, ['=', '<>'])
                    &&
                    $attributeType == Entity::BOOL
                ) {
                    if ($value) {
                        if ($operator === '=') {
                            $operatorModified = '= TRUE';
                        } else {
                            $operatorModified = '= FALSE';
                        }
                    } else {
                        if ($operator === '=') {
                            $operatorModified = '= FALSE';
                        } else {
                            $operatorModified = '= TRUE';
                        }
                    }
                } else if (is_array($value)) {
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
                    $wherePartList[] = str_replace('{value}', $this->stringifyValue($value), $whereSqlPart);
                } else {
                    if ($fieldDefs['type'] == Entity::FOREIGN) {
                        $leftPart = '';
                        if (isset($fieldDefs['relation'])) {
                            $relationName = $fieldDefs['relation'];
                            if ($entity->hasRelation($relationName)) {
                                $alias = $this->getAlias($entity, $relationName);
                                if ($alias) {
                                    if (!is_array($fieldDefs['foreign'])) {
                                        $leftPart = $this->convertComplexExpression(
                                            $entity,
                                            $alias . '.' . $fieldDefs['foreign'],
                                            false,
                                            $params
                                        );
                                    } else {
                                        $leftPart = $this->getFieldPath($entity, $field, $params);
                                    }
                                }
                            }
                        }
                    } else {
                        $leftPart = $this->toDb($entity->getEntityType()) . '.' . $this->toDb($this->sanitize($field));
                    }
                }
            }
            if (!empty($leftPart)) {
                if ($operatorOrm === '=s' || $operatorOrm === '!=s') {
                    if (!is_array($value)) {
                        continue;
                    }
                    if (!empty($value['entityType'])) {
                        $subQueryEntityType = $value['entityType'];
                    } else {
                        $subQueryEntityType = $entity->getEntityType();
                    }
                    $subQuerySelectParams = [];
                    if (!empty($value['selectParams'])) {
                        $subQuerySelectParams = $value['selectParams'];
                    }
                    if (!empty($value['withDeleted'])) {
                        $subQuerySelectParams['withDeleted'] = true;
                    }
                    $wherePartList[] = $leftPart . " " . $operator . " (" .
                        $this->createSelectQuery($subQueryEntityType, $subQuerySelectParams) . ")";
                } else if (!is_array($value)) {
                    if ($isNotValue) {
                        if (!is_null($value)) {
                            $wherePartList[] = $leftPart . " " . $operator . " " .
                                $this->convertComplexExpression($entity, $value, false, $params);
                        } else {
                            $wherePartList[] = $leftPart;
                        }
                    } else if (!is_null($value)) {
                        $wherePartList[] = $leftPart . " " . $operator . " " . $this->quote($value);
                    } else {
                        if ($operator == '=') {
                            $wherePartList[] = $leftPart . " IS NULL";
                        } else if ($operator == '<>') {
                            $wherePartList[] = $leftPart . " IS NOT NULL";
                        }
                    }
                } else {
                    $valArr = $value;
                    foreach ($valArr as $k => $v) {
                        $valArr[$k] = $this->quote($valArr[$k]);
                    }
                    $oppose = '';
                    $emptyValue = '0';
                    if ($operator == '<>') {
                        $oppose = 'NOT ';
                        $emptyValue = '1';
                    }
                    if (!empty($valArr)) {
                        $wherePartList[] = $leftPart . " {$oppose}IN " . "(" . implode(',', $valArr) . ")";
                    } else {
                        $wherePartList[] = "" . $emptyValue;
                    }
                }
            }
        }

        return implode(" " . $sqlOp . " ", $wherePartList);
    }

    protected function obtainJoinAlias($j)
    {
        if (is_array($j)) {
            if (count($j)) {
                if ($j[1])
                    $joinAlias = $j[1];
                else
                    $joinAlias = $j[0];
            } else {
                $joinAlias = $j[0];
            }
        } else {
            $joinAlias = $j;
        }

        return $joinAlias;
    }

    /**
     * Convert a value to a string.
     */
    public function stringifyValue($value) : string
    {
        if (is_array($value)) {
            $arr = [];
            foreach ($value as $v) {
                $arr[] = $this->quote($v);
            }
            $stringValue = '(' . implode(', ', $arr) . ')';
        } else {
            $stringValue = $this->quote($value);
        }

        return $stringValue;
    }

    /**
     * Sanitize a string.
     */
    public function sanitize(string $string) : string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string);
    }

    /**
     * Sanitize an alias for a SELECT statement.
     */
    public function sanitizeSelectAlias(string $string) : string
    {
        $string = preg_replace('/[^A-Za-z\r\n0-9_:\'" .,\-\(\)]+/', '', $string);
        if (strlen($string) > 256) $string = substr($string, 0, 256);
        return $string;
    }

    protected function sanitizeSelectItem(string $string) : string
    {
        return preg_replace('/[^A-Za-z0-9_:.]+/', '', $string);
    }

    protected function sanitizeIndexName(string $string) : string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string);
    }

    protected function getJoinsTypePart(Entity $entity, array $joins, bool $isLeft = false, $joinConditions = []) : string
    {
        $joinSqlList = [];

        foreach ($joins as $item) {
            $itemConditions = [];
            $params = [];
            if (is_array($item)) {
                $relationName = $item[0];
                if (count($item) > 1) {
                    $alias = $item[1] ?? $relationName;
                    if (count($item) > 2) {
                        $itemConditions = $item[2] ?? [];
                    }
                    if (count($item) > 3) {
                        $params = $item[3] ?? [];
                    }
                } else {
                    $alias = $relationName;
                }
            } else {
                $relationName = $item;
                $alias = $relationName;
            }
            $conditions = [];
            if (!empty($joinConditions[$alias])) {
                $conditions = $joinConditions[$alias];
            }
            foreach ($itemConditions as $left => $right) {
                $conditions[$left] = $right;
            }
            if ($sql = $this->getJoinItemPart($entity, $relationName, $isLeft, $conditions, $alias, $params)) {
                $joinSqlList[] = $sql;
            }
        }

        return implode(' ', $joinSqlList);
    }

    protected function buildJoinConditionStatement(Entity $entity, $alias = null, $left, $right)
    {
        $sql = '';

        if (is_array($right) && (is_int($left) || in_array($left, ['AND', 'OR']))) {
            $logicalOperator = 'AND';
            if ($left == 'OR') {
                $logicalOperator = 'OR';
            }

            $sqlList = [];
            foreach ($right as $k => $v) {
                $sqlList[] = $this->buildJoinConditionStatement($entity, $alias, $k, $v);
            }

            $sql = implode(' ' .$logicalOperator . ' ', $sqlList);

            if (count($sqlList) > 1) {
                $sql = '(' . $sql . ')';
            }

            return $sql;
        }

        $operator = '=';

        $isNotValue = false;
        if (substr($left, -1) === ':') {
            $left = substr($left, 0, strlen($left) - 1);
            $isNotValue = true;
        }

        if (!preg_match('/^[a-z0-9]+$/i', $left)) {
            foreach (self::$comparisonOperators as $op => $opDb) {
                if (substr($left, -strlen($op)) === $op) {
                    $left = trim(substr($left, 0, -strlen($op)));
                    $operator = $opDb;
                    break;
                }
            }
        }

        if (strpos($left, '.') > 0) {
            list($alias, $attribute) = explode('.', $left);
            $alias = $this->sanitize($alias);
            $column = $this->toDb($this->sanitize($attribute));
        } else {
            $column = $this->toDb($this->sanitize($left));
        }
        $sql .= "{$alias}.{$column}";

        if (is_array($right)) {
            $arr = [];
            foreach ($right as $item) {
                $arr[] = $this->quote($item);
            }
            $operator = "IN";
            if ($operator == '<>') {
                $operator = 'NOT IN';
            }
            if (count($arr)) {
                $sql .= " " . $operator . " (" . implode(', ', $arr) . ")";
            } else {
                if ($operator === 'IN') {
                    $sql .= " IS NULL";
                } else {
                    $sql .= " IS NOT NULL";
                }
            }
            return $sql;

        } else {
            $value = $right;
            if (is_null($value)) {
                if ($operator === '=') {
                    $sql .= " IS NULL";
                } else if ($operator === '<>') {
                    $sql .= " IS NOT NULL";
                }
                return $sql;
            }

            if ($isNotValue) {
                $rightPart = $this->convertComplexExpression($entity, $value);
                $sql .= " " . $operator . " " . $rightPart;
                return $sql;
            }

            $sql .= " " . $operator . " " . $this->quote($value);

            return $sql;
        }
    }

    protected function getJoinItemPart(Entity $entity, $name, $isLeft = false, $conditions = [], $alias = null, array $params = [])
    {
        $prefix = ($isLeft) ? 'LEFT ' : '';

        if (!$entity->hasRelation($name)) {
            if (!$alias) {
                $alias = $this->sanitize($name);
            } else {
                $alias = $this->sanitizeSelectAlias($alias);
            }
            $table = $this->toDb($this->sanitize($name));

            $sql = $prefix . "JOIN `{$table}` AS `{$alias}` ON";

            if (empty($conditions)) return '';

            $joinSqlList = [];
            foreach ($conditions as $left => $right) {
                $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right);
            }
            if (count($joinSqlList)) {
                $sql .= " " . implode(" AND ", $joinSqlList);
            }

            return $sql;
        }

        $relationName = $name;

        $relParams = $entity->getRelations()[$relationName];
        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        if (!$alias) {
            $alias = $relationName;
        }

        $alias = $this->sanitize($alias);

        if (!empty($relParams['conditions']) && is_array($relParams['conditions'])) {
            $conditions = array_merge($conditions, $relParams['conditions']);
        }

        $type = $relParams['type'];

        switch ($type) {
            case Entity::MANY_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $relTable = $this->toDb($relParams['relationName']);
                $midAlias = lcfirst($this->sanitize($relParams['relationName']));

                $distantTable = $this->toDb($relParams['entity']);

                $midAlias = $alias . 'Middle';

                $indexKeyList = null;
                $indexList = $params['useIndex'] ?? null;

                if ($indexList) {
                    $indexKeyList = [];
                    if (is_string($indexList)) {
                        $indexList = [$indexList];
                    }
                    foreach ($indexList as $indexName) {
                        $indexKey = $this->metadata->get($entity->getEntityType(), ['relations', $relationName, 'indexes', $indexName, 'key']);
                        if ($indexKey) {
                            $indexKeyList[] = $indexKey;
                        }
                    }
                }

                $indexPart = '';

                if ($indexKeyList && count($indexKeyList)) {
                    $sanitizedIndexList = [];
                    foreach ($indexKeyList as $indexKey) {
                        $sanitizedIndexList[] = '`' . $this->sanitizeIndexName($indexKey) . '`';
                    }
                    $indexPart = " USE INDEX (".implode(', ', $sanitizedIndexList).")";
                }

                $sql =
                    "{$prefix}JOIN `{$relTable}` AS `{$midAlias}`{$indexPart} ON {$this->toDb($entity->getEntityType())}." .
                    $this->toDb($key) . " = {$midAlias}." . $this->toDb($nearKey)
                    . " AND "
                    . "{$midAlias}.deleted = " . $this->quote(false);

                $joinSqlList = [];
                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $midAlias, $left, $right);
                }
                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                $onlyMiddle = $params['onlyMiddle'] ?? false;

                if (!$onlyMiddle) {
                    $sql .= " {$prefix}JOIN `{$distantTable}` AS `{$alias}` ON {$alias}." . $this->toDb($foreignKey) .
                    " = {$midAlias}." . $this->toDb($distantKey)
                        . " AND "
                        . "{$alias}.deleted = " . $this->quote(false) . "";
                }

                return $sql;

            case Entity::HAS_MANY:
            case Entity::HAS_ONE:
                $foreignKey = $keySet['foreignKey'];
                $distantTable = $this->toDb($relParams['entity']);

                $sql =
                    "{$prefix}JOIN `{$distantTable}` AS `{$alias}` ON {$this->toDb($entity->getEntityType())}." .
                    $this->toDb('id') . " = {$alias}." . $this->toDb($foreignKey)
                    . " AND "
                    . "{$alias}.deleted = " . $this->quote(false) . "";

                $joinSqlList = [];
                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right);
                }
                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                return $sql;

            case Entity::HAS_CHILDREN:
                $foreignKey = $keySet['foreignKey'];
                $foreignType = $keySet['foreignType'];
                $distantTable = $this->toDb($relParams['entity']);

                $sql =
                    "{$prefix}JOIN `{$distantTable}` AS `{$alias}` ON " . $this->toDb($entity->getEntityType()) . "." .
                    $this->toDb('id') . " = {$alias}." . $this->toDb($foreignKey)
                    . " AND "
                    . "{$alias}." . $this->toDb($foreignType) . " = " . $this->pdo->quote($entity->getEntityType())
                    . " AND "
                    . "{$alias}.deleted = " . $this->quote(false) . "";

                $joinSqlList = [];
                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right);
                }
                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                return $sql;

            case Entity::BELONGS_TO:
                $sql = $prefix . $this->getBelongsToJoinItemPart($entity, $relationName, null, $alias);
                return $sql;
        }

        return false;
    }

    protected function composeSelectQuery(
        string $table,
        string $select,
        ?string $joins = null,
        ?string $where = null,
        ?string $order = null,
        ?int $offset = null,
        ?int $limit = null,
        bool $distinct = false,
        ?string $groupBy = null,
        ?string $having = null,
        ?array $indexKeyList = null
    ) : string {
        $sql = "SELECT";

        if (!empty($distinct) && empty($groupBy)) {
            $sql .= " DISTINCT";
        }

        $sql .= " {$select}";

        $sql .= " FROM `{$table}`";

        if (!empty($indexKeyList)) {
            foreach ($indexKeyList as $index) {
                $sql .= " USE INDEX (`" . $this->sanitizeIndexName($index) . "`)";
            }
        }

        if (!empty($joins)) {
            $sql .= " {$joins}";
        }

        if ($where !== null && $where !== '') {
            $sql .= " WHERE {$where}";
        }

        if (!empty($groupBy)) {
            $sql .= " GROUP BY {$groupBy}";
        }

        if ($having !== null && $having !== '') {
            $sql .= " HAVING {$having}";
        }

        if (!empty($order)) {
            $sql .= " ORDER BY {$order}";
        }

        if (is_null($offset) && !is_null($limit)) {
            $offset = 0;
        }

        $sql = $this->limit($sql, $offset, $limit);

        return $sql;
    }

    protected function composeDeleteQuery(
        string $table,
        string $where,
        ?string $joins,
        ?string $order,
        ?int $limit
    ) : string {
        $sql = "DELETE FROM `{$table}`";

        if ($joins) {
            $sql .= " {$joins}";
        }

        if ($where) {
            $sql .= " WHERE {$where}";
        }

        if ($order) {
            $sql .= " ORDER BY {$order}";
        }

        if ($limit) {
            $sql = $this->limit($sql, null, $limit);
        }

        return $sql;
    }

    protected function composeUpdateQuery(
        string $table,
        string $set,
        string $where,
        ?string $joins,
        ?string $order,
        ?int $limit
    ) : string {
        $sql = "UPDATE `{$table}`";

        if ($joins) {
            $sql .= " {$joins}";
        }

        $sql .= " SET {$set}";

        if ($where) {
            $sql .= " WHERE {$where}";
        }

        if ($order) {
            $sql .= " ORDER BY {$order}";
        }

        if ($limit) {
            $sql = $this->limit($sql, null, $limit);
        }

        return $sql;
    }

    protected function composeInsertQuery(string $table, string $columns, string $values, ?string $update = null) : string
    {
        $sql = "INSERT INTO `{$table}`";

        $sql .= " ({$columns})";

        $sql .= " {$values}";

        if ($update) {
            $sql .= " ON DUPLICATE KEY UPDATE " . $update;
        }

        return $sql;
    }

    protected function getSetPart(Entity $entity, array $values) : string
    {
        if (!count($values)) {
            throw new RuntimeException("ORM Query: No SET values for update query.");
        }

        $list = [];

        foreach ($values as $attribute => $value) {
            $isNotValue = false;

            if (substr($attribute, -1) == ':') {
                $attribute = substr($attribute, 0, -1);
                $isNotValue = true;
            }

            if (strpos($attribute, '.') > 0) {
                list($alias, $attribute) = explode('.', $attribute);
                $alias = $this->sanitize($alias);
                $column = $this->toDb($this->sanitize($attribute));
                $left = "{$alias}.{$column}";
            } else {
                $table = $this->toDb($entity->getEntityType());
                $column = $this->toDb($this->sanitize($attribute));
                $left = "{$table}.{$column}";
            }

            if ($isNotValue) {
                $right = $this->convertComplexExpression($entity, $value);
            } else {
                $right = $this->quote($value);
            }

            $list[] = $left . " = " . $right;
        }

        return implode(', ', $list);
    }

    protected function getInsertColumnsPart(array $columnList) : string
    {
        $list = [];

        foreach ($columnList as $column) {
            $list[] = '`'.$this->toDb($this->sanitize($column)) . '`';
        }

        return implode(', ', $list);
    }

    protected function getInsertValuesItemPart(array $columnList, array $values) : string
    {
        $list = [];

        foreach ($columnList as $column) {
            $list[] = $this->quote($values[$column]);
        }

        return implode(', ', $list);
    }

    protected function getInsertUpdatePart(array $values) : string
    {
        $list = [];

        foreach ($values as $column => $value) {
            $list[] = "`" . $this->toDb($this->sanitize($column)) . "` = " . $this->quote($value);
        }

        return implode(', ', $list);
    }

    /**
     * Add a LIMIT part to a SQL query.
     */
    abstract public function limit(string $sql, ?int $offset = null, ?int $limit = null) : string;
}
