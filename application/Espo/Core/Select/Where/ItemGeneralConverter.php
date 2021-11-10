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

namespace Espo\Core\Select\Where;

use Espo\{
    Core\Exceptions\Error,
    ORM\Query\SelectBuilder as QueryBuilder,
    ORM\Query\Part\WhereClause,
    ORM\Query\Part\WhereItem as WhereClauseItem,
    ORM\EntityManager,
    ORM\Entity,
    ORM\Defs as ORMDefs,
    Entities\User,
    Core\Utils\Config,
    Core\Select\Helpers\RandomStringGenerator,
    Core\Utils\Metadata,
};

use DateTime;
use DateInterval;

/**
 * Converts a where item to a where clause (for ORM).
 */
class ItemGeneralConverter implements ItemConverter
{
    protected $entityType;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var DateTimeItemTransformer
     */
    protected $dateTimeItemTransformer;

    /**
     * @var Scanner
     */
    protected $scanner;

    /**
     * @var ItemConverterFactory
     */
    protected $itemConverterFactory;

    /**
     * @var RandomStringGenerator
     */
    protected $randomStringGenerator;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ORMDefs
     */
    protected $ormDefs;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Metadata
     */
    protected $metadata;

    public function __construct(
        string $entityType,
        User $user,
        DateTimeItemTransformer $dateTimeItemTransformer,
        Scanner $scanner,
        ItemConverterFactory $itemConverterFactory,
        RandomStringGenerator $randomStringGenerator,
        EntityManager $entityManager,
        ORMDefs $ormDefs,
        Config $config,
        Metadata $metadata
    ) {
        $this->entityType = $entityType;
        $this->user = $user;
        $this->dateTimeItemTransformer = $dateTimeItemTransformer;
        $this->scanner = $scanner;
        $this->itemConverterFactory = $itemConverterFactory;
        $this->randomStringGenerator = $randomStringGenerator;
        $this->entityManager = $entityManager;
        $this->ormDefs = $ormDefs;
        $this->config = $config;
        $this->metadata = $metadata;
    }

    public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
    {
        $type = $item->getType();
        $value = $item->getValue();
        $attribute = $item->getAttribute();

        if ($item->isDateTime()) {
            return $this->convert(
                $queryBuilder,
                $this->dateTimeItemTransformer->transform($item)
            );
        }

        if (!$type) {
            throw new Error("Bad where item. No 'type'.");
        }

        if ($attribute) {
            if ($this->itemConverterFactory->has($this->entityType, $attribute, $type)) {
                $converter = $this->itemConverterFactory->create(
                    $this->entityType, $attribute, $type, $this->user
                );

                return $converter->convert($queryBuilder, $item);
            }

            $methodName = 'convert' . ucfirst($attribute) . ucfirst($type);

            if (method_exists($this, $methodName)) {
                return $this->$methodName($queryBuilder, $value);
            }
        }

        switch ($type) {

            case 'or':
            case 'and':

                return WhereClause::fromRaw(
                    $this->groupProcessAndOr($queryBuilder, $type, $attribute, $value)
                );

            case 'not':
            case 'subQueryNotIn':
            case 'subQueryIn':

                return WhereClause::fromRaw(
                    $this->groupProcessSubQuery($queryBuilder, $type, $attribute, $value)
                );
        }

        if (!$attribute) {
            throw new Error("Bad where item. No 'attribute'.");
        }

        switch ($type) {

            case 'columnLike':
            case 'columnIn':
            case 'columnNotIn':
            case 'columnIsNotNull':
            case 'columnEquals':
            case 'columnNotEquals':

                return WhereClause::fromRaw(
                    $this->groupProcessColumn($queryBuilder, $type, $attribute, $value)
                );

            case 'arrayAnyOf':
            case 'arrayNoneOf':
            case 'arrayIsEmpty':
            case 'arrayIsNotEmpty':
            case 'arrayAllOf':

                return WhereClause::fromRaw(
                    $this->groupProcessArray($queryBuilder, $type, $attribute, $value)
                );
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

    protected function groupProcessAndOr(
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

    protected function groupProcessSubQuery(
        QueryBuilder $queryBuilder,
        string $type,
        ?string $attribute,
        $value
    ): array {

        if (!is_array($value)) {
            throw new Error("Bad where item.");
        }

        $sqQueryBuilder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from($this->entityType);

        $whereItem = Item::fromRaw([
            'type' => 'and',
            'value' => $value,
        ]);

        $whereClause = $this->convert($sqQueryBuilder, $whereItem)->getRaw();

        $this->scanner->applyLeftJoins($sqQueryBuilder, $whereItem);

        $rawParams = $sqQueryBuilder->build()->getRaw();

        $key = $type === 'subQueryIn' ? 'id=s' : 'id!=s';

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

    protected function groupProcessColumn(
        QueryBuilder $queryBuilder,
        string $type,
        string $attribute,
        $value
    ): array {

        $link = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $attribute, 'link']);
        $column = $this->metadata->get(['entityDefs', $this->entityType, 'fields', $attribute, 'column']);

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

    protected function groupProcessArray(
        QueryBuilder $queryBuilder,
        string $type,
        string $attribute,
        $value
    ): array {

        $arrayValueAlias = 'arrayFilter' . $this->randomStringGenerator->generate();

        $arrayAttribute = $attribute;
        $arrayEntityType = $this->entityType;
        $idPart = 'id';

        $isForeign = strpos($attribute, '.') !== false;

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
                list($arrayAttributeLink, $arrayAttribute) = explode('.', $attribute);
            }

            if (!$arrayAttributeLink || !$arrayAttribute) {
                throw new Error("Bad where item.");
            }

            $arrayEntityType = $entityDefs->getRelation($arrayAttributeLink)->getForeignEntityType();

            $arrayLinkAlias = $arrayAttributeLink . 'ArrayFilter' . $this->randomStringGenerator->generate();

            $idPart = $arrayLinkAlias . '.id';

            $queryBuilder->leftJoin($arrayAttributeLink, $arrayLinkAlias);

            $relationType = $entityDefs->getRelation($arrayAttributeLink)->getType();

            if ($relationType === Entity::MANY_MANY || $relationType === Entity::HAS_MANY) {
                $queryBuilder->distinct();
            }
        }

        if ($type === 'arrayAnyOf') {
            if (is_null($value) || !$value && !is_array($value)) {
                throw new Error("Bad where item. No value.");
            }

            $queryBuilder->leftJoin(
                'ArrayValue',
                $arrayValueAlias,
                [
                    $arrayValueAlias . '.entityId:' => $idPart,
                    $arrayValueAlias . '.entityType' => $arrayEntityType,
                    $arrayValueAlias . '.attribute' => $arrayAttribute,
                ]
            );

            $queryBuilder->distinct();

            return [
                $arrayValueAlias . '.value' => $value,
            ];
        }

        if ($type === 'arrayNoneOf') {
            if (is_null($value) || !$value && !is_array($value)) {
                throw new Error("Bad where item 'array'. No value.");
            }

            $queryBuilder->leftJoin(
                'ArrayValue',
                $arrayValueAlias,
                [
                    $arrayValueAlias . '.entityId:' => $idPart,
                    $arrayValueAlias . '.entityType' => $arrayEntityType,
                    $arrayValueAlias . '.attribute' => $arrayAttribute,
                    $arrayValueAlias . '.value=' => $value,
                ]
            );

            $queryBuilder->distinct();

            return [
                $arrayValueAlias . '.id' => null,
            ];
        }

        if ($type === 'arrayIsEmpty') {
            $queryBuilder->distinct();

            $queryBuilder->leftJoin(
                'ArrayValue',
                $arrayValueAlias,
                [
                    $arrayValueAlias . '.entityId:' => $idPart,
                    $arrayValueAlias . '.entityType' => $arrayEntityType,
                    $arrayValueAlias . '.attribute' => $arrayAttribute,
                ]
            );

            return [
                $arrayValueAlias . '.id' => null,
            ];
        }

        if ($type === 'arrayIsNotEmpty') {
            $queryBuilder->distinct();

            $queryBuilder->leftJoin(
                'ArrayValue',
                $arrayValueAlias,
                [
                    $arrayValueAlias . '.entityId:' => $idPart,
                    $arrayValueAlias . '.entityType' => $arrayEntityType,
                    $arrayValueAlias . '.attribute' => $arrayAttribute,
                ]
            );

            return [
                $arrayValueAlias . '.id!=' => null,
            ];
        }

        if ($type === 'arrayAllOf') {
            if (is_null($value) || !$value && !is_array($value)) {
                throw new Error("Bad where item 'array'. No value.");
            }

            if (!is_array($value)) {
                $value = [$value];
            }

            $whereList = [];

            foreach ($value as $arrayValue) {
                $whereList[] = [
                    $idPart .'=s' => QueryBuilder::create()
                        ->from('ArrayValue')
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
     */
    protected function processExpression(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $key = $attribute;

        if (substr($key, -1) !== ':') {
            $key .= ':';
        }

        return [
            $key => null,
        ];
    }

    protected function processLike(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '*' => $value,
        ];
    }

    protected function processNotLike(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '!*' => $value,
        ];
    }

    protected function processEquals(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => $value,
        ];
    }

    protected function processOn(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processEquals($queryBuilder, $attribute, $value);
    }

    protected function processNotEquals(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '!=' => $value,
        ];
    }

    protected function processNotOn(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processNotEquals($queryBuilder, $attribute, $value);
    }

    protected function processStartsWith(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '*' => $value . '%',
        ];
    }

    protected function processEndsWith(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '*' => '%' . $value,
        ];
    }

    protected function processContains(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '*' => '%' . $value . '%',
        ];
    }

    protected function processNotContains(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '!*' => '%' . $value . '%',
        ];
    }

    protected function processGreaterThan(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '>' => $value,
        ];
    }

    protected function processAfter(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processGreaterThan($queryBuilder, $attribute, $value);
    }

    protected function processLessThan(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '<' => $value,
        ];
    }

    protected function processBefore(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processLessThan($queryBuilder, $attribute, $value);
    }

    protected function processGreaterThanOrEquals(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '>=' => $value,
        ];
    }

    protected function processLessThanOrEquals(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '<=' => $value,
        ];
    }

    protected function processIn(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        if (!is_array($value)) {
            throw new Error("Bad where item 'in'.");
        }

        return [
            $attribute . '=' => $value,
        ];
    }

    protected function processNotIn(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        if (!is_array($value)) {
            throw new Error("Bad where item 'notIn'.");
        }

        return [
            $attribute . '!=' => $value,
        ];
    }

    protected function processBetween(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processAny(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            'true:' => null,
        ];
    }

    protected function processNone(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            'false:' => null,
        ];
    }

    protected function processIsNull(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => null,
        ];
    }

    protected function processIsNotNull(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '!=' => null,
        ];
    }

    protected function processEver(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return $this->processIsNotNull($queryBuilder, $attribute, $value);
    }

    protected function processIsTrue(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => true,
        ];
    }

    protected function processIsFalse(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => false,
        ];
    }

    protected function processToday(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '=' => date('Y-m-d'),
        ];
    }

    protected function processPast(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '<' => date('Y-m-d'),
        ];
    }

    protected function processFuture(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            $attribute . '>' => date('Y-m-d'),
        ];
    }

    protected function processLastSevenDays(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processLastXDays(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processNextXDays(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processOlderThanXDays(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        $number = strval(intval($value));

        $dt->modify('-' . $number . ' days');

        return [
            $attribute . '<' => $dt->format('Y-m-d'),
        ];
    }

    protected function processAfterXDays(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        $number = strval(intval($value));

        $dt->modify('+' . $number . ' days');

        return [
            $attribute . '>' => $dt->format('Y-m-d'),
        ];
    }

    protected function processCurrentMonth(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of this month')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
            ]
        ];
    }

    protected function processLastMonth(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of last month')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
            ]
        ];
    }

    protected function processNextMonth(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of next month')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
            ]
        ];
    }

    protected function processCurrentQuarter(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processLastQuarter(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processCurrentYear(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of January this year')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1Y'))->format('Y-m-d'),
            ]
        ];
    }

    protected function processLastYear(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $dt = new DateTime();

        return [
            'AND' => [
                $attribute . '>=' => $dt->modify('first day of January last year')->format('Y-m-d'),
                $attribute . '<' => $dt->add(new DateInterval('P1Y'))->format('Y-m-d'),
            ]
        ];
    }

    protected function processCurrentFiscalYear(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processLastFiscalYear(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processCurrentFiscalQuarter(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processLastFiscalQuarter(QueryBuilder $queryBuilder, string $attribute, $value): array
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

    protected function processIsNotLinked(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        return [
            'id!=s' => [
                'select' => ['id'],
                'from' => $this->entityType,
                'joins' => [$attribute],
            ]
        ];
    }

    protected function processIsLinked(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $link = $attribute;

        $alias = $link . 'IsLinkedFilter' . $this->randomStringGenerator->generate();

        $queryBuilder->distinct();

        $queryBuilder->leftJoin($link, $alias);

        return [
            $alias . '.id!=' => null,
        ];
    }

    protected function processLinkedWith(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $link = $attribute;

        if (!$this->ormDefs->getEntity($this->entityType)->hasRelation($link)) {
            throw new Error("Not existing link '{$link}' in where item.");
        }

        $defs = $this->ormDefs->getEntity($this->entityType)->getRelation($link);

        $alias =  $link . 'LinkedWithFilter' . $this->randomStringGenerator->generate();

        if (is_null($value) || !$value && !is_array($value)) {
            throw new Error("Bad where item. Empty value.");
        }

        $relationType = $defs->getType();

        $queryBuilder->distinct();

        if ($relationType == Entity::MANY_MANY) {
            $queryBuilder->leftJoin($link, $alias);

            $key = $defs->getForeignMidKey();

            if (!$key) {
                throw new Error("Bad link '{$link}' in where item.");
            }

            return [
                $alias . 'Middle.' . $key => $value,
            ];
        }
        else if ($relationType == Entity::HAS_MANY) {
            $queryBuilder->leftJoin($link, $alias);

            return [
                $alias . '.id' => $value,
            ];
        }
        else if ($relationType == Entity::BELONGS_TO) {
            $key = $defs->getKey();

            if (!$key) {
                throw new Error("Bad link '{$link}' in where item.");
            }

            return [
                $key => $value,
            ];
        }
        else if ($relationType == Entity::HAS_ONE) {
            $queryBuilder->leftJoin($link, $alias);

            return [
                $alias . '.id' => $value,
            ];
        }

        throw new Error("Bad where item. Not supported relation type.");
    }

    protected function processNotLinkedWith(QueryBuilder $queryBuilder, string $attribute, $value): array
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

        $queryBuilder->distinct();

        if ($relationType == Entity::MANY_MANY) {
            $key = $defs->getForeignMidKey();

            if (!$key) {
                throw new Error("Bad link '{$link}' in where item.");
            }

            $queryBuilder->leftJoin(
                $link,
                $alias,
                [$key => $value]
            );

            return [
                $alias . 'Middle.' . $key => null,
            ];
        }
        else if ($relationType == Entity::HAS_MANY) {
            $queryBuilder->leftJoin(
                $link,
                $alias,
                ['id' => $value]
            );

            return [
                $alias . '.id' => null,
            ];
        }
        else if ($relationType == Entity::BELONGS_TO) {
            $key = $defs->getKey();

            if (!$key) {
                throw new Error("Bad link '{$link}' in where item.");
            }

            return [
                $key . '!=' => $value,
            ];
        }
        else if ($relationType == Entity::HAS_ONE) {
            $queryBuilder->leftJoin($link, $alias);

            return [
                $alias . '.id!=' => $value,
            ];
        }

        throw new Error("Bad where item. Not supported relation type.");
    }

    protected function processLinkedWithAll(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $link = $attribute;

        if (!$this->ormDefs->getEntity($this->entityType)->hasRelation($link)) {
            throw new Error("Not existing link '{$link}' in where item.");
        }

        if (is_null($value) || !$value && !is_array($value)) {
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
                    ->where([
                        $link . '.id' => $targetId,
                    ])
                    ->build();

                $whereList[] = ['id=s' => $sq->getRaw()];
            }

            return $whereList;
        }

        throw new Error("Bad where item. Not supported relation type.");
    }
}
