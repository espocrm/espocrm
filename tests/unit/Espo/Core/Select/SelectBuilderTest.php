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

namespace tests\unit\Espo\Core\Select;

use Espo\Core\Select\AccessControl\Applier as AccessControlFilterApplier;
use Espo\Core\Select\Applier\Appliers\Additional as AdditionalApplier;
use Espo\Core\Select\Applier\Appliers\Limit as LimitApplier;
use Espo\Core\Select\Applier\Factory as ApplierFactory;
use Espo\Core\Select\Bool\Applier as BoolFilterListApplier;
use Espo\Core\Select\Order\Applier as OrderApplier;
use Espo\Core\Select\Order\Params as OrderParams;
use Espo\Core\Select\Primary\Applier as PrimaryFilterApplier;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Select\Applier as SelectApplier;
use Espo\Core\Select\SelectBuilder;
use Espo\Core\Select\Text\Applier as TextFilterApplier;
use Espo\Core\Select\Text\FilterParams as TextFilterParams;
use Espo\Core\Select\Where\Applier as WhereApplier;
use Espo\Core\Select\Where\Params as WhereParams;

use Espo\Entities\User;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use PHPUnit\Framework\TestCase;

class SelectBuilderTest extends TestCase
{
    /** @var SelectBuilder */
    private $selectBuilder;

    private $additionalApplier;
    private $entityType;
    private $whereApplier;
    private $orderApplier;
    private $limitApplier;
    private $accessControlFilterApplier;
    private $textFilterApplier;
    private $primaryFilterApplier;
    private $boolFilterListApplier;

    protected function setUp(): void
    {
        $user = $this->createMock(User::class);
        $applierFactory = $this->createMock(ApplierFactory::class);

        $this->entityType = 'Test';

        $this->whereApplier = $this->createMock(WhereApplier::class);
        $selectApplier = $this->createMock(SelectApplier::class);
        $this->orderApplier =  $this->createMock(OrderApplier::class);
        $this->limitApplier = $this->createMock(LimitApplier::class);
        $this->accessControlFilterApplier = $this->createMock(AccessControlFilterApplier::class);
        $this->textFilterApplier = $this->createMock(TextFilterApplier::class);
        $this->primaryFilterApplier = $this->createMock(PrimaryFilterApplier::class);
        $this->boolFilterListApplier = $this->createMock(BoolFilterListApplier::class);
        $this->additionalApplier = $this->createMock(AdditionalApplier::class);

        $applierFactory
            ->expects($this->any())
            ->method('createWhere')
            ->with($this->entityType, $user)
            ->willReturn($this->whereApplier);

        $applierFactory
            ->expects($this->any())
            ->method('createSelect')
            ->with($this->entityType, $user)
            ->willReturn($selectApplier);

        $applierFactory
            ->expects($this->any())
            ->method('createOrder')
            ->with($this->entityType, $user)
            ->willReturn($this->orderApplier);

        $applierFactory
            ->expects($this->any())
            ->method('createLimit')
            ->with($this->entityType, $user)
            ->willReturn($this->limitApplier);

        $applierFactory
            ->expects($this->any())
            ->method('createAccessControlFilter')
            ->with($this->entityType, $user)
            ->willReturn($this->accessControlFilterApplier);

        $applierFactory
            ->expects($this->any())
            ->method('createTextFilter')
            ->with($this->entityType, $user)
            ->willReturn($this->textFilterApplier);

        $applierFactory
            ->expects($this->any())
            ->method('createPrimaryFilter')
            ->with($this->entityType, $user)
            ->willReturn($this->primaryFilterApplier);

        $applierFactory
            ->expects($this->any())
            ->method('createBoolFilterList')
            ->with($this->entityType, $user)
            ->willReturn($this->boolFilterListApplier);

        $applierFactory
            ->expects($this->any())
            ->method('createAdditional')
            ->with($this->entityType, $user)
            ->willReturn($this->additionalApplier);

        $this->selectBuilder = new SelectBuilder($user, $applierFactory);
    }

    public function testBuild1()
    {
        $raw = [
            'textFilter' => 'testText',
            'primaryFilter' => 'testPrimary',
            'boolFilterList' => [
                'testBool1',
                'testBool2',
            ],
            'where' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test',
                    'value' => 'value',
                ],
            ],
            'orderBy' => 'test',
            'order' => SearchParams::ORDER_DESC,
        ];

        $searchParams = SearchParams::fromRaw($raw);

        $this->primaryFilterApplier
            ->expects($this->once())
            ->method('apply')
            ->with(
                $this->isInstanceOf(QueryBuilder::class),
                $raw['primaryFilter']
            );

        $this->boolFilterListApplier
            ->expects($this->once())
            ->method('apply')
            ->with(
                $this->isInstanceOf(QueryBuilder::class),
                [
                    'testBool1',
                    'testBool2',
                    'testBool3',
                ]
            );

        $textFilterParams = TextFilterParams::create();

        $this->textFilterApplier
            ->expects($this->once())
            ->method('apply')
            ->with(
                $this->isInstanceOf(QueryBuilder::class),
                $raw['textFilter'],
                $textFilterParams
            );

        $this->accessControlFilterApplier
            ->expects($this->once())
            ->method('apply')
            ->with(
                $this->isInstanceOf(QueryBuilder::class)
            );

        $whereItem = $searchParams->getWhere();

        $whereParams = WhereParams::fromAssoc([
            'applyPermissionCheck' => true,
            'forbidComplexExpressions' => true,
        ]);

        $this->whereApplier
            ->expects($this->once())
            ->method('apply')
            ->with(
                $this->isInstanceOf(QueryBuilder::class),
                $whereItem,
                $whereParams
            );

        $orderParams = OrderParams::fromAssoc([
            'orderBy' => $searchParams->getOrderBy(),
            'order' => $searchParams->getOrder(),
            'applyPermissionCheck' => true,
        ]);

        $this->orderApplier
            ->expects($this->once())
            ->method('apply')
            ->with(
                $this->isInstanceOf(QueryBuilder::class),
                $orderParams
            );

        $this->additionalApplier
            ->expects($this->once())
            ->method('apply')
            ->with(
                [],
                $this->isInstanceOf(QueryBuilder::class),
                $this->isInstanceOf(SearchParams::class),
            );

        $query = $this->selectBuilder
            ->from($this->entityType)
            ->withSearchParams($searchParams)
            ->withStrictAccessControl()
            ->withBoolFilter('testBool3')
            ->build();

        $this->assertEquals($this->entityType, $query->getFrom());
    }

    public function testBuildDefaultOrder1()
    {
        $raw = [
            'textFilter' => 'testText',
        ];

        $searchParams = SearchParams::fromRaw($raw);

        $orderParams = OrderParams::fromAssoc([
            'forceDefault' => true,
            'order' => null,
        ]);

        $this->orderApplier
            ->expects($this->once())
            ->method('apply')
            ->with(
                $this->isInstanceOf(QueryBuilder::class),
                $orderParams
            );

        $query = $this->selectBuilder
            ->from($this->entityType)
            ->withSearchParams($searchParams)
            ->build();

        $this->assertEquals($this->entityType, $query->getFrom());
    }

    public function testBuildClone1()
    {
        $query = (new QueryBuilder())
            ->from($this->entityType)
            ->build();

        $query = $this->selectBuilder
            ->clone($query)
            ->withPrimaryFilter('testPrimary')
            ->build();

        $this->assertEquals($this->entityType, $query->getFrom());
    }

    public function testBuildMaxSize0()
    {

        $searchParams = SearchParams::create()->withMaxSize(0);


        $this->limitApplier
            ->expects($this->once())
            ->method('apply')
            ->with($this->isInstanceOf(QueryBuilder::class), 0, 0);

        $query = $this->selectBuilder
            ->from($this->entityType)
            ->withSearchParams($searchParams)
            ->build();

        $this->assertEquals($this->entityType, $query->getFrom());
    }
}
