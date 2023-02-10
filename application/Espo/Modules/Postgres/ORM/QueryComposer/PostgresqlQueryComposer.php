<?php

namespace Espo\Modules\Postgres\ORM\QueryComposer;

use Espo\ORM\Entity;
use Espo\ORM\Query\LockTable as LockTableQuery;
use Espo\ORM\QueryComposer\BaseQueryComposer;
use Espo\ORM\QueryComposer\Functions;
use Espo\ORM\QueryComposer\Util;
use LogicException;
use RuntimeException;

class PostgresqlQueryComposer extends BaseQueryComposer
{
    protected string $identifierQuoteCharacter = '"';

    protected bool $indexHints = false;

    protected function quoteColumn(string $column): string
    {
        $list = explode('.', $column);

        foreach ($list as $i => $item) {
            $list[$i] = $this->quoteIdentifier($item);
        }

        return implode('.', $list);
    }

    protected function limit(string $sql, ?int $offset = null, ?int $limit = null): string
    {
        if (!is_null($limit)) {
            $sql .= " LIMIT {$limit}";
        }

        if (!is_null($offset)) {
            $sql .= " OFFSET {$offset}";
        }

        return $sql;
    }

    public function composeLockTable(LockTableQuery $query): string
    {
        $params = $query->getRaw();

        $table = $this->toDb($this->sanitize($params['table']));

        $mode = $params['mode'];

        if (empty($table)) {
            throw new LogicException();
        }

        if (!in_array($mode, [LockTableQuery::MODE_SHARE, LockTableQuery::MODE_EXCLUSIVE], true)) {
            throw new LogicException();
        }

        $table = $this->quoteIdentifier($table);

        return "LOCK TABLE $table IN $mode MODE";
    }

    public function composeRollbackToSavepoint(string $savepointName): string
    {
        return 'ROLLBACK TO ' . $this->sanitize($savepointName);
    }

    public function quote($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string)$value;
        }

        if (is_float($value)) {
            return (string)$value;
        }

        return $this->pdo->quote($value);
    }

    protected function convertMatchExpression(Entity $entity, string $expression, array $params): string
    {
        $delimiterPosition = strpos($expression, ':');

        if ($delimiterPosition === false) {
            throw new RuntimeException("ORM Query: Bad MATCH usage.");
        }

        $rest = substr($expression, $delimiterPosition + 1);

        if (empty($rest)) {
            throw new RuntimeException("ORM Query: Empty MATCH parameters.");
        }

        if (str_starts_with($rest, '(') && str_ends_with($rest, ')')) {
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
        }
        else {
            throw new RuntimeException("ORM Query: Bad MATCH usage.");
        }

        $fromAlias = $this->getFromAlias($params, $entity->getEntityType());

        foreach ($columnList as $i => $column) {
            $columnList[$i] = $this->quoteColumn($fromAlias . '.' . $this->sanitize($this->toDb($column)));
        }

        if (!Util::isArgumentString($query)) {
            throw new RuntimeException("ORM Query: Bad MATCH usage. The last argument should be a string.");
        }

        $query = mb_substr($query, 1, -1);
        $query = $this->quote($query);

        $columnPart = implode(' || ', $columnList);
        return "to_tsvector( 'english', $columnPart ) @@ to_tsquery( 'english', $query )";
    }

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
                [, $attribute] = explode('.', $attribute);

            }
            $column = $this->toDb($this->sanitize($attribute));
            $left = $column;

            if ($isNotValue) {
                $right = $this->convertComplexExpression($entity, $value, false, $params);
            } else {
                $right = $this->quote($value);
            }

            $list[] = $this->quoteColumn($left) . " = " . $right;
        }

        return implode(', ', $list);
    }

    protected function composeInsertQuery(
        string  $table,
        string  $columns,
        string  $values,
        ?string $update = null
    ): string
    {
        $tableQuoted = $this->quoteIdentifier($table);

        $sql = "INSERT INTO $tableQuoted ({$columns}) {$values}";

        $constraint = $this->quoteIdentifier("{$table}_pkey");

        if ($update) {
            $sql .= " ON CONFLICT ON CONSTRAINT $constraint DO UPDATE SET $update";
        }

        return $sql;
    }

    protected function composeUpdateQuery(string $table, string $set, string $where, ?string $joins, ?string $order, ?int $limit): string
    {
        return parent::composeUpdateQuery($table, $set, $where, $joins, null, null); //TODO - revise if this is a good idea, maybe use sub-query
    }

    protected function getFunctionPart(
        string $function,
        string $part,
        array  $params,
        string $entityType,
        bool   $distinct,
        array  $argumentPartList = []
    ): string
    {
        switch ($function) {
            case 'IFNULL':
                return "COALESCE($part)";

            case 'POSITION_IN_LIST':
                $sql = 'CASE ';

                $column = $argumentPartList[0];
                $i = 1;

                $parts = array_slice($argumentPartList, 1);

                foreach ($parts as $argumentPart) {
                    $sql .= "WHEN $column = $argumentPart THEN $i ";

                    $i++;
                }

                $sql .= 'ELSE 0 END';
                return $sql;

            case 'TIMESTAMPDIFF_YEAR':
                return "( EXTRACT( EPOCH FROM $argumentPartList[1]) - EXTRACT( EPOCH FROM $argumentPartList[0]) ) / 31536000";

            case 'TIMESTAMPDIFF_MONTH':
                return "( EXTRACT( EPOCH FROM $argumentPartList[1]) - EXTRACT( EPOCH FROM $argumentPartList[0]) ) / 2592000";  //probably not accurate

            case 'TIMESTAMPDIFF_WEEK':
                return "( EXTRACT( EPOCH FROM $argumentPartList[1]) - EXTRACT( EPOCH FROM $argumentPartList[0]) ) / 604800";

            case 'TIMESTAMPDIFF_DAY':
                return "( EXTRACT( EPOCH FROM $argumentPartList[1]) - EXTRACT( EPOCH FROM $argumentPartList[0]) ) / 86400";

            case 'TIMESTAMPDIFF_HOUR':
                return "( EXTRACT( EPOCH FROM $argumentPartList[1]) - EXTRACT( EPOCH FROM $argumentPartList[0]) ) / 3600";

            case 'TIMESTAMPDIFF_MINUTE':
                return "( EXTRACT( EPOCH FROM $argumentPartList[1]) - EXTRACT( EPOCH FROM $argumentPartList[0]) ) / 60";

            case 'TIMESTAMPDIFF_SECOND':
                return "( EXTRACT( EPOCH FROM $argumentPartList[1]) - EXTRACT( EPOCH FROM $argumentPartList[0]) )";

            default:
                return parent::getFunctionPart($function, $part, $params, $entityType, $distinct, $argumentPartList);
        }

    }
}

