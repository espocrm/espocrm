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

use Espo\Core\Select\Helpers\RandomStringGenerator;
use Espo\Core\Select\Where\Converter;
use Espo\Core\Select\Where\DefaultDateTimeItemTransformer;
use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverter;
use Espo\Core\Select\Where\ItemConverterFactory;
use Espo\Core\Select\Where\ItemGeneralConverter;
use Espo\Core\Select\Where\Scanner;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;

use Espo\Entities\User;
use Espo\ORM\Defs as ORMDefs;
use Espo\ORM\Defs\EntityDefs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Metadata as ormMetadata;
use Espo\ORM\Query\Part\Join\JoinType;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\QueryBuilder as BaseQueryBuilder;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    private $entityType;
    private $user;
    private $scanner;
    private $itemConverterFactory;
    private $ormDefs;
    private $queryBuilder;
    private $converter;

    protected function setUp() : void
    {
        $this->entityType = 'Test';

        $this->user = $this->createMock(User::class);

        $config = $this->createMock(Config::class);
        $applicationConfig = $this->createMock(Config\ApplicationConfig::class);
        $metadata = $this->createMock(Metadata::class);

        $this->scanner = $this->createMock(Scanner::class);
        $randomStringGenerator = $this->createMock(RandomStringGenerator::class);
        $this->itemConverterFactory = $this->createMock(ItemConverterFactory::class);

        $entityManager = $this->createMock(EntityManager::class);
        $ormMetadata = $this->createMock(ormMetadata::class);

        $baseQueryBuilder = $this->createMock(BaseQueryBuilder::class);

        $entityManager
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($ormMetadata);

        $entityManager
            ->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($baseQueryBuilder);

        $this->ormDefs = $this->createMock(ORMDefs::class);

        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $randomStringGenerator
            ->expects($this->any())
            ->method('generate')
            ->willReturn('Random');

        $applicationConfig
            ->expects($this->any())
            ->method('getTimeZone')
            ->willReturn('UTC');

        $dateTimeItemTransformer = new DefaultDateTimeItemTransformer($config, $applicationConfig);

        $itemConverter = new ItemGeneralConverter(
            $this->entityType,
            $this->user,
            $dateTimeItemTransformer,
            $this->scanner,
            $this->itemConverterFactory,
            $randomStringGenerator,
            $this->ormDefs,
            $config,
            $metadata,
            $applicationConfig,
        );

        $this->converter = new Converter(
            $itemConverter,
            $this->scanner
        );
    }

    public function testConvertApplyLeftJoins()
    {
        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
            ],
        ]);

        $this->scanner
            ->expects($this->once())
            ->method('apply')
            ->with($this->queryBuilder, $item);

        $this->converter->convert($this->queryBuilder, $item);
    }

    public function testConvertEquals1()
    {
        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test',
                    'value' => 'test-value',
                ],
            ],
        ]);

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            'test=' => 'test-value',
        ];

        $this->assertEquals($expected, $whereClause->getRaw());
    }

    public function testConvertEquals2()
    {
        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test1',
                    'value' => 'value1',
                ],
                [
                    'type' => 'equals',
                    'attribute' => 'test2',
                    'value' => 'value2',
                ],
            ],
        ]);

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            ['test1=' => 'value1'],
            ['test2=' => 'value2'],
        ];

        $this->assertEquals($expected, $whereClause->getRaw());
    }

    public function testConvertOr()
    {
        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'or',
                    'value' => [
                        [
                            'type' => 'equals',
                            'attribute' => 'test1',
                            'value' => 'value1',
                        ],
                        [
                            'type' => 'notEquals',
                            'attribute' => 'test2',
                            'value' => 'value2',
                        ],
                    ],
                ],

            ],
        ]);

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            'OR' => [
                ['test1=' => 'value1'],
                ['test2!=' => 'value2'],
            ],
        ];

        $this->assertEquals($expected, $whereClause->getRaw());
    }

    public function testConvertDateTimeOn1()
    {
        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'on',
                    'attribute' => 'test',
                    'value' => '2020-12-20',
                    'dateTime' => true,
                    'timeZone' => 'Europe/Kiev',
                ],
            ],
        ]);

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            'AND' => [
                'test>=' => '2020-12-19 22:00:00',
                'test<=' => '2020-12-20 21:59:59',
            ],
        ];

        $this->assertEquals($expected, $whereClause->getRaw());
    }

    public function testConvertSubQueryIn()
    {
        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'subQueryIn',
                    'value' => [
                        [
                            'type' => 'equals',
                            'attribute' => 'test1',
                            'value' => 'value1',
                        ],
                    ],
                ],

            ],
        ]);

        $sqItem = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test1',
                    'value' => 'value1',
                ],
            ],
        ]);

        $c = $this->exactly(2);

        $this->scanner
            ->expects($c)
            ->method('apply')
            ->willReturnCallback(function ($qb, $it) use ($c, $sqItem, $item) {
                if ($c->numberOfInvocations() === 1) {
                    $this->assertEquals($sqItem, $it);
                }

                if ($c->numberOfInvocations() === 2) {
                    $this->assertEquals($item, $it);
                }
            });

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            'id=s' => Select::fromRaw([
                'select' => ['id'],
                'from' => $this->entityType,
                'whereClause' => [
                    'AND' => [
                        ['test1=' => 'value1'],
                    ],
                ],
                'leftJoins' => [],
                'joins' => [],
            ]),
        ];

        $this->assertEquals($expected, $whereClause->getRaw());
    }

    public function testConvertLinkedWith1()
    {
        $link = 'test';

        $value = ['value-id'];

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'linkedWith',
                    'attribute' => $link,
                    'value' => $value,
                ],
            ],
        ]);

        $this->ormDefs
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->entityType)
            ->willReturn(
                EntityDefs::fromRaw(
                    [
                        'relations' => [
                            $link => [
                                'type' => Entity::MANY_MANY,
                                'entity' => 'Foreign',
                                'midKeys' => ['localId', 'foreignId'],
                                'relationName' => 'entityEntity'
                            ],
                        ],
                    ],
                    $this->entityType
                )
            );

        $alias = $link . 'LinkedWithFilterRandom';

        $this->queryBuilder
            ->expects($this->never())
            ->method('distinct');


        $expected = [
            'id=s' => Select::fromRaw([
                'select' => ['id'],
                'from' => 'Test',
                'joins' => [
                    [
                        'test',
                        $alias,
                        [$alias . '.localId=:' => 'id'],
                        [
                            'noLeftAlias' => true,
                            'onlyMiddle' => true,
                            'type' => JoinType::left,
                        ],
                    ],
                ],
                'whereClause' => [
                    $alias . '.foreignId' => $value,
                ],
            ]),
        ];

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $this->assertEquals($expected, $whereClause->getRaw());
    }

    public function testConvertCustomConverter()
    {
        $type = 'testType';
        $attribute = 'testAttribute';

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => $type,
                    'attribute' => $attribute,
                    'value' => 'test-value',
                ],
            ],
        ]);

        $subItem = Item::fromRaw([
            'type' => $type,
            'attribute' => $attribute,
            'value' => 'test-value',
        ]);

        $converter = $this->createMock(ItemConverter::class);

        $converter
            ->expects($this->once())
            ->method('convert')
            ->with($this->queryBuilder, $subItem)
            ->willReturn(
                WhereClause::fromRaw([
                    'testAnother=' => 'test-value',
                ])
            );

        $this->itemConverterFactory
            ->expects($this->once())
            ->method('has')
            ->with($this->entityType, $attribute, $type)
            ->willReturn(true);

        $this->itemConverterFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->entityType, $attribute, $type, $this->user)
            ->willReturn($converter);

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            'testAnother=' => 'test-value',
        ];

        $this->assertEquals($expected, $whereClause->getRaw());
    }
}
