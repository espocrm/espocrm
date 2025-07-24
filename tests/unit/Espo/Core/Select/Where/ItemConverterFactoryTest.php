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

namespace tests\unit\Espo\Core\Select\Where;

use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingData;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\Where\ItemConverterFactory;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use PHPUnit\Framework\TestCase;
use tests\unit\testClasses\Core\Select\Where\ItemConverters\TestConverter;

class ItemConverterFactoryTest extends TestCase
{
    private $metadata;
    private $injectableFactory;
    private $user;
    private $factory;

    protected function setUp(): void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->user = $this->createMock(User::class);

        $this->factory = new ItemConverterFactory(
            $this->injectableFactory,
            $this->metadata
        );
    }

    public function testCreateForType()
    {
        $this->prepareFactoryTest(TestConverter::class);
        $this->prepareFactoryTest(TestConverter::class, true);
    }

    public function testHasFalseForType()
    {
        $this->metadata
            ->expects($this->once())
            ->method('get')
            ->with([
                'app', 'select', 'whereItemConverterClassNameMap', 'someType'
            ])
            ->willReturn(null);

        $this->assertFalse(
            $this->factory->hasForType('someType')
        );
    }

    public function testCreateEntityType()
    {
        $this->prepareFactoryTestEntityType(TestConverter::class);
        $this->prepareFactoryTestEntityType(TestConverter::class, true);
    }

    protected function prepareFactoryTest(?string $className, bool $testHas = false)
    {
        $entityType = 'Test';

        $type = 'someType';

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->with([
                'app', 'select', 'whereItemConverterClassNameMap', $type
            ])
            ->willReturn($className);

        if ($testHas) {
            $this->assertTrue(
                $this->factory->hasForType($type)
            );

            return;
        }

        $object = $this->createMock($className);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $this->user);

        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType);

        $bindingContainer = new BindingContainer($bindingData);

        $this->injectableFactory
            ->expects($this->once())
            ->method('createWithBinding')
            ->with(
                $className,
                $bindingContainer
            )
            ->willReturn($object);

        $resultObject = $this->factory->createForType(
            $type,
            $entityType,
            $this->user
        );

        $this->assertEquals($object, $resultObject);
    }

    protected function prepareFactoryTestEntityType(?string $className, bool $testHas = false)
    {
        $entityType = 'Test';

        $type = 'someType';

        $attribute = 'test';

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->with([
                'selectDefs', $entityType, 'whereItemConverterClassNameMap', $attribute . '_' . $type
            ])
            ->willReturn($className);

        if ($testHas) {
            $this->assertTrue(
                $this->factory->has(
                    $entityType,
                    $attribute,
                    $type
                )
            );

            return;
        }

        $object = $this->createMock($className);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $this->user);

        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType);

        $bindingContainer = new BindingContainer($bindingData);

        $this->injectableFactory
            ->expects($this->once())
            ->method('createWithBinding')
            ->with(
                $className,
                $bindingContainer
            )
            ->willReturn($object);

        $resultObject = $this->factory->create(
            $entityType,
            $attribute,
            $type,
            $this->user
        );

        $this->assertEquals($object, $resultObject);
    }
}
