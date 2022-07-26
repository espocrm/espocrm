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

namespace Espo\Core\Select;

use Espo\Core\Select\Applier\Factory as ApplierFactory;
use Espo\Core\Select\Where\Params as WhereParams;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Core\Select\Order\Params as OrderParams;
use Espo\Core\Select\Text\FilterParams as TextFilterParams;

use Espo\Core\Select\Where\Applier as WhereApplier;
use Espo\Core\Select\Select\Applier as SelectApplier;
use Espo\Core\Select\Order\Applier as OrderApplier;
use Espo\Core\Select\AccessControl\Applier as AccessControlFilterApplier;
use Espo\Core\Select\Primary\Applier as PrimaryFilterApplier;
use Espo\Core\Select\Bool\Applier as BoolFilterListApplier;
use Espo\Core\Select\Text\Applier as TextFilterApplier;
use Espo\Core\Select\Applier\Appliers\Limit as LimitApplier;
use Espo\Core\Select\Applier\Appliers\Additional as AdditionalApplier;

use Espo\ORM\Query\Select as Query;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

use Espo\Entities\User;

use LogicException;

/**
 * Builds select queries for ORM. Applies search parameters(passed from front-end),
 * ACL restrictions, filters, etc.
 */
class SelectBuilder
{
    private ?string $entityType = null;

    private ?OrmSelectBuilder $queryBuilder = null;

    private ?Query $sourceQuery = null;

    private ?SearchParams $searchParams = null;

    private bool $applyAccessControlFilter = false;

    private bool $applyDefaultOrder = false;

    private ?string $textFilter = null;

    private ?string $primaryFilter = null;

    /**
     * @var string[]
     */
    private array $boolFilterList = [];

    /**
     * @var WhereItem[]
     */
    private array $whereItemList = [];

    private bool $applyWherePermissionCheck = false;

    private bool $applyComplexExpressionsForbidden = false;

    /**
     * @var class-string<\Espo\Core\Select\Applier\AdditionalApplier>[]
     */
    private array $additionalApplierClassNameList = [];

    private User $user;

    private ApplierFactory $applierFactory;

    public function __construct(User $user, ApplierFactory $applierFactory)
    {
        $this->user = $user;
        $this->applierFactory = $applierFactory;
    }

    /**
     * Specify an entity type to select from.
     */
    public function from(string $entityType): self
    {
        if ($this->sourceQuery) {
            throw new LogicException("Can't call 'from' after 'clone'.");
        }

        $this->entityType = $entityType;

        return $this;
    }

    /**
     * Start building from an existing select query.
     */
    public function clone(Query $query): self
    {
        if ($this->entityType && $this->entityType !== $query->getFrom()) {
            throw new LogicException("Not matching entity type.");
        }

        $this->entityType = $query->getFrom();
        $this->sourceQuery = $query;

        return $this;
    }

    /**
     * Build a result query.
     */
    public function build(): Query
    {
        return $this->buildQueryBuilder()->build();
    }

    /**
     * Build an ORM query builder. Used to continue building but by means of ORM.
     */
    public function buildQueryBuilder(): QueryBuilder
    {
        $this->queryBuilder = new OrmSelectBuilder();

        if (!$this->entityType) {
            throw new LogicException("No entity type.");
        }

        if ($this->sourceQuery) {
            $this->queryBuilder->clone($this->sourceQuery);
        }
        else {
            $this->queryBuilder->from($this->entityType);
        }

        $this->applyFromSearchParams();

        if (count($this->whereItemList)) {
            $this->applyWhereItemList();
        }

        if ($this->applyDefaultOrder) {
            $this->applyDefaultOrder();
        }

        if ($this->primaryFilter) {
            $this->applyPrimaryFilter();
        }

        if (count($this->boolFilterList)) {
            $this->applyBoolFilterList();
        }

        if ($this->textFilter) {
            $this->applyTextFilter();
        }

        if ($this->applyAccessControlFilter) {
            $this->applyAccessControlFilter();
        }

        $this->applyAdditional();

        /** @var QueryBuilder */
        return $this->queryBuilder;
    }

    /**
     * Switch a user for whom a select query will be built.
     */
    public function forUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Apply search parameters.
     *
     * Note: If there's no order set in the search parameters then a default order will be applied.
     */
    public function withSearchParams(SearchParams $searchParams): self
    {
        $this->searchParams = $searchParams;

        $this->withBoolFilterList(
            $searchParams->getBoolFilterList()
        );

        $primaryFilter = $searchParams->getPrimaryFilter();

        if ($primaryFilter) {
            $this->withPrimaryFilter($primaryFilter);
        }

        $textFilter = $searchParams->getTextFilter();

        if ($textFilter) {
            $this->withTextFilter($textFilter);
        }

        return $this;
    }

    /**
     * Apply maximum restrictions for a user.
     */
    public function withStrictAccessControl(): self
    {
        $this->withAccessControlFilter();
        $this->withWherePermissionCheck();
        $this->withComplexExpressionsForbidden();

        return $this;
    }

    /**
     * Apply an access control filter.
     */
    public function withAccessControlFilter(): self
    {
        $this->applyAccessControlFilter = true;

        return $this;
    }

    /**
     * Apply a default order.
     */
    public function withDefaultOrder(): self
    {
        $this->applyDefaultOrder = true;

        return $this;
    }

    /**
     * Check permissions to where items.
     */
    public function withWherePermissionCheck(): self
    {
        $this->applyWherePermissionCheck = true;

        return $this;
    }

    /**
     * Forbid complex expression usage.
     */
    public function withComplexExpressionsForbidden(): self
    {
        $this->applyComplexExpressionsForbidden = true;

        return $this;
    }

    /**
     * Apply a text filter.
     */
    public function withTextFilter(string $textFilter): self
    {
        $this->textFilter = $textFilter;

        return $this;
    }

    /**
     * Apply a primary filter.
     */
    public function withPrimaryFilter(string $primaryFilter): self
    {
        $this->primaryFilter = $primaryFilter;

        return $this;
    }

    /**
     * Apply a bool filter.
     */
    public function withBoolFilter(string $boolFilter): self
    {
        $this->boolFilterList[] = $boolFilter;

        return $this;
    }

    /**
     * Apply a list of bool filters.
     *
     * @param string[] $boolFilterList
     */
    public function withBoolFilterList(array $boolFilterList): self
    {
        $this->boolFilterList = array_merge($this->boolFilterList, $boolFilterList);

        return $this;
    }

    /**
     * Apply a Where Item.
     */
    public function withWhere(WhereItem $whereItem): self
    {
        $this->whereItemList[] = $whereItem;

        return $this;
    }

    /**
     * Apply a list of additional applier class names.
     *
     * @param class-string<\Espo\Core\Select\Applier\AdditionalApplier>[] $additionalApplierClassNameList
     */
    public function withAdditionalApplierClassNameList(array $additionalApplierClassNameList): self
    {
        $this->additionalApplierClassNameList = array_merge(
            $this->additionalApplierClassNameList,
            $additionalApplierClassNameList
        );

        return $this;
    }

    private function applyPrimaryFilter(): void
    {
        assert($this->queryBuilder !== null);
        assert($this->primaryFilter !== null);

        $this->createPrimaryFilterApplier()
            ->apply(
                $this->queryBuilder,
                $this->primaryFilter
            );
    }

    private function applyBoolFilterList(): void
    {
        assert($this->queryBuilder !== null);

        $this->createBoolFilterListApplier()
            ->apply(
                $this->queryBuilder,
                $this->boolFilterList
            );
    }

    private function applyTextFilter(): void
    {
        assert($this->queryBuilder !== null);
        assert($this->textFilter !== null);

        $this->createTextFilterApplier()
            ->apply(
                $this->queryBuilder,
                $this->textFilter,
                TextFilterParams::create()
            );
    }

    private function applyAccessControlFilter(): void
    {
        assert($this->queryBuilder !== null);

        $this->createAccessControlFilterApplier()
            ->apply(
                $this->queryBuilder
            );
    }

    private function applyDefaultOrder(): void
    {
        assert($this->queryBuilder !== null);

        $order = null;

        if ($this->searchParams) {
            $order = $this->searchParams->getOrder();
        }

        $params = OrderParams::fromArray([
            'forceDefault' => true,
            'order' => $order,
        ]);

        $this->createOrderApplier()
            ->apply(
                $this->queryBuilder,
                $params
            );
    }

    private function applyWhereItemList(): void
    {
        foreach ($this->whereItemList as $whereItem) {
            $this->applyWhereItem($whereItem);
        }
    }

    private function applyWhereItem(WhereItem $whereItem): void
    {
        assert($this->queryBuilder !== null);

        $params = WhereParams::fromArray([
            'applyPermissionCheck' => $this->applyWherePermissionCheck,
            'forbidComplexExpressions' => $this->applyComplexExpressionsForbidden,
        ]);

        $this->createWhereApplier()
            ->apply(
                $this->queryBuilder,
                $whereItem,
                $params
            );
    }

    private function applyFromSearchParams(): void
    {
        if (!$this->searchParams) {
            return;
        }

        assert($this->queryBuilder !== null);

        if (
            !$this->applyDefaultOrder &&
            ($this->searchParams->getOrderBy() || $this->searchParams->getOrder())
        ) {
            $params = OrderParams::fromArray([
                'forbidComplexExpressions' => $this->applyComplexExpressionsForbidden,
                'orderBy' => $this->searchParams->getOrderBy(),
                'order' => $this->searchParams->getOrder(),
            ]);

            $this->createOrderApplier()
                ->apply(
                    $this->queryBuilder,
                    $params
                );
        }

        if (!$this->searchParams->getOrderBy() && !$this->searchParams->getOrder()) {
            $this->withDefaultOrder();
        }

        if ($this->searchParams->getMaxSize() !== null || $this->searchParams->getOffset() !== null) {
            $this->createLimitApplier()
                ->apply(
                    $this->queryBuilder,
                    $this->searchParams->getOffset(),
                    $this->searchParams->getMaxSize()
                );
        }

        if ($this->searchParams->getSelect()) {
            $this->createSelectApplier()
                ->apply(
                    $this->queryBuilder,
                    $this->searchParams
                );
        }

        if ($this->searchParams->getWhere()) {
            $this->whereItemList[] = $this->searchParams->getWhere();
        }
    }

    private function applyAdditional(): void
    {
        assert($this->queryBuilder !== null);

        if (count($this->additionalApplierClassNameList) === 0) {
            return;
        }

        $searchParams = SearchParams::fromRaw([
            'boolFilterList' => $this->boolFilterList,
            'primaryFilter' => $this->primaryFilter,
            'textFilter' => $this->textFilter,
        ]);

        if ($this->searchParams) {
            $searchParams = SearchParams::merge($searchParams, $this->searchParams);
        }

        $this->createAdditionalApplier()->apply(
            $this->additionalApplierClassNameList,
            $this->queryBuilder,
            $searchParams
        );
    }

    private function createWhereApplier(): WhereApplier
    {
        assert($this->entityType !== null);

        return $this->applierFactory->createWhere($this->entityType, $this->user);
    }

    private function createSelectApplier(): SelectApplier
    {
        assert($this->entityType !== null);

        return $this->applierFactory->createSelect($this->entityType, $this->user);
    }

    private function createOrderApplier(): OrderApplier
    {
        assert($this->entityType !== null);

        return $this->applierFactory->createOrder($this->entityType, $this->user);
    }

    private function createLimitApplier(): LimitApplier
    {
        assert($this->entityType !== null);

        return $this->applierFactory->createLimit($this->entityType, $this->user);
    }

    private function createAccessControlFilterApplier(): AccessControlFilterApplier
    {
        assert($this->entityType !== null);

        return $this->applierFactory->createAccessControlFilter($this->entityType, $this->user);
    }

    private function createTextFilterApplier(): TextFilterApplier
    {
        assert($this->entityType !== null);

        return $this->applierFactory->createTextFilter($this->entityType, $this->user);
    }

    private function createPrimaryFilterApplier(): PrimaryFilterApplier
    {
        assert($this->entityType !== null);

        return $this->applierFactory->createPrimaryFilter($this->entityType, $this->user);
    }

    private function createBoolFilterListApplier(): BoolFilterListApplier
    {
        assert($this->entityType !== null);

        return $this->applierFactory->createBoolFilterList($this->entityType, $this->user);
    }

    private function createAdditionalApplier(): AdditionalApplier
    {
        assert($this->entityType !== null);

        return $this->applierFactory->createAdditional($this->entityType, $this->user);
    }
}
