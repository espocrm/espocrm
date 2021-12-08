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

namespace tests\unit\Espo\Core\Select\Bool;

use Espo\Core\{
    Select\Bool\FilterFactory as BoolFilterFactory,
    Select\Bool\Filters\Followed,
    Utils\Metadata,
    InjectableFactory,
    Binding\BindingContainer,
    Binding\Binder,
    Binding\BindingData,
};

use Espo\{
    Entities\User,
};

class FilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->user = $this->createMock(User::class);

        $this->factory = new BoolFilterFactory(
            $this->injectableFactory,
            $this->metadata
        );
    }

    public function testCreate1()
    {
        $this->prepareFactoryTest(null, Followed::class, 'followed');
    }

    public function testCreate2()
    {
        $this->prepareFactoryTest('SomeClass', Followed::class, 'followed');
    }

    protected function prepareFactoryTest(?string $className, string $defaultClassName, string $name)
    {
        $entityType = 'Test';

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [['selectDefs', $entityType, 'boolFilterClassNameMap', $name], null, $className],
            ]);

        $className = $className ?? $defaultClassName;

        $object = $this->createMock($defaultClassName);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $this->user)
            ->for($className)
            ->bindValue('$entityType', $entityType);

        $bindingContainer = new BindingContainer($bindingData);

        $this->injectableFactory
            ->expects($this->once())
            ->method('createWithBinding')
            ->with($className, $bindingContainer)
            ->willReturn($object);

        $resultObject = $this->factory->create(
            $entityType,
            $this->user,
            $name
        );

        $this->assertEquals($object, $resultObject);

        $this->assertTrue(
            $this->factory->has($entityType, $name)
        );

        $this->metadata
            ->method('get')
            ->withConsecutive(
                [[
                    'selectDefs',
                    $entityType,
                    'boolFilterClassNameMap',
                    'badName',
                ]],
                [['app', 'select', 'boolFilterClassNameMap', 'badName']],
            )
            ->willReturnOnConsecutiveCalls(
                null,
                null
            );

        $this->assertFalse(
            $this->factory->has($entityType, 'badName')
        );
    }
}
