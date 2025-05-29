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

use Espo\ORM\Entity;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Delete as DeleteQuery;
use Espo\ORM\Query\DeleteBuilder;
use Espo\ORM\Query\Insert as InsertQuery;
use Espo\ORM\Query\LockTable as LockTableQuery;

use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Update as UpdateQuery;
use Espo\ORM\Query\UpdateBuilder;
use LogicException;
use RuntimeException;

class PostgresqlQueryComposer extends BaseQueryComposer
{
    protected string $identifierQuoteCharacter = '"';
    protected bool $indexHints = false;
    protected bool $skipForeignIfForUpdate = true;
    protected int $aliasMaxLength = 128;

    /** @var array<string, string> */
    protected array $comparisonOperatorMap = [
        '!=s' => 'NOT IN',
        '=s' => 'IN',
        '!=' => '<>',
        '!*' => 'NOT ILIKE',
        '*' => 'ILIKE',
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
        'LIKE' => 'ILIKE',
        'NOT_LIKE' => 'NOT ILIKE',
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

    protected function quoteColumn(string $column): string
    {
        $list = explode('.', $column);
        $list = array_map(fn ($item) => '"' . $item . '"', $list);

        return implode('.', $list);
    }

    /**
     * @todo Make protected.
     *
     * @param mixed $value
     */
    public function quote($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
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

        if (in_array($function, ['MATCH_BOOLEAN', 'MATCH_NATURAL_LANGUAGE'])) {
            if (count($argumentPartList) < 2) {
                throw new RuntimeException("Not enough arguments for MATCH function.");
            }

            $queryPart = end($argumentPartList);
            $columnsPart = implode(
                " || ' ' || ",
                array_map(
                    fn ($item) => "COALESCE($item, '')",
                    array_slice($argumentPartList, 0, -1)
                )
            );

            return "TS_RANK_CD(TO_TSVECTOR($columnsPart), PLAINTO_TSQUERY($queryPart))";
        }

        if ($function === 'IF') {
            if (count($argumentPartList) < 3) {
                throw new RuntimeException("Not enough arguments for IF function.");
            }

            $conditionPart = $argumentPartList[0];
            $thenPart = $argumentPartList[1];
            $elsePart = $argumentPartList[2];

            return "CASE WHEN $conditionPart THEN $thenPart ELSE $elsePart END";
        }

        if ($function === 'ROUND') {
            if (count($argumentPartList) === 2 && $argumentPartList[1] === '0') {
                $argumentPartList = array_slice($argumentPartList, 0, -1);

                return "ROUND($argumentPartList[0])";
            }
        }

        if ($function === 'UNIX_TIMESTAMP') {
            $arg = $argumentPartList[0] ?? 'NOW()';

            return "FLOOR(EXTRACT(EPOCH FROM $arg))";
        }

        if ($function === 'BINARY') {
            // Not supported.
            return $argumentPartList[0] ?? '0';
        }

        if ($function === 'TZ') {
            if (count($argumentPartList) < 2) {
                throw new RuntimeException("Not enough arguments for function TZ.");
            }

            $offsetHoursString = $argumentPartList[1];
            if (str_starts_with($offsetHoursString, '\'') && str_ends_with($offsetHoursString, '\'')) {
                $offsetHoursString = substr($offsetHoursString, 1, -1);
            }

            if (str_contains($offsetHoursString, '.')) {
                $minutes = (int) (floatval($offsetHoursString) * 60);
                $minutesString = (string) $minutes;

                return "$argumentPartList[0] + INTERVAL '$minutesString MINUTE'";
            }

            return "$argumentPartList[0] + INTERVAL '$offsetHoursString HOUR'";
        }

        if ($function === 'POSITION_IN_LIST') {
            if (count($argumentPartList) <= 1) {
                return $this->quote(1);
            }

            $field = $argumentPartList[0];

            $pairs = array_map(
                fn($i) => [$i, $argumentPartList[$i]],
                array_keys($argumentPartList)
            );

            $whenParts = array_map(function ($item) use ($field) {
                $resolution = intval($item[0]);
                $value = $item[1];

                return " WHEN $field = $value THEN $resolution";
            }, array_slice($pairs, 1));

            return "CASE" . implode('', $whenParts) . " ELSE 0 END";
        }

        if ($function === 'IFNULL') {
            $function = 'COALESCE';
        }

        if (str_starts_with($function, 'YEAR_') && $function !== 'YEAR_NUMBER') {
            $fiscalShift = substr($function, 5);

            if (is_numeric($fiscalShift)) {
                $fiscalShift = (int) $fiscalShift;
                $fiscalFirstMonth = $fiscalShift + 1;

                return
                    "CASE WHEN EXTRACT(MONTH FROM $part) >= $fiscalFirstMonth THEN ".
                    "EXTRACT(YEAR FROM $part) ".
                    "ELSE EXTRACT(YEAR FROM $part) - 1 END";
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
                    "CASE WHEN EXTRACT(MONTH FROM $part) >= $fiscalFirstMonth " .
                    "THEN " .
                    "CONCAT(" .
                    "EXTRACT(YEAR FROM $part), '_', " .
                    "FLOOR((EXTRACT(MONTH FROM $part) - $fiscalFirstMonth) / 3) + 1" .
                    ") " .
                    "ELSE " .
                    "CONCAT(" .
                    "EXTRACT(YEAR FROM $part) - 1, '_', " .
                    "CEIL((EXTRACT(MONTH FROM $part) + $fiscalDistractedMonth) / 3)" .
                    ") " .
                    "END";
            }
        }

        switch ($function) {
            case 'MONTH':
                return "TO_CHAR($part, 'YYYY-MM')";

            case 'DAY':
                return "TO_CHAR($part, 'YYYY-MM-DD')";

            case 'WEEK':
            case 'WEEK_0':
            case 'WEEK_1':
                if (str_starts_with($part, "'")) {
                    $part = "DATE " . $part;
                }

                return "CONCAT(TO_CHAR($part, 'YYYY'), '/', TRIM(LEADING '0' FROM TO_CHAR($part, 'IW')))";

            case 'QUARTER':
                return "CONCAT(TO_CHAR($part, 'YYYY'), '_', TO_CHAR($part, 'Q'))";

            case 'WEEK_NUMBER_0':
            case 'WEEK_NUMBER':
            case 'WEEK_NUMBER_1':
                // Monday week-start not implemented.
                return "TO_CHAR($part, 'IW')::INTEGER";

            case 'HOUR_NUMBER':
            case 'HOUR':
                return "EXTRACT(HOUR FROM $part)";

            case 'MINUTE_NUMBER':
            case 'MINUTE':
                return "EXTRACT(MINUTE FROM $part)";

            case 'SECOND_NUMBER':
            case 'SECOND':
                return "FLOOR(EXTRACT(SECOND FROM $part))";

            case 'DATE_NUMBER':
            case 'DAYOFMONTH':
                return "EXTRACT(DAY FROM $part)";

            case 'DAYOFWEEK_NUMBER':
            case 'DAYOFWEEK':
                return "EXTRACT(DOW FROM $part)";

            case 'MONTH_NUMBER':
                return "EXTRACT(MONTH FROM $part)";

            case 'YEAR_NUMBER':
            case 'YEAR':
                return "EXTRACT(YEAR FROM $part)";

            case 'QUARTER_NUMBER':
                return "EXTRACT(QUARTER FROM $part)";
        }

        if (str_starts_with($function, 'TIMESTAMPDIFF_')) {
            $from = $argumentPartList[0] ?? $this->quote(0);
            $to = $argumentPartList[1] ?? $this->quote(0);

            switch ($function) {
                case 'TIMESTAMPDIFF_YEAR':
                    return "EXTRACT(YEAR FROM $to - $from)";

                case 'TIMESTAMPDIFF_MONTH':
                    return "EXTRACT(MONTH FROM $to - $from)";

                case 'TIMESTAMPDIFF_WEEK':
                    return "FLOOR(EXTRACT(DAY FROM $to - $from) / 7)";

                case 'TIMESTAMPDIFF_DAY':
                    return "EXTRACT(DAY FROM ($to) - $from)";

                case 'TIMESTAMPDIFF_HOUR':
                    return "EXTRACT(HOUR FROM $to - $from)";

                case 'TIMESTAMPDIFF_MINUTE':
                    return "EXTRACT(MINUTE FROM $to - $from)";

                case 'TIMESTAMPDIFF_SECOND':
                    return "FLOOR(EXTRACT(SECOND FROM $to - $from))";
            }
        }

        return parent::getFunctionPart(
            $function,
            $part,
            $params,
            $entityType,
            $distinct,
            $argumentPartList
        );
    }

    public function composeDelete(DeleteQuery $query): string
    {
        if (
            $query->getJoins() !== [] ||
            $query->getLeftJoins() !== [] ||
            $query->getLimit() !== null ||
            $query->getOrder() !== []
        ) {
            $subQueryBuilder = SelectBuilder::create()
                ->select(Attribute::ID)
                ->from($query->getFrom())
                ->order($query->getOrder());

            foreach ($query->getJoins() as $join) {
                $subQueryBuilder->join($join);
            }

            foreach ($query->getLeftJoins() as $join) {
                $subQueryBuilder->leftJoin($join);
            }

            if ($query->getWhere()) {
                $subQueryBuilder->where($query->getWhere());
            }

            if ($query->getLimit() !== null) {
                $subQueryBuilder->limit(null, $query->getLimit());
            }

            $builder = DeleteBuilder::create()
                ->from($query->getFrom(), $query->getFromAlias())
                ->where(
                    Cond::in(
                        Cond::column(Attribute::ID),
                        $subQueryBuilder->build()
                    )
                );

            $query = $builder->build();
        }

        return parent::composeDelete($query);
    }

    public function composeUpdate(UpdateQuery $query): string
    {
        if (
            $query->getJoins() !== [] ||
            $query->getLeftJoins() !== [] ||
            $query->getLimit() !== null ||
            $query->getOrder() !== []
        ) {
            $subQueryBuilder = SelectBuilder::create()
                ->select(Attribute::ID)
                ->from($query->getIn())
                ->order($query->getOrder())
                ->forUpdate();

            foreach ($query->getJoins() as $join) {
                $subQueryBuilder->join($join);
            }

            foreach ($query->getLeftJoins() as $join) {
                $subQueryBuilder->leftJoin($join);
            }

            if ($query->getWhere()) {
                $subQueryBuilder->where($query->getWhere());
            }

            if ($query->getLimit() !== null) {
                $subQueryBuilder->limit(null, $query->getLimit());
            }

            $builder = UpdateBuilder::create()
                ->in($query->getIn())
                ->set($query->getSet())
                ->where(
                    Cond::in(
                        Cond::column(Attribute::ID),
                        $subQueryBuilder->build()
                    )
                );

            $query = $builder->build();
        }

        return parent::composeUpdate($query);
    }

    public function composeInsert(InsertQuery $query): string
    {
        $params = $query->getRaw();
        $params = $this->normalizeInsertParams($params);

        $entityType = $params['into'];
        $columns = $params['columns'];
        $updateSet = $params['updateSet'];

        $columnsPart = $this->getInsertColumnsPart($columns);
        $valuesPart = $this->getInsertValuesPart($entityType, $params);
        $updatePart = $updateSet ? $this->getInsertUpdatePart($updateSet) : null;

        $table = $this->toDb($entityType);

        $sql = "INSERT INTO " . $this->quoteIdentifier($table) . " ($columnsPart) $valuesPart";

        if ($updatePart) {
            $updateColumnsPart = implode(', ',
                array_map(fn ($item) => $this->quoteIdentifier($this->toDb($this->sanitize($item))),
                    $this->getEntityUniqueColumns($entityType)
                )
            );

            $sql .= " ON CONFLICT($updateColumnsPart) DO UPDATE SET " . $updatePart;
        }

        return $sql;
    }

    /**
     * @return string[]
     */
    private function getEntityUniqueColumns(string $entityType): array
    {
        $indexes = $this->metadata
            ->getDefs()
            ->getEntity($entityType)
            ->getIndexList();

        foreach ($indexes as $index) {
            if ($index->isUnique()) {
                return $index->getColumnList();
            }
        }

        return [Attribute::ID];
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

                $alias = $this->sanitize($alias);
                $column = $this->toDb($this->sanitize($attribute));

                $left = $this->quoteColumn("{$alias}.{$column}");
            } else {
                $column = $this->toDb($this->sanitize($attribute));

                $left = $this->quoteColumn("{$column}"); // Diff.
            }

            $right = $isNotValue ?
                $this->convertComplexExpression($entity, $value, false, $params) :
                $this->quote($value);

            $list[] = $left . " = " . $right;
        }

        return implode(', ', $list);
    }

    public function composeRollbackToSavepoint(string $savepointName): string
    {
        return 'ROLLBACK TRANSACTION TO SAVEPOINT ' . $this->sanitize($savepointName);
    }

    public function composeLockTable(LockTableQuery $query): string
    {
        $params = $query->getRaw();

        $table = $this->toDb($this->sanitize($params['table']));

        $mode = $params['mode'];

        if (empty($table)) {
            throw new LogicException();
        }

        if (!in_array($mode, [LockTableQuery::MODE_SHARE, LockTableQuery::MODE_EXCLUSIVE])) {
            throw new LogicException();
        }

        $sql = "LOCK TABLE " . $this->quoteIdentifier($table) . " IN ";

        $modeMap = [
            LockTableQuery::MODE_SHARE => 'SHARE',
            LockTableQuery::MODE_EXCLUSIVE => 'EXCLUSIVE',
        ];

        $sql .= $modeMap[$mode] . " MODE";

        return $sql;
    }

    protected function limit(string $sql, ?int $offset = null, ?int $limit = null): string
    {
        if (!is_null($offset) && !is_null($limit)) {
            $offset = intval($offset);
            $limit = intval($limit);

            $sql .= " LIMIT $limit OFFSET $offset";

            return $sql;
        }

        if (!is_null($limit)) {
            $limit = intval($limit);

            $sql .= " LIMIT $limit";

            return $sql;
        }

        return $sql;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getSelectTailPart(array $params): ?string
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

    protected function composeDeleteQuery(
        string $table,
        ?string $alias,
        string $where,
        ?string $joins,
        ?string $order,
        ?int $limit
    ): string {

        $sql = "DELETE ";

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
}
