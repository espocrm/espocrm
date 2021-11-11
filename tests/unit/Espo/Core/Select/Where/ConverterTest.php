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
    Select\Where\Converter,
    Select\Where\Item,
    Select\Where\Scanner,
    Select\Where\ItemConverterFactory,
    Select\Where\ItemGeneralConverter,
    Select\Where\DateTimeItemTransformer,
    Select\Where\ItemConverter,
    Select\Helpers\RandomStringGenerator,
    Utils\Config,
    Utils\Metadata,
};

use Espo\{
    ORM\EntityManager,
    ORM\Entity,
    ORM\Metadata as ormMetadata,
    ORM\Query\SelectBuilder as QueryBuilder,
    ORM\Query\Part\WhereClause,
    ORM\QueryBuilder as BaseQueryBuilder,
    ORM\Query\Select,
    ORM\Defs as ORMDefs,
    ORM\Defs\EntityDefs,
    Entities\User,
};

class ConverterTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->entityType = 'Test';

        $this->user = $this->createMock(User::class);

        $this->config = $this->createMock(Config::class);
        $this->metadata = $this->createMock(Metadata::class);

        $this->scanner = $this->createMock(Scanner::class);
        $this->randomStringGenerator = $this->createMock(RandomStringGenerator::class);
        $this->itemConverterFactory = $this->createMock(ItemConverterFactory::class);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->ormMetadata = $this->createMock(ormMetadata::class);

        $this->baseQueryBuilder = $this->createMock(BaseQueryBuilder::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($this->ormMetadata);

        $this->entityManager
            ->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->baseQueryBuilder);

        $this->ormDefs = $this->createMock(ORMDefs::class);

        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $this->randomStringGenerator
            ->expects($this->any())
            ->method('generate')
            ->willReturn('Random');

        $this->dateTimeItemTransformer = new DateTimeItemTransformer(
            $this->user,
            $this->config
        );

        $this->itemConverter = new ItemGeneralConverter(
            $this->entityType,
            $this->user,
            $this->dateTimeItemTransformer,
            $this->scanner,
            $this->itemConverterFactory,
            $this->randomStringGenerator,
            $this->entityManager,
            $this->ormDefs,
            $this->config,
            $this->metadata
        );

        $this->converter = new Converter(
            $this->entityType,
            $this->itemConverter,
            $this->scanner,
            $this->randomStringGenerator,
            $this->ormDefs
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
            ->method('applyLeftJoins')
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

    public function testConvertInCategoryManyMany()
    {
        $this->ormDefs
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->entityType)
            ->willReturn(
                EntityDefs::fromRaw(
                    [
                        'relations' => [
                            'test' => [
                                'type' => Entity::MANY_MANY,
                                'entity' => 'Foreign',
                                'midKeys' => ['localId', 'foreignId'],
                            ],
                        ],
                    ],
                    $this->entityType
                )
            );

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'inCategory',
                    'attribute' => 'test',
                    'value' => 'value',
                ],
            ],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct');

        $this->queryBuilder
            ->method('join')
            ->withConsecutive(
                [
                    'test',
                    'testInCategoryFilter',
                ],
                [
                    'ForeignPath',
                    'foreignPath',
                    [
                         "foreignPath.descendorId:" => "testInCategoryFilterMiddle.foreignId",
                    ]
                ]
            );

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            'foreignPath.ascendorId' => 'value',
        ];

        $this->assertEquals($expected, $whereClause->getRaw());
    }

    public function testConvertInCategoryBelongsTo()
    {
        $this->ormDefs
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->entityType)
            ->willReturn(
                EntityDefs::fromRaw(
                    [
                        'relations' => [
                            'test' => [
                                'type' => Entity::BELONGS_TO,
                                'entity' => 'Foreign',
                                'key' => 'foreignId',
                            ],
                        ],
                    ],
                    $this->entityType
                )
            );

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'inCategory',
                    'attribute' => 'test',
                    'value' => 'value',
                ],
            ],
        ]);

        $this->queryBuilder
            ->expects($this->never())
            ->method('distinct');

        $this->queryBuilder
            ->method('join')
            ->withConsecutive(
                [
                    'ForeignPath',
                    'foreignPath',
                    [
                        "foreignPath.descendorId:" => "foreignId",
                    ]
                ]
            );

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            'foreignPath.ascendorId' => 'value',
        ];

        $this->assertEquals($expected, $whereClause->getRaw());
    }

    public function testConvertIsUserFromTeams()
    {
        $this->ormDefs
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->entityType)
            ->willReturn(
                EntityDefs::fromRaw(
                    [
                        'relations' => [
                            'user' => [
                                'type' => Entity::BELONGS_TO,
                                'entity' => 'User',
                                'key' => 'userId',
                            ],
                        ],
                    ],
                    $this->entityType
                )
            );

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'isUserFromTeams',
                    'attribute' => 'user',
                    'value' => 'valueTeamId',
                ],
            ],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct');

        $aliasName = 'userIsUserFromTeamsFilterRandom';

        $this->queryBuilder
            ->method('join')
            ->withConsecutive(
                [
                    'TeamUser',
                    $aliasName . 'Middle',
                    [
                        $aliasName . 'Middle.userId:' => 'userId',
                        $aliasName . 'Middle.deleted' => false,
                    ]
                ]
            );

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            $aliasName . 'Middle.teamId' => 'valueTeamId',
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

        $sqQueryBuilder = $this->createMock(QueryBuilder::class);

        $this->baseQueryBuilder
            ->expects($this->once())
            ->method('select')
            ->willReturn($sqQueryBuilder);

        $sqQueryBuilder
            ->expects($this->once())
            ->method('from')
            ->with($this->entityType)
            ->willReturn($sqQueryBuilder);

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

        $this->scanner
            ->method('applyLeftJoins')
            ->withConsecutive(
                [
                    $sqQueryBuilder, $sqItem
                ],
                [
                    $this->queryBuilder, $item
                ]
            );

        $query = Select::fromRaw([
            'select' => ['id'],
            'from' => $this->entityType,
            'leftJoins' => [['test']],
            'joins' => [],
        ]);

        $sqQueryBuilder
            ->expects($this->once())
            ->method('build')
            ->willReturn($query);

        $whereClause = $this->converter->convert($this->queryBuilder, $item);

        $expected = [
            'id=s' => [
                'select' => ['id'],
                'from' => $this->entityType,
                'whereClause' => [
                    'AND' => [
                        ['test1=' => 'value1'],
                    ],
                ],
                'leftJoins' => [['test']],
                'joins' => [],
            ],
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
                            ],
                        ],
                    ],
                    $this->entityType
                )
            );

        $alias = $link . 'LinkedWithFilterRandom';

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct');

        $this->queryBuilder
            ->method('leftJoin')
            ->withConsecutive(
                [
                    $link,
                    $alias,
                ]
            );

        $expected = [
            $alias . 'Middle.foreignId' => $value,
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
