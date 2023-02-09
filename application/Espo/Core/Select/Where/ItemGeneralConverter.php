<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Select\Where;

use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Exceptions\Error;
use Espo\Core\Select\Helpers\RandomStringGenerator;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\ArrayValue;
use Espo\Entities\User;
use Espo\ORM\Defs as ORMDefs;
use Espo\ORM\Entity;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem as WhereClauseItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

use DateTime;
use DateInterval;

/**
 * Converts a where item to a where clause (for ORM).
 */
class ItemGeneralConverter implements ItemConverter
{
    public function __construct(
        private string $entityType,
        private User $user,
        private DateTimeItemTransformer $dateTimeItemTransformer,
        private Scanner $scanner,
        private ItemConverterFactory $itemConverterFactory,
        private RandomStringGenerator $randomStringGenerator,
        private ORMDefs $ormDefs,
        private Config $config,
        private Metadata $metadata
    ) {}

    /**
     * @throws Error
     */
    public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
    {
        $type = $item->getType();
        $value = $item->getValue();
        $attribute = $item->getAttribute();
        $data = $item->getData();

        if ($data instanceof Item\Data\DateTime) {
            return $this->convert(
                $queryBuilder,
                $this->dateTimeItemTransformer->transform($item)
            );
        }

        if (!$type) {
            throw new Error("Bad where item. No 'type'.");
        }

        if (
            $attribute &&
            $this->itemConverterFactory->has($this->entityType, $attribute, $type)
        ) {

            $converter = $this->itemConverterFactory->create(
                $this->entityType,
                $attribute,
                $type,
                $this->user
            );

            return $converter->convert($queryBuilder, $item);
        }

        switch ($type) {
            case Type::OR:
            case Type::AND:
                return WhereClause::fromRaw($this->groupProcessAndOr($queryBuilder, $type, $attribute, $value));

            case Type::NOT:
            case Type::SUBQUERY_NOT_IN:
            case Type::SUBQUERY_IN:
                return WhereClause::fromRaw($this->groupProcessSubQuery($queryBuilder, $type, $attribute, $value));
        }

        if (!$attribute) {
            throw new Error("Bad where item. No 'attribute'.");
        }

        switch ($type) {
            // Revise.
            case 'columnLike':
            case 'columnIn':
            case 'columnNotIn':
            case 'columnIsNotNull':
            case 'columnEquals':
            case 'columnNotEquals':
                return WhereClause::fromRaw($this->groupProcessColumn($queryBuilder, $type, $attribute, $value));

            case Type::ARRAY_ANY_OF:
            case Type::ARRAY_NONE_OF:
            case Type::ARRAY_IS_EMPTY:
            case Type::ARRAY_ALL_OF:
            case Type::ARRAY_IS_NOT_EMPTY:
                return WhereClause::fromRaw($this->groupProcessArray($queryBuilder, $type, $attribute, $value));
        }

        if ($type === Type::IS_LINKED_WITH) {
            return WhereClause::fromRaw($this->processLinkedWith($queryBuilder, $attribute, $value));
        }

        if ($type === Type::IS_NOT_LINKED_WITH) {
            return WhereClause::fromRaw($this->processNotLinkedWith($queryBuilder, $attribute, $value));
        }

        if ($type === Type::IS_LINKED_WITH_ALL) {
            return WhereClause::fromRaw($this->processLinkedWithAll($queryBuilder, $attribute, $value));
        }

        if ($type === Type::IS_LINKED_WITH_ANY) {
            return WhereClause::fromRaw($this->processIsLinked($queryBuilder, $attribute));
        }

        if ($type === Type::IS_LINKED_WITH_NONE) {
            return WhereClause::fromRaw($this->processIsNotLinked($queryBuilder, $attribute));
        }

        if ($type === Type::EXPRESSION) {
            return WhereClause::fromRaw($this->processExpression($queryBuilder, $attribute, $value));
        }

        if ($type === Type::EQUALS) {
            return WhereClause::fromRaw($this->processEquals($queryBuilder, $attribute, $value));
        }

        if ($type === Type::NOT_EQUALS) {
            return WhereClause::fromRaw($this->processNotEquals($queryBuilder, $attribute, $value));
        }

        if ($type === Type::ON) {
            return WhereClause::fromRaw($this->processOn($queryBuilder, $attribute, $value));
        }

        if ($type === Type::NOT_ON) {
            return WhereClause::fromRaw($this->processNotOn($queryBuilder, $attribute, $value));
        }

        $methodName = 'process' .  ucfirst($type);

        if (method_exists($this, $methodName)) {
            return WhereClause::fromRaw(
                $this->$methodName($queryBuilder, $attribute, $value)
            );
        }

        if (!$this->itemConverterFactory->hasForType($type)) {
            throw new Error("Unknown where item type '{$type}'.");
        }

        $converter = $this->itemConverterFactory->createForType($type, $this->entityType, $this->user);

        return $converter->convert($queryBuilder, $item);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function groupProcessAndOr(
        QueryBuilder $queryBuilder,
        string $type,
        ?string $attribute,
        $value
    ): array {

        if (!is_array($value)) {
            throw new Error("Bad where item.");
        }

        $whereClause = [];

        foreach ($value as $item) {
            $subPart = $this->convert($queryBuilder, Item::fromRaw($item))->getRaw();

            foreach ($subPart as $left => $right) {
                if (!empty($right) || is_null($right) || $right === '' || $right === 0 || $right === false) {
                    $whereClause[] = [
                        $left => $right,
                    ];
                }
            }
        }

        return [
            strtoupper($type) => $whereClause,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function groupProcessSubQuery(
        QueryBuilder $queryBuilder,
        string $type,
        ?string $attribute,
        $value
    ): array {

        if (!is_array($value)) {
            throw new Error("Bad where item.");
        }

        $sqQueryBuilder = QueryBuilder::create()
            ->from($this->entityType);

        $whereItem = Item::fromRaw([
            'type' => Type::AND,
            'value' => $value,
        ]);

        $whereClause = $this->convert($sqQueryBuilder, $whereItem)->getRaw();

        $this->scanner->applyLeftJoins($sqQueryBuilder, $whereItem);

        $rawParams = $sqQueryBuilder->build()->getRaw();

        $key = $type === Type::SUBQUERY_IN ? 'id=s' : 'id!=s';

        return [
            $key => [
                'select' => ['id'],
                'from' => $this->entityType,
                'whereClause' => $whereClause,
                'leftJoins' => $rawParams['leftJoins'] ?? [],
                'joins' => $rawParams['joins'] ?? [],
            ],
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function groupProcessColumn(
        QueryBuilder $queryBuilder,
        string $type,
        string $attribute,
        $value
    ): array {

        $link = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $attribute, 'link']);
        $column = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $attribute, 'column']);

        if (!$column || !$link) {
            throw new Error("Bad where item 'column'.");
        }

        $alias =  $link . 'ColumnFilter' . $this->randomStringGenerator->generate();

        $queryBuilder->distinct();
        $queryBuilder->leftJoin($link, $alias);

        $columnKey = $alias . 'Middle.' . $column;

        if ($type === 'columnLike') {
            return [
                $columnKey . '*' => $value,
            ];
        }

        if ($type === 'columnIn') {
            return [
                $columnKey . '=' => $value,
            ];
        }

        if ($type === 'columnEquals') {
            return [
                $columnKey . '=' => $value,
            ];
        }

        if ($type === 'columnNotEquals') {
            return [
                $columnKey . '!=' => $value,
            ];
        }

        if ($type === 'columnNotIn') {
            return [
                $columnKey . '!=' => $value,
            ];
        }

        if ($type === 'columnIsNull') {
            return [
                $columnKey . '=' => null,
            ];
        }

        if ($type === 'columnIsNotNull') {
            return [
                $columnKey . '!=' => null,
            ];
        }

        throw new Error("Bad where item 'column'.");
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function groupProcessArray(
        QueryBuilder $queryBuilder,
        string $type,
        string $attribute,
        $value
    ): array {

        $arrayValueAlias = 'arrayFilter' . $this->randomStringGenerator->generate();

        $arrayAttribute = $attribute;
        $arrayEntityType = $this->entityType;
        $idPart = 'id';

        $isForeign = str_contains($attribute, '.');

        $isForeignType = false;

        $entityDefs = $this->ormDefs->getEntity($this->entityType);

        if (!$isForeign) {
            $isForeignType = $entityDefs->getAttribute($attribute)->getType() === Entity::FOREIGN;
            $isForeign = $isForeignType;
        }

        if ($isForeign) {
            if ($isForeignType) {
                $arrayAttributeLink = $entityDefs->getAttribute($attribute)->getParam('relation');
                $arrayAttribute = $entityDefs->getAttribute($attribute)->getParam('foreign');
            }
            else {
                [$arrayAttributeLink, $arrayAttribute] = explode('.', $attribute);
            }

            if (!$arrayAttributeLink || !$arrayAttribute) {
                throw new Error("Bad where item.");
            }

            $arrayEntityType = $entityDefs->getRelation($arrayAttributeLink)->getForeignEntityType();
            $arrayLinkAlias = $arrayAttributeLink . 'ArrayFilter' . $this->randomStringGenerator->generate();
            $idPart = $arrayLinkAlias . '.id';

            $queryBuilder->leftJoin($arrayAttributeLink, $arrayLinkAlias);

            $relationType = $entityDefs->getRelation($arrayAttributeLink)->getType();

            if (
                $relationType === Entity::MANY_MANY ||
                $relationType === Entity::HAS_MANY
            ) {
                $queryBuilder->distinct();
            }
        }

        if ($type === Type::ARRAY_ANY_OF) {
            if (!$value && !is_array($value)) {
                throw new Error("Bad where item. No value.");
            }

            $subQuery = QueryBuilder::create()
                ->select('entityId')
                ->from(ArrayValue::ENTITY_TYPE)
                ->where([
                    'entityType' => $arrayEntityType,
                    'attribute' => $arrayAttribute,
                    'value' => $value,
                ])
                ->build();

            return [$idPart . '=s' => $subQuery->getRaw()];
        }

        if ($type === Type::ARRAY_NONE_OF) {
            if (!$value && !is_array($value)) {
                throw new Error("Bad where item 'array'. No value.");
            }

            $subQuery = QueryBuilder::create()
                ->select('entityId')
                ->from(ArrayValue::ENTITY_TYPE)
                ->where([
                    'entityType' => $arrayEntityType,
                    'attribute' => $arrayAttribute,
                    'value' => $value,
                ])
                ->build();

            return [$idPart . '!=s' => $subQuery->getRaw()];
        }

        if ($type === Type::ARRAY_IS_EMPTY) {
            // Though distinct-left-join may perform faster than not-in-subquery
            // it's reasonable to avoid using distinct as it may negatively affect
            // performance when other filters are applied.
            $subQuery = QueryBuilder::create()
                ->select('entityId')
                ->from(ArrayValue::ENTITY_TYPE)
                ->where([
                    'entityType' => $arrayEntityType,
                    'attribute' => $arrayAttribute,
                ])
                ->build();

            return [$idPart . '!=s' => $subQuery->getRaw()];
        }

        if ($type === Type::ARRAY_IS_NOT_EMPTY) {
            $subQuery = QueryBuilder::create()
                ->select('entityId')
                ->from(ArrayValue::ENTITY_TYPE)
                ->where([
                    'entityType' => $arrayEntityType,
                    'attribute' => $arrayAttribute,
                ])
                ->build();

            return [$idPart . '=s' => $subQuery->getRaw()];
        }

        if ($type === Type::ARRAY_ALL_OF) {
            if (!$value && !is_array($value)) {
                throw new Error("Bad where item 'array'. No value.");
            }

            if (!is_array($value)) {
                $value = [$value];
            }

            $whereList = [];

            foreach ($value as $arrayValue) {
                $whereList[] = [
                    $idPart .'=s' => QueryBuilder::create()
                        ->from(ArrayValue::ENTITY_TYPE)
                        ->select('entityId')
                        ->where([
                            'value' => $arrayValue,
                            'attribute' => $arrayAttribute,
                            'entityType' => $arrayEntityType,
                            'deleted' => false,
                        ])
                        ->build()
                        ->getRaw()
                ];
            }

            return $whereList;
        }

        throw new Error("Bad where item 'array'.");
    }

    /**
     * A complex expression w/o a value.
     *
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processExpression(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $key = $attribute;

        if (!str_ends_with($key, ':')) {
            $key .= ':';
        }

        return [
            $key => null,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLike(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '*' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNotLike(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '!*' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processEquals(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processOn(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processEquals($queryBuilder, $attribute, $value);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNotEquals(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '!=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNotOn(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processNotEquals($queryBuilder, $attribute, $value);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processStartsWith(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '*' => $value . '%',
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processEndsWith(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '*' => '%' . $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processContains(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '*' => '%' . $value . '%',
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNotContains(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '!*' => '%' . $value . '%',
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processGreaterThan(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '>' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processAfter(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processGreaterThan($queryBuilder, $attribute, $value);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLessThan(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '<' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processBefore(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processLessThan($queryBuilder, $attribute, $value);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processGreaterThanOrEquals(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '>=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLessThanOrEquals(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '<=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function processIn(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        if (!is_array($value)) {
            throw new Error("Bad where item 'in'.");
        }

        return [
            $attribute . '=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function processNotIn(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        if (!is_array($value)) {
            throw new Error("Bad where item 'notIn'.");
        }

        return [
            $attribute . '!=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function processBetween(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        if (!is_array($value) || count($value) < 2) {
            throw new Error("Bad where item 'between'.");
        }

        return [
            'AND' => [
                $attribute . '>=' => $value[0],
                $attribute . '<=' => $value[1],
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processAny(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            'true:' => null,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNone(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            'false:' => null,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processIsNull(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => null,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processIsNotNull(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '!=' => null,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processEver(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processIsNotNull($queryBuilder, $attribute, $value);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processIsTrue(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => true,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processIsFalse(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => false,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processToday(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => date('Y-m-d'),
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processPast(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '<' => date('Y-m-d'),
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processFuture(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '>' => date('Y-m-d'),
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLastSevenDays(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt1 = new DateTime();

        $dt2 = clone $dt1;

        $dt2->modify('-7 days');

        return [
            'AND' => [
                $attribute . '>=' => $dt2->format('Y-m-d'),
                $attribute . '<=' => $dt1->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLastXDays(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt1 = new DateTime();

        $dt2 = clone $dt1;

        $number = strval(intval($value));

        $dt2->modify('-'.$number.' days');

        return [
            'AND' => [
                $attribute . '>=' => $dt2->format('Y-m-d'),
                $attribute . '<=' => $dt1->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNextXDays(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt1 = new DateTime();

        $dt2 = clone $dt1;

        $number = strval(intval($value));

        $dt2->modify('+' . $number . ' days');

        return [
            'AND' => [
                $attribute . '>=' => $dt1->format('Y-m-d'),
                $attribute . '<=' => $dt2->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processOlderThanXDays(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        $number = strval(intval($value));

        $dt->modify('-' . $number . ' days');

        return [
            $attribute . '<' => $dt->format('Y-m-d'),
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processAfterXDays(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        $number = strval(intval($value));

        $dt->modify('+' . $number . ' days');

        return [
            $attribute . '>' => $dt->format('Y-m-d'),
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processCurrentMonth(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of this month')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLastMonth(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of last month')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNextMonth(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of next month')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws \Exception
     */
    private function processCurrentQuarter(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        $quarter = ceil($dt->format('m') / 3);

        $dt->modify('first day of January this year');

        return [
            'AND' => [
                $attribute . '>=' => $dt->add(new DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P3M'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws \Exception
     */
    private function processLastQuarter(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        $quarter = ceil($dt->format('m') / 3);

        $dt->modify('first day of January this year');

        $quarter--;

        if ($quarter == 0) {
            $quarter = 4;
            $dt->modify('-1 year');
        }

        return [
            'AND' => [
                $attribute . '>=' => $dt->add(new DateInterval('P' . (($quarter - 1) * 3) . 'M'))->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P3M'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processCurrentYear(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of January this year')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1Y'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLastYear(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of January last year')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1Y'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processCurrentFiscalYear(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dtToday = new DateTime();
        $dt = new DateTime();

        $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

        $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');

        if (intval($dtToday->format('m')) < $fiscalYearShift + 1) {
            $dt->modify('-1 year');
        }

        return [
            'AND' => [
                $attribute . '>=' => $dt->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1Y'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLastFiscalYear(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dtToday = new DateTime();
        $dt = new DateTime();

        $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

        $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');

        if (intval($dtToday->format('m')) < $fiscalYearShift + 1) {
            $dt->modify('-1 year');
        }

        $dt->modify('-1 year');

        return [
            'AND' => [
                $attribute . '>=' => $dt->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1Y'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws \Exception
     */
    private function processCurrentFiscalQuarter(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dtToday = new DateTime();
        $dt = new DateTime();

        $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

        $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');

        $month = intval($dtToday->format('m'));

        $quarterShift = floor(($month - $fiscalYearShift - 1) / 3);

        if ($quarterShift) {
            if ($quarterShift >= 0) {
                $dt->add(new DateInterval('P' . ($quarterShift * 3) . 'M'));
            } else {
                $quarterShift *= -1;
                $dt->sub(new DateInterval('P' . ($quarterShift * 3) . 'M'));
            }
        }

        return [
            'AND' => [
                $attribute . '>=' => $dt->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P3M'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws \Exception
     */
    private function processLastFiscalQuarter(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dtToday = new DateTime();
        $dt = new DateTime();

        $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

        $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');

        $month = intval($dtToday->format('m'));

        $quarterShift = floor(($month - $fiscalYearShift - 1) / 3);

        if ($quarterShift) {
            if ($quarterShift >= 0) {
                $dt->add(new DateInterval('P' . ($quarterShift * 3) . 'M'));
            } else {
                $quarterShift *= -1;
                $dt->sub(new DateInterval('P' . ($quarterShift * 3) . 'M'));
            }
        }

        $dt->modify('-3 months');

        return [
            'AND' => [
                $attribute . '>=' => $dt->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P3M'))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processIsNotLinked(QueryBuilder $queryBuilder, string $attribute): array
    {
        $link = $attribute;
        $alias = $link . 'IsLinkedFilter' . $this->randomStringGenerator->generate();

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $key = $defs->getForeignMidKey();
        $nearKey = $defs->getMidKey();
        $middleEntityType = ucfirst($defs->getRelationshipName());

        $relationType = $defs->getType();

        if ($relationType == Entity::MANY_MANY) {
            // The foreign table is not joined as it would perform much slower.
            // Trade off is that if a foreign record is deleted but the middle table
            // is not yet deleted, it will give a non-actual result.
            $subQuery = QueryBuilder::create()
                ->select('id')
                ->from($this->entityType)
                ->leftJoin($middleEntityType, $alias, [
                    "{$alias}.{$nearKey}:" => 'id',
                    "{$alias}.deleted" => false,
                ])
                ->where(["{$alias}.{$key}" => null])
                ->build();

            return ['id=s' =>  $subQuery->getRaw()];
        }

        if (
            $relationType == Entity::HAS_MANY ||
            $relationType == Entity::HAS_ONE ||
            $relationType == Entity::BELONGS_TO
        ) {
            $subQuery = QueryBuilder::create()
                ->select('id')
                ->from($this->entityType)
                ->leftJoin($link, $alias)
                ->where([$alias . '.id' => null])
                ->build();

            return ['id=s' =>  $subQuery->getRaw()];
        }

        throw new Error("Bad where item. Not supported relation type.");
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processIsLinked(QueryBuilder $queryBuilder, string $attribute): array
    {
        $link = $attribute;
        $alias = $link . 'IsLinkedFilter' . $this->randomStringGenerator->generate();

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $key = $defs->getForeignMidKey();
        $nearKey = $defs->getMidKey();
        $middleEntityType = ucfirst($defs->getRelationshipName());

        $relationType = $defs->getType();

        if ($relationType == Entity::MANY_MANY) {
            // The foreign table is not joined as it would perform much slower.
            // Trade off is that if a foreign record is deleted but the middle table
            // is not yet deleted, it will give a non-actual result.
            $subQuery = QueryBuilder::create()
                ->select('id')
                ->from($this->entityType)
                ->leftJoin($middleEntityType, $alias, [
                    "{$alias}.{$nearKey}:" => 'id',
                    "{$alias}.deleted" => false,
                ])
                ->where(["{$alias}.{$key}!=" => null])
                ->build();

            return ['id=s' =>  $subQuery->getRaw()];
        }

        if (
            $relationType == Entity::HAS_MANY ||
            $relationType == Entity::HAS_ONE ||
            $relationType == Entity::BELONGS_TO
        ) {
            $subQuery = QueryBuilder::create()
                ->select('id')
                ->from($this->entityType)
                ->leftJoin($link, $alias)
                ->where([$alias . '.id!=' => null])
                ->build();

            return ['id=s' =>  $subQuery->getRaw()];
        }

        throw new Error("Bad where item. Not supported relation type.");
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function processLinkedWith(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $link = $attribute;

        if (!$this->ormDefs->getEntity($this->entityType)->hasRelation($link)) {
            throw new Error("Not existing link '{$link}' in where item.");
        }

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $alias =  $link . 'LinkedWithFilter' . $this->randomStringGenerator->generate();

        if (!$value && !is_array($value)) {
            throw new Error("Bad where item. Empty value.");
        }

        // @todo Add check for foreign record existence.

        $relationType = $defs->getType();

        if ($relationType == Entity::MANY_MANY) {
            $key = $defs->getForeignMidKey();
            $nearKey = $defs->getMidKey();
            $middleEntityType = ucfirst($defs->getRelationshipName());

            // Left-join performs faster than Inner-join.
            // Not joining a foreign table as it affects performance in MySQL.
            $subQuery = QueryBuilder::create()
                ->select('id')
                ->from($this->entityType)
                ->leftJoin($middleEntityType, $alias, [
                    "{$alias}.{$nearKey}:" => 'id',
                    "{$alias}.deleted" => false,
                ])
                ->where(["{$alias}.{$key}" => $value])
                ->build();

            return ['id=s' =>  $subQuery->getRaw()];
        }

        if (
            $relationType == Entity::HAS_MANY ||
            $relationType == Entity::HAS_ONE
        ) {
            $subQuery = QueryBuilder::create()
                ->select('id')
                ->from($this->entityType)
                ->leftJoin($link, $alias)
                ->where([$alias . '.id' => $value])
                ->build();

            return ['id=s' =>  $subQuery->getRaw()];
        }

        if ($relationType == Entity::BELONGS_TO) {
            $key = $defs->getKey();

            return [$key => $value];
        }

        throw new Error("Bad where item. Not supported relation type.");
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function processNotLinkedWith(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $link = $attribute;

        if (!$this->ormDefs->getEntity($this->entityType)->hasRelation($link)) {
            throw new Error("Not existing link '{$link}' in where item.");
        }

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $alias =  $link . 'NotLinkedWithFilter' . $this->randomStringGenerator->generate();

        if (is_null($value)) {
            throw new Error("Bad where item. Empty value.");
        }

        $relationType = $defs->getType();

        if ($relationType == Entity::MANY_MANY) {
            $key = $defs->getForeignMidKey();
            $nearKey = $defs->getMidKey();
            $middleEntityType = ucfirst($defs->getRelationshipName());

            $subQuery = QueryBuilder::create()
                ->select('id')
                ->from($this->entityType)
                ->leftJoin($middleEntityType, $alias, [
                    "{$alias}.{$nearKey}:" => 'id',
                    "{$alias}.deleted" => false,
                ])
                ->where(["{$alias}.{$key}=" => $value])
                ->build();

            return ['id!=s' =>  $subQuery->getRaw()];
        }

        if (
            $relationType == Entity::HAS_MANY ||
            $relationType == Entity::HAS_ONE
        ) {
            $subQuery = QueryBuilder::create()
                ->select('id')
                ->from($this->entityType)
                ->leftJoin($link, $alias)
                ->where(["{$alias}.id" => $value])
                ->build();

            return ['id!=s' =>  $subQuery->getRaw()];
        }

        if ($relationType == Entity::BELONGS_TO) {
            $key = $defs->getKey();

            return [$key . '!=' => $value];
        }

        throw new Error("Bad where item. Not supported relation type.");
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws Error
     */
    private function processLinkedWithAll(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $link = $attribute;

        if (!$this->ormDefs->getEntity($this->entityType)->hasRelation($link)) {
            throw new Error("Not existing link '{$link}' in where item.");
        }

        if (!$value && !is_array($value)) {
            throw new Error("Bad where item. Empty value.");
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $relationType = $defs->getType();

        if ($relationType === Entity::MANY_MANY) {
            $key = $defs->getForeignMidKey();

            $whereList = [];

            foreach ($value as $targetId) {
                $sq = QueryBuilder::create()
                    ->from($this->entityType)
                    ->select('id')
                    ->leftJoin($link)
                    ->where([
                        $link . 'Middle.' . $key => $targetId,
                    ])
                    ->build();

                $whereList[] = ['id=s' => $sq->getRaw()];
            }

            return $whereList;
        }

        if ($relationType === Entity::HAS_MANY) {
            $whereList = [];

            foreach ($value as $targetId) {
                $sq = QueryBuilder::create()
                    ->from($this->entityType)
                    ->select('id')
                    ->leftJoin($link)
                    ->where([$link . '.id' => $targetId])
                    ->build();

                $whereList[] = ['id=s' => $sq->getRaw()];
            }

            return $whereList;
        }

        throw new Error("Bad where item. Not supported relation type.");
    }
}
