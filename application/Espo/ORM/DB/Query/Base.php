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

namespace Espo\ORM\DB\Query;

use Espo\ORM\Entity;
use Espo\ORM\IEntity;
use Espo\ORM\EntityFactory;
use Espo\ORM\Metadata;
use PDO;

abstract class Base
{
    protected static $selectParamList = [
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
        'useIndexList',
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

    protected $entityFactory;

    protected $pdo;

    protected $metadata;

    protected $fieldsMapCache = [];

    protected $aliasesCache = [];

    protected $seedCache = [];

    public function __construct(PDO $pdo, EntityFactory $entityFactory, Metadata $metadata = null)
    {
        $this->entityFactory = $entityFactory;
        $this->pdo = $pdo;
        $this->metadata = $metadata;
    }

    protected function getSeed($entityType)
    {
        if (empty($this->seedCache[$entityType])) {
            $this->seedCache[$entityType] = $this->entityFactory->create($entityType);
        }
        return $this->seedCache[$entityType];
    }

    public function createSelectQuery(string $entityType, ?array $params = null, $withDeleted = false)
    {
        $entity = $this->getSeed($entityType);

        $params = $params ?? [];

        foreach (self::$selectParamList as $k) {
            $params[$k] = array_key_exists($k, $params) ? $params[$k] : null;
        }

        $whereClause = $params['whereClause'];
        if (empty($whereClause)) {
            $whereClause = [];
        }

        if (!$withDeleted && empty($params['withDeleted'])) {
            $whereClause = $whereClause + ['deleted' => 0];
        }

        if (empty($params['joins'])) {
            $params['joins'] = [];
        }
        if (empty($params['leftJoins'])) {
            $params['leftJoins'] = [];
        }
        if (empty($params['customJoin'])) {
            $params['customJoin'] = '';
        }

        $wherePart = $this->getWhere($entity, $whereClause, 'AND', $params);

        $havingClause = $params['havingClause'];
        $havingPart = '';
        if (!empty($havingClause)) {
            $havingPart = $this->getWhere($entity, $havingClause, 'AND', $params);
        }

        if (empty($params['aggregation'])) {
            $selectPart = $this->getSelect($entity, $params['select'], $params['distinct'], $params['skipTextColumns'], $params['maxTextColumnsLength'], $params);
            $orderPart = $this->getOrder($entity, $params['orderBy'], $params['order'], $params);

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
            $params['select'] = [];
            $selectPart = $this->getAggregationSelect($entity, $params['aggregation'], $params['aggregationBy'], $aggDist);
        }

        $joinsPart = $this->getBelongsToJoins($entity, $params['select'], array_merge($params['joins'], $params['leftJoins']));

        if (!empty($params['customWhere'])) {
            if (!empty($wherePart)) {
                $wherePart .= ' ';
            }
            $wherePart .= $params['customWhere'];
        }

        if (!empty($params['customHaving'])) {
            if (!empty($havingPart)) {
                $havingPart .= ' ';
            }
            $havingPart .= $params['customHaving'];
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
            $arr = [];
            foreach ($params['groupBy'] as $field) {
                $arr[] = $this->convertComplexExpression($entity, $field, false, $params);
            }
            $groupByPart = implode(', ', $arr);
        }

        $indexKeyList = null;
        if (!empty($params['useIndexList']) && $this->metadata) {
            $indexKeyList = [];
            foreach ($params['useIndexList'] as $indexName) {
                $indexKey = $this->metadata->get($entityType, ['indexes', $indexName, 'key']);
                if ($indexKey) {
                    $indexKeyList[] = $indexKey;
                }
            }
        }

        if (!empty($params['aggregation'])) {
            $sql = $this->composeSelectQuery(
                $this->toDb($entityType),
                $selectPart,
                $joinsPart,
                $wherePart,
                null,
                null,
                null,
                false,
                $params['aggregation'],
                $groupByPart,
                $havingPart,
                $indexKeyList
            );
            if ($params['aggregation'] === 'COUNT' && $groupByPart && $havingPart) {
                $sql = "SELECT COUNT(*) AS `AggregateValue` FROM ({$sql}) AS `countAlias`";
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
            null,
            $groupByPart,
            $havingPart,
            $indexKeyList
        );
        return $sql;
    }

    protected function getFunctionPart($function, $part, $entityType, $distinct = false, ?array $argumentPartList = null)
    {
        if (!in_array($function, $this->functionList)) {
            throw new \Exception("ORM Query: Not allowed function '{$function}'.");
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
                throw new \Exception("ORM Query: Not enough arguments for function '{$function}'.");
            }
            $operator = $this->comparisonFunctionOperatorMap[$function];
            return $argumentPartList[0] . ' ' . $operator . ' ' . $argumentPartList[1];
        }

        if (in_array($function, $this->mathOperationFunctionList)) {
            if (count($argumentPartList) < 2) {
                throw new \Exception("ORM Query: Not enough arguments for function '{$function}'.");
            }
            $operator = $this->mathFunctionOperatorMap[$function];
            return '(' . implode(' ' . $operator . ' ', $argumentPartList) . ')';
        }

        if (in_array($function, ['IN', 'NOT_IN'])) {
            $operator = $this->comparisonFunctionOperatorMap[$function];

            if (count($argumentPartList) < 2) {
                throw new \Exception("ORM Query: Not enough arguments for function '{$function}'.");
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

    protected function getFunctionPartTZ($entityType, ?array $argumentPartList = null)
    {
        if (!$argumentPartList || count($argumentPartList) < 2) {
            throw new \Exception("Not enough arguments for function TZ.");
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
            throw new \Exception("Bad MATCH usage.");
        }

        $function = substr($expression, 0, $delimiterPosition);
        $rest = substr($expression, $delimiterPosition + 1);

        if (empty($rest)) {
            throw new \Exception("Empty MATCH parameters.");
        }

        $delimiterPosition = strpos($rest, ':');
        if ($delimiterPosition === false) {
            throw new \Exception("Bad MATCH usage.");
        }

        $columns = substr($rest, 0, $delimiterPosition);
        $query = mb_substr($rest, $delimiterPosition + 1);

        $columnList = explode(',', $columns);

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

    protected function convertComplexExpression($entity, $attribute, $distinct = false, &$params = null)
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

    public static function getAllAttributesFromComplexExpression(string $expression, &$list = null) : array
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
            self::getAllAttributesFromComplexExpression($argument, $list);
        }

        return $list;
    }

    static protected function parseArgumentListFromFunctionContent($functionContent)
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
            if (!empty($entity->fields[$attribute]['select'])) {
                $part = $this->getAttributeSql($entity, $attribute, 'select', $params);
            } else {
                if ($part !== '') {
                    $part = $this->toDb($entityType) . '.' . $part;
                }
            }
        }

        return $part;
    }

    protected function getAttributeSql(IEntity $entity, $attribute, $type, &$params = null, $alias = null)
    {
        $fieldDefs = $entity->fields[$attribute];

        if (is_string($fieldDefs[$type])) {
            $part = $fieldDefs[$type];
        } else {
            if (!empty($fieldDefs[$type]['sql'])) {
                $part = $fieldDefs[$type]['sql'];
                if ($alias) {
                    $part = str_replace('{alias}', $alias, $part);
                }
            } else {
                $part = $this->toDb($entity->getEntityType()) . '.' . $this->toDb($attribute);
                if ($type === 'orderBy') {
                    $part .= ' {direction}';
                }
            }
        }

        if ($params) {
            if (!empty($fieldDefs[$type]['leftJoins'])) {
                foreach ($fieldDefs[$type]['leftJoins'] as $j) {
                    $jAlias = $this->obtainJoinAlias($j);
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
                                $conditions[$k] = $value;
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
                    foreach ($params['joins'] as $jE) {
                        $jEAlias = $this->obtainJoinAlias($jE);
                        if ($jEAlias === $jAlias) {
                            continue 2;
                        }
                    }
                    $params['joins'][] = $j;
                }
            }
        }

        return $part;
    }

    protected function getSelect(IEntity $entity, $itemList = null, $distinct = false, $skipTextColumns = false, $maxTextColumnsLength = null, &$params = null)
    {
        $select = "";
        $arr = [];
        $specifiedList = is_array($itemList) ? true : false;

        if (empty($itemList)) {
            $attributeList = array_keys($entity->fields);
        } else {
            $attributeList = $itemList;
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
                    if (!array_key_exists($attribute[0], $entity->fields)) {
                        $part = $this->convertComplexExpression($entity, $attribute[0], $distinct, $params);
                    } else {
                        $fieldDefs = $entity->fields[$attribute[0]];
                        if (!empty($fieldDefs['select'])) {
                            $part = $this->getAttributeSql($entity, $attribute[0], 'select', $params);
                        } else {
                            if (!empty($fieldDefs['notStorable']) || !empty($fieldDefs['noSelect'])) {
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

            if (array_key_exists($attribute, $entity->fields)) {
                $fieldDefs = $entity->fields[$attribute];
            } else {
                $part = $this->convertComplexExpression($entity, $attribute, $distinct, $params);
                $arr[] = $part . ' AS `' . $this->sanitizeSelectAlias($attribute) . '`';
                continue;
            }

            if (!empty($fieldDefs['select'])) {
                $fieldPath = $this->getAttributeSql($entity, $attribute, 'select', $params);
            } else {
                if (!empty($fieldDefs['notStorable'])) {
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

    protected function getBelongsToJoin(IEntity $entity, $relationName, $r = null, $alias = null)
    {
        if (empty($r)) {
            $r = $entity->relations[$relationName];
        }

        $keySet = $this->getKeys($entity, $relationName);
        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        if (!$alias) {
            $alias = $this->getAlias($entity, $relationName);
        }

        if ($alias) {
            return "JOIN `" . $this->toDb($r['entity']) . "` AS `" . $alias . "` ON ".
                   $this->toDb($entity->getEntityType()) . "." . $this->toDb($key) . " = " . $alias . "." . $this->toDb($foreignKey);
        }
    }

    protected function getBelongsToJoins(IEntity $entity, $select = null, $skipList = [])
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

                if (is_array($select)) {
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

    protected function getOrderPart(IEntity $entity, $orderBy = null, $order = null, $useColumnAlias = false, &$params = null) {

        if (!is_null($orderBy)) {
            if (is_array($orderBy)) {
                $arr = [];

                foreach ($orderBy as $item) {
                    if (is_array($item)) {
                        $orderByInternal = $item[0];
                        $orderInternal = null;
                        if (!empty($item[1])) {
                            $orderInternal = $item[1];
                        }
                        $arr[] = $this->getOrderPart($entity, $orderByInternal, $orderInternal, false, $params);
                    }
                }
                return implode(", ", $arr);
            }

            if (strpos($orderBy, 'LIST:') === 0) {
                list($l, $field, $list) = explode(':', $orderBy);
                $fieldPath = $this->getFieldPathForOrderBy($entity, $field, $params);
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

            if (!empty($entity->fields[$orderBy])) {
                $fieldDefs = $entity->fields[$orderBy];
            }
            if (!empty($fieldDefs) && !empty($fieldDefs['orderBy'])) {
                $orderPart = $this->getAttributeSql($entity, $orderBy, 'orderBy', $params);
                $orderPart = str_replace('{direction}', $order, $orderPart);
                return "{$orderPart}";
            } else {
                if ($useColumnAlias) {
                    $fieldPath = '`'. $this->sanitizeSelectAlias($orderBy) . '`';
                } else {
                    $fieldPath = $this->getFieldPathForOrderBy($entity, $orderBy, $params);
                }
                return "{$fieldPath} " . $order;
            }
        }
    }

    protected function getOrder(IEntity $entity, $orderBy = null, $order = null, &$params = null)
    {
        $orderPart = $this->getOrderPart($entity, $orderBy, $order, false, $params);
        if ($orderPart) {
            return "ORDER BY " . $orderPart;
        }
    }

    public function order(string $sql, IEntity $entity, $orderBy = null, $order = null, $useColumnAlias = false)
    {
        $orderPart = $this->getOrderPart($entity, $orderBy, $order, $useColumnAlias);
        if ($orderPart) {
            $sql .= " ORDER BY " . $orderPart;
        }
        return $sql;
    }

    protected function getFieldPathForOrderBy($entity, $orderBy, $params)
    {
        if (strpos($orderBy, '.') !== false) {
            $fieldPath = $this->convertComplexExpression(
                $entity,
                $orderBy,
                false,
                $params
            );
        } else {
            $fieldPath = $this->getFieldPath($entity, $orderBy, $params);
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
        } else if (is_bool($value)) {
            return $value ? '1' : '0';
        } else {
            return $this->pdo->quote($value);
        }
    }

    public function toDb(string $field)
    {
        if (array_key_exists($field, $this->fieldsMapCache)) {
            return $this->fieldsMapCache[$field];

        } else {
            $field[0] = strtolower($field[0]);
            $dbField = preg_replace_callback('/([A-Z])/', [$this, 'toDbConversion'], $field);

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
        $aliases = [];
        $c = 0;

        $occuranceHash = [];

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

    protected function getFieldPath(IEntity $entity, $field, &$params = null)
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
                            $expression = $this->getAlias($entity, $relationName) . '.' . $foreigh;
                            $fieldPath = $this->convertComplexExpression($entity, $expression, false, $params);
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

    public function getWhere(IEntity $entity, $whereClause = null, $sqlOp = 'AND', &$params = [], $level = 0)
    {
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
                $internalPart = $this->getWhere($entity, $value, $field, $params, $level + 1);
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
                if (!isset($entity->fields[$field])) {
                    $wherePartList[] = '0';
                    continue;
                }

                $fieldDefs = $entity->fields[$field];

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
                    $attributeType == IEntity::BOOL
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
                    if ($fieldDefs['type'] == IEntity::FOREIGN) {
                        $leftPart = '';
                        if (isset($fieldDefs['relation'])) {
                            $relationName = $fieldDefs['relation'];
                            if (isset($entity->relations[$relationName])) {
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
                    $wherePartList[] = $leftPart . " " . $operator . " (" . $this->createSelectQuery($subQueryEntityType, $subQuerySelectParams) . ")";
                } else if (!is_array($value)) {
                    if ($isNotValue) {
                        if (!is_null($value)) {
                            $wherePartList[] = $leftPart . " " . $operator . " " . $this->convertComplexExpression($entity, $value, $params);
                        } else {
                            $wherePartList[] = $leftPart;
                        }
                    } else if (!is_null($value)) {
                        $wherePartList[] = $leftPart . " " . $operator . " " . $this->pdo->quote($value);
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
                        $valArr[$k] = $this->pdo->quote($valArr[$k]);
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

    public function obtainJoinAlias($j)
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

    public function stringifyValue($value)
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

    public function sanitize($string)
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string);
    }

    public function sanitizeSelectAlias($string)
    {
        $string = preg_replace('/[^A-Za-z\r\n0-9_:\'" .,\-\(\)]+/', '', $string);
        if (strlen($string) > 256) $string = substr($string, 0, 256);
        return $string;
    }

    public function sanitizeSelectItem($string)
    {
        return preg_replace('/[^A-Za-z0-9_:.]+/', '', $string);
    }

    public function sanitizeIndexName($string)
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string);
    }

    protected function getJoins(IEntity $entity, array $joins, $isLeft = false, $joinConditions = [])
    {
        $joinSqlList = [];
        foreach ($joins as $item) {
            $itemConditions = [];
            if (is_array($item)) {
                $relationName = $item[0];
                if (count($item) > 1) {
                    $alias = $item[1];
                    if (count($item) > 2) {
                        $itemConditions = $item[2];
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
            if ($sql = $this->getJoin($entity, $relationName, $isLeft, $conditions, $alias)) {
                $joinSqlList[] = $sql;
            }
        }
        return implode(' ', $joinSqlList);
    }

    public function buildJoinConditionsStatement($entity, $alias = null, array $conditions)
    {
        $sql = '';

        $joinSqlList = [];
        foreach ($conditions as $left => $right) {
            $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right);
        }
        if (count($joinSqlList)) {
            $sql .= implode(" AND ", $joinSqlList);
        }

        return $sql;
    }

    protected function buildJoinConditionStatement($entity, $alias = null, $left, $right)
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
                $arr[] = $this->pdo->quote($item);
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

            $sql .= " " . $operator . " " . $this->pdo->quote($value);

            return $sql;
        }
    }

    protected function getJoin(IEntity $entity, $name, $isLeft = false, $conditions = [], $alias = null)
    {
        $prefix = ($isLeft) ? 'LEFT ' : '';

        if (!$entity->hasRelation($name)) {
            if (!$alias) {
                $alias = $this->sanitize($name);
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

        $relOpt = $entity->relations[$relationName];
        $keySet = $this->getKeys($entity, $relationName);

        if (!$alias) {
            $alias = $relationName;
        }

        $alias = $this->sanitize($alias);

        if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
            $conditions = array_merge($conditions, $relOpt['conditions']);
        }

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

                $midAlias = $alias . 'Middle';

                $sql =
                    "{$prefix}JOIN `{$relTable}` AS `{$midAlias}` ON {$this->toDb($entity->getEntityType())}." . $this->toDb($key) . " = {$midAlias}." . $this->toDb($nearKey)
                    . " AND "
                    . "{$midAlias}.deleted = " . $this->pdo->quote(0);

                $joinSqlList = [];
                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $midAlias, $left, $right);
                }
                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                $sql .= " {$prefix}JOIN `{$distantTable}` AS `{$alias}` ON {$alias}." . $this->toDb($foreignKey) . " = {$midAlias}." . $this->toDb($distantKey)
                    . " AND "
                    . "{$alias}.deleted = " . $this->pdo->quote(0) . "";

                return $sql;

            case IEntity::HAS_MANY:
            case IEntity::HAS_ONE:
                $foreignKey = $keySet['foreignKey'];
                $distantTable = $this->toDb($relOpt['entity']);

                $sql =
                    "{$prefix}JOIN `{$distantTable}` AS `{$alias}` ON {$this->toDb($entity->getEntityType())}." . $this->toDb('id') . " = {$alias}." . $this->toDb($foreignKey)
                    . " AND "
                    . "{$alias}.deleted = " . $this->pdo->quote(0) . "";

                $joinSqlList = [];
                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right);
                }
                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                return $sql;

            case IEntity::HAS_CHILDREN:
                $foreignKey = $keySet['foreignKey'];
                $foreignType = $keySet['foreignType'];
                $distantTable = $this->toDb($relOpt['entity']);

                $sql =
                    "{$prefix}JOIN `{$distantTable}` AS `{$alias}` ON " . $this->toDb($entity->getEntityType()) . "." . $this->toDb('id') . " = {$alias}." . $this->toDb($foreignKey)
                    . " AND "
                    . "{$alias}." . $this->toDb($foreignType) . " = " . $this->pdo->quote($entity->getEntityType())
                    . " AND "
                    . "{$alias}.deleted = " . $this->pdo->quote(0) . "";

                $joinSqlList = [];
                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right);
                }
                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                return $sql;

            case IEntity::BELONGS_TO:
                $sql = $prefix . $this->getBelongsToJoin($entity, $relationName, null, $alias);
                return $sql;
        }

        return false;
    }

    public function composeSelectQuery(
        $table,
        $select,
        $joins = '',
        $where = '',
        $order = '',
        $offset = null,
        $limit = null,
        $distinct = null,
        $aggregation = false,
        $groupBy = null,
        $having = null,
        $indexKeyList = null
    )
    {
        $sql = "SELECT";

        if (!empty($distinct) && empty($groupBy)) {
            $sql .= " DISTINCT";
        }

        $sql .= " {$select} FROM `{$table}`";

        if (!empty($indexKeyList)) {
            foreach ($indexKeyList as $index) {
                $sql .= " USE INDEX (`".$this->sanitizeIndexName($index)."`)";
            }
        }

        if (!empty($joins)) {
            $sql .= " {$joins}";
        }

        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }

        if (!empty($groupBy)) {
            $sql .= " GROUP BY {$groupBy}";
        }

        if (!empty($having)) {
            $sql .= " HAVING {$having}";
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
                if (isset($relOpt['foreignKey'])){
                    $foreignKey = $relOpt['foreignKey'];
                }
                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                ];

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
                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                ];

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
                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                    'foreignType' => $foreignType,
                ];

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
                return [
                    'key' => $key,
                    'foreignKey' => $foreignKey,
                    'nearKey' => $nearKey,
                    'distantKey' => $distantKey
                ];
            case IEntity::BELONGS_TO_PARENT:
                $key = $relationName . 'Id';
                $typeKey = $relationName . 'Type';
                return [
                    'key' => $key,
                    'typeKey' => $typeKey,
                    'foreignKey' => 'id'
                ];
        }
    }
}
