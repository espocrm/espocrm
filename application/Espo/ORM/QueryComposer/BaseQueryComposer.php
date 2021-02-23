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

namespace Espo\ORM\QueryComposer;

use Espo\ORM\{
    Entity,
    EntityFactory,
    BaseEntity,
    Metadata,
    Mapper\Helper,
    QueryParams\Query as Query,
    QueryParams\Select as SelectQuery,
    QueryParams\Update as UpdateQuery,
    QueryParams\Insert as InsertQuery,
    QueryParams\Delete as DeleteQuery,
    QueryParams\Union as UnionQuery,
    QueryParams\Selecting as SelectingQuery,
    QueryParams\LockTable as LockTableQuery,
};

use PDO;
use RuntimeException;
use LogicException;

/**
 * Composes SQL queries.
 *
 * @todo Break into sub-classes. Put sub-classes into `\Parts` namespace.
 */
abstract class BaseQueryComposer implements QueryComposer
{
    // @todo Remove.
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
        'set',
        'from',
        'fromAlias',
        'fromQuery',
        'forUpdate',
        'forShare',
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

    protected $matchFunctionMap = [
        'MATCH_BOOLEAN' => 'IN BOOLEAN MODE',
        'MATCH_NATURAL_LANGUAGE' => 'IN NATURAL LANGUAGE MODE',
        'MATCH_QUERY_EXPANSION' => 'WITH QUERY EXPANSION',
    ];

    const SELECT_METHOD = 'SELECT';
    const DELETE_METHOD = 'DELETE';
    const UPDATE_METHOD = 'UPDATE';
    const INSERT_METHOD = 'INSERT';

    protected $identifierQuoteCharacter = '`';

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

    protected function getSeed(?string $entityType) : Entity
    {
        if (!$entityType) {
            return new BaseEntity('_Stub');
        }

        if (empty($this->seedCache[$entityType])) {
            $this->seedCache[$entityType] = $this->entityFactory->create($entityType);
        }

        return $this->seedCache[$entityType];
    }

    /**
     * {@inheritdoc}
     */
    public function compose(Query $query) : string
    {
        if ($query instanceof SelectQuery) {
            return $this->composeSelect($query);
        }

        if ($query instanceof UpdateQuery) {
            return $this->composeUpdate($query);
        }

        if ($query instanceof InsertQuery) {
            return $this->composeInsert($query);
        }

        if ($query instanceof DeleteQuery) {
            return $this->composeDelete($query);
        }

        if ($query instanceof UnionQuery) {
            return $this->composeUnion($query);
        }

        if ($query instanceof LockTableQuery) {
            return $this->composeLockTable($query);
        }

        throw new RuntimeException("ORM Query: Unknown query type passed.");
    }

    public function composeCreateSavepoint(string $savepointName) : string
    {
        $savepointName = $this->sanitize($savepointName);

        return 'SAVEPOINT ' . $savepointName;
    }

    public function composeReleaseSavepoint(string $savepointName) : string
    {
        $savepointName = $this->sanitize($savepointName);

        return 'RELEASE SAVEPOINT ' . $savepointName;
    }

    public function composeRollbackToSavepoint(string $savepointName) : string
    {
        $savepointName = $this->sanitize($savepointName);

        return 'ROLLBACK TO SAVEPOINT ' . $savepointName;
    }

    protected function composeSelecting(SelectingQuery $queryParams) : string
    {
        return $this->compose($queryParams);
    }

    public function composeSelect(SelectQuery $queryParams) : string
    {
        $params = $queryParams->getRaw();

        return $this->createSelectQueryInternal($params);
    }

    public function composeUpdate(UpdateQuery $queryParams) : string
    {
        $params = $queryParams->getRaw();

        return $this->createUpdateQuery($params);
    }

    public function composeDelete(DeleteQuery $queryParams) : string
    {
        $params = $queryParams->getRaw();

        return $this->createDeleteQuery($params);
    }

    public function composeInsert(InsertQuery $queryParams) : string
    {
        $params = $queryParams->getRaw();

        return $this->createInsertQuery($params);
    }

    public function composeUnion(UnionQuery $queryParams) : string
    {
        $params = $queryParams->getRaw();

        return $this->createUnionQuery($params);
    }

    /**
     * @deprecated
     * @todo Remove in 6.5.
     */
    public function createSelectQuery(string $entityType, ?array $params = null) : string
    {
        $params = $params ?? [];

        $params['from'] = $entityType;

        return $this->compose(SelectQuery::fromRaw($params));
    }

    protected function createDeleteQuery(array $params = null) : string
    {
        $params = $this->normilizeParams(self::DELETE_METHOD, $params);

        $entityType = $params['from'];

        $alias = $params['fromAlias'] ?? null;

        $entity = $this->getSeed($entityType);

        $wherePart = $this->getWherePart($entity, $params['whereClause'], 'AND', $params);
        $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order'], $params);
        $joinsPart = $this->getJoinsPart($entity, $params);

        $aliasPart = null;

        if ($alias) {
            $aliasPart = $this->sanitize($alias);
        }

        $sql = $this->composeDeleteQuery(
            $this->toDb($entityType),
            $aliasPart,
            $wherePart,
            $joinsPart,
            $orderPart,
            $params['limit']
        );

        return $sql;
    }

    protected function createUpdateQuery(array $params = null) : string
    {
        $params = $this->normilizeParams(self::UPDATE_METHOD, $params);

        $entityType = $params['from'];

        $values = $params['set'];

        $entity = $this->getSeed($entityType);

        $wherePart = $this->getWherePart($entity, $params['whereClause'], 'AND', $params);
        $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order'], $params);
        $joinsPart = $this->getJoinsPart($entity, $params);

        $setPart = $this->getSetPart($entity, $values, $params);

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

    protected function createInsertQuery(array $params) : string
    {
        $params = $this->normilizeInsertParams($params);

        $entityType = $params['into'];

        $columns = $params['columns'];
        $values = $params['values'];
        $updateSet = $params['updateSet'];

        $columnsPart = $this->getInsertColumnsPart($columns);

        $valuesPart = $this->getInsertValuesPart($entityType, $params);

        $updatePart = null;

        if ($updateSet) {
            $updatePart = $this->getInsertUpdatePart($updateSet);
        }

        return $this->composeInsertQuery($this->toDb($entityType), $columnsPart, $valuesPart, $updatePart);
    }

    protected function getInsertValuesPart(string $entityType, array $params)
    {
        $isMass = $params['isMass'];
        $isBySelect = $params['isBySelect'];

        $columns = $params['columns'];
        $values = $params['values'];

        $valuesQuery = $params['valuesQuery'] ?? null;

        if ($isBySelect) {
            return $this->composeSelecting($valuesQuery);
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

    protected function createUnionQuery(array $params) : string
    {
        $selectQueryList = $params['queries'] ?? [];

        $isAll = $params['all'] ?? false;

        $limit = $params['limit'] ?? null;
        $offset = $params['offset'] ?? null;

        $orderBy = $params['orderBy'] ?? [];

        $subSqlList = [];

        foreach ($selectQueryList as $select) {
            $rawSelectParams = $select->getRaw();
            $rawSelectParams['strictSelect'] = true;
            $select = SelectQuery::fromRaw($rawSelectParams);

            $subSqlList[] = '(' . $this->composeSelect($select) . ')';
        }

        $joiner = 'UNION';

        if ($isAll) {
            $joiner .= ' ALL';
        }

        $joiner = ' ' . $joiner . ' ';

        $sql = implode($joiner, $subSqlList);

        if (!empty($orderBy)) {
            $sql .= " ORDER BY " . $this->getUnionOrderPart($orderBy);
        }

        if ($limit !== null || $offset !== null) {
            $sql = $this->limit($sql, $offset, $limit);
        }

        return $sql;
    }

    protected function getUnionOrderPart(array $orderBy)
    {
        $orderByParts = [];

        foreach ($orderBy as $item) {
            $direction = $item[1] ?? 'ASC';

            if (is_bool($direction)) {
                $direction = $direction ? 'DESC' : 'ASC';
            }

            if (is_int($item[0])) {
                $by = (string) $item[0];
            } else {
                $by = $this->quoteIdentifier(
                    $this->sanitizeSelectAlias($item[0])
                );
            }

            $orderByParts[] = $by . ' ' . $direction;
        }

        return implode(', ', $orderByParts);
    }

    protected function normilizeInsertParams(array $params) : array
    {
        $columns = $params['columns'] ?? null;

        if (empty($columns) || !is_array($columns)) {
            throw new RuntimeException("ORM Query: 'columns' is empty for INSERT.");
        }

        $values = $params['values'] = $params['values'] ?? null;

        $valuesQuery = $params['valuesQuery'] = $params['valuesQuery'] ?? null;

        $isBySelect = false;

        if ($valuesQuery) {
            $isBySelect = true;
        }

        if (!$isBySelect) {
            if (empty($values) || !is_array($values)) {
                throw new RuntimeException("ORM Query: 'values' is empty for INSERT.");
            }
        }

        $params['isBySelect'] = $isBySelect;

        $isMass = !$isBySelect && array_keys($values)[0] === 0;

        $params['isMass'] = $isMass;

        if (!$isBySelect) {
            if (!$isMass) {
                foreach ($columns as $item) {
                    if (!array_key_exists($item, $values)) {
                        throw new RuntimeException(
                            "ORM Query: 'values' should contain all items listed in 'columns'."
                        );
                    }
                }
            } else {
                foreach ($values as $valuesItem) {
                    foreach ($columns as $item) {
                        if (!array_key_exists($item, $valuesItem)) {
                            throw new RuntimeException(
                                "ORM Query: 'values' should contain all items listed in 'columns'."
                            );
                        }
                    }
                }
            }
        }

        $updateSet = $params['updateSet'] = $params['updateSet'] ?? null;

        if ($updateSet && !is_array($updateSet)) {
            throw new RuntimeException("ORM Query: Bad 'updateSet' param.");
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

        if ($method !== self::SELECT_METHOD) {
            if (isset($params['aggregation'])) {
                throw new RuntimeException("ORM Query: Param 'aggregation' is not allowed for '{$method}'.");
            }

            if (isset($params['offset'])) {
                throw new RuntimeException("ORM Query: Param 'offset' is not allowed for '{$method}'.");
            }
        }

        if ($method !== self::UPDATE_METHOD) {
            if (isset($params['set'])) {
                throw new RuntimeException("ORM Query: Param 'set' is not allowed for '{$method}'.");
            }
        }

        if (isset($params['set']) && !is_array($params['set'])) {
            throw new RuntimeException("ORM Query: Param 'set' should be an array.");
        }

        return $params;
    }

    protected function createSelectQueryInternal(array $params = null) : string
    {
        $params = $this->normilizeParams(self::SELECT_METHOD, $params);

        $entityType = $params['from'] ?? null;
        $fromQuery = $params['fromQuery'] ?? null;

        if ($entityType === null && !$fromQuery) {
            return $this->createSelectQueryNoFrom($params);
        }

        $entity = $this->getSeed($entityType);

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
        $tailPart = null;

        $wherePart = $this->getWherePart($entity, $whereClause, 'AND', $params);

        if (!empty($havingClause)) {
            $havingPart = $this->getWherePart($entity, $havingClause, 'AND', $params);
        }

        if (!$isAggregation) {
            $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order'], $params);

            $selectPart = $this->getSelectPart($entity, $params);

            $additionalSelectPart = $this->getAdditionalSelect($entity, $params);
            if ($additionalSelectPart) {
                $selectPart .= $additionalSelectPart;
            }

            $tailPart = $this->getSelectTailPart($params);
        }

        if ($isAggregation) {
            $aggregationDistinct = false;

            if ($params['distinct'] && $params['aggregation'] == 'COUNT') {
                $aggregationDistinct = true;
            }

            $params['select'] = [];

            $selectPart = $this->getAggregationSelectPart(
                $entity, $params['aggregation'], $params['aggregationBy'], $aggregationDistinct, $params
            );
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

        $joinsPart = $this->getJoinsPart($entity, $params, !$isAggregation);

        $groupByPart = $this->getGroupByPart($entity, $params);

        $indexKeyList = [];

        if ($entityType) {
            $indexKeyList = $this->getIndexKeyList($entityType, $params);
        }

        $fromAlias = $params['fromAlias'] ?? null;

        if ($fromAlias) {
            $fromAlias = $this->sanitize($fromAlias);
        }

        if ($entityType) {
            $fromPart = $this->quoteIdentifier(
                $this->toDb($entityType)
            );
        }

        if ($fromQuery) {
            $fromPart = '(' . $this->composeSelecting($fromQuery) . ')';
        }

        if ($isAggregation) {
            $sql = $this->composeSelectQuery(
                $fromPart,
                $selectPart,
                $fromAlias,
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
                return $this->wrapCountSql($sql);
            }

            return $sql;
        }

        $sql = $this->composeSelectQuery(
            $fromPart,
            $selectPart,
            $fromAlias,
            $joinsPart,
            $wherePart,
            $orderPart,
            $params['offset'],
            $params['limit'],
            $params['distinct'],
            $groupByPart,
            $havingPart,
            $indexKeyList,
            $tailPart
        );

        return $sql;
    }

    protected function wrapCountSql(string $sql) : string
    {
        return
            "SELECT COUNT(*) AS " . $this->quoteIdentifier('value') . " ".
            "FROM ({$sql}) AS " . $this->quoteIdentifier('countAlias');
    }

    protected function createSelectQueryNoFrom(array $params) : string
    {
        $selectPart = $this->getSelectPart(null, $params);

        $sql = $this->composeSelectQuery(
            null,
            $selectPart
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
                $entity,
                $params['select'],
                array_merge($params['joins'], $params['leftJoins']),
                $params
            );
        }

        if (!empty($params['joins']) && is_array($params['joins'])) {
            // @todo array unique
            $joinsItemPart = $this->getJoinsTypePart(
                $entity, $params['joins'], false, $params['joinConditions'], $params
            );

            if (!empty($joinsItemPart)) {
                if (!empty($joinsPart)) {
                    $joinsPart .= ' ';
                }

                $joinsPart .= $joinsItemPart;
            }
        }

        if (!empty($params['leftJoins']) && is_array($params['leftJoins'])) {
            // @todo array unique
            $joinsItemPart = $this->getJoinsTypePart(
                $entity, $params['leftJoins'], true, $params['joinConditions'], $params
            );

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
        if (!empty($params['strictSelect'])) {
            return null;
        }

        $selectPart = '';

        if (!empty($params['extraAdditionalSelect'])) {
            $extraSelect = [];
            foreach ($params['extraAdditionalSelect'] as $item) {
                if (!in_array($item, $params['select'])) {
                    $extraSelect[] = $item;
                }
            }
            if (count($extraSelect)) {
                $newParams = ['select' => $extraSelect];
                $extraSelectPart = $this->getSelectPart($entity, $newParams);

                if ($extraSelectPart) {
                    $selectPart .= ', ' . $extraSelectPart;
                }
            }
        }

        if (!empty($params['additionalSelectColumns']) && is_array($params['additionalSelectColumns'])) {
            foreach ($params['additionalSelectColumns'] as $column => $field) {
                $itemAlias = $this->sanitizeSelectAlias($field);
                $selectPart .= ", " . $column . " AS " . $this->quoteIdentifier($itemAlias);
            }
        }

        if ($selectPart === '') {
            return null;
        }

        return $selectPart;
    }

    protected function getFunctionPart(
        string $function,
        string $part,
        array $params,
        string $entityType,
        bool $distinct = false,
        ?array $argumentPartList = null
    ) : string {

        if (!in_array($function, Functions::$functionList)) {
            throw new RuntimeException("ORM Query: Not allowed function '{$function}'.");
        }

        if (strpos($function, 'YEAR_') === 0 && $function !== 'YEAR_NUMBER') {
            $fiscalShift = substr($function, 5);
            if (is_numeric($fiscalShift)) {
                $fiscalShift = (int) $fiscalShift;
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
                $fiscalShift = (int) $fiscalShift;
                $fiscalFirstMonth = $fiscalShift + 1;
                $fiscalDistractedMonth = 12 - $fiscalFirstMonth;

                return
                    "CASE WHEN MONTH({$part}) >= {$fiscalFirstMonth} THEN ".
                    "CONCAT(YEAR({$part}), '_', FLOOR((MONTH({$part}) - {$fiscalFirstMonth}) / 3) + 1) ".
                    "ELSE CONCAT(YEAR({$part}) - 1, '_', CEIL((MONTH({$part}) + {$fiscalDistractedMonth}) / 3)) END";
            }
        }

        if ($function === 'TZ') {
            return $this->getFunctionPartTZ($argumentPartList);
        }

        if (in_array($function, Functions::$comparisonFunctionList)) {
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
                return "CONCAT(SUBSTRING(YEARWEEK({$part}, 6), 1, 4), '/', ".
                    "TRIM(LEADING '0' FROM SUBSTRING(YEARWEEK({$part}, 6), 5, 2)))";

            case 'WEEK':
            case 'WEEK_1':
                return "CONCAT(SUBSTRING(YEARWEEK({$part}, 3), 1, 4), '/', ".
                    "TRIM(LEADING '0' FROM SUBSTRING(YEARWEEK({$part}, 3), 5, 2)))";

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

            case 'TIMESTAMPDIFF_SECOND':
                return 'TIMESTAMPDIFF(SECOND, ' . implode(', ', $argumentPartList) . ')';
        }

        if ($distinct) {
            $fromAlias = $this->getFromAlias($params, $entityType);

            $idPart = $fromAlias . ".id";

            switch ($function) {
                case 'COUNT':
                    return $function . "({$part}) * COUNT(DISTINCT {$idPart}) / COUNT({$idPart})";
            }
        }

        return $function . '(' . $part . ')';
    }

    protected function getFunctionPartTZ(?array $argumentPartList = null)
    {
        if (!$argumentPartList || count($argumentPartList) < 2) {
            throw new RuntimeException("ORM Query: Not enough arguments for function TZ.");
        }

        $offsetHoursString = $argumentPartList[1];

        if (substr($offsetHoursString, 0, 1) === '\'' && substr($offsetHoursString, -1) === '\'') {
            $offsetHoursString = substr($offsetHoursString, 1, -1);
        }

        $offset = floatval($offsetHoursString);

        $offsetHours = (int) (floor(abs($offset)));

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

    protected function convertMatchExpression(Entity $entity, string $expression, array $params) : string
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

            $argumentList = Util::parseArgumentListFromFunctionContent($rest);

            if (count($argumentList) < 2) {
                throw new RuntimeException("ORM Query: Bad MATCH usage.");
            }

            $columnList = [];
            for ($i = 0; $i < count($argumentList) - 1; $i++) {
                $columnList[] = $argumentList[$i];
            }

            $query = $argumentList[count($argumentList) - 1];
        } else {
            throw new RuntimeException("ORM Query: Bad MATCH usage.");
        }

        $fromAlias = $this->getFromAlias($params, $entity->getEntityType());

        foreach ($columnList as $i => $column) {
            $columnList[$i] = $fromAlias . '.' . $this->sanitize($this->toDb($column));
        }

        if (!Util::isArgumentString($query)) {
            throw new RuntimeException("ORM Query: Bad MATCH usage. The last argument should be a string.");
        }

        $query = mb_substr($query, 1, -1);

        $query = $this->quote($query);

        if (!in_array($function, Functions::$matchFunctionList)) {
            throw new RuntimeException("ORM Query: Not allowed MATCH usage.");
        }

        $modePart = ' ' . $this->matchFunctionMap[$function];

        $result = "MATCH (" . implode(',', $columnList) . ") AGAINST (" . $query . "" . $modePart . ")";

        return $result;
    }

    protected function convertComplexExpression(
        ?Entity $entity, string $attribute, bool $distinct, array &$params
    ) : string {
        $function = null;

        if (!$entity) {
            $entity = $this->getSeed(null);
        }

        $entityType = $entity->getEntityType();

        if (strpos($attribute, ':')) {
            $dilimeterPosition = strpos($attribute, ':');
            $function = substr($attribute, 0, $dilimeterPosition);

            if (in_array($function, Functions::$matchFunctionList)) {
                return $this->convertMatchExpression($entity, $attribute, $params);
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

        if ($function && in_array($function, Functions::$multipleArgumentsFunctionList)) {
            $arguments = $attribute;

            $argumentList = Util::parseArgumentListFromFunctionContent($arguments);

            $argumentPartList = [];

            foreach ($argumentList as $argument) {
                $argumentPartList[] = $this->getFunctionArgumentPart($entity, $argument, $distinct, $params);
            }

            $part = implode(', ', $argumentPartList);

        } else {
            $part = $this->getFunctionArgumentPart($entity, $attribute, $distinct, $params);
        }

        if ($function) {
            $part = $this->getFunctionPart($function, $part, $params, $entityType, $distinct, $argumentPartList);
        }

        return $part;
    }

    public static function getAllAttributesFromComplexExpression(string $expression) : array
    {
        return Util::getAllAttributesFromComplexExpression($expression);
    }

    protected function getFunctionArgumentPart(
        Entity $entity, string $attribute, bool $distinct, array &$params
    ) : string {

        $argument = $attribute;

        if (Util::isArgumentString($argument)) {
            $string = substr($argument, 1, -1);
            $string = $this->quote($string);

            return $string;
        }
        else if (Util::isArgumentNumeric($argument)) {
            if (filter_var($argument, FILTER_VALIDATE_INT) !== false) {
                $argument = intval($argument);
            }
            else if (filter_var($argument, FILTER_VALIDATE_FLOAT) !== false) {
                $argument = floatval($argument);
            }

            return $this->quote($argument);
        }
        else if (Util::isArgumentBoolOrNull($argument)) {
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
            }
            else {
                if ($part !== '') {
                    $part = $this->getFromAlias($params, $entityType) . '.' . $part;
                }
            }
        }

        return $part;
    }

    protected function getFromAlias(?array $params = null, ?string $entityType = null) : string
    {
        $params = $params ?? [];

        $alias = $params['fromAlias'] ?? null;

        if ($alias) {
            return $this->sanitize($alias);
        }

        $from = $params['from'] ?? null;

        if ($from) {
            return $this->toDb($from);
        }

        if ($entityType) {
            return $this->toDb($entityType);
        }

        throw new RuntimeException();
    }

    protected function getAttributeOrderSql(
        Entity $entity, string $attribute, ?array &$params, string $order
    ) : string {

        $fieldDefs = $entity->getAttributes()[$attribute];

        $defs = $fieldDefs['order'];

        if (is_string($defs)) {
            $defs = [];
        }

        if ($params) {
            $this->applyAttributeCustomParams($defs, $params, $attribute);
        }

        if (is_string($fieldDefs['order'])) {
            $defs = [];
            $part = $fieldDefs['order'];

            $part = str_replace('{direction}', $order, $part);

            return $part;
        }

        if (!empty($defs['sql'])) {
            $part = $defs['sql'];
            $part = str_replace('{direction}', $order, $part);

            return $part;
        }

        if (!empty($defs['order'])) {
            $list = [];

            if (!is_array($defs['order'])) {
                throw new LogicException("Bad custom order defenition.");
            }

            $modifiedOrder = [];

            foreach ($defs['order'] as $item) {
                if (!is_array($item) && !isset($item[0])) {
                    throw new LogicException("Bad custom order defenition.");
                }

                $newItem = [
                    $item[0],
                ];

                if (isset($item[1]) && $item[1] === '{direction}') {
                    $newItem[] = $order;
                }

                $modifiedOrder[] = $newItem;
            }

            $part = $this->getOrderExpressionPart($entity, $modifiedOrder, null, $params, true);

            return $part;
        }

        $part = $this->getFromAlias($params, $entity->getEntityType()) . '.' . $this->toDb($this->sanitize($attribute));

        $part .= ' ' . $order;

        return $part;
    }

    protected function getAttributeSql(
        Entity $entity, string $attribute, string $type, ?array &$params = null, ?string $alias = null
    ) : string {

        $fieldDefs = $entity->getAttributes()[$attribute];

        $defs = $fieldDefs[$type];

        if (is_string($defs)) {
            $defs = [];
        }

        if ($params) {
            $this->applyAttributeCustomParams($defs, $params, $attribute, $alias);
        }

        if (is_string($fieldDefs[$type])) {
            return $fieldDefs[$type];
        }

        if (!empty($defs['sql'])) {
            $part = $defs['sql'];

            if ($alias) {
                $part = str_replace('{alias}', $alias, $part);
            }

            return $part;
        }

        if (!empty($defs['select'])) {
            $expression = $defs['select'];

            $alias = $alias ?? $this->getFromAlias($params, $entity->getEntityType());

            $expression = str_replace('{alias}', $alias, $expression);

            $pair = $this->getSelectPartItemPair($entity, $params, $expression);

            if ($pair === null) {
                throw new LogicException("Could not handle 'select'.");
            }

            return $pair[0];
        }

        return $this->getFromAlias($params, $entity->getEntityType()) . '.' . $this->toDb($this->sanitize($attribute));
    }

    protected function applyAttributeCustomParams(
        array $defs, array &$params, string $attribute, ?string $alias = null
    ) {
        if (!empty($defs['leftJoins'])) {
            foreach ($defs['leftJoins'] as $j) {
                $jAlias = $this->obtainJoinAlias($j);

                if ($alias) {
                    $jAlias = str_replace('{alias}', $alias, $jAlias);
                }

                if (isset($j[1])) {
                    $j[1] = $jAlias;
                }

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

        if (!empty($defs['joins'])) {
            foreach ($defs['joins'] as $j) {
                $jAlias = $this->obtainJoinAlias($j);
                $jAlias = str_replace('{alias}', $alias, $jAlias);

                if (isset($j[1])) {
                    $j[1] = $jAlias;
                }

                foreach ($params['joins'] as $jE) {
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

                $params['joins'][] = $j;
            }
        }

        // Some fields may need additional select items add to a query.
        if (!empty($defs['additionalSelect'])) {
            $params['extraAdditionalSelect'] = $params['extraAdditionalSelect'] ?? [];

            foreach ($defs['additionalSelect'] as $value) {
                $value = str_replace('{alias}', $alias, $value);
                $value = str_replace('{attribute}', $attribute, $value);

                if (!in_array($value, $params['extraAdditionalSelect'])) {
                    $params['extraAdditionalSelect'][] = $value;
                }
            }
        }
    }

    protected function getOrderByAttributeList(array $params) : array
    {
        $value = $params['orderBy'] ?? null;

        if (!$value) {
            return [];
        }

        if (is_numeric($value)) {
            return [];
        }

        if (is_string($value)) {
            $value = [[$value]];
        }

        if (!is_array($value)) {
            return [];
        }

        $list = [];

        foreach ($value as $item) {
            if (!is_array($item) || !isset($item[0])) {
                continue;
            }

            $expression = $item[0];

            if (strpos($expression, 'LIST:') === 0 && substr_count($expression, ':') === 2) {
                $expression = explode(':', $expression)[1];
            }

            $attributeList = self::getAllAttributesFromComplexExpression($expression);

            $list = array_merge(
                $list,
                $attributeList
            );
        }

        return $list;
    }

    protected function getNotIntersectingSelectItemList(array $itemList, array $newItemList) : array
    {
        $list = [];

        foreach ($newItemList as $newItem) {
            $isMet = false;

            foreach ($itemList as $item) {
                $itemToCompare = is_array($item) ? ($item[0] ?? null) : $item;

                if ($itemToCompare === $newItem) {
                    $isMet = true;
                    continue;
                }
            }

            if (!$isMet) {
                $list[] = $newItem;
            }
        }

        return $list;
    }

    protected function getSelectPart(?Entity $entity, array &$params) : string
    {
        $itemList = $params['select'] ?? [];

        $selectNotSpecified = !count($itemList);

        if (!$selectNotSpecified && $itemList[0] === '*' && $entity) {
            array_shift($itemList);

            foreach (array_reverse($entity->getAttributeList()) as $item) {
                array_unshift($itemList, $item);
            }
        }

        if ($selectNotSpecified && $entity) {
            $itemList = $entity->getAttributeList();
        }

        if (empty($params['strictSelect']) && $entity) {
            $itemList = array_merge(
                $itemList,
                $this->getSelectDependeeAdditionalList($entity, $itemList)
            );
        }

        if (empty($params['strictSelect']) && !empty($params['distinct'])) {
            $orderByAttributeList = $this->getOrderByAttributeList($params);

            $itemList = array_merge(
                $itemList,
                $this->getNotIntersectingSelectItemList($itemList, $orderByAttributeList)
            );
        }

        foreach ($itemList as $i => $item) {
            if (is_string($item)) {
                if (strpos($item, ':')) {
                    $itemList[$i] = [$item, $item];

                    continue;
                }

                continue;
            }
        }

        $itemPairList = [];

        foreach ($itemList as $item) {
            $pair = $this->getSelectPartItemPair($entity, $params, $item);

            if ($pair === null) {
                continue;
            }

            $itemPairList[] = $pair;
        }

        if (!count($itemPairList)) {
            throw new RuntimeException("ORM Query: Select part can't be empty.");
        }

        $selectPartItemList = [];

        foreach ($itemPairList as $item) {
            $expression = $item[0];
            $alias = $this->sanitizeSelectAlias($item[1]);

            if ($expression === '' || $alias === '') {
                throw new RuntimeException("Bad select expression.");
            }

            $selectPartItemList[] = "{$expression} AS " . $this->quoteIdentifier($alias);
        }

        $selectPart = implode(', ', $selectPartItemList);

        return $selectPart;
    }

    protected function getSelectPartItemPair(?Entity $entity, array &$params, $attribute) : ?array
    {
        $maxTextColumnsLength = $params['maxTextColumnsLength'] ?? null;
        $skipTextColumns = $params['skipTextColumns'] ?? false;
        $distinct = $params['distinct'] ?? false;

        $attributeType = null;

        if (!is_array($attribute) && !is_string($attribute)) {
            throw new RuntimeException("ORM Query: Bad select item.");
        }

        if (is_string($attribute) && $entity) {
            $attributeType = $entity->getAttributeType($attribute);
        }

        if ($skipTextColumns) {
            if ($attributeType === Entity::TEXT) {
                return null;
            }
        }

        $expression = $attribute;
        $alias = $expression;

        if (is_array($attribute) && count($attribute)) {
            $expression = $attribute[0];

            if (count($attribute) >= 2) {
                $alias = $attribute[1];
            }
        }

        // @todo Make VALUE: usage deprecated.
        if (stripos($expression, 'VALUE:') === 0) {
            $part = substr($expression, 6);

            if ($part !== false) {
                $part = $this->quote($part);
            } else {
                $part = $this->quote('');
            }

            return [$part, $alias];
        }

        if (!$entity) {
            return [
                $this->convertComplexExpression(null, $expression, false, $params),
                $alias
            ];
        }

        if (is_array($attribute) && count($attribute) == 2) {
            $alias = $attribute[1];

            if (!$entity->hasAttribute($attribute[0])) {
                $part = $this->convertComplexExpression($entity, $attribute[0], $distinct, $params);

                return [$part, $alias];
            }

            $fieldDefs = $entity->getAttributes()[$attribute[0]];

            if (!empty($fieldDefs['select'])) {
                $part = $this->getAttributeSql($entity, $attribute[0], 'select', $params);

                return [$part, $alias];
            }

            if (!empty($fieldDefs['noSelect'])) {
                return null;
            }

            if (!empty($fieldDefs['notStorable'])) {
                return null;
            }

            $part = $this->getAttributePath($entity, $attribute[0], $params);

            return [$part, $alias];
        }

        if (!$entity->hasAttribute($attribute)) {
            $expression = $attribute;

            $part = $this->convertComplexExpression($entity, $expression, $distinct, $params);

            return [$part, $attribute];
        }

        if ($entity->getAttributeParam($attribute, 'select')) {
            $fieldPath = $this->getAttributeSql($entity, $attribute, 'select', $params);

            return [$fieldPath, $attribute];
        }

        if ($attributeType === null) {
            return null;
        }

        if ($entity->getAttributeParam($attribute, 'notStorable') && $attributeType !== Entity::FOREIGN) {
            return null;
        }

        $fieldPath = $this->getAttributePath($entity, $attribute, $params);

        if ($attributeType === Entity::TEXT && $maxTextColumnsLength !== null) {
            $fieldPath = 'LEFT(' . $fieldPath . ', '. strval($maxTextColumnsLength) . ')';
        }

        return [$fieldPath, $attribute];
    }

    protected function getSelectDependeeAdditionalList(Entity $entity, array $itemList) : array
    {
        $additionalList = [];

        $itemList = array_filter(
            $itemList,
            function ($item) use ($entity) {
                return is_string($item) && $entity->hasAttribute($item);
            }
        );

        foreach ($itemList as $item) {
            $additionalList = array_merge(
                $additionalList,
                $entity->getAttributeParam($item, 'dependeeAttributeList') ?? []
            );
        }

        $additionalList = array_filter(
            $additionalList,
            function ($item) use ($itemList) {
                return !in_array($item, $itemList);
            }
        );

        return $additionalList;
    }

    protected function getBelongsToJoinItemPart(
        Entity $entity, string $relationName, ?string $alias = null, ?array $params = null
    ) : ?string {

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        if (!$alias) {
            $alias = $this->getAlias($entity, $relationName);
        }
        else {
            $alias = $this->sanitizeSelectAlias($alias);
        }

        if (!$alias) {
            return null;
        }

        $foreignEntityType = $entity->getRelationParam($relationName, 'entity');

        $table = $this->toDb($foreignEntityType);

        $fromAlias = $this->getFromAlias($params, $entity->getEntityType());

        return
            "JOIN " . $this->quoteIdentifier($table) . " AS " . $this->quoteIdentifier($alias) . " ON ".
            "{$fromAlias}." . $this->toDb($key) . " = " .
            "{$alias}." . $this->toDb($foreignKey);
    }

    protected function getSelectTailPart(array $params) : ?string
    {
        $forShare = $params['forShare'] ?? null;
        $forUpdate = $params['forUpdate'] ?? null;

        if ($forShare) {
            return "FOR SHARE";
        }

        if ($forUpdate) {
            return "FOR UPDATE";
        }

        return null;
    }

    protected function getBelongsToJoinsPart(Entity $entity, ?array $select, array $skipList, array $params) : string
    {
        $joinsArr = [];

        $relationsToJoin = [];

        if (is_array($select)) {

            foreach ($select as $item) {
                $field = $item;

                if (is_array($item)) {
                    if (count($field) == 0) {
                        continue;
                    }

                    $field = $item[0];
                }
                if (
                    $entity->getAttributeType($field) == 'foreign' && $entity->getAttributeParam($field, 'relation')
                ) {
                    $relationsToJoin[] = $entity->getAttributeParam($field, 'relation');
                }
                else if (
                    $entity->getAttributeParam($field, 'fieldType') == 'linkOne' &&
                    $entity->getAttributeParam($field, 'relation')
                ) {
                    $relationsToJoin[] = $entity->getAttributeParam($field, 'relation');
                }
            }
        }

        foreach ($entity->getRelationList() as $relationName) {
            $type = $entity->getRelationType($relationName);

            if ($type === Entity::BELONGS_TO || $type === Entity::HAS_ONE) {
                if ($entity->getRelationParam($relationName, 'noJoin')) {
                    continue;
                }

                if (in_array($relationName, $skipList)) {
                    continue;
                }

                foreach ($skipList as $sItem) {
                    if (is_array($sItem) && count($sItem) > 1) {
                        if ($sItem[1] === $relationName) {
                            continue 2;
                        }
                    }
                }

                if (
                    is_array($select) && !self::isSelectAll($select) && !in_array($relationName, $relationsToJoin)
                ) {
                    continue;
                }

                if ($type == Entity::BELONGS_TO) {
                    $join = $this->getBelongsToJoinItemPart($entity, $relationName, null, $params);

                    if (!$join) {
                        continue;
                    }

                    $joinsArr[] = 'LEFT ' . $join;
                }
                else if ($type == Entity::HAS_ONE) {
                    $join =  $this->getJoinItemPart($entity, $relationName, true, [], null, $params);

                    $joinsArr[] = $join;
                }
            }
        }

        return implode(' ', $joinsArr);
    }

    protected static function isSelectAll(array $select) : bool
    {
        if (!count($select)) {
            return true;
        }

        return $select[0] === '*';
    }

    protected function getOrderExpressionPart(
        Entity $entity,
        $orderBy = null,
        $order = null,
        ?array &$params = null,
        bool $noCustom = false
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

                    $arr[] = $this->getOrderExpressionPart(
                        $entity, $orderByInternal, $orderInternal, $params, $noCustom
                    );
                }
            }

            return implode(", ", $arr);
        }

        if (strpos($orderBy, 'LIST:') === 0) {
            list($l, $field, $list) = explode(':', $orderBy);

            $fieldPath = $this->getAttributePathForOrderBy($entity, $field, $params);


            $listQuoted = [];

            $list = array_reverse(explode(',', $list));

            foreach ($list as $i => $listItem) {
                $listItem = str_replace('_COMMA_', ',', $listItem);
                $listQuoted[] = $this->quote($listItem);
            }

            return "FIELD(" . $fieldPath . ", " . implode(", ", $listQuoted) . ") DESC";
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

        if (!$noCustom && !empty($fieldDefs) && !empty($fieldDefs['order'])) {
            return $this->getAttributeOrderSql($entity, $orderBy, $params, $order);
        }

        $fieldPath = $this->getAttributePathForOrderBy($entity, $orderBy, $params);

        if (!$fieldPath) {
            throw new LogicException("Could not handle 'order' for '".$entity->getEntityType()."'.");
        }

        return "{$fieldPath} " . $order;
    }

    protected function getOrderPart(Entity $entity, $orderBy = null, $order = null, &$params = null) : ?string
    {
        return $this->getOrderExpressionPart($entity, $orderBy, $order, $params);
    }

    protected function getAttributePathForOrderBy(Entity $entity, string $orderBy, array $params) : ?string
    {
        if (strpos($orderBy, '.') !== false || strpos($orderBy, ':') !== false) {
            return $this->convertComplexExpression(
                $entity,
                $orderBy,
                false,
                $params
            );
        }

        return $this->getAttributePath($entity, $orderBy, $params);
    }

    protected function getAggregationSelectPart(
        Entity $entity, string $aggregation, string $aggregationBy, bool $distinct, array $params
    ) : ?string {

        if (!isset($entity->getAttributes()[$aggregationBy])) {
            return null;
        }

        $aggregation = strtoupper($aggregation);

        $distinctPart = '';
        if ($distinct) {
            $distinctPart = 'DISTINCT ';
        }

        $fromAlias = $this->getFromAlias($params, $entity->getEntityType());

        $selectPart = "{$aggregation}({$distinctPart}{$fromAlias}." .
            $this->toDb($this->sanitize($aggregationBy)) . ") AS " . $this->quoteIdentifier('value') . "";

        return $selectPart;
    }

    protected function quoteIdentifier(string $string) : string
    {
        return $this->identifierQuoteCharacter . $string . $this->identifierQuoteCharacter;
    }

    /**
     * Quote a value (if needed).
     * @deprecated
     * @todo Make protected in 6.5.
     */
    public function quote($value) : string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return strval($value);
        }

        if (is_float($value)) {
            return strval($value);
        }

        return $this->pdo->quote($value);
    }

    /**
     * Converts field and entity names to a form required for database.
     */
    protected function toDb(string $string) : string
    {
        if (!array_key_exists($string, $this->attributeDbMapCache)) {
            $string[0] = strtolower($string[0]);
            $this->attributeDbMapCache[$string] = preg_replace_callback('/([A-Z])/', [$this, 'toDbMatchConversion'], $string);
        }

        return $this->attributeDbMapCache[$string];
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
        }

        return null;
    }

    protected function getTableAliases(Entity $entity) : array
    {
        $aliases = [];

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

    protected function getAttributePath(Entity $entity, string $attribute, array &$params) : ?string
    {
        if (!isset($entity->getAttributes()[$attribute])) {
            return null;
        }

        $entityType = $entity->getEntityType();

        $f = $entity->getAttributes()[$attribute];

        $relationType = $f['type'];

        if (isset($f['source'])) {
            if ($f['source'] != 'db') {
                return null;
            }
        }

        if (!empty($f['notStorable']) && $relationType !== 'foreign') {
            return null;
        }

        switch ($relationType) {
            case 'foreign':
                $relationName = $f['relation'] ?? null;

                if (!$relationName) {
                    return null;
                }

                $relationName = $f['relation'];

                $foreign = $f['foreign'];

                if (is_array($foreign)) {
                    $wsCount = 0;

                    foreach ($foreign as $i => $value) {
                        if ($value == ' ') {
                            $foreign[$i] = '\' \'';
                            $wsCount ++;
                        }
                        else {
                            $item =  $this->getAlias($entity, $relationName) . '.' . $this->toDb($value);

                            $foreign[$i] = "IFNULL({$item}, '')";
                        }
                    }

                    $path = 'TRIM(CONCAT(' . implode(', ', $foreign). '))';

                    if ($wsCount > 1) {
                        $path = "REPLACE({$path}, '  ', ' ')";
                    }

                    $path = "NULLIF({$path}, '')";
                }
                else {
                    $expression = $this->getAlias($entity, $relationName) . '.' . $foreign;
                    $path = $this->convertComplexExpression($entity, $expression, false, $params);
                }

                return $path;
        }

        $alias = $this->getFromAlias($params, $entityType);

        return $alias . '.' . $this->toDb($this->sanitize($attribute));
    }

    protected function getWherePart(
        Entity $entity,
        ?array $whereClause = null,
        string $sqlOp = 'AND',
        array &$params = [],
        int $level = 0,
        bool $noCustomWhere = false
    ) : string {

        $wherePartList = [];

        $whereClause = $whereClause ?? [];

        foreach ($whereClause as $field => $value) {
            $partItem = $this->getWherePartItem($entity, $field, $value, $params, $level, $noCustomWhere);

            if ($partItem === null) {
                continue;
            }

            $wherePartList[] = $partItem;
        }

        return implode(" " . $sqlOp . " ", $wherePartList);
    }

    protected function getWherePartItem(
        Entity $entity, $field, $value, array &$params, int $level, bool $noCustomWhere = false
    ) : ?string {

        if (is_int($field) && is_string($value)) {
            return $this->convertMatchExpression($entity, $value, $params);
        }

        $noCustomWhere = $noCustomWhere ?? $params['noCustomWhere'] ?? false;

        if (is_int($field)) {
            $field = 'AND';
        }

        if ($field === 'NOT') {
            if ($level > 1) {
                return '0';
            }

            $field = 'id!=s';

            $value = [
                'selectParams' => [
                    'select' => ['id'],
                    'whereClause' => $value,
                ],
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

            if (!$internalPart && $internalPart !== '0') {
                return null;
            }

            return "(" . $internalPart . ")";
        }

        $isComplex = false;
        $isNotValue = false;

        $operator = '=';
        $operatorOrm = '=';

        $leftPart = null;

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

        if (!$isComplex) {
            if (!$entity->hasAttribute($field)) {
                return '0';
            }

            $operatorModified = $operator;

            $attributeType = $entity->getAttributeType($field) ?? null;

            $fieldDefs = $entity->getAttributes()[$field];

            if (is_bool($value) && in_array($operator, ['=', '<>']) && $attributeType == Entity::BOOL) {
                if ($value) {
                    if ($operator === '=') {
                        $operatorModified = '= TRUE';
                    }
                    else {
                        $operatorModified = '= FALSE';
                    }
                } else {
                    if ($operator === '=') {
                        $operatorModified = '= FALSE';
                    }
                    else {
                        $operatorModified = '= TRUE';
                    }
                }
            } else if (is_array($value)) {
                if ($operator == '=') {
                    $operatorModified = 'IN';
                }
                else if ($operator == '<>') {
                    $operatorModified = 'NOT IN';
                }
            } else if (is_null($value)) {
                if ($operator == '=') {
                    $operatorModified = 'IS NULL';
                }
                else if ($operator == '<>') {
                    $operatorModified = 'IS NOT NULL';
                }
            }

            if (!$noCustomWhere && !empty($fieldDefs['where']) && !empty($fieldDefs['where'][$operatorModified])) {
                $whereSqlPart = '';
                $customWhereClause = null;

                $whereDefs = $fieldDefs['where'][$operatorModified];

                if (is_string($whereDefs)) {
                    $whereSqlPart = $whereDefs;
                    $whereDefs = [];
                }
                else if (!empty($whereDefs['sql'])) {
                    $whereSqlPart = $whereDefs['sql'];
                }
                else if (!empty($whereDefs['whereClause'])) {
                    $customWhereClause = $this->applyValueToCustomWhereClause($whereDefs['whereClause'], $value);
                }
                else {
                    return '0';
                }

                if (!empty($whereDefs['leftJoins'])) {
                    foreach ($whereDefs['leftJoins'] as $j) {
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

                if (!empty($whereDefs['joins'])) {
                    foreach ($whereDefs['joins'] as $j) {
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

                if (!empty($whereDefs['customJoin'])) {
                    $params['customJoin'] .= ' ' . $whereDefs['customJoin'];
                }

                if (!empty($whereDefs['distinct'])) {
                    $params['distinct'] = true;
                }

                if ($customWhereClause) {
                    return "(" .$this->getWherePart($entity, $customWhereClause, 'AND', $params, $level, true) . ")";
                }

                return str_replace('{value}', $this->stringifyValue($value), $whereSqlPart);
            }

            if ($fieldDefs['type'] == Entity::FOREIGN) {
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
                                $leftPart = $this->getAttributePath($entity, $field, $params);
                            }
                        }
                    }
                }
            } else {
                $fromAlias = $this->getFromAlias($params, $entity->getEntityType());

                $leftPart = $fromAlias . '.' . $this->toDb($this->sanitize($field));
            }
        }

        if ($leftPart === null) {
            return '0';
        }

        if ($operatorOrm === '=s' || $operatorOrm === '!=s') {
            if (!is_array($value)) {
                return '0';
            }

            if (!empty($value['entityType'])) {
                $subQueryEntityType = $value['entityType'];
            } else {
                $subQueryEntityType = $entity->getEntityType();
            }

            $subQuerySelectParams = [];

            if (!empty($value['selectParams'])) {
                $subQuerySelectParams = $value['selectParams'];
            } else {
                $subQuerySelectParams = $value;
            }

            $subQueryEntityType = $subQuerySelectParams['from'] ?? $subQueryEntityType;

            if (!empty($value['withDeleted'])) {
                $subQuerySelectParams['withDeleted'] = true;
            }

            $subSql = $this->createSelectQuery($subQueryEntityType, $subQuerySelectParams);

            return $leftPart . " {$operator} ({$subSql})";
        }

        if (is_array($value)) {
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

            if (empty($valArr)) {
                return $emptyValue;
            }

            return $leftPart . " {$oppose}IN " . "(" . implode(',', $valArr) . ")";
        }

        if ($isNotValue) {
            if (is_null($value)) {
                return $leftPart;
            }

            $expressionSql = $this->convertComplexExpression($entity, $value, false, $params);

            return $leftPart . " " . $operator . " " . $expressionSql;
        }

        if (is_null($value)) {
            if ($operator == '=') {
                return $leftPart . " IS NULL";
            }

            if ($operator == '<>') {
                return $leftPart . " IS NOT NULL";
            }

            return '0';
        }

        return $leftPart . " " . $operator . " " . $this->quote($value);
    }

    protected function applyValueToCustomWhereClause(array $whereClause, $value) : array
    {
        $modified = [];

        foreach ($whereClause as $left => $right) {
            if ($right === '{value}') {
                $right = $value;
            }
            else if (is_string($right)) {
                $right = str_replace('{value}', (string) $value, $right);
            }
            else if (is_array($right)) {
                $right = $this->applyValueToCustomWhereClause($right, $value);
            }

            $modified[$left] = $right;
        }

        return $modified;
    }

    protected function obtainJoinAlias($j)
    {
        if (is_array($j)) {
            if (count($j)) {
                if ($j[1]) {
                    $joinAlias = $j[1];
                }
                else {
                    $joinAlias = $j[0];
                }
            } else {
                $joinAlias = $j[0];
            }
        } else {
            $joinAlias = $j;
        }

        return $joinAlias;
    }

    protected function stringifyValue($value) : string
    {
        if (is_array($value)) {
            $arr = [];

            foreach ($value as $v) {
                $arr[] = $this->quote($v);
            }

            $stringValue = '(' . implode(', ', $arr) . ')';
        }
        else {
            $stringValue = $this->quote($value);
        }

        return $stringValue;
    }

    /**
     * Sanitize a string.
     * @todo Make protected in 6.5.
     * @deprecated
     */
    public function sanitize(string $string) : string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string);
    }

    /**
     * Sanitize an alias for a SELECT statement.
     * @todo Make protected in 6.5.
     * @deprecated
     */
    public function sanitizeSelectAlias(string $string) : string
    {
        $string = preg_replace('/[^A-Za-z\r\n0-9_:\'" .,\-\(\)]+/', '', $string);

        if (strlen($string) > 256) {
            $string = substr($string, 0, 256);
        }

        return $string;
    }

    protected function sanitizeIndexName(string $string) : string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string);
    }

    protected function getJoinsTypePart(
        Entity $entity, array $joins, bool $isLeft, $joinConditions, array $params
    ) : string {
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

            $sql = $this->getJoinItemPart($entity, $relationName, $isLeft, $conditions, $alias, $params);

            if ($sql) {
                $joinSqlList[] = $sql;
            }
        }

        return implode(' ', $joinSqlList);
    }

    protected function buildJoinConditionStatement(Entity $entity, $alias, $left, $right, array $params)
    {
        $sql = '';

        if (is_array($right) && (is_int($left) || in_array($left, ['AND', 'OR']))) {
            $logicalOperator = 'AND';

            if ($left == 'OR') {
                $logicalOperator = 'OR';
            }

            $sqlList = [];
            foreach ($right as $k => $v) {
                $sqlList[] = $this->buildJoinConditionStatement($entity, $alias, $k, $v, $params);
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
        }
        else {
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
        }

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
            $rightPart = $this->convertComplexExpression($entity, $value, false, $params);

            $sql .= " " . $operator . " " . $rightPart;

            return $sql;
        }

        $sql .= " " . $operator . " " . $this->quote($value);

        return $sql;
    }

    protected function getJoinItemPart(
        Entity $entity,
        string $name,
        bool $isLeft = false,
        array $conditions = [],
        ?string $alias = null,
        array $params = []
    ) : string {

        $prefix = ($isLeft) ? 'LEFT ' : '';

        if (!$entity->hasRelation($name)) {
            if (!$alias) {
                $alias = $this->sanitize($name);
            } else {
                $alias = $this->sanitizeSelectAlias($alias);
            }
            $table = $this->toDb($this->sanitize($name));

            $sql = $prefix . "JOIN " . $this->quoteIdentifier($table) . " AS " . $this->quoteIdentifier($alias) . "";

            if (empty($conditions)) {
                return $sql;
            }

            $sql .= " ON";

            $joinSqlList = [];

            foreach ($conditions as $left => $right) {
                $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right, $params);
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

        $fromAlias = $this->getFromAlias($params, $entity->getEntityType());

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
                        $indexKey = $this->metadata->get(
                            $entity->getEntityType(), ['relations', $relationName, 'indexes', $indexName, 'key']
                        );

                        if ($indexKey) {
                            $indexKeyList[] = $indexKey;
                        }
                    }
                }

                $indexPart = '';

                if ($indexKeyList && count($indexKeyList)) {
                    $sanitizedIndexList = [];

                    foreach ($indexKeyList as $indexKey) {
                        $sanitizedIndexList[] = $this->quoteIdentifier(
                            $this->sanitizeIndexName($indexKey)
                        );
                    }

                    $indexPart = " USE INDEX (".implode(', ', $sanitizedIndexList).")";
                }

                $sql =
                    "{$prefix}JOIN ".$this->quoteIdentifier($relTable)." AS " .
                    $this->quoteIdentifier($midAlias) . "{$indexPart} " .
                    "ON {$fromAlias}." . $this->toDb($key) . " = {$midAlias}." . $this->toDb($nearKey) .
                    " AND " .
                    "{$midAlias}.deleted = " . $this->quote(false);

                $joinSqlList = [];

                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $midAlias, $left, $right, $params);
                }

                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                $onlyMiddle = $params['onlyMiddle'] ?? false;

                if (!$onlyMiddle) {
                    $sql .= " {$prefix}JOIN " . $this->quoteIdentifier($distantTable)." AS " .
                        $this->quoteIdentifier($alias) . " ".
                        "ON {$alias}." . $this->toDb($foreignKey) .
                        " = {$midAlias}." . $this->toDb($distantKey)
                            . " AND "
                            . "{$alias}.deleted = " . $this->quote(false);
                }

                return $sql;

            case Entity::HAS_MANY:
            case Entity::HAS_ONE:
                $foreignKey = $keySet['foreignKey'];
                $distantTable = $this->toDb($relParams['entity']);

                $sql =
                    "{$prefix}JOIN " . $this->quoteIdentifier($distantTable) . " AS " .
                    $this->quoteIdentifier($alias) . " ".
                    "ON {$fromAlias}." .
                    $this->toDb('id') . " = {$alias}." . $this->toDb($foreignKey)
                    . " AND "
                    . "{$alias}.deleted = " . $this->quote(false);

                $joinSqlList = [];

                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right, $params);
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
                    "{$prefix}JOIN " . $this->quoteIdentifier($distantTable) . " AS ".
                    $this->quoteIdentifier($alias) . " ON ".
                    "{$fromAlias}." .
                    $this->toDb('id') . " = {$alias}." . $this->toDb($foreignKey)
                    . " AND "
                    . "{$alias}." . $this->toDb($foreignType) . " = " . $this->pdo->quote($entity->getEntityType())
                    . " AND "
                    . "{$alias}.deleted = " . $this->quote(false) . "";

                $joinSqlList = [];

                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right, $params);
                }

                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                return $sql;

            case Entity::BELONGS_TO:
                $sql = $prefix . $this->getBelongsToJoinItemPart($entity, $relationName, $alias, $params);

                return $sql;
        }

        return false;
    }

    protected function composeSelectQuery(
        ?string $from,
        string $select,
        ?string $alias = null,
        ?string $joins = null,
        ?string $where = null,
        ?string $order = null,
        ?int $offset = null,
        ?int $limit = null,
        bool $distinct = false,
        ?string $groupBy = null,
        ?string $having = null,
        ?array $indexKeyList = null,
        ?string $tailPart = null
    ) : string {
        $sql = "SELECT";

        if (!empty($distinct) && empty($groupBy)) {
            $sql .= " DISTINCT";
        }

        $sql .= " {$select}";

        if ($from) {
            $sql .= " FROM {$from}";
        }

        if ($alias) {
            $sql .= " AS " . $this->quoteIdentifier($alias);
        }

        if (!empty($indexKeyList)) {
            foreach ($indexKeyList as $index) {
                $sql .= " USE INDEX (" . $this->quoteIdentifier($this->sanitizeIndexName($index)) . ")";
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

        if ($tailPart) {
            $sql .= " " . $tailPart;
        }

        return $sql;
    }

    protected function composeDeleteQuery(
        string $table,
        ?string $alias,
        string $where,
        ?string $joins,
        ?string $order,
        ?int $limit
    ) : string {

        $sql = "DELETE ";

        if ($alias) {
            $sql .= $this->quoteIdentifier($alias) . " ";
        }

        $sql .= "FROM " . $this->quoteIdentifier($table);


        if ($alias) {
            $sql .= " AS " . $this->quoteIdentifier($alias);
        }

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

        $sql = "UPDATE " . $this->quoteIdentifier($table);

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
        $sql = "INSERT INTO " . $this->quoteIdentifier($table) . " ({$columns}) {$values}";

        if ($update) {
            $sql .= " ON DUPLICATE KEY UPDATE " . $update;
        }

        return $sql;
    }

    protected function getSetPart(Entity $entity, array $values, array $params) : string
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
                $right = $this->convertComplexExpression($entity, $value, false, $params);
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
            $list[] = $this->quoteIdentifier(
                $this->toDb(
                    $this->sanitize($column)
                )
            );
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
            $list[] = $this->quoteIdentifier(
                $this->toDb(
                    $this->sanitize($column)
                )
            ) . " = " . $this->quote($value);
        }

        return implode(', ', $list);
    }

    /**
     * Add a LIMIT part to a SQL query.
     */
    abstract protected function limit(string $sql, ?int $offset = null, ?int $limit = null) : string;
}
