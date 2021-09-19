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
    Select\Text\FullTextSearchDataComposer,
    Select\Text\FullTextSearchDataComposerParams,
    Select\Text\MetadataProvider,
    Utils\Config,
};

class FullTextSearchDataComposerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->config = $this->createMock(Config::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->entityType = 'Test';

        $this->fullTextSearchDataComposer = new FullTextSearchDataComposer(
            $this->entityType, $this->config, $this->metadataProvider
        );
    }

    public function testCompose1()
    {
        $filter = 'test filter';

        $this->config
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['fullTextSearchDisabled', false],
                        ['fullTextSearchMinLength', 4],
                    ]
                )
            );

        $this->metadataProvider
            ->expects($this->any())
            ->method('isFieldNotStorable')
            ->will(
                $this->returnValueMap(
                    [
                        [$this->entityType, 'field1', false],
                        [$this->entityType, 'field2', false],
                        [$this->entityType, 'field3', false],
                    ]
                )
            );

        $this->metadataProvider
            ->expects($this->any())
            ->method('isFullTextSearchSupportedForField')
            ->will(
                $this->returnValueMap(
                    [
                        [$this->entityType, 'field1', true],
                        [$this->entityType, 'field2', true],
                        [$this->entityType, 'field3', false],
                    ]
                )
            );

        $this->metadataProvider
            ->expects($this->any())
            ->method('hasFullTextSearch')
            ->with($this->entityType)
            ->willReturn(true);

        $this->metadataProvider
            ->expects($this->any())
            ->method('getFullTextSearchColumnList')
            ->with($this->entityType)
            ->willReturn(
                ['field1A', 'field1B', 'field2']
            );

        $this->metadataProvider
            ->expects($this->any())
            ->method('getTextFilterAttributeList')
            ->with($this->entityType)
            ->willReturn(
                ['field1', 'field2', 'field3']
            );

        $params = FullTextSearchDataComposerParams::fromArray([
            'isAuxiliaryUse' => false,
        ]);

        $data = $this->fullTextSearchDataComposer->compose($filter, $params);

        $this->assertNotEquals(null, $data);

        $this->assertEquals(['field1A', 'field1B', 'field2'], $data->getColumnList());
        $this->assertEquals(['field1', 'field2'], $data->getFieldList());

        $this->assertEquals('MATCH_BOOLEAN:(field1A, field1B, field2, \'test filter\')', $data->getExpression());
    }

    public function testCompose2()
    {
        $filter = 'bad';

        $this->config
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['fullTextSearchDisabled', false],
                        ['fullTextSearchMinLength', 4],
                    ]
                )
            );

        $params = FullTextSearchDataComposerParams::fromArray([
            'isAuxiliaryUse' => false,
        ]);

        $data = $this->fullTextSearchDataComposer->compose($filter, $params);

        $this->assertEquals(null, $data);
    }

    public function testCompose3()
    {
        $filter = 'test filter';

        $this->config
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['fullTextSearchDisabled', true],
                        ['fullTextSearchMinLength', 4],
                    ]
                )
            );

        $params = FullTextSearchDataComposerParams::fromArray([
            'isAuxiliaryUse' => false,
        ]);

        $data = $this->fullTextSearchDataComposer->compose($filter, $params);

        $this->assertEquals(null, $data);
    }
}
