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

namespace tests\unit\Espo\Core\Select\Applier;

use Espo\Core\{
    Select\Applier\Factory as ApplierFactory,
    Select\SelectManagerFactory,
    Select\SelectManager,
    Select\Applier\Appliers\Where as WhereApplier,
    Select\Applier\Appliers\Select as SelectApplier,
    Select\Applier\Appliers\Order as OrderApplier,
    Select\Applier\Appliers\Limit as LimitApplier,
    Select\Applier\Appliers\AccessControlFilter as AccessControlFilterApplier,
    Select\Applier\Appliers\PrimaryFilter as PrimaryFilterApplier,
    Select\Applier\Appliers\BoolFilterList as BoolFilterListApplier,
    Select\Applier\Appliers\TextFilter as TextFilterApplier,
    Select\Applier\Appliers\Additional as AdditionalApplier,
    Utils\Metadata,
    InjectableFactory,
    Binding\BindingContainer,
    Binding\Binder,
    Binding\BindingData,
};

use Espo\{
    Entities\User,
};

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->selectManagerFactory = $this->createMock(SelectManagerFactory::class);
        $this->user = $this->createMock(User::class);

        $this->selectManager = $this->createMock(SelectManager::class);

        $this->factory = new ApplierFactory(
            $this->injectableFactory,
            $this->metadata,
            $this->selectManagerFactory
        );
    }

    public function testCreate1()
    {
        $this->prepareFactoryTest(null, SelectApplier::class, ApplierFactory::SELECT);
    }

    public function testCreate2()
    {
        $this->prepareFactoryTest('SomeClass', BoolFilterListApplier::class, ApplierFactory::BOOL_FILTER_LIST);
    }

    public function testCreate3()
    {
        $this->prepareFactoryTest(null, TextFilterApplier::class, ApplierFactory::TEXT_FILTER);
    }

    public function testCreate4()
    {
        $this->prepareFactoryTest(null, WhereApplier::class, ApplierFactory::WHERE);
    }

    public function testCreate5()
    {
        $this->prepareFactoryTest(null, OrderApplier::class, ApplierFactory::ORDER);
    }

    public function testCreate6()
    {
        $this->prepareFactoryTest(null, LimitApplier::class, ApplierFactory::LIMIT);
    }

    public function testCreate7()
    {
        $this->prepareFactoryTest(null, AdditionalApplier::class, ApplierFactory::ADDITIONAL);
    }

    public function testCreate8()
    {
        $this->prepareFactoryTest(null, PrimaryFilterApplier::class, ApplierFactory::PRIMARY_FILTER);
    }

    public function testCreate9()
    {
        $this->prepareFactoryTest(null, AccessControlFilterApplier::class, ApplierFactory::ACCESS_CONTROL_FILTER);
    }

    protected function prepareFactoryTest(?string $className, string $defaultClassName, string $type)
    {
        $entityType = 'Test';

        $this->selectManagerFactory
            ->expects($this->once())
            ->method('create')
            ->with('Test', $this->user)
            ->willReturn($this->selectManager);

        $this->metadata
            ->expects($this->once())
            ->method('get')
            ->with(['selectDefs', $entityType, 'applierClassNameMap', $type])
            ->willReturn($className);

        $applierClassName = $className ?? $defaultClassName;

        $applier = $this->createMock($defaultClassName);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $this->user)
            ->bindInstance(SelectManager::class, $this->selectManager)
            ->for($applierClassName)
            ->bindValue('$entityType', $entityType)
            ->bindValue('$selectManager', $this->selectManager);

        $bindingContainer = new BindingContainer($bindingData);

        $this->injectableFactory
            ->expects($this->once())
            ->method('createWithBinding')
            ->with($applierClassName, $bindingContainer)
            ->willReturn($applier);

        $resultApplier = $this->factory->create(
            $entityType,
            $this->user,
            $type
        );

        $this->assertEquals($applier, $resultApplier);
    }
}
