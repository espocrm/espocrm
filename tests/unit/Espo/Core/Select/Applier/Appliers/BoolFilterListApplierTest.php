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

namespace tests\unit\Espo\Core\Select\Applier\Appliers;

use Espo\Core\{
    Exceptions\Error,
    Select\Applier\Appliers\BoolFilterList as BoolFilterListApplier,
    Select\Bool\FilterFactory as BoolFilterFactory,
    Select\Bool\Filter as BoolFilter,
    Select\SelectManager,
};

use Espo\{
    ORM\Query\SelectBuilder as QueryBuilder,
    ORM\Query\Part\WhereClause,
    ORM\Query\Part\Where\OrGroupBuilder,
    Entities\User,
};

class BoolFilterListApplierTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->boolFilterFactory = $this->createMock(BoolFilterFactory::class);
        $this->user = $this->createMock(User::class);
        $this->selectManager = $this->createMock(SelectManager::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->orGroupBuilder = $this->createMock(OrGroupBuilder::class);

        $this->entityType = 'Test';

        $this->applier = new BoolFilterListApplier(
            $this->entityType,
            $this->user,
            $this->boolFilterFactory,
            $this->selectManager
        );
    }

    public function testApply1()
    {
        $boolFilterList = ['test1', 'test2'];

        $filter1 = $this->createFilterMock(['test' => '1']);
        $filter2 = $this->createFilterMock(['test' => '2']);

        $this->initApplierTest($boolFilterList, [$filter1, $filter2], [true, true]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where');

        $this->applier->apply($this->queryBuilder, $boolFilterList);
    }

    public function testApply2()
    {
        $boolFilterList = ['test1'];

        $filter1 = $this->createFilterMock(['test' => '1']);

        $this->initApplierTest($boolFilterList, [$filter1], [true]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where');

        $this->applier->apply($this->queryBuilder, $boolFilterList);
    }

    public function testApply3()
    {
        $boolFilterList = ['test1'];

        $this->initApplierTest($boolFilterList, [null], [false]);

        $this->selectManager
            ->expects($this->once())
            ->method('hasBoolFilter')
            ->with('test1')
            ->willReturn(false);

        $this->expectException(Error::class);

        $this->applier->apply($this->queryBuilder, $boolFilterList);
    }

    protected function initApplierTest(array $filterNameList, array $filterList, array $hasList)
    {
        $hasMap = [];
        $createMap = [];

        foreach ($filterNameList as $i => $filterName) {
            $hasMap[] = [$this->entityType, $filterName, $hasList[$i]];

            if (!$hasList[$i]) {
                continue;
            }

            $createMap[] = [$this->entityType, $this->user, $filterName, $filterList[$i]];
        }

        $this->boolFilterFactory
            ->expects($this->any())
            ->method('has')
            ->willReturnMap($hasMap);

        $this->boolFilterFactory
            ->expects($this->any())
            ->method('create')
            ->willReturnMap($createMap);
    }

    protected function createFilterMock(array $rawWhereClause): BoolFilter
    {
        $filter = $this->createMock(BoolFilter::class);

        $whereClause = $this->createMock(WhereClause::class);

        $whereClause
            ->expects($this->any())
            ->method('getRawValue')
            ->willReturn($rawWhereClause);

        $filter
            ->expects($this->any())
            ->method('apply')
            ->with($this->queryBuilder, $this->isInstanceOf(OrGroupBuilder::class));

        return $filter;
    }
}
