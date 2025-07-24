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

namespace tests\unit\Espo\Core\Select\Order;

use Espo\Entities\User;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\Order\ItemConverter;
use Espo\Core\Select\Order\ItemConverterFactory;
use Espo\Core\Select\Order\ItemConverters\EnumType;
use Espo\Core\Utils\Metadata;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Binding\ContextualBinder;
use PHPUnit\Framework\TestCase;

class ItemConverterFactoryTest extends TestCase
{
    private $injectableFactory;
    private $metadata;
    private $user;
    private $factory;

    protected function setUp() : void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->user = $this->createMock(User::class);

        $this->factory = new ItemConverterFactory(
            $this->injectableFactory,
            $this->metadata,
            $this->user
        );
    }

    public function testCreate1()
    {
        $this->prepareFactoryTest(null, EnumType::class);
        $this->prepareFactoryTest(null, EnumType::class, true);
    }

    public function testCreate2()
    {
        $this->prepareFactoryTest(EnumType::class, null);
        $this->prepareFactoryTest(EnumType::class, null, true);
    }

    protected function prepareFactoryTest(?string $className1, ?string $className2, bool $testHas = false)
    {
        $defaultClassName = ItemConverter::class;

        $entityType = 'Test';

        $field = 'name';

        $type = 'varchar';

        $object = $this->createMock($defaultClassName);

        $className = $className1 ?? $className2 ?? null;

        if (!$className1) {
            $this->metadata
                ->expects($this->any())
                ->method('get')
                ->willReturnMap([
                    [['selectDefs', $entityType, 'orderItemConverterClassNameMap', $field], null, $className1],
                    [['entityDefs', $entityType, 'fields', $field, 'type'], null, $type],
                    [['app', 'select', 'orderItemConverterClassNameMap', $type], null, $className2],
                ]);
        } else {
            $this->metadata
                ->expects($this->any())
                ->method('get')
                ->willReturnMap([
                    [['selectDefs', $entityType, 'orderItemConverterClassNameMap', $field], null, $className1],
                ]);
        }

        if ($testHas) {
            $this->assertTrue(
                $this->factory->has($entityType, $field)
            );

            return;
        }

        $object = $this->createMock($className);

        $container = BindingContainerBuilder::create()
            ->bindInstance(User::class, $this->user)
            ->inContext($className, function (ContextualBinder $binder) use ($entityType) {
                $binder->bindValue('$entityType', $entityType);
            })
            ->build();

        $this->injectableFactory
            ->expects($this->once())
            ->method('createWithBinding')
            ->with(
                $className,
                $container
            )
            ->willReturn($object);

        $resultObject = $this->factory->create(
            $entityType,
            $field
        );

        $this->assertEquals($object, $resultObject);
    }

    public function testHasFalse()
    {
        $entityType = 'Test';

        $field = 'name';

        $type = 'varchar';

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [['selectDefs', $entityType, 'orderItemConverterClassNameMap', 'badName'], null, null],
                [['entityDefs', $entityType, 'fields', 'badName', 'type'], null, $type],
                [['app', 'select', 'orderItemConverterClassNameMap', $type], null, null],
            ]);

        $this->assertFalse(
            $this->factory->has($entityType, 'badName')
        );
    }
}
