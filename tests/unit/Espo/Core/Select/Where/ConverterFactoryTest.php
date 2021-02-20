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

namespace tests\unit\Espo\Core\Select\Where;

use Espo\Core\{
    Select\Where\ConverterFactory,
    Select\Where\Converter,
    Select\Where\DateTimeItemTransformer,
    Select\Where\ItemGeneralConverter,
    Utils\Metadata,
    InjectableFactory,
};

use Espo\{
    Entities\User,
};

class ConverterFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->user = $this->createMock(User::class);

        $this->factory = new ConverterFactory(
            $this->injectableFactory,
            $this->metadata
        );

        $this->itemConverter = $this->createMock(ItemGeneralConverter::class);
        $this->dateTimeItemTransformer = $this->createMock(DateTimeItemTransformer::class);
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

        $className1 = $className1 ?? DateTimeItemTransformer::class;
        $className2 = $className2 ?? ItemGeneralConverter::class;
        $className3 = $className3 ?? Converter::class;

        $object = $this->createMock(Converter::class);

        $this->injectableFactory
            ->expects($this->exactly(3))
            ->method('createWith')
            ->withConsecutive(
                [
                    $className1,
                    [
                        'entityType' => $entityType,
                        'user' => $this->user,
                    ]
                ],
                [
                    $className2,
                    [
                        'entityType' => $entityType,
                        'user' => $this->user,
                        'dateTimeItemTransformer' => $this->dateTimeItemTransformer,
                    ]
                ],
                [
                    $className3,
                    [
                        'entityType' => $entityType,
                        'user' => $this->user,
                        'itemConverter' => $this->itemConverter,
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $this->dateTimeItemTransformer,
                $this->itemConverter,
                $object
            );

        $resultObject = $this->factory->create($entityType, $this->user);

        $this->assertEquals($object, $resultObject);
    }
}
