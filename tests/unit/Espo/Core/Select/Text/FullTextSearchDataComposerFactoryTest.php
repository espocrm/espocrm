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

namespace tests\unit\Espo\Core\Select\Text;

use Espo\Core\{
    Select\Text\FullTextSearchDataComposerFactory,
    Select\Text\FullTextSearchDataComposer,
    Utils\Metadata,
    InjectableFactory,
};

class FullTextSearchDataComposerFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);

        $this->factory = new FullTextSearchDataComposerFactory(
            $this->injectableFactory,
            $this->metadata
        );
    }

    public function testCreate1()
    {
        $this->prepareFactoryTest(null);
    }

    public function testCreate2()
    {
        $this->prepareFactoryTest('SomeClass');
    }

    protected function prepareFactoryTest(?string $className)
    {
        $entityType = 'Test';

        $defaultClassName = FullTextSearchDataComposer::class;

        $object = $this->createMock($defaultClassName);

        $this->metadata
            ->expects($this->once())
            ->method('get')
            ->with([
                'selectDefs', $entityType, 'fullTextSearchDataComposerClassName'
            ])
            ->willReturn($className);

        $className = $className ?? $defaultClassName;

        $object = $this->createMock($defaultClassName);

        $this->injectableFactory
            ->expects($this->once())
            ->method('createWith')
            ->with(
                $className,
                [
                    'entityType' => $entityType,
                ]
            )
            ->willReturn($object);

        $resultObject = $this->factory->create($entityType);

        $this->assertEquals($object, $resultObject);
    }
}
