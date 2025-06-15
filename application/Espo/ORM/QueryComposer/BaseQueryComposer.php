<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\ORM\QueryComposer;

use Espo\Core\ORM\Type\FieldType;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\EntityParam;
use Espo\ORM\Defs\Params\IndexParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityFactory;
use Espo\ORM\BaseEntity;
use Espo\ORM\EventDispatcher;
use Espo\ORM\Metadata;
use Espo\ORM\Mapper\Helper;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Join\JoinType;
use Espo\ORM\Query\Query;
use Espo\ORM\Query\SelectingQuery;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\Update;
use Espo\ORM\Query\Insert;
use Espo\ORM\Query\Delete;
use Espo\ORM\Query\Union;
use Espo\ORM\QueryComposer\Part\FunctionConverterFactory;

use Espo\ORM\Type\AttributeType;
use PDO;
use RuntimeException;
use LogicException;

use const STR_PAD_LEFT;

/**
 * Composes SQL queries.
 *
 * @todo Break into sub-classes. Put sub-classes into `\Part` namespace.
 * @todo Use entityDefs. Don't use methods of BaseEntity.
 */
abstract class BaseQueryComposer implements QueryComposer
{
    /**
     * @var string[]
     * @todo Remove.
     */
    protected const PARAM_LIST = [
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

    /** @var string[] */
    protected const SQL_OPERATORS = [
        'OR',
        'AND',
    ];

    protected const EXISTS_OPERATOR = 'EXISTS';

    /** @var string[] */
    private array $comparisonOperators = [
        '!=s',
        '=s',
        '!=',
        '!*',
        '*',
        '>=',
        '<=',
        '>',
        '<',
        '=',
        '>=any',
        '<=any',
        '>any',
        '<any',
        '!=any',
        '=any',
        '>=all',
        '<=all',
        '>all',
        '<all',
        '!=all',
        '=all',
    ];

    /** @var array<string, string> */
    protected array $comparisonOperatorMap = [
        '!=s' => 'NOT IN',
        '=s' => 'IN',
        '!=' => '<>',
        '!*' => 'NOT LIKE',
        '*' => 'LIKE',
        '>=any' => '>= ANY',
        '<=any' => '<= ANY',
        '>any' => '> ANY',
        '<any' => '< ANY',
        '!=any' => '<> ANY',
        '=any' => '= ANY',
        '>=all' => '>= ALL',
        '<=all' => '<= ALL',
        '>all' => '> ALL',
        '<all' => '< ALL',
        '!=all' => '<> ALL',
        '=all' => '= ALL',
    ];

    /** @var array<string, string> */
    protected array $comparisonFunctionOperatorMap = [
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

    /** @var array<string, string> */
    protected array $mathFunctionOperatorMap = [
        'ADD' => '+',
        'SUB' => '-',
        'MUL' => '*',
        'DIV' => '/',
        'MOD' => '%',
    ];

    protected const SELECT_METHOD = 'SELECT';
    protected const DELETE_METHOD = 'DELETE';
    protected const UPDATE_METHOD = 'UPDATE';
    protected const INSERT_METHOD = 'INSERT';

    protected string $identifierQuoteCharacter = '`';

    protected int $aliasMaxLength = 256;

    protected bool $indexHints = true;
    protected bool $skipForeignIfForUpdate = false;

    protected EntityFactory $entityFactory;
    protected PDO $pdo;
    protected Metadata $metadata;
    protected ?FunctionConverterFactory $functionConverterFactory;
    protected Helper $helper;

    /** @var array<string, string> */
    protected array $attributeDbMapCache = [];
    /** @var array<string, array<string, string>> */
    protected $aliasesCache = [];
    /** @var array<string, Entity> */
    protected $seedCache = [];

    public function __construct(
        PDO $pdo,
        EntityFactory $entityFactory,
        Metadata $metadata,
        ?FunctionConverterFactory $functionConverterFactory = null,
        ?EventDispatcher $eventDispatcher = null
    ) {
        $this->entityFactory = $entityFactory;
        $this->pdo = $pdo;
        $this->metadata = $metadata;
        $this->functionConverterFactory = $functionConverterFactory;

        $this->helper = new Helper($metadata);

        $eventDispatcher?->subscribeToMetadataUpdate(fn () => $this->seedCache = []);
    }

    protected function quoteIdentifier(string $string): string
    {
        return $this->identifierQuoteCharacter . $string . $this->identifierQuoteCharacter;
    }

    protected function quoteColumn(string $column): string
    {
        return $column;
    }

    protected function getSeed(?string $entityType): Entity
    {
        if (!$entityType) {
            return new BaseEntity('_Stub', []);
        }

        if (empty($this->seedCache[$entityType])) {
            $this->seedCache[$entityType] = $this->entityFactory->create($entityType);
        }

        return $this->seedCache[$entityType];
    }

    /**
     * @deprecated As of v7.2. Use the wrapper or methods directly.
     */
    public function compose(Query $query): string
    {
        $wrapper = new QueryComposerWrapper($this);

        return $wrapper->compose($query);
    }

    public function composeCreateSavepoint(string $savepointName): string
    {
        /** @noinspection PhpDeprecationInspection */
        return 'SAVEPOINT ' . $this->sanitize($savepointName);
    }

    public function composeReleaseSavepoint(string $savepointName): string
    {
        /** @noinspection PhpDeprecationInspection */
        return 'RELEASE SAVEPOINT ' . $this->sanitize($savepointName);
    }

    public function composeRollbackToSavepoint(string $savepointName): string
    {
        /** @noinspection PhpDeprecationInspection */
        return 'ROLLBACK TO SAVEPOINT ' . $this->sanitize($savepointName);
    }

    protected function composeSelecting(SelectingQuery $query): string
    {
        if ($query instanceof Select) {
            return $this->composeSelect($query);
        }

        if ($query instanceof Union) {
            return $this->composeUnion($query);
        }

        throw new RuntimeException("Unknown query type.");
    }

    public function composeSelect(Select $query): string
    {
        $params = $query->getRaw();

        return $this->createSelectQueryInternal($params);
    }

    public function composeUpdate(Update $query): string
    {
        $params = $query->getRaw();

        return $this->createUpdateQuery($params);
    }

    public function composeDelete(Delete $query): string
    {
        $params = $query->getRaw();

        return $this->createDeleteQuery($params);
    }

    public function composeInsert(Insert $query): string
    {
        $params = $query->getRaw();

        return $this->createInsertQuery($params);
    }

    public function composeUnion(Union $query): string
    {
        $params = $query->getRaw();

        return $this->createUnionQuery($params);
    }

    /**
     * @param array<string, mixed>|null $params
     */
    protected function createDeleteQuery(?array $params = null): string
    {
        $params = $this->normalizeParams(self::DELETE_METHOD, $params);

        $entityType = $params['from'];

        $alias = $params['fromAlias'] ?? null;

        $entity = $this->getSeed($entityType);

        $wherePart = $this->getWherePart($entity, $params['whereClause'], 'AND', $params);
        $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order'], $params);
        $joinsPart = $this->getJoinsPart($entity, $params);

        $aliasPart = null;

        if ($alias) {
            /** @noinspection PhpDeprecationInspection */
            $aliasPart = $this->sanitize($alias);
        }

        return $this->composeDeleteQuery(
            $this->toDb($entityType),
            $aliasPart,
            $wherePart,
            $joinsPart,
            $orderPart,
            $params['limit']
        );
    }

    /**
     * @param array<string, mixed>|null $params
     */
    protected function createUpdateQuery(?array $params = null): string
    {
        $params = $this->normalizeParams(self::UPDATE_METHOD, $params);

        $entityType = $params['from'];

        $values = $params['set'];

        $entity = $this->getSeed($entityType);

        $wherePart = $this->getWherePart($entity, $params['whereClause'], 'AND', $params);
        $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order'], $params);
        $joinsPart = $this->getJoinsPart($entity, $params);

        $setPart = $this->getSetPart($entity, $values, $params);

        return $this->composeUpdateQuery(
            $this->toDb($entityType),
            $setPart,
            $wherePart,
            $joinsPart,
            $orderPart,
            $params['limit']
        );
    }

    /**
     * @param array<string, mixed>|null $params
     */
    protected function createInsertQuery(?array $params): string
    {
        $params = $this->normalizeInsertParams($params ?? []);

        $entityType = $params['into'];

        $columns = $params['columns'];
        $updateSet = $params['updateSet'];

        $columnsPart = $this->getInsertColumnsPart($columns);

        $valuesPart = $this->getInsertValuesPart($entityType, $params);

        $updatePart = null;

        if ($updateSet) {
            $updatePart = $this->getInsertUpdatePart($updateSet);
        }

        return $this->composeInsertQuery($this->toDb($entityType), $columnsPart, $valuesPart, $updatePart);
    }

    /**
     * @param array<string, mixed> $params
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getInsertValuesPart(string $entityType, array $params): string
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

    /**
     * @param array<string, mixed> $params
     */
    protected function createUnionQuery(array $params): string
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
            $select = Select::fromRaw($rawSelectParams);

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

    /**
     * @param array<string|mixed[]> $orderBy
     */
    protected function getUnionOrderPart(array $orderBy): string
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
                /** @noinspection PhpDeprecationInspection */
                $by = $this->quoteIdentifier(
                    $this->sanitizeSelectAlias($item[0])
                );
            }

            $orderByParts[] = $by . ' ' . $direction;
        }

        return implode(', ', $orderByParts);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function normalizeInsertParams(array $params): array
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

    /**
     * @param array<string, mixed>|null $params
     * @return array<string, mixed>
     */
    protected function normalizeParams(string $method, ?array $params): array
    {
        $params = $params ?? [];

        foreach (self::PARAM_LIST as $k) {
            $params[$k] = array_key_exists($k, $params) ? $params[$k] : null;
        }

        $params['distinct'] = $params['distinct'] ?? false;
        $params['skipTextColumns'] = $params['skipTextColumns'] ?? false;

        $params['joins'] = $params['joins'] ?? [];
        $params['leftJoins'] = $params['leftJoins'] ?? [];

        if ($method !== self::SELECT_METHOD) {
            if (isset($params['offset'])) {
                throw new RuntimeException("ORM Query: Param 'offset' is not allowed for '$method'.");
            }
        }

        if ($method !== self::UPDATE_METHOD) {
            if (isset($params['set'])) {
                throw new RuntimeException("ORM Query: Param 'set' is not allowed for '$method'.");
            }
        }

        if (isset($params['set']) && !is_array($params['set'])) {
            throw new RuntimeException("ORM Query: Param 'set' should be an array.");
        }

        return $params;
    }

    /**
     * @param array<string, mixed>|null $params
     */
    protected function createSelectQueryInternal(?array $params = null): string
    {
        $params = $this->normalizeParams(self::SELECT_METHOD, $params);

        $entityType = $params['from'] ?? null;
        $fromQuery = $params['fromQuery'] ?? null;
        $fromAlias = $params['fromAlias'] ?? null;
        $whereClause = $params['whereClause'] ?? [];
        $havingClause = $params['havingClause'] ?? [];

        if ($entityType === null && !$fromQuery) {
            return $this->createSelectQueryNoFrom($params);
        }

        $entity = $this->getSeed($entityType);

        if (!$params['withDeleted'] && $entity->hasAttribute(Attribute::DELETED)) {
            $whereClause = $whereClause + [Attribute::DELETED => false];
        }

        $wherePart = $this->getWherePart($entity, $whereClause, 'AND', $params);
        $havingPart = $havingClause ? $this->getWherePart($entity, $havingClause, 'AND', $params) : null;
        $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order'], $params);
        $selectPart = $this->getSelectPart($entity, $params);
        $selectPart .= $this->getAdditionalSelect($entity, $params) ?? '';
        $tailPart = $this->getSelectTailPart($params);
        $joinsPart = $this->getJoinsPart($entity, $params, true);
        $groupByPart = $this->getGroupByPart($entity, $params);

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

        $indexKeyList = $entityType ?
            $this->getIndexKeyList($entityType, $params) : null;

        /** @noinspection PhpDeprecationInspection */
        $fromAlias = $fromAlias ?
            $this->sanitize($fromAlias) : null;

        $fromPart = $fromQuery ?
            '(' . $this->composeSelecting($fromQuery) . ')' :
            (
                $entityType ?
                    $this->quoteIdentifier($this->toDb($entityType)) : null
            );

        /** @var string $selectPart */
        /** @var string $fromAlias */

        return $this->composeSelectQuery(
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
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function createSelectQueryNoFrom(array $params): string
    {
        $selectPart = $this->getSelectPart(null, $params);

        return $this->composeSelectQuery(null, $selectPart);
    }

    /**
     * @param array<string, mixed> $params
     * @return string[]|null
     */
    protected function getIndexKeyList(string $entityType, array $params): ?array
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
            $indexKey = $this->metadata->get($entityType, [EntityParam::INDEXES, $indexName, IndexParam::KEY]);

            if ($indexKey) {
                $indexKeyList[] = $indexKey;
            }
        }

        return $indexKeyList;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function skipForeign(array $params): bool
    {
        return $this->skipForeignIfForUpdate && ($params['forUpdate'] ?? false);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getJoinsPart(Entity $entity, array $params, bool $includeBelongsTo = false): string
    {
        if ($includeBelongsTo && $this->skipForeign($params)) {
            $includeBelongsTo = false;
        }

        $joinsPart = '';

        if ($includeBelongsTo) {
            $joinsPart = $this->getBelongsToJoinsPart(
                entity: $entity,
                select: $params['select'],
                explicitJoins: array_merge($params['joins'], $params['leftJoins']),
                params: $params,
            );
        }

        if (!empty($params['joins']) && is_array($params['joins'])) {
            // @todo array unique
            $joinsItemPart = $this->getJoinsTypePart(
                entity: $entity,
                joins: $params['joins'],
                params: $params,
                joinConditions: $params['joinConditions'],
            );

            if (!empty($joinsItemPart)) {
                if (!empty($joinsPart)) {
                    $joinsPart .= ' ';
                }

                $joinsPart .= $joinsItemPart;
            }
        }

        // For bc.
        if (!empty($params['leftJoins']) && is_array($params['leftJoins'])) {
            $joinsItemPart = $this->getJoinsTypePart(
                entity: $entity,
                joins: $params['leftJoins'],
                params: $params,
                joinConditions: $params['joinConditions'],
                isLeft: true,
            );

            if (!empty($joinsItemPart)) {
                if (!empty($joinsPart)) {
                    $joinsPart .= ' ';
                }

                $joinsPart .= $joinsItemPart;
            }
        }

        // @todo Remove custom join.
        if (!empty($params['customJoin'])) {
            if (!empty($joinsPart)) {
                $joinsPart .= ' ';
            }
            $joinsPart .= $params['customJoin'];
        }

        return $joinsPart;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getGroupByPart(Entity $entity, array $params): ?string
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

    /**
     * @param array<string, mixed> $params
     */
    protected function getAdditionalSelect(Entity $entity, array $params): ?string
    {
        if (!empty($params['strictSelect'])) {
            return null;
        }

        $selectPart = '';

        if (!empty($params['extraAdditionalSelect'])) {
            $extraSelect = [];

            foreach ($params['extraAdditionalSelect'] as $item) {
                if (!in_array($item, $params['select'] ?? [])) {
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

        /*if (!empty($params['additionalSelectColumns']) && is_array($params['additionalSelectColumns'])) {
            foreach ($params['additionalSelectColumns'] as $column => $field) {
                $itemAlias = $this->sanitizeSelectAlias($field);

                $selectPart .= ", " . $column . " AS " . $this->quoteIdentifier($itemAlias);
            }
        }*/

        if ($selectPart === '') {
            return null;
        }

        return $selectPart;
    }

    /**
     * @param string[] $argumentPartList
     * @param array<string, mixed> $params
     */
    protected function getFunctionPart(
        string $function,
        string $part,
        array $params,
        string $entityType,
        bool $distinct,
        array $argumentPartList = []
    ): string {

        $isBuiltIn = in_array($function, Functions::FUNCTION_LIST);

        if (
            !$isBuiltIn &&
            (
                !$this->functionConverterFactory ||
                !$this->functionConverterFactory->isCreatable($function)
            )
        ) {
            throw new RuntimeException("ORM Query: Not allowed function '$function'.");
        }

        if (in_array($function, ['MATCH_BOOLEAN', 'MATCH_NATURAL_LANGUAGE'])) {
            if (count($argumentPartList) < 2) {
                throw new RuntimeException("Not enough arguments for MATCH function.");
            }

            $queryPart = end($argumentPartList);
            $columnsPart = implode(', ', array_splice($argumentPartList, 0, -1));
            $modePart = $function === 'MATCH_BOOLEAN' ?
                'IN BOOLEAN MODE' : 'IN NATURAL LANGUAGE MODE';

            return "MATCH ($columnsPart) AGAINST ($queryPart $modePart)";
        }

        if (str_starts_with($function, 'YEAR_') && $function !== 'YEAR_NUMBER') {
            $fiscalShift = substr($function, 5);

            if (is_numeric($fiscalShift)) {
                $fiscalShift = (int) $fiscalShift;
                $fiscalFirstMonth = $fiscalShift + 1;

                return
                    "CASE WHEN MONTH($part) >= $fiscalFirstMonth THEN ".
                    "YEAR($part) ".
                    "ELSE YEAR($part) - 1 END";
            }
        }

        if (str_starts_with($function, 'QUARTER_') && $function !== 'QUARTER_NUMBER') {
            $fiscalShift = substr($function, 8);

            if (is_numeric($fiscalShift)) {
                $fiscalShift = (int) $fiscalShift;
                $fiscalFirstMonth = $fiscalShift + 1;
                $fiscalDistractedMonth = $fiscalFirstMonth < 4 ?
                    12 - $fiscalFirstMonth :
                    12 - $fiscalFirstMonth + 1;

                return
                    "CASE WHEN MONTH($part) >= $fiscalFirstMonth THEN ".
                    "CONCAT(YEAR($part), '_', FLOOR((MONTH($part) - $fiscalFirstMonth) / 3) + 1) ".
                    "ELSE CONCAT(YEAR($part) - 1, '_', CEIL((MONTH($part) + $fiscalDistractedMonth) / 3)) END";
            }
        }

        if ($function === 'TZ') {
            return $this->getFunctionPartTZ($argumentPartList);
        }

        if (in_array($function, Functions::COMPARISON_FUNCTION_LIST)) {
            if (count($argumentPartList) < 2) {
                throw new RuntimeException("Not enough arguments for function '$function'.");
            }

            $operator = $this->comparisonFunctionOperatorMap[$function];

            return $argumentPartList[0] . ' ' . $operator . ' ' . $argumentPartList[1];
        }

        if (in_array($function, Functions::MATH_OPERATION_FUNCTION_LIST)) {
            if (count($argumentPartList) < 2) {
                throw new RuntimeException("ORM Query: Not enough arguments for function '$function'.");
            }

            $operator = $this->mathFunctionOperatorMap[$function];

            return '(' . implode(' ' . $operator . ' ', $argumentPartList) . ')';
        }

        if (in_array($function, ['IN', 'NOT_IN'])) {
            $operator = $this->comparisonFunctionOperatorMap[$function];

            if (count($argumentPartList) < 2) {
                throw new RuntimeException("ORM Query: Not enough arguments for function '$function'.");
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
            return '(' . implode(' ' . $function . ' ', $argumentPartList) . ')';
        }

        if (!$isBuiltIn && $this->functionConverterFactory) {
            return $this->getFunctionPartFromFactory($function, $argumentPartList);
        }

        switch ($function) {
            case 'SWITCH':
                if (count($argumentPartList) < 2) {
                    throw new RuntimeException("Not enough arguments for SWITCH function.");
                }

                $part = "CASE";

                for ($i = 0; $i < floor(count($argumentPartList) / 2); $i++) {
                    $whenPart = $argumentPartList[$i * 2];
                    $thenPart = $argumentPartList[$i * 2 + 1];

                    $part .= " WHEN $whenPart THEN $thenPart";
                }

                if (count($argumentPartList) % 2) {
                    $part .= " ELSE " . end($argumentPartList);
                }

                $part .= " END";

                return $part;

            case 'MAP':
                if (count($argumentPartList) < 3) {
                    throw new RuntimeException("Not enough arguments for MAP function.");
                }

                $part = "CASE " . $argumentPartList[0];

                array_shift($argumentPartList);

                for ($i = 0; $i < floor(count($argumentPartList) / 2); $i++) {
                    $whenPart = $argumentPartList[$i * 2];
                    $thenPart = $argumentPartList[$i * 2 + 1];

                    $part .= " WHEN $whenPart THEN $thenPart";
                }

                if (count($argumentPartList) % 2) {
                    $part .= " ELSE " . end($argumentPartList);
                }

                $part .= " END";

                return $part;

            case 'MONTH':
                return "DATE_FORMAT($part, '%Y-%m')";

            case 'DAY':
                return "DATE_FORMAT($part, '%Y-%m-%d')";

            case 'WEEK_0':
                return "CONCAT(SUBSTRING(YEARWEEK($part, 6), 1, 4), '/', ".
                    "TRIM(LEADING '0' FROM SUBSTRING(YEARWEEK($part, 6), 5, 2)))";

            case 'WEEK':
            case 'WEEK_1':
                return "CONCAT(SUBSTRING(YEARWEEK($part, 3), 1, 4), '/', ".
                    "TRIM(LEADING '0' FROM SUBSTRING(YEARWEEK($part, 3), 5, 2)))";

            case 'QUARTER':
                return "CONCAT(YEAR($part), '_', QUARTER($part))";

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
                return "WEEK($part, 6)";

            case 'WEEK_NUMBER':
            case 'WEEK_NUMBER_1':
                return "WEEK($part, 3)";

            case 'HOUR_NUMBER':
                $function = 'HOUR';
                break;

            case 'MINUTE_NUMBER':
                $function = 'MINUTE';
                break;

            case 'SECOND_NUMBER':
                $function = 'SECOND';
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

            case 'POSITION_IN_LIST':
                return 'FIELD(' . implode(', ', $argumentPartList) . ')';
        }

        return $function . '(' . $part . ')';
    }

    /**
     * @param string[] $argumentPartList
     */
    private function getFunctionPartFromFactory(string $function, array $argumentPartList): string
    {
        assert($this->functionConverterFactory !== null);

        $obj = $this->functionConverterFactory->create($function);

        return $obj->convert(...$argumentPartList);
    }

    /**
     * @param string[]|null $argumentPartList
     */
    protected function getFunctionPartTZ(?array $argumentPartList = null): string
    {
        if (!$argumentPartList || count($argumentPartList) < 2) {
            throw new RuntimeException("ORM Query: Not enough arguments for function TZ.");
        }

        $offsetHoursString = $argumentPartList[1];

        if (str_starts_with($offsetHoursString, '\'') && str_ends_with($offsetHoursString, '\'')) {
            $offsetHoursString = substr($offsetHoursString, 1, -1);
        }

        $offset = floatval($offsetHoursString);

        $offsetHours = (int) (floor(abs($offset)));

        $offsetMinutes = (abs($offset) - $offsetHours) * 60;

        $offsetString =
            str_pad((string) $offsetHours, 2, '0', STR_PAD_LEFT) .
            ':' .
            str_pad((string) $offsetMinutes, 2, '0', STR_PAD_LEFT);

        $offsetString = $offset < 0 ?
            '-' . $offsetString :
            '+' . $offsetString;

        return "CONVERT_TZ(". $argumentPartList[0]. ", '+00:00', " . $this->quote($offsetString) . ")";
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function convertComplexExpression(
        ?Entity $entity,
        string $attribute,
        bool $distinct,
        array &$params
    ): string {

        $function = null;

        if (!$entity) {
            $entity = $this->getSeed(null);
        }

        $entityType = $entity->getEntityType();

        if (strpos($attribute, ':') && !Util::isArgumentString($attribute)) {
            $delimiterPosition = (int) strpos($attribute, ':');
            $function = substr($attribute, 0, $delimiterPosition);
            $attribute = substr($attribute, $delimiterPosition + 1);

            if (str_starts_with($attribute, '(') && str_ends_with($attribute, ')')) {
                $attribute = substr($attribute, 1, -1);
            }
        }

        if (!empty($function)) {
            /** @noinspection PhpDeprecationInspection */
            $function = strtoupper($this->sanitize($function));
        }

        if (!$function) {
            return $this->getFunctionArgumentPart($entity, $attribute, $distinct, $params);

        }

        $argumentList = Util::parseArgumentListFromFunctionContent($attribute);

        $argumentPartList = [];

        foreach ($argumentList as $argument) {
            $argumentPartList[] = $this->getFunctionArgumentPart($entity, $argument, $distinct, $params);
        }

        $part = implode(', ', $argumentPartList);

        return $this->getFunctionPart(
            $function,
            $part,
            $params,
            $entityType,
            $distinct,
            $argumentPartList
        );
    }

    /**
     * @deprecated As of v6.0. Use `Util::getAllAttributesFromComplexExpression`.
     * @return string[]
     */
    public static function getAllAttributesFromComplexExpression(string $expression): array
    {
        return Util::getAllAttributesFromComplexExpression($expression);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getFunctionArgumentPart(
        Entity $entity,
        string $attribute,
        bool $distinct,
        array &$params
    ): string {

        $argument = $attribute;

        if (Util::isArgumentString($argument)) {
            $isSingleQuote = $argument[0] === "'";

            $string = substr($argument, 1, -1);

            $string = $isSingleQuote ?
                str_replace("\\'", "'", $string) :
                str_replace('\\"', '"', $string);

            return $this->quote($string);
        }

        if (Util::isArgumentNumeric($argument)) {
            if (filter_var($argument, FILTER_VALIDATE_INT) !== false) {
                $argument = intval($argument);
            } else if (filter_var($argument, FILTER_VALIDATE_FLOAT) !== false) {
                $argument = floatval($argument);
            }

            return $this->quote($argument);
        }

        if (Util::isArgumentBoolOrNull($argument)) {
            return strtoupper($argument);
        }

        if (strpos($argument, ':')) {
            return $this->convertComplexExpression($entity, $argument, $distinct, $params);
        }

        $relName = null;
        $entityType = $entity->getEntityType();

        if (strpos($argument, '.')) {
            [$relName, $attribute] = explode('.', $argument);
        }

        if (!empty($relName)) {
            /** @noinspection PhpDeprecationInspection */
            $relName = $this->sanitize($relName);
        }

        $isAlias = false;

        if (!empty($attribute)) {
            $isAlias = str_starts_with($attribute, '#');

            /** @noinspection PhpDeprecationInspection */
            $attribute = $isAlias ?
                $this->sanitizeSelectAlias($attribute) :
                $this->sanitize($attribute);
        }

        if ($attribute !== '') {
            $part = !$isAlias ?
                $this->toDb($attribute):
                $attribute;
        } else {
            $part = '';
        }

        if ($relName) {
            $part = $this->quoteColumn($relName . '.' . $part);

            $foreignEntityType = $this->getRelationParam($entity, $relName, RelationParam::ENTITY);

            if ($foreignEntityType) {
                $foreignSeed = $this->getSeed($foreignEntityType);

                $selectForeign = $this->getAttributeParam($foreignSeed, $attribute, 'selectForeign');

                if (is_array($selectForeign)) {
                    $part = $this->getAttributeSql($foreignSeed, $attribute, 'selectForeign', $params, $relName);
                }
            }

            return $part;
        }

        if (!$isAlias && $this->getAttributeParam($entity, $attribute, 'select')) {
            return $this->getAttributeSql($entity, $attribute, 'select', $params);
        }

        if ($part === '') {
            return $part;
        }

        if ($isAlias) {
            return $this->quoteColumn($part);
        }

        $part = $this->getFromAlias($params, $entityType) . '.' . $part;

        return $this->quoteColumn($part);
    }

    /**
     * @param array<string, mixed>|null $params
     */
    protected function getFromAlias(?array $params = null, ?string $entityType = null): string
    {
        $params = $params ?? [];

        $alias = $params['fromAlias'] ?? null;

        if ($alias) {
            /** @noinspection PhpDeprecationInspection */
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

    /**
     * @param array<string, mixed> $params
     */
    protected function getAttributeOrderSql(
        Entity $entity,
        string $attribute,
        array &$params,
        string $order
    ): string {

        $defs = $this->getAttributeParam($entity, $attribute, 'order') ?? [];

        if (is_string($defs)) {
            $defs = [];
        }

        if ($params) {
            $this->applyAttributeCustomParams($defs, $params, $attribute);
        }

        if (is_string($this->getAttributeParam($entity, $attribute, 'order'))) {
            // @deprecated

            $part = $this->getAttributeParam($entity, $attribute, 'order');

            return str_replace('{direction}', $order, $part);
        }

        if (!empty($defs['sql'])) {
            // @deprecated
            $part = $defs['sql'];

            return str_replace('{direction}', $order, $part);
        }

        if (!empty($defs['order'])) {
            if (!is_array($defs['order'])) {
                throw new LogicException("Bad custom order definition.");
            }

            $modifiedOrder = [];

            foreach ($defs['order'] as $item) {
                if (!is_array($item) && !isset($item[0])) {
                    throw new LogicException("Bad custom order definition.");
                }

                $newItem = [
                    $item[0],
                ];

                if (isset($item[1]) && $item[1] === '{direction}') {
                    $newItem[] = $order;
                }

                $modifiedOrder[] = $newItem;
            }

            /** @var string $part */
            $part = $this->getOrderExpressionPart($entity, $modifiedOrder, null, $params, true);

            return $part;
        }

        /** @noinspection PhpDeprecationInspection */
        $part = $this->getFromAlias($params, $entity->getEntityType()) . '.' .
            $this->toDb($this->sanitize($attribute));

        $part = $this->quoteColumn($part);

        $part .= ' ' . $order;

        return $part;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getAttributeSql(
        Entity $entity,
        string $attribute,
        string $type,
        array &$params = [],
        ?string $alias = null
    ): string {

        $defs = $this->getAttributeParam($entity, $attribute, $type) ?? [];

        if (is_string($defs)) {
            $defs = [];
        }

        if ($params) {
            $this->applyAttributeCustomParams($defs, $params, $attribute, $alias);
        }

        if (is_string($this->getAttributeParam($entity, $attribute, $type))) {
            return $this->getAttributeParam($entity, $attribute, $type);
        }

        if (!empty($defs['sql'])) {
            // @deprecated
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

        $fromAlias = $this->getFromAlias($params, $entity->getEntityType());

        /** @noinspection PhpDeprecationInspection */
        $path = $fromAlias . '.' . $this->toDb($this->sanitize($attribute));

        return $this->quoteColumn($path);
    }

    /**
     * @param array<string, mixed> $defs
     * @param array<string, mixed> $params
     */
    protected function applyAttributeCustomParams(
        array $defs,
        array &$params,
        string $attribute,
        ?string $alias = null
    ): void {

        if (!empty($defs['leftJoins'])) {
            foreach ($defs['leftJoins'] as $j) {
                if (is_string($j)) {
                    $j = [$j];
                }

                $jAlias = $this->obtainJoinAlias($j);

                if ($alias) {
                    $jAlias = str_replace('{alias}', $alias, $jAlias);
                }

                if (isset($j[1])) {
                    $j[1] = $jAlias;
                }

                foreach ($params['joins'] as $jE) {
                    $jEAlias = $this->obtainJoinAlias($jE);

                    if ($jEAlias === $jAlias) {
                        continue 2;
                    }
                }

                if ($alias && count($j) >= 3 && is_array($j[2])) {
                    $conditions = [];

                    foreach ($j[2] as $k => $value) {
                        if (is_string($value)) {
                            $value = str_replace('{alias}', $alias, $value);
                        }

                        /** @var string $left */
                        $left = $k;
                        $left = str_replace('{alias}', $alias, $left);

                        $conditions[$left] = $value;
                    }

                    $j[2] = $conditions;
                }

                $j[1] ??= null;
                $j[2] ??= null;
                $j[3] ??= [];

                $j[3]['type'] = JoinType::left;

                $params['joins'][] = $j;
            }
        }

        if (!empty($defs['joins'])) {
            foreach ($defs['joins'] as $j) {
                if (is_string($j)) {
                    $j = [$j];
                }

                $jAlias = $this->obtainJoinAlias($j);

                $jAlias = str_replace('{alias}', $alias ?? '', $jAlias);

                if (isset($j[1])) {
                    $j[1] = $jAlias;
                }

                foreach ($params['joins'] as $jE) {
                    $jEAlias = $this->obtainJoinAlias($jE);

                    if ($jEAlias === $jAlias) {
                        continue 2;
                    }
                }

                if ($alias && count($j) >= 3 && is_array($j[2])) {
                    $conditions = [];

                    foreach ($j[2] as $k => $value) {
                        if (is_string($value)) {
                            $value = str_replace('{alias}', $alias, $value);
                        }

                        /** @var string $left */
                        $left = $k;
                        $left = str_replace('{alias}', $alias, $left);

                        $conditions[$left] = $value;
                    }

                    $j[2] = $conditions;
                }

                $j[1] ??= null;
                $j[2] ??= null;
                $j[3] ??= [];

                $joinType = $j[3]['type'] ?? null;

                $j[3]['type'] = $joinType ? JoinType::from($joinType) : JoinType::inner;

                $params['joins'][] = $j;
            }
        }

        // Some fields may need additional select items add to a query.
        if (!empty($defs['additionalSelect'])) {
            $params['extraAdditionalSelect'] = $params['extraAdditionalSelect'] ?? [];

            foreach ($defs['additionalSelect'] as $value) {
                if (is_string($value)) {
                    $value = str_replace('{alias}', $alias ?? '', $value);
                }

                $value = str_replace('{attribute}', $attribute, $value);

                if (!in_array($value, $params['extraAdditionalSelect'])) {
                    $params['extraAdditionalSelect'][] = $value;
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return string[]
     */
    protected function getOrderByAttributeList(array $params): array
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

            if (str_starts_with($expression, 'LIST:') && substr_count($expression, ':') === 2) {
                $expression = explode(':', $expression)[1];
            }

            /** @noinspection PhpDeprecationInspection */
            $attributeList = self::getAllAttributesFromComplexExpression($expression);

            $list = array_merge(
                $list,
                $attributeList
            );
        }

        return $list;
    }

    /**
     *
     * @param string[]|array<string[]> $itemList
     * @param string[]|array<string[]> $newItemList
     * @return string[]|array<string[]>
     */
    protected function getNotIntersectingSelectItemList(array $itemList, array $newItemList): array
    {
        $list = [];

        foreach ($newItemList as $newItem) {
            $isMet = false;

            foreach ($itemList as $item) {
                $itemToCompare = is_array($item) ? ($item[0] ?? null) : $item;

                if ($itemToCompare === $newItem) {
                    $isMet = true;
                }
            }

            if (!$isMet) {
                $list[] = $newItem;
            }
        }

        return $list;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getSelectPart(?Entity $entity, array &$params): string
    {
        $itemList = $params['select'] ?? [];

        $selectNotSpecified = !count($itemList);

        if (!$selectNotSpecified && self::isSelectAll($itemList) && $entity) {
            array_shift($itemList);

            foreach (array_reverse($entity->getAttributeList()) as $item) {
                array_unshift($itemList, $item);
            }
        }

        if ($selectNotSpecified && $entity) {
            $itemList = $entity->getAttributeList();
        }

        if (empty($params['strictSelect']) && $entity && empty($params['groupBy'])) {
            $itemList = array_merge(
                $itemList,
                $this->getSelectDependeeAdditionalList($entity, $itemList)
            );
        }

        if (empty($params['strictSelect']) && !empty($params['distinct']) && empty($params['groupBy'])) {
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
                }
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
            /** @noinspection PhpDeprecationInspection */
            $alias = $this->sanitizeSelectAlias($item[1]);

            if ($expression === '' || $alias === '') {
                throw new RuntimeException("Bad select expression.");
            }

            $selectPartItemList[] = "$expression AS " . $this->quoteIdentifier($alias);
        }

        return implode(', ', $selectPartItemList);
    }

    /**
     * @param array<string, mixed> $params
     * @param string|string[] $attribute
     * @return array{string, string}|null
     */
    protected function getSelectPartItemPair(?Entity $entity, array &$params, $attribute): ?array
    {
        $maxTextColumnsLength = $params['maxTextColumnsLength'] ?? null;
        $skipTextColumns = $params['skipTextColumns'] ?? false;
        $distinct = $params['distinct'] ?? false;

        $attributeType = null;

        if (!is_array($attribute) && !is_string($attribute)) { /** @phpstan-ignore-line */
            throw new RuntimeException("ORM Query: Bad select item.");
        }

        if (is_array($attribute) && count($attribute) === 1) {
            $attribute = $attribute[0];
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

        /** @var string $alias */

        // @todo Make VALUE: usage deprecated.
        if (is_string($expression) && stripos($expression, 'VALUE:') === 0) {
            $part = $this->quote(
                substr($expression, 6)
            );

            return [$part, $alias];
        }

        if (!$entity) {
            if (!is_string($expression)) {
                throw new RuntimeException();
            }

            return [
                $this->convertComplexExpression(null, $expression, false, $params),
                $alias
            ];
        }

        if (is_array($attribute) && count($attribute) === 1) {
            $attribute = $attribute[0];
        }

        if (is_array($attribute) && count($attribute) === 2) {
            // @todo Refactor to unite convertComplexExpression and select, noSelect, notStorable (here and below).

            $alias = $attribute[1];
            $attribute0 = $attribute[0];

            if (!$entity->hasAttribute($attribute0)) {
                $part = $this->convertComplexExpression($entity, $attribute0, $distinct, $params);

                return [$part, $alias];
            }

            if ($this->getAttributeParam($entity, $attribute0, 'select')) {
                $part = $this->getAttributeSql($entity, $attribute0, 'select', $params);

                return [$part, $alias];
            }

            if ($this->getAttributeParam($entity, $attribute0, 'noSelect')) {
                return null;
            }

            if (
                $this->getAttributeParam($entity, $attribute0, AttributeParam::NOT_STORABLE) &&
                $entity->getAttributeType($attribute0) !== Entity::FOREIGN
            ) {
                return null;
            }

            /** @var string $part */
            $part = $this->getAttributePath($entity, $attribute0, $params);

            return [$part, $alias];
        }

        if (!is_string($attribute)) {
            throw new RuntimeException("Bad select.");
        }

        if (!$entity->hasAttribute($attribute)) {
            $expression = $attribute;

            $part = $this->convertComplexExpression($entity, $expression, $distinct, $params);

            return [$part, $attribute];
        }

        if ($this->getAttributeParam($entity, $attribute, 'select')) {
            $fieldPath = $this->getAttributeSql($entity, $attribute, 'select', $params);

            return [$fieldPath, $attribute];
        }

        if ($attributeType === null) {
            return null;
        }

        if (
            $this->getAttributeParam($entity, $attribute, AttributeParam::NOT_STORABLE) &&
            $attributeType !== Entity::FOREIGN
        ) {
            return null;
        }

        if ($attributeType === Entity::FOREIGN && $this->skipForeign($params)) {
            return null;
        }

        /** @var string $fieldPath */
        $fieldPath = $this->getAttributePath($entity, $attribute, $params);

        if ($attributeType === Entity::TEXT && $maxTextColumnsLength !== null) {
            $fieldPath = 'LEFT(' . $fieldPath . ', ' . $maxTextColumnsLength . ')';
        }

        return [$fieldPath, $attribute];
    }

    /**
     * @param string[]|array<string[]> $itemList
     * @return string[]|array<string[]>
     */
    protected function getSelectDependeeAdditionalList(Entity $entity, array $itemList): array
    {
        $additionalList = [];

        $itemListFiltered = array_filter(
            $itemList,
            function ($item) use ($entity) {
                return is_string($item) && $entity->hasAttribute($item);
            }
        );

        foreach ($itemListFiltered as $item) {
            $additionalList = array_merge(
                $additionalList,
                $this->getAttributeParam($entity, $item, AttributeParam::DEPENDEE_ATTRIBUTE_LIST) ?? []
            );
        }

        return array_filter(
            $additionalList,
            function ($item) use ($itemList) {
                return !in_array($item, $itemList);
            }
        );
    }

    /**
     * @param array<string, mixed>|null $params
     */
    protected function getBelongsToJoinItemPart(
        Entity $entity,
        string $relationName,
        ?string $alias = null,
        ?array $params = null
    ): ?string {

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        /** @noinspection PhpDeprecationInspection */
        $alias = !$alias ?
            $this->getAlias($entity, $relationName) :
            $this->sanitizeSelectAlias($alias);

        if (!$alias) {
            return null;
        }

        $foreignEntityType = $this->getRelationParam($entity, $relationName, RelationParam::ENTITY);

        $table = $this->toDb($foreignEntityType);

        $fromAlias = $this->getFromAlias($params, $entity->getEntityType());

        $leftColumnPart = $this->quoteColumn("$fromAlias." . $this->toDb($key));
        $rightColumnPart = $this->quoteColumn("$alias." . $this->toDb($foreignKey));

        $hasDeleted = $this->metadata
            ->getDefs()
            ->tryGetEntity($foreignEntityType)
            ?->hasAttribute(Attribute::DELETED);

        $tablePart = $this->quoteIdentifier($table);
        $aliasPart = $this->quoteIdentifier($alias);

        $part = "JOIN $tablePart AS $aliasPart ON $leftColumnPart = $rightColumnPart";

        if ($hasDeleted) {
            $deletedColumnPart = $this->quoteColumn("$alias." . Attribute::DELETED);
            $deletedValuePart = $this->quote(false);

            $part .= " AND $deletedColumnPart = $deletedValuePart";
        }

        return $part;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getSelectTailPart(array $params): ?string
    {
        $forShare = $params['forShare'] ?? null;
        $forUpdate = $params['forUpdate'] ?? null;

        if ($forShare) {
            return "LOCK IN SHARE MODE";
        }

        if ($forUpdate) {
            return "FOR UPDATE";
        }

        return null;
    }

    /**
     * @param string[]|array<string[]> $select
     * @param string[] $explicitJoins
     * @param array<string, mixed> $params
     */
    protected function getBelongsToJoinsPart(
        Entity $entity,
        ?array $select,
        array $explicitJoins,
        array $params,
    ): string {

        $joinsArr = [];

        $relationsToJoin = [];

        if (is_array($select)) {
            foreach ($select as $item) {
                $field = $item;

                if (is_array($item)) {
                    if (count($item) == 0) {
                        continue;
                    }

                    $field = $item[0];
                }

                /** @var string $field */

                if (
                    $entity->getAttributeType($field) == AttributeType::FOREIGN &&
                    $this->getAttributeParam($entity, $field, AttributeParam::RELATION)
                ) {
                    $relationsToJoin[] = $this->getAttributeParam($entity, $field, AttributeParam::RELATION);
                } else if (
                    $this->getAttributeParam($entity, $field, 'fieldType') == FieldType::LINK_ONE &&
                    $this->getAttributeParam($entity, $field, AttributeParam::RELATION)
                ) {
                    $relationsToJoin[] = $this->getAttributeParam($entity, $field, AttributeParam::RELATION);
                }
            }
        }

        foreach ($entity->getRelationList() as $relationName) {
            $type = $entity->getRelationType($relationName);

            if ($type !== Entity::BELONGS_TO && $type !== Entity::HAS_ONE) {
                continue;
            }

            if ($this->getRelationParam($entity, $relationName, RelationParam::NO_JOIN)) {
                continue;
            }

            if (in_array($relationName, $explicitJoins)) {
                // Never suppose to happen.
                continue;
            } else {
                foreach ($explicitJoins as $skipItem) {
                    if (!is_array($skipItem)) {
                        continue;
                    }

                    if (
                        ($skipItem[0] ?? null) === $relationName &&
                        (
                            ($skipItem[1] ?? null) === null ||
                            ($skipItem[1] ?? null) === $relationName
                        )
                    ) {
                        continue 2;
                    }
                }
            }

            foreach ($explicitJoins as $sItem) {
                if (is_array($sItem) && count($sItem) > 1) {
                    if ($sItem[1] === $relationName) {
                        continue 2;
                    }
                }
            }

            if (
                is_array($select) &&
                !self::isSelectAll($select) &&
                !in_array($relationName, $relationsToJoin)
            ) {
                continue;
            }

            if ($type === Entity::BELONGS_TO) {
                $join = $this->getBelongsToJoinItemPart($entity, $relationName, null, $params);

                if (!$join) {
                    continue;
                }

                $joinsArr[] = 'LEFT ' . $join;

                continue;
            }

            // HAS_ONE
            $join = $this->getJoinItemPart(
                $entity,
                $relationName,
                true,
                [],
                null,
                [],
                $params,
            );

            $joinsArr[] = $join;
        }

        return implode(' ', $joinsArr);
    }

    /**
     * @param array<int, string[]|string> $select
     */
    protected static function isSelectAll(array $select): bool
    {
        if (!count($select)) {
            return true;
        }

        return $select[0] === '*' || $select[0][0] === '*';
    }

    /**
     * @param array<string, mixed> $params
     * @param mixed $orderBy
     * @param mixed $order
     */
    protected function getOrderExpressionPart(
        Entity $entity,
        $orderBy = null,
        $order = null,
        array &$params = [],
        bool $noCustom = false
    ): ?string {

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
                        $entity,
                        $orderByInternal,
                        $orderInternal,
                        $params,
                        $noCustom
                    );
                }
            }

            return implode(", ", $arr);
        }

        if (str_starts_with($orderBy, 'LIST:')) {
            [, $field, $listString] = explode(':', $orderBy);

            $list = explode(',', $listString);
            $list = array_map(fn($item) => str_replace('_COMMA_', ',', $item), $list);
            $list = array_map(fn($item) => $this->quote($item), $list);
            $list = array_reverse($list);
            $listString = implode(', ', $list);

            $orderBy = "POSITION_IN_LIST:($field, $listString)";
            $order = 'DESC';
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
            return "$orderBy " . $order;
        }

        if (
            !$noCustom &&
            $entity->hasAttribute($orderBy) &&
            $this->getAttributeParam($entity, $orderBy, 'order')
        ) {
            return $this->getAttributeOrderSql($entity, $orderBy, $params, $order);
        }

        $fieldPath = $this->getAttributePathForOrderBy($entity, $orderBy, $params);

        if ($fieldPath === null || $fieldPath === '') {
            throw new LogicException("Could not handle 'order' for '".$entity->getEntityType()."'.");
        }

        return "$fieldPath " . $order;
    }

    /**
     * @param array<string, mixed> $params
     * @param mixed $orderBy
     * @param mixed $order
     */
    protected function getOrderPart(Entity $entity, $orderBy = null, $order = null, &$params = []): ?string
    {
        return $this->getOrderExpressionPart($entity, $orderBy, $order, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getAttributePathForOrderBy(Entity $entity, string $orderBy, array $params): ?string
    {
        if (Util::isComplexExpression($orderBy)) {
            return $this->convertComplexExpression(
                $entity,
                $orderBy,
                false,
                $params
            );
        }

        return $this->getAttributePath($entity, $orderBy, $params);
    }

    /**
     * Quote a value (if needed).
     * @param mixed $value
     */
    protected function quote($value): string
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
    protected function toDb(string $string): string
    {
        if (!array_key_exists($string, $this->attributeDbMapCache)) {
            $string[0] = strtolower($string[0]);

            /** @var string $dbString */
            $dbString = preg_replace_callback(
                '/([A-Z])/',
                fn($matches) => '_' . strtolower($matches[1]),
                $string
            );

            $this->attributeDbMapCache[$string] = $dbString;
        }

        return $this->attributeDbMapCache[$string];
    }


    protected function getAlias(Entity $entity, string $relationName): ?string
    {
        if (!isset($this->aliasesCache[$entity->getEntityType()])) {
            $this->aliasesCache[$entity->getEntityType()] = $this->getTableAliases($entity);
        }

        if (isset($this->aliasesCache[$entity->getEntityType()][$relationName])) {
            return $this->aliasesCache[$entity->getEntityType()][$relationName];
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function getTableAliases(Entity $entity): array
    {
        $aliases = [];

        $occurrenceHash = [];

        foreach ($entity->getRelationList() as $name) {
            $type = $entity->getRelationType($name);

            if (
                ($type === Entity::BELONGS_TO || $type === Entity::HAS_ONE) &&
                !array_key_exists($name, $aliases)
            ) {
                if (array_key_exists($name, $occurrenceHash)) {
                    $occurrenceHash[$name]++;
                } else {
                    $occurrenceHash[$name] = 0;
                }

                $suffix = '';

                if ($occurrenceHash[$name] > 0) {
                    $suffix .= '_' . $occurrenceHash[$name];
                }

                $aliases[$name] = $name . $suffix;
            }
        }

        return $aliases;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getAttributePath(Entity $entity, string $attribute, array &$params): ?string
    {
        if (!$entity->hasAttribute($attribute)) {
            return null;
        }

        $entityType = $entity->getEntityType();

        $attributeType = $entity->getAttributeType($attribute);

        if ($this->getAttributeParam($entity, $attribute, 'source')) {
            // For bc.
            if ($this->getAttributeParam($entity, $attribute, 'source') !== 'db') {
                return null;
            }
        }

        if (
            $this->getAttributeParam($entity, $attribute, AttributeParam::NOT_STORABLE) &&
            $attributeType !== Entity::FOREIGN
        ) {
            return null;
        }

        switch ($attributeType) {
            case Entity::FOREIGN:
                $relationName = $this->getAttributeParam($entity, $attribute, AttributeParam::RELATION);

                if (!$relationName) {
                    return null;
                }

                $foreign = $this->getAttributeParam($entity, $attribute, AttributeParam::FOREIGN);

                if (is_array($foreign)) {
                    $wsCount = 0;

                    foreach ($foreign as $i => $value) {
                        if ($value == ' ') {
                            $foreign[$i] = '\' \'';

                            $wsCount ++;

                            continue;
                        }

                        $item =  $this->getAlias($entity, $relationName) . '.' . $this->toDb($value);
                        $item = $this->quoteColumn($item);

                        $foreign[$i] = "COALESCE($item, '')";
                    }

                    $path = 'TRIM(CONCAT(' . implode(', ', $foreign). '))';

                    if ($wsCount > 1) {
                        $path = "REPLACE($path, '  ', ' ')";
                    }

                    return "NULLIF($path, '')";
                }

                $expression = $this->getAlias($entity, $relationName) . '.' . $foreign;

                return $this->convertComplexExpression($entity, $expression, false, $params);
        }

        $alias = $this->getFromAlias($params, $entityType);

        /** @noinspection PhpDeprecationInspection */
        $path = $alias . '.' . $this->toDb($this->sanitize($attribute));

        return $this->quoteColumn($path);
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string|int, mixed> $whereClause
     */
    protected function getWherePart(
        Entity $entity,
        ?array $whereClause = null,
        string $sqlOp = 'AND',
        array &$params = [],
        int $level = 0,
        bool $noCustomWhere = false
    ): string {

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

    /**
     * @return array{string, string, string}
     */
    private function splitWhereLeftItem(string $item): array
    {
        if (preg_match('/^[a-z0-9]+$/i', $item)) {
            return [$item, '=', '='];
        }

        foreach ($this->comparisonOperators as $operator) {
            $sqlOperator = $this->comparisonOperatorMap[$operator] ?? $operator;

            if (!str_ends_with($item, $operator)) {
                continue;
            }

            $expression = trim(substr($item, 0, -strlen($operator)));

            return [$expression, $sqlOperator, $operator];
        }

        return [$item, '=', '='];
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getWherePartItem(
        Entity $entity,
        mixed $leftKey,
        mixed $right,
        array &$params,
        int $level,
        bool $noCustomWhere = false
    ): ?string {

        if (is_int($leftKey) && is_string($right)) {
            return $this->convertComplexExpression($entity, $right, false, $params);
        }

        $left = $leftKey;

        if (is_int($left)) {
            $left = 'AND';
        }

        if ($leftKey === 'NOT') {
            $left = 'AND';
        }

        if (in_array($left, self::SQL_OPERATORS)) {
            $internalPart = $this->getWherePart($entity, $right, $left, $params, $level + 1);

            if (!$internalPart && $internalPart !== '0') {
                return null;
            }

            if ($leftKey === 'NOT') {
                return "NOT (" . $internalPart . ")";
            }

            return "(" . $internalPart . ")";
        }

        if ($left === self::EXISTS_OPERATOR) {
            if ($right instanceof Select) {
                $subQueryPart = $this->composeSelect($right);
            } else if (is_array($right)) {
                $subQueryPart = $this->createSelectQueryInternal($right);
            } else {
                throw new RuntimeException("Bad EXISTS usage in where-clause.");
            }

            return "EXISTS ($subQueryPart)";
        }

        $isComplex = false;
        $isNotValue = false;

        if (str_ends_with($left, ':')) {
            $left = substr($left, 0, strlen($left) - 1);

            $isNotValue = true;
        }

        [$left, $operator, $operatorOrm] = $this->splitWhereLeftItem($left);

        $leftPart = null;

        if (Util::isComplexExpression($left)) {
            $leftPart = $this->convertComplexExpression($entity, $left, false, $params);

            $isComplex = true;
        }

        if (!$isComplex) {
            if (!$entity->hasAttribute($left)) {
                return $this->quote(false);
            }

            $operatorKey = $this->getWhereOperatorKey(
                $operator,
                $operatorOrm,
                $right,
                $entity->getAttributeType($left)
            );

            if (
                !$noCustomWhere &&
                $this->getAttributeParam($entity, $left, 'where') &&
                isset($this->getAttributeParam($entity, $left, 'where')[$operatorKey])
            ) {
                $whereDefs = $this->getAttributeParam($entity, $left, 'where')[$operatorKey];

                return $this->getWherePartItemCustom($entity, $right, $whereDefs, $params, $level);
            }

            $leftPart = $this->getWherePartItemAttributeLeftPart($entity, $left, $params);
        }

        return $this->addRightWherePartItem(
            leftPart: $leftPart,
            operatorOrm: $operatorOrm,
            operator: $operator,
            right: $right,
            entity: $entity,
            isNotValue: $isNotValue,
            params: $params,
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    private function addRightWherePartItem(
        ?string $leftPart,
        string $operatorOrm,
        string $operator,
        mixed $right,
        Entity $entity,
        bool $isNotValue,
        array $params,
    ): string {

        if ($leftPart === null) {
            return $this->quote(false);
        }

        if ($operatorOrm === '=s' || $operatorOrm === '!=s') {
            if ($right instanceof Select) {
                $subSql = $this->composeSelect($right);

                return "$leftPart $operator ($subSql)";
            }

            if (!is_array($right)) {
                throw new RuntimeException("Bad `=s` operator usage, value must be sub-query.");
            }

            $subQuerySelectParams = !empty($right['selectParams']) ?
                $right['selectParams'] :
                $right;

            if (
                !isset($subQuerySelectParams['from']) &&
                !isset($subQuerySelectParams['fromQuery'])
            ) {
                // 'entityType' is for backward compatibility.
                $subQuerySelectParams['from'] = $right['entityType'] ?? $entity->getEntityType();
            }

            if (!empty($right['withDeleted'])) {
                $subQuerySelectParams['withDeleted'] = true;
            }

            $subSql = $this->createSelectQueryInternal($subQuerySelectParams);

            return "$leftPart $operator ($subSql)";
        }

        if ($right instanceof Select) {
            if ($operatorOrm === '*' || $operatorOrm === '!*') {
                throw new RuntimeException("LIKE operator is not compatible with sub-query.");
            }

            $subQueryPart = $this->composeSelect($right);

            return "$leftPart $operator ($subQueryPart)";
        }

        if (str_ends_with($operatorOrm, 'any') || str_ends_with($operatorOrm, 'all')) {
            throw new RuntimeException("ANY/ALL operators can be used only with sub-query.");
        }

        if ($right instanceof Expression) {
            $isNotValue = true;

            $right = $right->getValue();
        }

        if (is_array($right)) {
            $valuePartList = $right;

            foreach ($valuePartList as $k => $v) {
                $valuePartList[$k] = $this->quote($v);
            }

            $negatingPart = '';
            $emptyValuePart = $this->quote(false);

            if ($operator === '<>') {
                $negatingPart = 'NOT ';
                $emptyValuePart = $this->quote(true);
            }

            if ($valuePartList === []) {
                return $emptyValuePart;
            }

            $valuesPart = implode(',', $valuePartList);

            return "$leftPart {$negatingPart}IN ($valuesPart)";
        }

        if ($isNotValue) {
            if (is_null($right)) {
                return $leftPart;
            }

            $expressionSql = $this->convertComplexExpression($entity, $right, false, $params);

            return "$leftPart $operator $expressionSql";
        }

        if (is_null($right)) {
            if ($operator === '=') {
                return "$leftPart IS NULL";
            }

            if ($operator === '<>') {
                return "$leftPart IS NOT NULL";
            }

            return $this->quote(false);
        }

        $valuePart = $this->quote($right);

        return "$leftPart $operator $valuePart";
    }

    /**
     * @param array<string, mixed> $params
     */
    private function getWherePartItemAttributeLeftPart(Entity $entity, string $attribute, array &$params): ?string
    {
        $attributeType = $entity->getAttributeType($attribute);
        $entityType = $entity->getEntityType();

        if ($attributeType === Entity::FOREIGN) {
            // @todo Add a test.
            $relationName = $this->getAttributeParam($entity, $attribute, AttributeParam::RELATION);
            $foreign = $this->getAttributeParam($entity, $attribute, AttributeParam::FOREIGN);

            if (!$relationName) {
                throw new RuntimeException("No 'relation' param for field $entityType.$attribute.");
            }

            if (!$foreign) {
                throw new RuntimeException("No 'foreign' param for field $entityType.$attribute.");
            }

            if (!$entity->hasRelation($relationName)) {
                throw new RuntimeException("No relation '$relationName' for field $entityType.$attribute.");
            }

            $alias = $this->getAlias($entity, $relationName);

            if (!$alias) {
                throw new RuntimeException("Could not get alias for $entityType.$relationName.");
            }

            if (is_array($foreign)) {
                return $this->getAttributePath($entity, $attribute, $params);
            }

            return $this->convertComplexExpression($entity, "$alias.$foreign", false, $params);
        }

        $fromAlias = $this->getFromAlias($params, $entity->getEntityType());
        /** @noinspection PhpDeprecationInspection */
        $column = $fromAlias . '.' . $this->toDb($this->sanitize($attribute));

        return $this->quoteColumn($column);
    }

    private function getWhereOperatorKey(
        string $operator,
        string $operatorOrm,
        mixed $value,
        ?string $attributeType
    ): string {

        $operatorKey = $operator;

        if ($operatorOrm === '*') {
            $operatorKey = 'LIKE';
        } else if ($operatorOrm === '!*') {
            $operatorKey = 'NOT LIKE';
        }

        if (
            is_bool($value) &&
            in_array($operator, ['=', '<>']) &&
            $attributeType == Entity::BOOL
        ) {
            if ($value) {
                $operatorKey = $operator === '=' ?
                    '= TRUE' : '= FALSE';
            } else {
                $operatorKey = $operator === '=' ?
                    '= FALSE' : '= TRUE';
            }
        } else if (is_array($value)) {
            if ($operator == '=') {
                $operatorKey = 'IN';
            } else if ($operator == '<>') {
                $operatorKey = 'NOT IN';
            }
        } else if (is_null($value)) {
            if ($operator == '=') {
                $operatorKey = 'IS NULL';
            } else if ($operator == '<>') {
                $operatorKey = 'IS NOT NULL';
            }
        }

        return $operatorKey;
    }

    /**
     * @param array<string, mixed>|string $whereDefs
     * @param array<string, mixed> $params
     */
    protected function getWherePartItemCustom(
        Entity $entity,
        mixed $value,
        array|string $whereDefs,
        array &$params,
        int $level
    ): string {

        $whereSqlPart = '';
        $whereClause = null;

        if (is_string($whereDefs)) {
            $whereSqlPart = $whereDefs;
            $whereDefs = [];
        } else if (!empty($whereDefs['sql'])) {
            $whereSqlPart = $whereDefs['sql'];
        } else if (!empty($whereDefs['whereClause'])) {
            $whereClause = $this->applyValueToCustomWhereClause($whereDefs['whereClause'], $value);
        } else {
            return $this->quote(false);
        }

        $leftJoins = $whereDefs['leftJoins'] ?? [];
        $joins = $whereDefs['joins'] ?? [];

        foreach ($leftJoins as $j) {
            $jAlias = $this->obtainJoinAlias($j);

            if (is_string($j)) {
                $j = [$j];
            }

            foreach ($params['joins'] as $jE) {
                $jEAlias = $this->obtainJoinAlias($jE);

                if ($jEAlias === $jAlias) {
                    continue 2;
                }
            }

            $j[1] ??= null;
            $j[2] ??= null;
            $j[3] ??= [];

            $j[3]['type'] = JoinType::left;

            $params['joins'][] = $j;
        }

        foreach ($joins as $j) {
            $jAlias = $this->obtainJoinAlias($j);

            if (is_string($j)) {
                $j = [$j];
            }

            foreach ($params['joins'] as $jE) {
                $jEAlias = $this->obtainJoinAlias($jE);

                if ($jEAlias === $jAlias) {
                    continue 2;
                }
            }

            $j[1] ??= null;
            $j[2] ??= null;
            $j[3] ??= [];

            $joinType = $j[3]['type'] ?? null;

            $j[3]['type'] = $joinType ? JoinType::from($joinType) : JoinType::inner;

            $params['joins'][] = $j;
        }

        if (!empty($whereDefs['customJoin'])) {
            // For bc.
            $params['customJoin'] .= ' ' . $whereDefs['customJoin'];
        }

        if (!empty($whereDefs['distinct'])) {
            $params['distinct'] = true;
        }

        if ($whereClause) {
            return
                "(" .
                $this->getWherePart($entity, $whereClause, 'AND', $params, $level, true) .
                ")";
        }

        return str_replace('{value}', $this->stringifyValue($value), $whereSqlPart);
    }

    /**
     * @param array<string|int, mixed> $whereClause
     * @return array<string|int, mixed>
     */
    protected function applyValueToCustomWhereClause(array $whereClause, mixed $value): array
    {
        $modified = [];

        foreach ($whereClause as $left => $right) {
            if ($right === '{value}') {
                $right = $value;
            } else if (is_string($right)) {
                if (!is_array($value)) {
                    $right = str_replace('{value}', (string) $value, $right);
                }
            } else if (is_array($right)) {
                $right = $this->applyValueToCustomWhereClause($right, $value);
            }

            if (is_string($left) && str_ends_with($left, ':') && str_contains($left, '{value}')) {
                $left = str_replace('{value}', Expression\Util::stringifyArgument($value), $left);
            }

            $modified[$left] = $right;
        }

        return $modified;
    }

    /**
     * @param array<string>|string $j
     * @return string
     */
    protected function obtainJoinAlias($j)
    {
        if (is_array($j)) {
            if (isset($j[0])) {
                if (isset($j[1]) && $j[1]) {
                    $joinAlias = $j[1];
                } else {
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

    /**
     * @param mixed $value
     */
    protected function stringifyValue($value): string
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
     * @todo Make protected in v10.0.
     * @deprecated As of v6.0. Not to be used outside.
     */
    public function sanitize(string $string): string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string) ?? '';
    }

    /**
     * Sanitize an alias for a SELECT statement.
     * @todo Make protected in v10.0.
     * @deprecated As of v6.0. Not to be used outside.
     */
    public function sanitizeSelectAlias(string $string): string
    {
        $string = preg_replace('/[^A-Za-z\r\n0-9_:\'" .,\-()]+/', '', $string) ?? '';

        if (strlen($string) > $this->aliasMaxLength) {
            $string = substr($string, 0, $this->aliasMaxLength);
        }

        return $string;
    }

    protected function sanitizeIndexName(string $string): string
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string) ?? '';
    }

    /**
     * @param array<string|int, mixed> $joinConditions
     * @param array<string, mixed[]> $joins
     * @param array<string, mixed> $params
     */
    protected function getJoinsTypePart(
        Entity $entity,
        array $joins,
        array $params,
        $joinConditions,
        bool $isLeft = false,
    ): string {

        $joinSqlList = [];

        foreach ($joins as $item) {
            $itemConditions = [];
            $itemParams = [];

            $isItemLeft = $isLeft;

            if (is_array($item)) {
                $target = $item[0];

                if (count($item) > 1) {
                    $alias = $item[1] ?? $target;

                    if (count($item) > 2) {
                        $itemConditions = $item[2] ?? [];
                    }

                    if (count($item) > 3) {
                        $itemParams = $item[3] ?? [];
                    }
                } else {
                    $alias = $target;
                }

                if (($itemParams['type'] ?? null) === JoinType::left) {
                    $isItemLeft = true;
                }

                if ($target instanceof Select && !is_string($alias)) {
                    throw new LogicException("Sub-query join can't be w/o alias");
                }
            } else {
                $target = $item;
                $alias = $target;
            }

            $conditions = [];

            if (!empty($joinConditions[$alias])) {
                $conditions = $joinConditions[$alias];
            }

            foreach ($itemConditions as $left => $right) {
                $conditions[$left] = $right;
            }

            $sql = $this->getJoinItemPart(
                entity: $entity,
                target: $target,
                isLeft: $isItemLeft,
                conditions: $conditions,
                alias: $alias,
                joinParams: $itemParams,
                params: $params,
            );

            if ($sql) {
                $joinSqlList[] = $sql;
            }
        }

        return implode(' ', $joinSqlList);
    }

    /**
     * @param array<string, mixed> $params
     * @noinspection PhpDeprecationInspection
     */
    protected function buildJoinConditionStatement(
        Entity $entity,
        string $alias,
        mixed $leftKey,
        mixed $right,
        array $params,
        bool $noLeftAlias = false
    ): string {

        // @todo Unify with `getWherePartItem`. If reasonable.

        $left = $leftKey;

        if (is_int($left)) {
            $left = 'AND';
        }

        if ($leftKey === 'NOT') {
            $left = 'AND';
        }

        if (in_array($left, self::SQL_OPERATORS)) {
            $logicalOperator = 'AND';

            if ($left === 'OR') {
                $logicalOperator = 'OR';
            }

            $parts = [];

            foreach ($right as $k => $v) {
                $part = $this->buildJoinConditionStatement($entity, $alias, $k, $v, $params, $noLeftAlias);

                if ($part === '') {
                    continue;
                }

                $parts[] = $part;
            }

            $sql = implode(' ' . $logicalOperator . ' ', $parts);

            if (count($parts) > 1) {
                $sql = '(' . $sql . ')';
            }

            if ($leftKey === 'NOT') {
                return "NOT ($sql)";
            }

            return $sql;
        }

        $isNotValue = false;
        $isComplex = false;

        if (str_ends_with($left, ':')) {
            $left = substr($left, 0, strlen($left) - 1);

            $isNotValue = true;
        }

        [$left, $operator, $operatorOrm] = $this->splitWhereLeftItem($left);

        $leftPart = null;

        if (Util::isComplexExpression($left)) {
            $isComplex = true;

            // Differs from where logic.
            $stub = []; // @todo Revise the need.
            $leftPart = $this->convertComplexExpression($entity, $left, false, $stub);
        }

        if (!$isComplex) {
            // Differs from where logic.

            $column = $this->toDb($this->sanitize($left));

            $leftAlias = $noLeftAlias ?
                $this->getFromAlias($params, $entity->getEntityType()) :
                $this->sanitize($alias);

            $leftPart = $this->quoteColumn("$leftAlias.$column");
        }

        return $this->addRightWherePartItem(
            leftPart: $leftPart,
            operatorOrm: $operatorOrm,
            operator: $operator,
            right: $right,
            entity: $entity,
            isNotValue: $isNotValue,
            params: $params,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $joinParams
     * @param array<string|int, mixed> $conditions
     */
    protected function getJoinItemPart(
        Entity $entity,
        string|Select $target,
        bool $isLeft = false,
        array $conditions = [],
        ?string $alias = null,
        array $joinParams = [],
        array $params = []
    ): string {

        $prefixPart = $isLeft ? 'LEFT ' : '';

        if (!is_string($target) || !$entity->hasRelation($target)) {
            if ($alias === '') {
                throw new LogicException("Empty alias.");
            }

            if (!is_string($target)) {
                if ($alias === null) {
                    throw new LogicException();
                }

                /** @noinspection PhpDeprecationInspection */
                $alias = $this->sanitizeSelectAlias($alias);
            } else {
                /** @noinspection PhpDeprecationInspection */
                $alias = $alias === null ?
                    $this->sanitize($target) :
                    $this->sanitizeSelectAlias($alias);
            }

            /** @noinspection PhpDeprecationInspection */
            $targetPart = is_string($target) ?
                $this->quoteIdentifier($this->toDb($this->sanitize($target))) :
                '(' . $this->composeSelecting($target) . ')';

            $aliasPart = $this->quoteIdentifier($alias);

            $sql = $prefixPart . "JOIN ";

            if (!empty($joinParams['isLateral'])) {
                $sql .= "LATERAL ";
            }

            $sql .= "$targetPart AS $aliasPart";

            if ($conditions === []) {
                return $sql;
            }

            $sql .= " ON";

            $conditionParts = [];

            foreach ($conditions as $left => $right) {
                $conditionParts[] = $this->buildJoinConditionStatement(
                    $entity,
                    $alias,
                    $left,
                    $right,
                    $params,
                    $joinParams['noLeftAlias'] ?? false,
                );
            }

            $sql .= " " . implode(" AND ", $conditionParts);

            return $sql;
        }

        $relationName = $target;

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        if (!$alias) {
            $alias = $relationName;
        }

        /** @noinspection PhpDeprecationInspection */
        $alias = $this->sanitize($alias);

        $relationConditions = $this->getRelationParam($entity, $relationName, RelationParam::CONDITIONS);
        $foreignEntityType = $this->getRelationParam($entity, $relationName, RelationParam::ENTITY);

        if ($relationConditions) {
            $conditions = array_merge($conditions, $relationConditions);
        }

        $type = $entity->getRelationType($relationName);

        $fromAlias = $this->getFromAlias($params, $entity->getEntityType());

        switch ($type) {
            case Entity::MANY_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'] ?? null;
                $distantKey = $keySet['distantKey'] ?? null;

                if ($nearKey === null || $distantKey === null) {
                    throw new RuntimeException("Bad relation key.");
                }

                $relTable = $this->toDb(
                    $this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME)
                );

                $distantTable = $this->toDb($foreignEntityType);

                $onlyMiddle = $joinParams['onlyMiddle'] ?? false;

                $midAlias = $onlyMiddle ?
                    $alias :
                    $alias . 'Middle';

                $indexKeyList = null;
                $indexList = $joinParams['useIndex'] ?? null;

                if ($indexList) {
                    $indexKeyList = [];

                    if (is_string($indexList)) {
                        $indexList = [$indexList];
                    }

                    foreach ($indexList as $indexName) {
                        $indexKey = $this->metadata->get(
                            $entity->getEntityType(),
                            [EntityParam::RELATIONS, $relationName, RelationParam::INDEXES, $indexName, IndexParam::KEY]
                        );

                        if ($indexKey) {
                            $indexKeyList[] = $indexKey;
                        }
                    }
                }

                $indexPart = '';

                if ($this->indexHints && $indexKeyList !== null && count($indexKeyList)) {
                    $sanitizedIndexList = [];

                    foreach ($indexKeyList as $indexKey) {
                        $sanitizedIndexList[] = $this->quoteIdentifier(
                            $this->sanitizeIndexName($indexKey)
                        );
                    }

                    $indexPart = " USE INDEX (" . implode(', ', $sanitizedIndexList) . ")";
                }

                $leftKeyColumn = $this->quoteColumn("$fromAlias." . $this->toDb($key));
                $middleKeyColumn = $this->quoteColumn("$midAlias." . $this->toDb($nearKey));
                $middleDeletedColumn = $this->quoteColumn("$midAlias.deleted");

                $sql =
                    "{$prefixPart}JOIN ".$this->quoteIdentifier($relTable)." AS " .
                    $this->quoteIdentifier($midAlias) . "$indexPart " .
                    "ON $leftKeyColumn = $middleKeyColumn" .
                    " AND " .
                    "$middleDeletedColumn = " . $this->quote(false);

                $conditionParts = [];

                foreach ($conditions as $left => $right) {
                    $conditionParts[] = $this->buildJoinConditionStatement(
                        $entity,
                        $midAlias,
                        $left,
                        $right,
                        $params
                    );
                }

                if (count($conditionParts)) {
                    $sql .= " AND " . implode(" AND ", $conditionParts);
                }

                if (!$onlyMiddle) {
                    $rightKeyColumn = $this->quoteColumn("$alias." . $this->toDb($foreignKey));
                    $middleDistantKeyColumn = $this->quoteColumn("$midAlias." . $this->toDb($distantKey));
                    $rightDeletedColumn = $this->quoteColumn("$alias.deleted");

                    $sql .= " {$prefixPart}JOIN " . $this->quoteIdentifier($distantTable) . " AS " .
                        $this->quoteIdentifier($alias)
                        . " ON $rightKeyColumn = $middleDistantKeyColumn"
                        . " AND "
                        . "$rightDeletedColumn = " . $this->quote(false);
                }

                return $sql;

            case Entity::HAS_MANY:
            case Entity::HAS_ONE:
                $foreignKey = $keySet['foreignKey'];
                $distantTable = $this->toDb($foreignEntityType);

                $leftIdColumn = $this->quoteColumn("$fromAlias." . $this->toDb(Attribute::ID));
                $rightIdColumn = $this->quoteColumn("$alias." . $this->toDb($foreignKey));
                $leftDeletedColumn = $this->quoteColumn("$alias.deleted");

                $sql =
                    "{$prefixPart}JOIN " . $this->quoteIdentifier($distantTable) . " AS "
                    . $this->quoteIdentifier($alias) . " ON "
                    . "$leftIdColumn = $rightIdColumn AND "
                    . "$leftDeletedColumn = " . $this->quote(false);

                $conditionParts = [];

                foreach ($conditions as $left => $right) {
                    $conditionParts[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right, $params);
                }

                if (count($conditionParts)) {
                    $sql .= " AND " . implode(" AND ", $conditionParts);
                }

                return $sql;

            case Entity::HAS_CHILDREN:
                $foreignKey = $keySet['foreignKey'];
                $foreignType = $keySet['foreignType'] ?? null;

                if ($foreignType === null) {
                    throw new RuntimeException("Bad relation key.");
                }

                $distantTable = $this->toDb($foreignEntityType);

                $leftIdColumn = $this->quoteColumn("$fromAlias." . $this->toDb(Attribute::ID));
                $rightIdColumn = $this->quoteColumn("$alias." . $this->toDb($foreignKey));
                $leftTypeColumn = $this->quoteColumn("$alias." . $this->toDb($foreignType));
                $leftDeletedColumn = $this->quoteColumn("$alias.deleted");

                $sql =
                    "{$prefixPart}JOIN " . $this->quoteIdentifier($distantTable)
                    . " AS "
                    . $this->quoteIdentifier($alias) . " ON "
                    . "$leftIdColumn = $rightIdColumn AND "
                    . "$leftTypeColumn = " . $this->quote($entity->getEntityType()) . " AND "
                    . "$leftDeletedColumn = " . $this->quote(false);

                $conditionParts = [];

                foreach ($conditions as $left => $right) {
                    $conditionParts[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right, $params);
                }

                if (count($conditionParts)) {
                    $sql .= " AND " . implode(" AND ", $conditionParts);
                }

                return $sql;

            case Entity::BELONGS_TO:
                return $prefixPart . $this->getBelongsToJoinItemPart($entity, $relationName, $alias, $params);
        }

        return '';
    }

    /**
     * @param string[]|null $indexKeyList
     */
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
    ): string {

        $sql = "SELECT";

        if (!empty($distinct) && empty($groupBy)) {
            $sql .= " DISTINCT";
        }

        $sql .= " $select";

        if ($from) {
            $sql .= " FROM $from";
        }

        if ($alias) {
            $sql .= " AS " . $this->quoteIdentifier($alias);
        }

        if ($this->indexHints && !empty($indexKeyList)) {
            foreach ($indexKeyList as $index) {
                $sql .= " USE INDEX (" . $this->quoteIdentifier($this->sanitizeIndexName($index)) . ")";
            }
        }

        if (!empty($joins)) {
            $sql .= " $joins";
        }

        if ($where !== null && $where !== '') {
            $sql .= " WHERE $where";
        }

        if (!empty($groupBy)) {
            $sql .= " GROUP BY $groupBy";
        }

        if ($having !== null && $having !== '') {
            $sql .= " HAVING $having";
        }

        if (!empty($order)) {
            $sql .= " ORDER BY $order";
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
    ): string {

        $sql = "DELETE ";

        if ($alias) {
            $sql .= $this->quoteIdentifier($alias) . " ";
        }

        $sql .= "FROM " . $this->quoteIdentifier($table);


        if ($alias) {
            $sql .= " AS " . $this->quoteIdentifier($alias);
        }

        if ($joins) {
            $sql .= " $joins";
        }

        if ($where) {
            $sql .= " WHERE $where";
        }

        if ($order) {
            $sql .= " ORDER BY $order";
        }

        if ($limit !== null) {
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
    ): string {

        $sql = "UPDATE " . $this->quoteIdentifier($table);

        if ($joins) {
            $sql .= " $joins";
        }

        $sql .= " SET $set";

        if ($where) {
            $sql .= " WHERE $where";
        }

        if ($order) {
            $sql .= " ORDER BY $order";
        }

        if ($limit !== null) {
            $sql = $this->limit($sql, null, $limit);
        }

        return $sql;
    }

    protected function composeInsertQuery(
        string $table,
        string $columns,
        string $values,
        ?string $update = null
    ): string {

        $sql = "INSERT INTO " . $this->quoteIdentifier($table) . " ($columns) $values";

        if ($update) {
            $sql .= " ON DUPLICATE KEY UPDATE " . $update;
        }

        return $sql;
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $params
     */
    protected function getSetPart(Entity $entity, array $values, array $params): string
    {
        if (!count($values)) {
            throw new RuntimeException("ORM Query: No SET values for update query.");
        }

        $list = [];

        foreach ($values as $attribute => $value) {
            $isNotValue = false;

            if (str_ends_with($attribute, ':')) {
                $attribute = substr($attribute, 0, -1);
                $isNotValue = true;
            }

            if (strpos($attribute, '.') > 0) {
                [$alias, $attribute] = explode('.', $attribute);

                /** @noinspection PhpDeprecationInspection */
                $alias = $this->sanitize($alias);
                /** @noinspection PhpDeprecationInspection */
                $column = $this->toDb($this->sanitize($attribute));

                $left = $this->quoteColumn("$alias.$column");
            } else {
                $table = $this->toDb($entity->getEntityType());
                /** @noinspection PhpDeprecationInspection */
                $column = $this->toDb($this->sanitize($attribute));

                $left = $this->quoteColumn("$table.$column");
            }

            $right = $isNotValue ?
                $this->convertComplexExpression($entity, $value, false, $params) :
                $this->quote($value);

            $list[] = $left . " = " . $right;
        }

        return implode(', ', $list);
    }

    /**
     * @param string[] $columnList
     */
    protected function getInsertColumnsPart(array $columnList): string
    {
        $list = [];

        foreach ($columnList as $column) {
            /** @noinspection PhpDeprecationInspection */
            $list[] = $this->quoteIdentifier(
                $this->toDb(
                    $this->sanitize($column)
                )
            );
        }

        return implode(', ', $list);
    }

    /**
     * @param string[] $columnList
     * @param array<string, mixed> $values
     */
    protected function getInsertValuesItemPart(array $columnList, array $values): string
    {
        $list = [];

        foreach ($columnList as $column) {
            $list[] = $this->quote($values[$column]);
        }

        return implode(', ', $list);
    }

    /**
     * @param array<string, mixed> $values
     */
    protected function getInsertUpdatePart(array $values): string
    {
        $list = [];

        foreach ($values as $column => $value) {
            /** @noinspection PhpDeprecationInspection */
            $list[] = $this->quoteIdentifier(
                $this->toDb(
                    $this->sanitize($column)
                )
            ) . " = " . $this->quote($value);
        }

        return implode(', ', $list);
    }

    /**
     * @return mixed
     */
    protected function getAttributeParam(Entity $entity, string $attribute, string $param)
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getAttributeParam($attribute, $param);
        }

        $entityDefs = $this->metadata
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasAttribute($attribute)) {
            return null;
        }

        return $entityDefs->getAttribute($attribute)->getParam($param);
    }

    /**
     * @return mixed
     */
    protected function getRelationParam(Entity $entity, string $relation, string $param)
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getRelationParam($relation, $param);
        }

        $entityDefs = $this->metadata
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasRelation($relation)) {
            return null;
        }

        return $entityDefs->getRelation($relation)->getParam($param);
    }

    /**
     * Add a LIMIT part to an SQL query.
     */
    abstract protected function limit(string $sql, ?int $offset = null, ?int $limit = null): string;
}
