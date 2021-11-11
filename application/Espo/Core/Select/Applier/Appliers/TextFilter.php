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

namespace Espo\Core\Select\Applier\Appliers;

use Espo\Core\Select\Text\MetadataProvider;
use Espo\Core\Select\Text\FilterParams;
use Espo\Core\Select\Text\FullTextSearchData;
use Espo\Core\Select\Text\FullTextSearchDataComposerFactory;
use Espo\Core\Select\Text\FullTextSearchDataComposerParams;
use Espo\Core\Select\Text\Filter\Data as FilterData;
use Espo\Core\Select\Text\FilterFactory;

use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Query\Part\Order as OrderExpr;
use Espo\ORM\Query\Part\Where\AndGroup;

use Espo\Entities\User;

class TextFilter
{
    /** @todo Move to metadata. */
    private $fullTextRelevanceThreshold = null;

    /** @todo Move to metadata. */
    private $fullTextOrderRelevanceDivider = 5;

    private const DEFAULT_FT_ORDER = self::FT_ORDER_COMBINTED;

    private const FT_ORDER_COMBINTED = 0;

    private const FT_ORDER_RELEVANCE = 1;

    private const FT_ORDER_ORIGINAL = 3;

    private $entityType;

    /**
     * @var User
     */
    private $user;

    /**
     * @var MetadataProvider
     */
    private $metadataProvider;

    /**
     * @var FullTextSearchDataComposerFactory
     */
    private $fullTextSearchDataComposerFactory;

    /**
     * @var FilterFactory
     */
    private $filterFactory;

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

        $preferFullTextSearch = $params->preferFullTextSearch();

        if (mb_strpos($filter, 'ft:') === 0) {
            $filter = mb_substr($filter, 3);

            $preferFullTextSearch = true;
            $forceFullTextSearch = true;
        }

        $filterOriginal = $filter;

        $skipWildcards = false;

        if (mb_strpos($filter, '*') !== false) {
            $skipWildcards = true;

            $filter = str_replace('*', '%', $filter);
        }

        $filterForFullTextSearch = str_replace('%', '*', $filterOriginal);

        $skipFullTextSearch = false;

        if (!$forceFullTextSearch) {
            if (mb_strpos($filterForFullTextSearch, '*') === 0) {
                $skipFullTextSearch = true;
            }
            else if (mb_strpos($filterForFullTextSearch, ' *') !== false) {
                $skipFullTextSearch = true;
            }
        }

        if ($params->noFullTextSearch()) {
            $skipFullTextSearch = true;
        }

        $fullTextSearchData = null;

        if (!$skipFullTextSearch) {
            $fullTextSearchIsAuxiliary = !$preferFullTextSearch;

            $fullTextSearchData = $this->getFullTextSearchData(
                $filterForFullTextSearch,
                $fullTextSearchIsAuxiliary
            );
        }

        $fullTextGroup = [];

        $fullTextSearchFieldList = [];

        $hasFullTextSearch = false;

        if ($fullTextSearchData) {
            if ($this->fullTextRelevanceThreshold) {
                $fullTextGroup[] = [
                    $fullTextSearchData->getExpression() . '>=' => $this->fullTextRelevanceThreshold
                ];
            }
            else {
                $fullTextGroup[] = $fullTextSearchData->getExpression();
            }

            $fullTextSearchFieldList = $fullTextSearchData->getFieldList();
            $relevanceExpression = $fullTextSearchData->getExpression();

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
                $queryBuilder->order([
                    OrderExpr::fromString($relevanceExpression)->withDesc()
                ]);
            }
            else if ($fullTextOrderType === self::FT_ORDER_COMBINTED) {
                $relevanceExpression =
                    'ROUND:(DIV:(' . $fullTextSearchData->getExpression() . ',' .
                    $this->fullTextOrderRelevanceDivider . '))';

                $newOrderBy = array_merge(
                    [
                        OrderExpr::fromString($relevanceExpression)->withDesc()
                    ],
                    $previousOrderBy
                );

                $queryBuilder->order($newOrderBy);
            }

            $hasFullTextSearch = true;
        }

        $fieldList = array_filter(
            $this->metadataProvider->getTextFilterAttributeList($this->entityType) ?? ['name'],
            function ($field) use ($fullTextSearchFieldList, $forceFullTextSearch) {
                if ($forceFullTextSearch) {
                    return false;
                }

                // @todo Check this logic.
                if (in_array($field, $fullTextSearchFieldList)) {
                    return false;
                }

                return true;
            }
        );

        $filterData = FilterData::create($filter, $fieldList)
            ->withSkipWildcards($skipWildcards)
            ->withForceFullTextSearch($forceFullTextSearch)
            ->withFullTextSearchWhereItem(
                $hasFullTextSearch ? AndGroup::fromRaw($fullTextGroup) : null
            );

        $this->filterFactory
            ->create($this->entityType, $this->user)
            ->apply($queryBuilder, $filterData);
    }

    private function getFullTextSearchData(string $filter, bool $isAuxiliaryUse = false): ?FullTextSearchData
    {
        $composer = $this->fullTextSearchDataComposerFactory->create($this->entityType);

        $params = FullTextSearchDataComposerParams::fromArray([
            'isAuxiliaryUse' => $isAuxiliaryUse,
        ]);

        return $composer->compose($filter, $params);
    }
}
