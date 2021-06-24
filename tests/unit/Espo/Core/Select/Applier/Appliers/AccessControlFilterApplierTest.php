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
    AclManager,
    Acl,
    Select\Applier\Appliers\AccessControlFilter as AccessControlFilterApplier,
    Select\SelectManager,
    Select\AccessControl\FilterFactory as AccessControlFilterFactory,
    Select\AccessControl\FilterResolverFactory as AccessControlFilterResolverFactory,
    Select\AccessControl\FilterResolver,
    Select\AccessControl\Filter as AccessControlFilter,
};

use Espo\{
    ORM\Query\SelectBuilder as QueryBuilder,
    Entities\User,
};

class AccessControlFilterApplierTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->filterFactory = $this->createMock(AccessControlFilterFactory::class);
        $this->filterResolverFactory = $this->createMock(AccessControlFilterResolverFactory::class);
        $this->aclManager = $this->createMock(AclManager::class);
        $this->acl = $this->createMock(Acl::class);
        $this->user = $this->createMock(User::class);
        $this->selectManager = $this->createMock(SelectManager::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->filterResolver = $this->createMock(FilterResolver::class);
        $this->filter = $this->createMock(AccessControlFilter::class);
        $this->mandatoryFilter = $this->createMock(AccessControlFilter::class);

        $this->aclManager
            ->expects($this->any())
            ->method('createUserAcl')
            ->with($this->user)
            ->willReturn($this->acl);

        $this->entityType = 'Test';

        $this->applier = new AccessControlFilterApplier(
            $this->entityType,
            $this->user,
            $this->filterFactory,
            $this->filterResolverFactory,
            $this->aclManager,
            $this->selectManager
        );
    }

    public function testApply1()
    {
        $this->initApplierTest(true, true);

        $this->applier->apply($this->queryBuilder);
    }

    public function testApply2()
    {
        $this->initApplierTest(true, false);

        $this->applier->apply($this->queryBuilder);
    }

    public function testApply3()
    {
        $this->initApplierTest(false, false);

        $this->applier->apply($this->queryBuilder);
    }

    protected function initApplierTest(bool $resolve, bool $hasFilter)
    {
        $this->selectManager
            ->expects($this->once())
            ->method('hasInheritedAccessMethod')
            ->willReturn(false);

        $this->filterResolverFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->entityType, $this->user)
            ->willReturn($this->filterResolver);

        $filterName = null;

        if ($resolve) {
            $filterName = 'test';
        }

        $this->filterResolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn($filterName);

        if (!$resolve) {
            $this->filterFactory
                ->expects($this->never())
                ->method('has');

            return;
        }

        $this->selectManager
            ->expects($this->once())
            ->method('hasInheritedAccessFilterMethod')
            ->with('test')
            ->willReturn(false);

        $this->filterFactory
            ->expects($this->once())
            ->method('has')
            ->with($this->entityType, $filterName)
            ->willReturn($hasFilter);

        if (!$hasFilter) {
            $this->expectException(Error::class);

            return;
        }

        $this->filterFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$this->entityType, $this->user, 'mandatory'],
                [$this->entityType, $this->user, $filterName],
            )
            ->willReturnOnConsecutiveCalls(
                $this->mandatoryFilter,
                $this->filter
            );

        $this->filter
            ->expects($this->once())
            ->method('apply')
            ->with($this->queryBuilder);
    }
}
