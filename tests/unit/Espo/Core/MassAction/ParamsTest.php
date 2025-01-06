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

namespace tests\unit\Espo\Core\MassAction;

use Espo\Core\{
    MassAction\Params,
    Select\SearchParams,
};

use RuntimeException;

class ParamsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
    }

    public function testFromRawIds()
    {
        $params = Params::fromRaw(
            [
                'ids' => ['1'],
                'entityType' => 'Test',
            ]
        );

        $this->assertEquals('Test', $params->getEntityType());

        $this->assertEquals(['1'], $params->getIds());

        $this->assertTrue($params->hasIds());
    }

    public function testFromIds()
    {
        $params = Params::createWithIds('Test', ['1']);

        $this->assertEquals('Test', $params->getEntityType());

        $this->assertEquals(['1'], $params->getIds());
    }

    public function testFromSearchParams()
    {
        $searchParams = $this->createMock(SearchParams::class);

        $params = Params::createWithSearchParams('Test', $searchParams);

        $this->assertEquals('Test', $params->getEntityType());

        $this->assertEquals($searchParams, $params->getSearchParams());
    }

    public function testFromRawSearchParams1()
    {
        $where = [
            [
                'type' => 'equals',
                'attribute' => 'name',
                'value' => 'test',
            ]
        ];

        $params = Params::fromRaw(
            [
                'where' => $where,
                'searchParams' => [
                    'primaryFilter' => 'testFilter',
                ],
            ],
            'Test'
        );

        $searchParams = $params->getSearchParams();

        $this->assertEquals('Test', $params->getEntityType());

        $this->assertEquals($where, $searchParams->getWhere()->getRaw()['value']);

        $this->assertEquals('testFilter', $searchParams->getPrimaryFilter());

        $this->assertFalse($params->hasIds());
    }

    public function testFromRawSearchParams2()
    {
        $where = [
            [
                'type' => 'equals',
                'attribute' => 'name',
                'value' => 'test',
            ]
        ];

        $params = Params::fromRaw(
            [
                'searchParams' => [
                    'primaryFilter' => 'testFilter',
                    'where' => $where,
                ],
            ],
            'Test'
        );

        $searchParams = $params->getSearchParams();

        $this->assertEquals($where, $searchParams->getWhere()->getRaw()['value']);

        $this->assertEquals('testFilter', $searchParams->getPrimaryFilter());

        $this->assertFalse($params->hasIds());
    }

    public function testFromRawSearchException1()
    {
        $this->expectException(RuntimeException::class);

        Params::fromRaw(
            [
                'ids' => ['1'],
                'searchParams' => [
                    'primaryFilter' => 'testFilter',
                    'where' => [],
                ],
            ],
            'Test'
        );
    }

    public function testSerialize1(): void
    {
        $params1 = Params::fromRaw(
            [
                'searchParams' => [
                    'primaryFilter' => 'testFilter',
                    'where' => [
                        [
                            'type' => 'equals',
                            'attribute' => 'name',
                            'value' => 'test',
                        ]
                    ],
                ],
            ],
            'Test'
        );

        /** @var Params $params2 */
        $params2 = unserialize(serialize($params1));

        $this->assertEquals($params1, $params2);

        $this->assertEquals('equals', $params2->getSearchParams()->getWhere()->getItemList()[0]->getType());
    }
}
