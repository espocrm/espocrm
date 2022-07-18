<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Select\Text;

use Espo\Core\Select\Text\MetadataProvider;
use Espo\Core\Select\Text\FilterParams;
use Espo\Core\Select\Text\FullTextSearch\Data as FullTextSearchData;
use Espo\Core\Select\Text\FullTextSearch\DataComposerFactory as FullTextSearchDataComposerFactory;
use Espo\Core\Select\Text\FullTextSearch\DataComposer\Params as FullTextSearchDataComposerParams;
use Espo\Core\Select\Text\Filter\Data as FilterData;
use Espo\Core\Select\Text\FilterFactory;

use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Query\Part\Order as OrderExpr;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Entity;

use Espo\Entities\User;

class Applier
{
    /** @todo Move to metadata. */
    private ?int $fullTextRelevanceThreshold = null;

    /** @todo Move to metadata. */
    private int $fullTextOrderRelevanceDivider = 5;

    private const DEFAULT_FT_ORDER = self::FT_ORDER_COMBINTED;

    private const FT_ORDER_COMBINTED = 0;

    private const FT_ORDER_RELEVANCE = 1;

    private const FT_ORDER_ORIGINAL = 3;

    private string $entityType;

    private User $user;

    private MetadataProvider $metadataProvider;

    private FullTextSearchDataComposerFactory $fullTextSearchDataComposerFactory;

    private FilterFactory $filterFactory;

    public function __construct(
        string $entityType,
        User $user,
        MetadataProvider $metadataProvider,
        FullTextSearchDataComposerFactory $fullTextSearchDataComposerFactory,
        FilterFactory $filterFactory
    ) {
        $this->entityType = $entityType;
        $this->user = $user;
        $this->metadataProvider = $metadataProvider;
        $this->fullTextSearchDataComposerFactory = $fullTextSearchDataComposerFactory;
        $this->filterFactory = $filterFactory;
    }

    public function apply(QueryBuilder $queryBuilder, string $filter, FilterParams $params): void
    {
        $forceFullTextSearch = false;

        if (mb_strpos($filter, 'ft:') === 0) {
            $filter = mb_substr($filter, 3);

            $forceFullTextSearch = true;
        }

        $fullTextSearchData = $this->composeFullTextSearchData($filter);

        $fullTextOrGroup = $fullTextSearchData ?
            $this->processFullTextSearch($queryBuilder, $fullTextSearchData) :
            null;

        $fullTextSearchFieldList = $fullTextSearchData ? $fullTextSearchData->getFieldList() : [];

        foreach ($fullTextSearchFieldList as $field) {
            $this->processRelatedFullTextFields($queryBuilder, $field);
        }

        $fieldList = $forceFullTextSearch ? [] :
            array_filter(
                $this->metadataProvider->getTextFilterAttributeList($this->entityType) ?? ['name'],
                function ($field) use ($fullTextSearchFieldList) {
                    return !in_array($field, $fullTextSearchFieldList);
                }
            );

        $skipWildcards = false;

        if (mb_strpos($filter, '*') !== false) {
            $skipWildcards = true;

            $filter = str_replace('*', '%', $filter);
        }

        $filterData = FilterData::create($filter, $fieldList)
            ->withSkipWildcards($skipWildcards)
            ->withForceFullTextSearch($forceFullTextSearch)
            ->withFullTextSearchOrGroup($fullTextOrGroup);

        $this->filterFactory
            ->create($this->entityType, $this->user)
            ->apply($queryBuilder, $filterData);
    }

    private function processRelatedFullTextFields(QueryBuilder $queryBuilder, string $field): void
    {
        if (strpos($field, '.') !== false) {
            $link = explode('.', $field)[0];
            
            if ($this->metadataProvider->getRelationType($this->entityType, $link) === Entity::HAS_MANY) {
                $queryBuilder->distinct();
            }

            $queryBuilder->leftJoin($link);
        }
    }

    private function composeFullTextSearchData(string $filter): ?FullTextSearchData
    {
        $composer = $this->fullTextSearchDataComposerFactory->create($this->entityType);

        $params = FullTextSearchDataComposerParams::create();

        return $composer->compose($filter, $params);
    }

    private function processFullTextSearch(QueryBuilder $queryBuilder, FullTextSearchData $data): OrGroup
    {
        $expressions = $data->getExpressions();

        $fullTextOrderType = self::DEFAULT_FT_ORDER;

        $orderTypeMap = [
            'combined' => self::FT_ORDER_COMBINTED,
            'relevance' => self::FT_ORDER_RELEVANCE,
            'original' => self::FT_ORDER_ORIGINAL,
        ];

        $mOrderType = $this->metadataProvider->getFullTextSearchOrderType($this->entityType);

        if ($mOrderType) {
            $fullTextOrderType = $orderTypeMap[$mOrderType];
        }

        $previousOrderBy = $queryBuilder->build()->getOrder();

        $hasOrderBy = !empty($previousOrderBy);

        if (!$hasOrderBy || $fullTextOrderType === self::FT_ORDER_RELEVANCE) {
            $order = [];

            foreach ($expressions as $expression) {
                array_push($order, OrderExpr::create($expression)->withDesc());
            }

            $queryBuilder->order($order);
        }
        else if ($fullTextOrderType === self::FT_ORDER_COMBINTED) {
            $order = [];

            foreach ($expressions as $expression) {
                $orderExpression = Expr::round(
                    Expr::divide($expression, $this->fullTextOrderRelevanceDivider)
                );

                array_push($order, OrderExpr::create($orderExpression)->withDesc());
            }

            $newOrderBy = array_merge(
                $order,
                $previousOrderBy
            );

            $queryBuilder->order($newOrderBy);
        }

        if ($this->fullTextRelevanceThreshold) {
            $treshold = $this->fullTextRelevanceThreshold;

            return OrGroup::create(...array_map(
                function ($expression) use ($treshold) {
                    return Expr::greaterOrEqual($expression, $treshold );
                },
                $expressions
            ));
        }

        return $data->getOrGroup();
    }
}
