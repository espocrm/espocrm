<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Select\Text;

use Espo\Core\Select\Text\FullTextSearch\Data as FullTextSearchData;
use Espo\Core\Select\Text\FullTextSearch\DataComposerFactory as FullTextSearchDataComposerFactory;
use Espo\Core\Select\Text\FullTextSearch\DataComposer\Params as FullTextSearchDataComposerParams;
use Espo\Core\Select\Text\Filter\Data as FilterData;

use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Query\Part\Order as OrderExpr;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\WhereItem;

use Espo\Entities\User;

class Applier
{
    /** @todo Move to metadata. */
    private ?int $fullTextRelevanceThreshold = null;
    /** @todo Move to metadata. */
    private int $fullTextOrderRelevanceDivider = 5;

    private const DEFAULT_FT_ORDER = self::FT_ORDER_COMBINED;
    private const DEFAULT_ATTRIBUTE_LIST = ['name'];

    private const FT_ORDER_COMBINED = 0;
    private const FT_ORDER_RELEVANCE = 1;
    private const FT_ORDER_ORIGINAL = 3;

    public function __construct(
        private string $entityType,
        private User $user,
        private MetadataProvider $metadataProvider,
        private FullTextSearchDataComposerFactory $fullTextSearchDataComposerFactory,
        private FilterFactory $filterFactory
    ) {}

    public function apply(QueryBuilder $queryBuilder, string $filter, FilterParams $params): void
    {
        $forceFullTextSearch = false;

        if (mb_strpos($filter, 'ft:') === 0) {
            $filter = mb_substr($filter, 3);

            $forceFullTextSearch = true;
        }

        $fullTextSearchData = $this->composeFullTextSearchData($filter);

        $fullTextWhere = $fullTextSearchData ?
            $this->processFullTextSearch($queryBuilder, $fullTextSearchData) :
            null;

        $fullTextSearchFieldList = $fullTextSearchData ? $fullTextSearchData->getFieldList() : [];

        $fieldList = $forceFullTextSearch ? [] :
            array_filter(
                $this->metadataProvider->getTextFilterAttributeList($this->entityType) ?? self::DEFAULT_ATTRIBUTE_LIST,
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
            ->withFullTextSearchWhereItem($fullTextWhere);

        $this->filterFactory
            ->create($this->entityType, $this->user)
            ->apply($queryBuilder, $filterData);
    }

    private function composeFullTextSearchData(string $filter): ?FullTextSearchData
    {
        $composer = $this->fullTextSearchDataComposerFactory->create($this->entityType);

        $params = FullTextSearchDataComposerParams::create();

        return $composer->compose($filter, $params);
    }

    private function processFullTextSearch(QueryBuilder $queryBuilder, FullTextSearchData $data): WhereItem
    {
        $expression = $data->getExpression();

        $fullTextOrderType = self::DEFAULT_FT_ORDER;

        $orderTypeMap = [
            'combined' => self::FT_ORDER_COMBINED,
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
                OrderExpr::create($expression)->withDesc()
            ]);
        }
        else if ($fullTextOrderType === self::FT_ORDER_COMBINED) {
            $orderExpression =
                Expr::round(
                    Expr::divide($expression, $this->fullTextOrderRelevanceDivider)
                );

            $newOrderBy = array_merge(
                [OrderExpr::create($orderExpression)->withDesc()],
                $previousOrderBy
            );

            $queryBuilder->order($newOrderBy);
        }

        if ($this->fullTextRelevanceThreshold) {
            return Expr::greaterOrEqual(
                $expression,
                $this->fullTextRelevanceThreshold
            );
        }

        return Expr::notEqual($expression, 0);
    }
}
