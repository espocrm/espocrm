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
use Espo\Core\Select\Where\Converter;
use Espo\Core\Select\Where\ConverterFactory;
use Espo\Core\Select\Where\DateTimeItemTransformer;
use Espo\Core\Select\Where\DefaultDateTimeItemTransformer;
use Espo\Core\Select\Where\ItemConverter;
use Espo\Core\Select\Where\ItemGeneralConverter;
use Espo\Core\Utils\Metadata;

use Espo\Entities\User;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ConverterFactoryTest extends TestCase
{
    private $injectableFactory;
    private $metadata;
    private $user;
    private $factory;
    private $itemConverter;
    private $dateTimeItemTransformer;

    protected function setUp(): void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->user = $this->createMock(User::class);

        $this->factory = new ConverterFactory(
            $this->injectableFactory,
            $this->metadata
        );

        $this->itemConverter = $this->createMock(ItemGeneralConverter::class);
        $this->dateTimeItemTransformer = $this->createMock(DefaultDateTimeItemTransformer::class);
    }

    public function testCreate1()
    {
        $this->prepareFactoryTest(null, null, null);
    }

    public function testCreate2()
    {
        $this->prepareFactoryTest('SomeClass1', 'SomeClass2', 'SomeClass3');
    }

    protected function prepareFactoryTest(?string $className1, ?string $className2, ?string $className3)
    {
        $entityType = 'Test';

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                [['selectDefs', $entityType, 'whereDateTimeItemTransformerClassName'], null, $className1],
                [['selectDefs', $entityType, 'whereItemConverterClassName'], null, $className2],
                [['selectDefs', $entityType, 'whereConverterClassName'], null, $className3],
            ]);

        $className1 = $className1 ?? DefaultDateTimeItemTransformer::class;
        $className2 = $className2 ?? ItemGeneralConverter::class;
        $className3 = $className3 ?? Converter::class;

        $object = $this->createMock(Converter::class);

        $bindingData1 = new BindingData();

        $binder1 = new Binder($bindingData1);
        $binder1
            ->bindInstance(User::class, $this->user);
        $binder1
            ->for($className1)
            ->bindValue('$entityType', $entityType);
        $binder1
            ->for(DefaultDateTimeItemTransformer::class)
            ->bindValue('$entityType', $entityType);

        $bc1 = new BindingContainer($bindingData1);

        $bindingData2 = new BindingData();

        $binder2 = new Binder($bindingData2);

        $binder2
            ->bindInstance(User::class, $this->user);
        $binder2
            ->for($className2)
            ->bindValue('$entityType', $entityType)
            ->bindInstance(DateTimeItemTransformer::class, $this->dateTimeItemTransformer);
        $binder2
            ->for(ItemGeneralConverter::class)
            ->bindValue('$entityType', $entityType)
            ->bindInstance(DateTimeItemTransformer::class, $this->dateTimeItemTransformer);

        $bc2 = new BindingContainer($bindingData2);

        $bindingData3 = new BindingData();

        $binder3 = new Binder($bindingData3);

        $binder3
            ->bindInstance(User::class, $this->user)
            ->for($className3)
            ->bindValue('$entityType', $entityType)
            ->bindInstance(ItemConverter::class, $this->itemConverter);

        $bc3 = new BindingContainer($bindingData3);

        $c = $this->exactly(3);

        $this->injectableFactory
            ->expects($c)
            ->method('createWithBinding')
            ->willReturnCallback(function ($className, $bc) use
                ($c, $object, $className1, $className2, $className3, $bc1, $bc2, $bc3) {

                if ($c->numberOfInvocations() === 1) {
                    $this->assertEquals($className1, $className);
                    $this->assertEquals($bc1, $bc);

                    return $this->dateTimeItemTransformer;
                }

                if ($c->numberOfInvocations() === 2) {
                    $this->assertEquals($className2, $className);
                    $this->assertEquals($bc2, $bc);

                    return $this->itemConverter;
                }

                if ($c->numberOfInvocations() === 3) {
                    $this->assertEquals($className3, $className);
                    $this->assertEquals($bc3, $bc);

                    return $object;
                }

                throw new RuntimeException();
            });

        $resultObject = $this->factory->create($entityType, $this->user);

        $this->assertEquals($object, $resultObject);
    }
}
