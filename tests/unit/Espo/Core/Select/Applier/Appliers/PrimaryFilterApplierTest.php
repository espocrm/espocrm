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
    Select\Applier\Appliers\PrimaryFilter as PrimaryFilterApplier,
    Select\Primary\FilterFactory as PrimaryFilterFactory,
    Select\Primary\Filter as PrimaryFilter,
    Select\SelectManager,
};

use Espo\{
    ORM\Query\SelectBuilder as QueryBuilder,
    Entities\User,
};

class PrimaryFilterApplierTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->filterFactory = $this->createMock(PrimaryFilterFactory::class);
        $this->user = $this->createMock(User::class);
        $this->selectManager = $this->createMock(SelectManager::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $this->entityType = 'Test';

        $this->applier = new PrimaryFilterApplier(
            $this->entityType,
            $this->user,
            $this->filterFactory,
            $this->selectManager
        );
    }

    public function testApply1()
    {
        $filterName = 'test';

        $filter = $this->createMock(PrimaryFilter::class);

        $filter
            ->expects($this->once())
            ->method('apply')
            ->with($this->queryBuilder);

        $this->filterFactory
            ->expects($this->once())
            ->method('has')
            ->with($this->entityType, $filterName)
            ->willReturn(true);

        $this->filterFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->entityType, $this->user, $filterName)
            ->willReturn($filter);

        $this->applier->apply($this->queryBuilder, $filterName);
    }

    public function testApply2()
    {
        $filterName = 'test';

        $filter = $this->createMock(PrimaryFilter::class);

        $this->filterFactory
            ->expects($this->once())
            ->method('has')
            ->with($this->entityType, $filterName)
            ->willReturn(false);

        $this->filterFactory
            ->expects($this->never())
            ->method('create');

        $this->selectManager
            ->expects($this->once())
            ->method('hasPrimaryFilter')
            ->with($filterName)
            ->willReturn(false);

        $this->expectException(Error::class);

        $this->applier->apply($this->queryBuilder, $filterName);
    }
}
