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

namespace Espo\Core\Select\Where;

use DateTimeZone;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Field\DateTime;
use Espo\Core\Select\Where\Item\Data;
use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Select\Helpers\RandomStringGenerator;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Metadata;
use Espo\Entities\ArrayValue;
use Espo\Entities\User;
use Espo\ORM\Defs as ORMDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Entity;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Join;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem as WhereClauseItem;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

use Exception;
use RuntimeException;

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
        private Metadata $metadata,
        private Config\ApplicationConfig $applicationConfig,
    ) {}

    /**
     * @throws BadRequest
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
            throw new BadRequest("Bad where item. No 'type'.");
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
                return WhereClause::fromRaw($this->groupProcessAndOr($queryBuilder, $type, $value));

            case Type::NOT:
            case Type::SUBQUERY_NOT_IN:
            case Type::SUBQUERY_IN:
                return WhereClause::fromRaw($this->groupProcessSubQuery($type, $value));
        }

        if (!$attribute) {
            throw new BadRequest("Bad where item. No 'attribute'.");
        }

        switch ($type) {
            // @todo Revise. Maybe to use regular like, in, etc. types?
            case 'columnLike':
            case 'columnIn':
            case 'columnNotIn':
            case 'columnIsNull':
            case 'columnIsNotNull':
            case 'columnEquals':
            case 'columnNotEquals':
                return WhereClause::fromRaw($this->groupProcessColumn($type, $attribute, $value));

            case Type::ARRAY_ANY_OF:
            case Type::ARRAY_NONE_OF:
            case Type::ARRAY_IS_EMPTY:
            case Type::ARRAY_ALL_OF:
            case Type::ARRAY_IS_NOT_EMPTY:
                return WhereClause::fromRaw($this->groupProcessArray($queryBuilder, $type, $attribute, $value));
        }

        if ($type === Type::IN) {
            return WhereClause::fromRaw($this->processIn($attribute, $value));
        }

        if ($type === Type::NOT_IN) {
            return WhereClause::fromRaw($this->processNotIn($attribute, $value));
        }

        if ($type === Type::IS_LINKED_WITH) {
            return WhereClause::fromRaw($this->processLinkedWith($attribute, $value));
        }

        if ($type === Type::IS_NOT_LINKED_WITH) {
            return WhereClause::fromRaw($this->processNotLinkedWith($attribute, $value));
        }

        if ($type === Type::IS_LINKED_WITH_ALL) {
            return WhereClause::fromRaw($this->processLinkedWithAll($attribute, $value));
        }

        if ($type === Type::IS_LINKED_WITH_ANY) {
            return WhereClause::fromRaw($this->processIsLinked($attribute));
        }

        if ($type === Type::IS_LINKED_WITH_NONE) {
            return WhereClause::fromRaw($this->processIsNotLinked($attribute));
        }

        if ($type === Type::EXPRESSION) {
            return WhereClause::fromRaw($this->processExpression($attribute));
        }

        if ($type === Type::EQUALS) {
            return WhereClause::fromRaw($this->processEquals($attribute, $value));
        }

        if ($type === Type::NOT_EQUALS) {
            return WhereClause::fromRaw($this->processNotEquals($attribute, $value));
        }

        if ($type === Type::ON) {
            return WhereClause::fromRaw($this->processOn($attribute, $value));
        }

        if ($type === Type::NOT_ON) {
            return WhereClause::fromRaw($this->processNotOn($attribute, $value));
        }

        if ($type === Type::EVER) {
            return WhereClause::fromRaw($this->processEver($attribute));
        }

        if ($type === Type::TODAY) {
            return WhereClause::fromRaw($this->processToday($attribute, $item->getData()));
        }

        if ($type === Type::PAST) {
            return WhereClause::fromRaw($this->processPast($attribute, $item->getData()));
        }

        if ($type === Type::FUTURE) {
            return WhereClause::fromRaw($this->processFuture($attribute, $item->getData()));
        }

        if ($type === Type::LAST_SEVEN_DAYS) {
            return WhereClause::fromRaw($this->processLastSevenDays($attribute, $item->getData()));
        }

        if ($type === Type::LAST_X_DAYS) {
            return WhereClause::fromRaw($this->processLastXDays($attribute, $value, $item->getData()));
        }

        if ($type === Type::NEXT_X_DAYS) {
            return WhereClause::fromRaw($this->processNextXDays($attribute, $value, $item->getData()));
        }

        if ($type === Type::OLDER_THAN_X_DAYS) {
            return WhereClause::fromRaw($this->processOlderThanXDays($attribute, $value, $item->getData()));
        }

        if ($type === Type::AFTER_X_DAYS) {
            return WhereClause::fromRaw($this->processAfterXDays($attribute, $value, $item->getData()));
        }

        if ($type === Type::CURRENT_MONTH) {
            return WhereClause::fromRaw($this->processCurrentMonth($attribute));
        }

        if ($type === Type::LAST_MONTH) {
            return WhereClause::fromRaw($this->processLastMonth($attribute));
        }

        if ($type === Type::NEXT_MONTH) {
            return WhereClause::fromRaw($this->processNextMonth($attribute));
        }

        if ($type === Type::CURRENT_QUARTER) {
            return WhereClause::fromRaw($this->processCurrentQuarter($attribute));
        }

        if ($type === Type::LAST_QUARTER) {
            return WhereClause::fromRaw($this->processLastQuarter($attribute));
        }

        if ($type === Type::CURRENT_YEAR) {
            return WhereClause::fromRaw($this->processCurrentYear($attribute));
        }

        if ($type === Type::LAST_YEAR) {
            return WhereClause::fromRaw($this->processLastYear($attribute));
        }

        if ($type === Type::CURRENT_FISCAL_YEAR) {
            return WhereClause::fromRaw($this->processCurrentFiscalYear($attribute));
        }

        if ($type === Type::LAST_FISCAL_YEAR) {
            return WhereClause::fromRaw($this->processLastFiscalYear($attribute));
        }

        if ($type === Type::CURRENT_FISCAL_QUARTER) {
            return WhereClause::fromRaw($this->processCurrentFiscalQuarter($attribute));
        }

        if ($type === Type::LAST_FISCAL_QUARTER) {
            return WhereClause::fromRaw($this->processLastFiscalQuarter($attribute));
        }

        if ($type === Type::BEFORE) {
            return WhereClause::fromRaw($this->processBefore($attribute, $value));
        }

        if ($type === Type::AFTER) {
            return WhereClause::fromRaw($this->processAfter($attribute, $value));
        }

        if ($type === Type::BETWEEN) {
            return WhereClause::fromRaw($this->processBetween($attribute, $value));
        }

        if ($type === Type::LIKE) {
            return WhereClause::fromRaw($this->processLike($attribute, $value));
        }

        if ($type === Type::NOT_LIKE) {
            return WhereClause::fromRaw($this->processNotLike($attribute, $value));
        }

        if ($type === Type::IS_NULL) {
            return WhereClause::fromRaw($this->processIsNull($attribute));
        }

        if ($type === Type::NONE) {
            return WhereClause::fromRaw($this->processNone());
        }

        if ($type === Type::ANY) {
            return WhereClause::fromRaw($this->processAny());
        }

        if ($type === Type::IS_NOT_NULL) {
            return WhereClause::fromRaw($this->processIsNotNull($attribute));
        }

        if ($type === Type::IS_TRUE) {
            return WhereClause::fromRaw($this->processIsTrue($attribute));
        }

        if ($type === Type::IS_FALSE) {
            return WhereClause::fromRaw($this->processIsFalse($attribute));
        }

        if ($type === Type::STARTS_WITH) {
            return WhereClause::fromRaw($this->processStartsWith($attribute, $value));
        }

        if ($type === Type::ENDS_WITH) {
            return WhereClause::fromRaw($this->processEndsWith($attribute, $value));
        }

        if ($type === Type::CONTAINS) {
            return WhereClause::fromRaw($this->processContains($attribute, $value));
        }

        if ($type === Type::NOT_CONTAINS) {
            return WhereClause::fromRaw($this->processNotContains($attribute, $value));
        }

        if ($type === Type::GREATER_THAN) {
            return WhereClause::fromRaw($this->processGreaterThan($attribute, $value));
        }

        if ($type === Type::LESS_THAN) {
            return WhereClause::fromRaw($this->processLessThan($attribute, $value));
        }

        if ($type === Type::GREATER_THAN_OR_EQUALS) {
            return WhereClause::fromRaw($this->processGreaterThanOrEquals($attribute, $value));
        }

        if ($type === Type::LESS_THAN_OR_EQUALS) {
            return WhereClause::fromRaw($this->processLessThanOrEquals($attribute, $value));
        }

        if (!$this->itemConverterFactory->hasForType($type)) {
            throw new BadRequest("Unknown where item type '$type'.");
        }

        $converter = $this->itemConverterFactory->createForType($type, $this->entityType, $this->user);

        return $converter->convert($queryBuilder, $item);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function groupProcessAndOr(QueryBuilder $queryBuilder, string $type, $value): array
    {
        if (!is_array($value)) {
            throw new BadRequest("Bad where item.");
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
     * @throws BadRequest
     */
    private function groupProcessSubQuery(string $type, $value): array
    {
        if (!is_array($value)) {
            throw new BadRequest("Bad where item.");
        }

        $sqQueryBuilder = QueryBuilder::create()
            ->from($this->entityType);

        $whereItem = Item::fromRaw([
            'type' => Type::AND,
            'value' => $value,
        ]);

        $whereClause = $this->convert($sqQueryBuilder, $whereItem)->getRaw();

        $this->scanner->apply($sqQueryBuilder, $whereItem);

        $rawParams = $sqQueryBuilder->build()->getRaw();

        $key = $type === Type::SUBQUERY_IN ? 'id=s' : 'id!=s';

        return [
            $key => Select::fromRaw([
                'select' => [Attribute::ID],
                'from' => $this->entityType,
                'whereClause' => $whereClause,
                'leftJoins' => $rawParams['leftJoins'] ?? [],
                'joins' => $rawParams['joins'] ?? [],
            ]),
        ];
    }

    /**
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function groupProcessColumn(
        string $type,
        string $attribute,
        mixed $value
    ): array {

        $link = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $attribute, 'link']);
        $column = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $attribute, 'column']);

        if (!$column || !$link) {
            throw new BadRequest("Bad where item 'column'.");
        }

        $subQueryBuilder = QueryBuilder::create()
            ->from($this->entityType)
            ->select(Attribute::ID)
            ->leftJoin($link);

        $alias = $link;
        $expr = "{$alias}Middle.$column";

        if ($type === 'columnLike') {
            $where = [$expr . '*' => $value];
        } else if ($type === 'columnIn') {
            $where = [$expr . '=' => $value];
        } else if ($type === 'columnEquals') {
            $where = [$expr . '=' => $value];
        } else if ($type === 'columnNotEquals') {
            $where = [$expr . '!=' => $value];
        } else if ($type === 'columnNotIn') {
            $where = [$expr . '!=' => $value];
        } else if ($type === 'columnIsNull') {
            $where = [$expr . '=' => null];
        } else if ($type === 'columnIsNotNull') {
            $where = [$expr . '!=' => null];
        } else {
            throw new BadRequest("Bad where item 'column'.");
        }

        return Cond::in(
            Cond::column(Attribute::ID),
            $subQueryBuilder
                ->where($where)
                ->build()
        )->getRaw();
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function groupProcessArray(
        QueryBuilder $queryBuilder,
        string $type,
        string $attribute,
        $value
    ): array {

        $arrayAttribute = $attribute;
        $arrayEntityType = $this->entityType;
        $idPart = Attribute::ID;

        $isForeign = str_contains($attribute, '.');

        $isForeignType = false;

        $entityDefs = $this->ormDefs->getEntity($this->entityType);

        if (!$isForeign) {
            $isForeignType = $entityDefs->getAttribute($attribute)->getType() === Entity::FOREIGN;
            $isForeign = $isForeignType;
        }

        if ($isForeign) {
            if ($isForeignType) {
                $arrayLink = $entityDefs->getAttribute($attribute)->getParam(AttributeParam::RELATION);
                $arrayAttribute = $entityDefs->getAttribute($attribute)->getParam(AttributeParam::FOREIGN);
            } else {
                [$arrayLink, $arrayAttribute] = explode('.', $attribute);
            }

            if (!$arrayLink || !$arrayAttribute) {
                throw new BadRequest("Bad where item.");
            }

            $arrayEntityType = $entityDefs->getRelation($arrayLink)->getForeignEntityType();
            $arrayAlias = "{$arrayLink}ArrayFilter{$this->randomStringGenerator->generate()}";
            $idPart = "$arrayAlias.id";

            $queryBuilder->leftJoin($arrayLink, $arrayAlias);

            // A sub-query is supposed to be used.
            /*
            $relationType = $entityDefs->getRelation($arrayLink)->getType();

            if (
                in_array($relationType, [
                    Entity::MANY_MANY,
                    Entity::HAS_MANY,
                    Entity::HAS_CHILDREN,
                ])
            ) {
                $queryBuilder->distinct();
            }
            */
        }

        if ($type === Type::ARRAY_ANY_OF) {
            if (!$value && !is_array($value)) {
                throw new BadRequest("Bad where item. No value.");
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
                throw new BadRequest("Bad where item 'array'. No value.");
            }

            return Cond::not(
                Cond::exists(
                    QueryBuilder::create()
                        ->select('entityId')
                        ->from(ArrayValue::ENTITY_TYPE)
                        ->where([
                            'entityType' => $arrayEntityType,
                            'attribute' => $arrayAttribute,
                            'value' => $value,
                            'entityId:' => lcfirst($arrayEntityType) . '.id'
                        ])
                        ->build()
                )
            )->getRaw();
        }

        if ($type === Type::ARRAY_IS_EMPTY) {
            return Cond::not(
                Cond::exists(
                    QueryBuilder::create()
                        ->select('entityId')
                        ->from(ArrayValue::ENTITY_TYPE)
                        ->where([
                            'entityType' => $arrayEntityType,
                            'attribute' => $arrayAttribute,
                            'entityId:' => lcfirst($arrayEntityType) . '.id'
                        ])
                        ->build()
                )
            )->getRaw();
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

            return [$idPart . '=s' => $subQuery];
        }

        if ($type === Type::ARRAY_ALL_OF) {
            if (!$value && !is_array($value)) {
                throw new BadRequest("Bad where item 'array'. No value.");
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
                            Attribute::DELETED => false,
                        ])
                        ->build()
                ];
            }

            return $whereList;
        }

        throw new BadRequest("Bad where item 'array'.");
    }

    /**
     * A complex expression w/o a value.
     *
     * @return array<string|int, mixed>
     */
    private function processExpression(string $attribute): array
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
    private function processLike(string $attribute, $value): array
    {
        return [
            $attribute . '*' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNotLike(string $attribute, $value): array
    {
        return [
            $attribute . '!*' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processEquals(string $attribute, $value): array
    {
        return [
            $attribute . '=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processOn(string $attribute, $value): array
    {
        return $this->processEquals($attribute, $value);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNotEquals(string $attribute, $value): array
    {
        return [
            $attribute . '!=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNotOn(string $attribute, $value): array
    {
        return $this->processNotEquals($attribute, $value);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processStartsWith(string $attribute, $value): array
    {
        return [
            $attribute . '*' => $value . '%',
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processEndsWith(string $attribute, $value): array
    {
        return [
            $attribute . '*' => '%' . $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processContains(string $attribute, $value): array
    {
        return [
            $attribute . '*' => '%' . $value . '%',
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processNotContains(string $attribute, $value): array
    {
        return [
            $attribute . '!*' => '%' . $value . '%',
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processGreaterThan(string $attribute, $value): array
    {
        return [
            $attribute . '>' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processAfter(string $attribute, $value): array
    {
        return $this->processGreaterThan($attribute, $value);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLessThan(string $attribute, $value): array
    {
        return [
            $attribute . '<' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processBefore(string $attribute, $value): array
    {
        return $this->processLessThan($attribute, $value);
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processGreaterThanOrEquals(string $attribute, $value): array
    {
        return [
            $attribute . '>=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     */
    private function processLessThanOrEquals(string $attribute, $value): array
    {
        return [
            $attribute . '<=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processIn(string $attribute, $value): array
    {
        if (!is_array($value)) {
            throw new BadRequest("Bad where item 'in'.");
        }

        return [
            $attribute . '=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processNotIn(string $attribute, $value): array
    {
        if (!is_array($value)) {
            throw new BadRequest("Bad where item 'notIn'.");
        }

        return [
            $attribute . '!=' => $value,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processBetween(string $attribute, $value): array
    {
        if (!is_array($value) || count($value) < 2) {
            throw new BadRequest("Bad where item 'between'.");
        }

        return [
            'AND' => [
                $attribute . '>=' => $value[0],
                $attribute . '<=' => $value[1],
            ]
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function processAny(): array
    {
        return [
            'true:' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function processNone(): array
    {
        return [
            'false:' => null,
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processIsNull(string $attribute): array
    {
        return [
            $attribute . '=' => null,
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processIsNotNull(string $attribute): array
    {
        return [
            $attribute . '!=' => null,
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processEver(string $attribute): array
    {
        return $this->processIsNotNull($attribute);
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processIsTrue(string $attribute): array
    {
        return [
            $attribute . '=' => true,
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processIsFalse(string $attribute): array
    {
        return [
            $attribute . '=' => false,
        ];
    }

    /**
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processToday(string $attribute, ?Data $data): array
    {
        $timeZone = $this->getTimeZone($data);
        $today = DateTime::createNow()->withTimezone($timeZone);

        return [
            $attribute . '=' => $today->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
        ];
    }

    /**
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processPast(string $attribute, ?Data $data): array
    {
        $timeZone = $this->getTimeZone($data);
        $today = DateTime::createNow()->withTimezone($timeZone);

        return [
            $attribute . '<' => $today->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
        ];
    }

    /**
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processFuture(string $attribute, ?Data $data): array
    {
        $timeZone = $this->getTimeZone($data);
        $today = DateTime::createNow()->withTimezone($timeZone);

        return [
            $attribute . '>' => $today->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
        ];
    }

    /**
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processLastSevenDays(string $attribute, ?Data $data): array
    {
        $timeZone = $this->getTimeZone($data);
        $today = DateTime::createNow()->withTimezone($timeZone);

        $from = $today->addDays(-7);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<=' => $today->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processLastXDays(string $attribute, mixed $value, ?Data $data): array
    {
        $timeZone = $this->getTimeZone($data);
        $today = DateTime::createNow()->withTimezone($timeZone);

        $number = intval($value);

        $from = $today->addDays(- $number);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<=' => $today->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processNextXDays(string $attribute, mixed $value, ?Data $data): array
    {
        $timeZone = $this->getTimeZone($data);
        $today = DateTime::createNow()->withTimezone($timeZone);

        $number = intval($value);

        $to = $today->addDays($number);

        return [
            'AND' => [
                $attribute . '>=' => $today->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<=' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processOlderThanXDays(string $attribute, mixed $value, ?Data $data): array
    {
        $timeZone = $this->getTimeZone($data);
        $today = DateTime::createNow()->withTimezone($timeZone);

        $number = intval($value);

        $date = $today->addDays(- $number);

        return [
            $attribute . '<' =>  $date->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
        ];
    }

    /**
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processAfterXDays(string $attribute, mixed $value, ?Data $data): array
    {
        $timeZone = $this->getTimeZone($data);
        $today = DateTime::createNow()->withTimezone($timeZone);

        $number = intval($value);

        $date = $today->addDays($number);

        return [
            $attribute . '>' => $date->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processCurrentMonth(string $attribute): array
    {
        $from = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone())
            ->modify('first day of this month');

        $to = $from->addMonths(1);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processLastMonth( string $attribute): array
    {
        $from = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone())
            ->modify('first day of last month');

        $to = $from->addMonths(1);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processNextMonth(string $attribute): array
    {
        $from = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone())
            ->modify('first day of next month');

        $to = $from->addMonths(1);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processCurrentQuarter(string $attribute): array
    {
        $now = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone());

        $quarter = intval(ceil($now->getMonth() / 3));

        $from = $now
            ->modify('first day of January this year')
            ->addMonths(($quarter - 1) * 3);

        $to = $from->addMonths(3);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processLastQuarter(string $attribute): array
    {
        $now = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone());

        $quarter = intval(ceil($now->getMonth() / 3));

        $from = $now->modify('first day of January this year');

        $quarter--;

        if ($quarter == 0) {
            $quarter = 4;

            $from = $from->addYears(-1);
        }

        $from = $from->addMonths(($quarter - 1) * 3);
        $to = $from->addMonths(3);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processCurrentYear(string $attribute): array
    {
        $from = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone())
            ->modify('first day of January this year');

        $to = $from->addYears(1);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processLastYear(string $attribute): array
    {
        $from = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone())
            ->modify('first day of January last year');

        $to = $from->addYears(1);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processCurrentFiscalYear(string $attribute): array
    {
        $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

        $now = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone());

        $from = $now
            ->modify('first day of January this year')
            ->addMonths($fiscalYearShift);

        if ($now->getMonth() < $fiscalYearShift + 1) {
            $from = $from->addYears(-1);
        }

        $to = $from->addYears(1);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processLastFiscalYear(string $attribute): array
    {
        $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

        $now = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone());

        $from = $now
            ->modify('first day of January this year')
            ->addMonths($fiscalYearShift)
            ->addYears(-1);

        if ($now->getMonth() < $fiscalYearShift + 1) {
            $from = $from->addYears(-1);
        }

        $to = $from->addYears(1);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processCurrentFiscalQuarter(string $attribute): array
    {
        $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

        $now = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone());

        $quarterShift = (int) floor(($now->getMonth() - $fiscalYearShift - 1) / 3);

        $from = $now
            ->modify('first day of January this year')
            ->addMonths($fiscalYearShift)
            ->addMonths($quarterShift * 3);

        $to = $from->addMonths(3);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processLastFiscalQuarter(string $attribute): array
    {
        $fiscalYearShift = $this->config->get('fiscalYearShift', 0);

        $now = DateTime::createNow()
            ->withTimezone($this->getSystemTimeZone());

        $quarterShift = (int) floor(($now->getMonth() - $fiscalYearShift - 1) / 3);

        $from = $now
            ->modify('first day of January this year')
            ->addMonths($fiscalYearShift)
            ->addMonths($quarterShift * 3);

        $from = $from->addMonths(-3);
        $to = $from->addMonths(3);

        return [
            'AND' => [
                $attribute . '>=' => $from->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
                $attribute . '<' => $to->toDateTime()->format(DateTimeUtil::SYSTEM_DATE_FORMAT),
            ]
        ];
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processIsNotLinked(string $attribute): array
    {
        $link = $attribute;
        $alias = $link . 'IsLinkedFilter' . $this->randomStringGenerator->generate();

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $relationType = $defs->getType();

        if ($relationType == Entity::MANY_MANY) {
            $key = $defs->getForeignMidKey();
            $nearKey = $defs->getMidKey();
            $middleEntityType = ucfirst($defs->getRelationshipName());

            // The foreign table is not joined as it would perform much slower.
            // Trade off is that if a foreign record is deleted but the middle table
            // is not yet deleted, it will give a non-actual result.
            $subQuery = QueryBuilder::create()
                ->select(Attribute::ID)
                ->from($this->entityType)
                ->leftJoin($middleEntityType, $alias, [
                    "$alias.$nearKey:" => Attribute::ID,
                    "$alias.deleted" => false,
                ])
                ->where(["$alias.$key" => null])
                ->build();

            return ['id=s' =>  $subQuery];
        }

        if (
            $relationType == Entity::HAS_MANY ||
            $relationType == Entity::HAS_ONE ||
            $relationType == Entity::BELONGS_TO ||
            $relationType === Entity::HAS_CHILDREN
        ) {
            $subQuery = QueryBuilder::create()
                ->select(Attribute::ID)
                ->from($this->entityType)
                ->leftJoin($link, $alias)
                ->where([$alias . '.id' => null])
                ->build();

            return ['id=s' =>  $subQuery];
        }

        throw new RuntimeException("Bad where item. Not supported relation type.");
    }

    /**
     * @return array<string|int, mixed>
     */
    private function processIsLinked(string $attribute): array
    {
        $link = $attribute;
        $alias = $link . 'IsLinkedFilter' . $this->randomStringGenerator->generate();

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $relationType = $defs->getType();

        if ($relationType == Entity::MANY_MANY) {
            $key = $defs->getForeignMidKey();
            $nearKey = $defs->getMidKey();
            $middleEntityType = ucfirst($defs->getRelationshipName());

            // The foreign table is not joined as it would perform much slower.
            // Trade off is that if a foreign record is deleted but the middle table
            // is not yet deleted, it will give a non-actual result.
            $subQuery = QueryBuilder::create()
                ->select(Attribute::ID)
                ->from($this->entityType)
                ->leftJoin($middleEntityType, $alias, [
                    "$alias.$nearKey:" => Attribute::ID,
                    "$alias.deleted" => false,
                ])
                ->where(["$alias.$key!=" => null])
                ->build();

            return ['id=s' =>  $subQuery];
        }

        if (
            $relationType == Entity::HAS_MANY ||
            $relationType == Entity::HAS_ONE ||
            $relationType == Entity::BELONGS_TO ||
            $relationType == Entity::HAS_CHILDREN
        ) {
            $subQuery = QueryBuilder::create()
                ->select(Attribute::ID)
                ->from($this->entityType)
                ->leftJoin($link, $alias)
                ->where([$alias . '.id!=' => null])
                ->build();

            return ['id=s' =>  $subQuery];
        }

        throw new RuntimeException("Bad where item. Not supported relation type.");
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processLinkedWith(string $attribute, $value): array
    {
        $link = $attribute;

        if (!$this->ormDefs->getEntity($this->entityType)->hasRelation($link)) {
            throw new BadRequest("Not existing link '$link' in where item.");
        }

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $alias =  $link . 'LinkedWithFilter' . $this->randomStringGenerator->generate();

        if (!$value && !is_array($value)) {
            throw new BadRequest("Bad where item. Empty value.");
        }

        // @todo Add check for foreign record existence.

        $relationType = $defs->getType();

        if ($relationType == Entity::MANY_MANY) {
            $key = $defs->getForeignMidKey();
            $nearKey = $defs->getMidKey();

            // IN-sub-query performs faster than EXISTS on MariaDB when multiple IDs.
            // Left-join performs faster than inner-join.
            // Not joining a foreign table as it affects performance in MySQL.
            // MariaDB and PostgreSQL perform fast, MySQL – slow.
            return Cond::in(
                Cond::column(Attribute::ID),
                QueryBuilder::create()
                    ->select(Attribute::ID)
                    ->from($this->entityType)
                    ->leftJoin(
                        Join::create($link, $alias)
                            ->withConditions(
                                Cond::equal(
                                    Cond::column("$alias.$nearKey"),
                                    Cond::column(Attribute::ID)
                                )
                            )
                            ->withOnlyMiddle()
                    )
                    ->where(["$alias.$key" => $value])
                    ->build()
            )->getRaw();
        }

        if (
            $relationType == Entity::HAS_MANY ||
            $relationType == Entity::HAS_ONE
        ) {
            $subQuery = QueryBuilder::create()
                ->select(Attribute::ID)
                ->from($this->entityType)
                ->leftJoin($link, $alias)
                ->where([$alias . '.id' => $value])
                ->build();

            return ['id=s' => $subQuery];
        }

        if ($relationType == Entity::BELONGS_TO) {
            $key = $defs->getKey();

            return [$key => $value];
        }

        throw new BadRequest("Bad where item. Not supported relation type.");
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processNotLinkedWith(string $attribute, $value): array
    {
        $link = $attribute;

        if (!$this->ormDefs->getEntity($this->entityType)->hasRelation($link)) {
            throw new BadRequest("Not existing link '$link' in where item.");
        }

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $alias =  $link . 'NotLinkedWithFilter' . $this->randomStringGenerator->generate();

        if (is_null($value)) {
            throw new BadRequest("Bad where item. Empty value.");
        }

        $relationType = $defs->getType();

        if ($relationType == Entity::MANY_MANY) {
            $key = $defs->getForeignMidKey();

            // MariaDB and MySQL perform slow, PostgreSQL – fast.
            return Cond::not(
                Cond::exists(
                    QueryBuilder::create()
                        ->from($this->entityType, 'sq')
                        ->join(
                            Join::create($link, $alias)
                                ->withOnlyMiddle()
                        )
                        ->where(["$alias.$key" => $value])
                        ->where(
                            Cond::equal(
                                Cond::column('sq.id'),
                                Cond::column(lcfirst($this->entityType) . '.id')
                            )
                        )
                        ->build()
                )
            )->getRaw();
        }

        if (
            $relationType == Entity::HAS_MANY ||
            $relationType == Entity::HAS_ONE
        ) {
            return Cond::not(
                Cond::exists(
                    QueryBuilder::create()
                        ->select(Attribute::ID)
                        ->from($this->entityType, 'sq')
                        ->join($link, $alias)
                        ->where(["$alias.id" => $value])
                        ->where(['sq.id:' => lcfirst($this->entityType) . '.id'])
                        ->build()
                )
            )->getRaw();
        }

        if ($relationType == Entity::BELONGS_TO) {
            $key = $defs->getKey();

            return [$key . '!=' => $value];
        }

        throw new BadRequest("Bad where item. Not supported relation type.");
    }

    /**
     * @param mixed $value
     * @return array<string|int, mixed>
     * @throws BadRequest
     */
    private function processLinkedWithAll(string $attribute, $value): array
    {
        $link = $attribute;

        if (!$this->ormDefs->getEntity($this->entityType)->hasRelation($link)) {
            throw new BadRequest("Not existing link '$link' in where item.");
        }

        if (!$value && !is_array($value)) {
            throw new BadRequest("Bad where item. Empty value.");
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
                // Only-middle join performs slower on MariaDB.
                $sq = QueryBuilder::create()
                    ->from($this->entityType)
                    ->select(Attribute::ID)
                    ->leftJoin($link)
                    ->where([
                        $link . 'Middle.' . $key => $targetId,
                    ])
                    ->build();

                $whereList[] = [Attribute::ID . '=s' => $sq];
            }

            return $whereList;
        }

        if ($relationType === Entity::HAS_MANY) {
            $whereList = [];

            foreach ($value as $targetId) {
                $sq = QueryBuilder::create()
                    ->from($this->entityType)
                    ->select(Attribute::ID)
                    ->leftJoin($link)
                    ->where([$link . '.id' => $targetId])
                    ->build();

                $whereList[] = [Attribute::ID . '=s' => $sq];
            }

            return $whereList;
        }

        throw new BadRequest("Bad where item. Not supported relation type.");
    }

    private function getSystemTimeZone(): DateTimeZone
    {
        $timeZone = $this->applicationConfig->getTimeZone();

        try {
            return new DateTimeZone($timeZone);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @throws BadRequest
     */
    private function getTimeZone(?Data $data): DateTimeZone
    {
        $timeZone = $data instanceof Data\Date ? $data->getTimeZone() : null;

        if (!$timeZone) {
            return $this->getSystemTimeZone();
        }

        try {
            return new DateTimeZone($timeZone);
        } catch (Exception $e) {
            throw new BadRequest($e->getMessage());
        }
    }
}
