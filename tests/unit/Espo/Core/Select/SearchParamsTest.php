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

namespace tests\unit\Espo\Core\Select;

use Espo\Core\{
    Select\SearchParams,
    Select\Where\Item as WhereItem,
};

use InvalidArgumentException;

class SearchParamsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
    }

    public function testFromRaw1()
    {
        $raw = [
            'select' => ['id', 'name'],
            'offset' => 0,
            'maxSize' => 10,
            'order' => 'desc',
            'orderBy' => 'testOrderBy',
            'boolFilterList' => ['test1', 'test2'],
            'textFilter' => 'test',
            'primaryFilter' => 'testPrimary',
            'where' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test',
                    'value' => 'Test',
                ],
            ],
        ];

        $params = SearchParams::fromRaw($raw);

        $this->assertEquals(['id', 'name'], $params->getSelect());
        $this->assertEquals(0, $params->getOffset());
        $this->assertEquals(10, $params->getMaxSize());
        $this->assertEquals('DESC', $params->getOrder());
        $this->assertEquals('testOrderBy', $params->getOrderBy());
        $this->assertEquals(['test1', 'test2'], $params->getBoolFilterList());
        $this->assertEquals('test', $params->getTextFilter());
        $this->assertEquals('testPrimary', $params->getPrimaryFilter());
        $this->assertEquals($raw['where'], $params->getWhere()->getRaw()['value']);

        $this->assertFalse($params->noFullTextSearch());
    }

    public function testFromRawEmpty()
    {
        $params = SearchParams::fromRaw([
        ]);

        $this->assertEquals(null, $params->getSelect());
        $this->assertEquals(null, $params->getOffset());
        $this->assertEquals(null, $params->getMaxSize());
        $this->assertEquals(null, $params->getOrder());
        $this->assertEquals(null, $params->getOrderBy());
        $this->assertEquals(null, $params->getTextFilter());
        $this->assertEquals(null, $params->getPrimaryFilter());
        $this->assertEquals(null, $params->getWhere());
    }

    public function testQ()
    {
        $params = SearchParams::fromRaw([
            'q' => 'test',
        ]);

        $this->assertEquals('test', $params->getTextFilter());

        $this->assertTrue($params->noFullTextSearch());
    }

    public function testOrder()
    {
        $raw = [

            'order' => 'asc',
        ];

        $params = SearchParams::fromRaw($raw);

        $this->assertEquals('ASC', $params->getOrder());
    }

    public function testMaxTextAttributeLength()
    {
        $raw = [
            'maxTextAttributeLength' => 1000,
        ];

        $params = SearchParams::fromRaw($raw);

        $this->assertEquals(1000, $params->getMaxTextAttributeLength());
    }

    public function testAdjust()
    {
        $raw = [
            'where' => [
                [
                    'type' => 'primary',
                    'value' => 'testPrimary',
                ],
                [
                    'type' => 'textFilter',
                    'value' => 'testText',
                ],
                [
                    'type' => 'bool',
                    'value' => ['test'],
                ],
            ],
        ];

        $params = SearchParams::fromRaw($raw);

        $this->assertEquals('testPrimary', $params->getPrimaryFilter());
        $this->assertEquals('testText', $params->getTextFilter());
        $this->assertEquals(['test'], $params->getBoolFilterList());
    }

    public function testBadSelect1()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'select' => 'id,name',
        ]);
    }

    public function testBadSelect2()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'select' => [1],
        ]);
    }

    public function testBadOffset()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'offset' => 'hello',
        ]);
    }

    public function testBadBoolFilterList1()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'boolFilterList' => 'test',
        ]);
    }

    public function testBadBoolFilterList2()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'boolFilterList' => [1],
        ]);
    }

    public function testBadTextFilter()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'textFilter' => 1,
        ]);
    }

    public function testBadPrimaryFilter()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'primaryFilter' => 1,
        ]);
    }

    public function testBadWhere()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'where' => 1,
        ]);
    }

    public function testBadOrder1()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'order' => true,
        ]);
    }

    public function testBadOrder2()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = SearchParams::fromRaw([
            'order' => 'd',
        ]);
    }

    public function testMerge1()
    {
        $params1 = SearchParams::fromRaw([
            'boolFilterList' => ['f1', 'f2'],
            'textFilter' => 't1',
            'where' => [
                [
                    'type' => 'isTrue',
                    'attribute' => 't',
                ]
            ],
        ]);

        $params2 = SearchParams::fromRaw([
            'boolFilterList' => ['f2', 'f3'],
            'textFilter' => 't2',
            'where' => [
                [
                    'type' => 'isTrue',
                    'attribute' => 't2',
                ]
            ],
        ]);

        $params = SearchParams::merge($params1, $params2);

        $actual = $params->getRaw();

        $expected = [
            'boolFilterList' => ['f2', 'f3', 'f1'],
            'textFilter' => 't1',
            'where' => [
                [
                    'type' => 'isTrue',
                    'attribute' => 't2',
                ],
                [
                    'type' => 'isTrue',
                    'attribute' => 't',
                ],
            ],
            'select' => null,
            'orderBy' => null,
            'order' => null,
            'offset' => null,
            'maxSize' => null,
            'primaryFilter' => null,
            'noFullTextSearch' => false,
            'maxTextAttributeLength' => null,
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testCloning(): void
    {
        $rawWhere = [
            'type' => 'isTrue',
            'attribute' => 't',
        ];

        $params = SearchParams
            ::create()
            ->withBoolFilterList(['a'])
            ->withMaxSize(10)
            ->withOffset(0)
            ->withMaxTextAttributeLength(100)
            ->withNoFullTextSearch()
            ->withOrder('DESC')
            ->withOrderBy('name')
            ->withPrimaryFilter('test')
            ->withSelect(['name'])
            ->withTextFilter('test*')
            ->withWhere(WhereItem::fromRaw($rawWhere));

        $this->assertEquals(['a'], $params->getBoolFilterList());
        $this->assertEquals(10, $params->getMaxSize());
        $this->assertEquals(0, $params->getOffset());
        $this->assertEquals(100, $params->getMaxTextAttributeLength());
        $this->assertEquals(true, $params->noFullTextSearch());
        $this->assertEquals('DESC', $params->getOrder());
        $this->assertEquals('name', $params->getOrderBy());
        $this->assertEquals('test', $params->getPrimaryFilter());
        $this->assertEquals(['name'], $params->getSelect());
        $this->assertEquals('test*', $params->getTextFilter());
        $this->assertEquals('isTrue', $params->getWhere()->getRaw()['value'][0]['type']);
    }

    public function testWithWhereAdded1(): void
    {
        $params = SearchParams
            ::create()
            ->withWhere(
                WhereItem::fromRaw([
                    'type' => 'isTrue',
                    'attribute' => 'a1',
                ])
            )
            ->withWhereAdded(
                 WhereItem::fromRaw([
                    'type' => 'isTrue',
                    'attribute' => 'a2',
                ])
            );

        $where = $params->getWhere();

        $this->assertEquals(WhereItem::TYPE_AND, $where->getType());

        $this->assertEquals(
            [
                'type' => WhereItem::TYPE_AND,
                'value' => [
                    [
                        'type' => 'isTrue',
                        'attribute' => 'a1',
                        'value' => null,
                    ],
                    [
                        'type' => 'isTrue',
                        'attribute' => 'a2',
                        'value' => null,
                    ],
                ],
            ],
            $where->getRaw()
        );
    }

    public function testWithWhereAdded2(): void
    {
        $params = SearchParams
            ::create()
            ->withWhereAdded(
                 WhereItem::fromRaw([
                    'type' => 'isTrue',
                    'attribute' => 'a2',
                ])
            );

        $where = $params->getWhere();

        $this->assertEquals(WhereItem::TYPE_AND, $where->getType());

        $this->assertEquals(
            [
                'type' => WhereItem::TYPE_AND,
                'value' => [
                    [
                        'type' => 'isTrue',
                        'attribute' => 'a2',
                        'value' => null,
                    ],
                ],
            ],
            $where->getRaw()
        );
    }
}
