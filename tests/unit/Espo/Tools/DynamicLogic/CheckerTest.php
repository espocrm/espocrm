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

namespace tests\unit\Espo\Tools\DynamicLogic;

use DateTimeImmutable;
use DateTimeZone;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Tools\DynamicLogic\ConditionChecker;
use Espo\Tools\DynamicLogic\Item;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class CheckerTest extends TestCase
{
    /**
     * @param array<string, mixed> $map
     */
    private function initEntity(array $map): Entity
    {
        $entity = $this->createMock(Entity::class);

        $entity
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(fn ($attribute) => $map[$attribute] ?? null);

        return $entity;
    }

    private function createClock(): ClockInterface
    {
        return new class() implements ClockInterface {

            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2025-01-01 10:00:00', new DateTimeZone('UTC'));
            }
        };
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testEquals(): void
    {
        $map = [
            'k' => 'v',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $item = Item::fromItemDefinition((object) [
            'type' => 'equals',
            'attribute' => 'k',
            'value' => 'v',
        ]);

        $this->assertTrue($checker->check($item));

        $item = Item::fromItemDefinition((object) [
            'type' => 'equals',
            'attribute' => 'k',
            'value' => 'v1',
        ]);

        $this->assertFalse($checker->check($item));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testNotEquals(): void
    {
        $map = [
            'k' => 'v',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $item = Item::fromItemDefinition((object) [
            'type' => 'notEquals',
            'attribute' => 'k',
            'value' => 'v',
        ]);

        $this->assertFalse($checker->check($item));

        $item = Item::fromItemDefinition((object) [
            'type' => 'notEquals',
            'attribute' => 'k',
            'value' => 'v1',
        ]);

        $this->assertTrue($checker->check($item));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAnd(): void
    {
        $map = [
            'k1' => 'v1',
            'k2' => 'v2',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $item = Item::fromGroupDefinition([
            (object) [
                'type' => 'equals',
                'attribute' => 'k1',
                'value' => 'v1',
            ],
            (object) [
                'type' => 'equals',
                'attribute' => 'k2',
                'value' => 'v2',
            ]
        ]);

        $this->assertTrue($checker->check($item));

        $item = Item::fromGroupDefinition([
            (object) [
                'type' => 'equals',
                'attribute' => 'k1',
                'value' => 'v1',
            ],
            (object) [
                'type' => 'equals',
                'attribute' => 'k2',
                'value' => 'v2-modified',
            ]
        ]);

        $this->assertFalse($checker->check($item));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testOr(): void
    {
        $map = [
            'k1' => 'v1',
            'k2' => 'v2',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $item = Item::fromGroupDefinition([
            (object) [
                'type' => 'or',
                'value' => [
                    (object) [
                        'type' => 'equals',
                        'attribute' => 'k1',
                        'value' => 'v1-m',
                    ],
                    (object) [
                        'type' => 'equals',
                        'attribute' => 'k2',
                        'value' => 'v2',
                    ],
                ],
            ]
        ]);

        $this->assertTrue($checker->check($item));

        $item = Item::fromGroupDefinition([
            (object) [
                'type' => 'or',
                'value' => [
                    (object) [
                        'type' => 'equals',
                        'attribute' => 'k1',
                        'value' => 'v1-m',
                    ],
                    (object) [
                        'type' => 'equals',
                        'attribute' => 'k2',
                        'value' => 'v2-m',
                    ],
                ],
            ]
        ]);

        $this->assertFalse($checker->check($item));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testNot(): void
    {
        $map = [
            'k1' => 'v1',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $item = Item::fromGroupDefinition([
            (object) [
                'type' => 'not',
                'value' => (object) [
                    'type' => 'equals',
                    'attribute' => 'k1',
                    'value' => 'v1',
                ],
            ]
        ]);

        $this->assertFalse($checker->check($item));

        $item = Item::fromGroupDefinition([
            (object) [
                'type' => 'not',
                'value' => (object) [
                    'type' => 'equals',
                    'attribute' => 'k1',
                    'value' => 'v1-m',
                ],
            ]
        ]);

        $this->assertTrue($checker->check($item));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testIsEmpty(): void
    {
        $map = [
            'k1' => 'v1',
            'k' => '',
            'array' => [],
            'null' => null,
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isEmpty',
                        'attribute' => 'k',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isEmpty',
                        'attribute' => 'array',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isEmpty',
                        'attribute' => 'null',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isEmpty',
                        'attribute' => 'nonExistent',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isEmpty',
                        'attribute' => 'k1',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testIsNotEmpty(): void
    {
        $map = [
            'k1' => 'v1',
            'k' => '',
            'array' => [],
            'null' => null,
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isNotEmpty',
                        'attribute' => 'k',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isNotEmpty',
                        'attribute' => 'array',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isNotEmpty',
                        'attribute' => 'null',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isNotEmpty',
                        'attribute' => 'nonExistent',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isNotEmpty',
                        'attribute' => 'k1',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testIsTrue(): void
    {
        $map = [
            'k1' => true,
            'k2' => false,
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isTrue',
                        'attribute' => 'k1',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isTrue',
                        'attribute' => 'k2',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isTrue',
                        'attribute' => 'nonExistent',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testIsFalse(): void
    {
        $map = [
            'k1' => true,
            'k2' => false,
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isFalse',
                        'attribute' => 'k1',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isFalse',
                        'attribute' => 'k2',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isFalse',
                        'attribute' => 'nonExistent',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testContains(): void
    {
        $map = [
            'string' => '_test_',
            'array' => ['test'],
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'contains',
                        'attribute' => 'string',
                        'value' => 'test',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'contains',
                        'attribute' => 'string',
                        'value' => 'hello',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'contains',
                        'attribute' => 'array',
                        'value' => 'test',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'contains',
                        'attribute' => 'array',
                        'value' => 'hello',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testNotContains(): void
    {
        $map = [
            'string' => '_test_',
            'array' => ['test'],
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'notContains',
                        'attribute' => 'string',
                        'value' => 'test',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'notContains',
                        'attribute' => 'string',
                        'value' => 'hello',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'notContains',
                        'attribute' => 'array',
                        'value' => 'test',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'notContains',
                        'attribute' => 'array',
                        'value' => 'hello',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testHas(): void
    {
        $map = [
            'array' => ['test'],
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'has',
                        'attribute' => 'array',
                        'value' => 'test',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'has',
                        'attribute' => 'array',
                        'value' => 'hello',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testNotHas(): void
    {
        $map = [
            'array' => ['test'],
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'notHas',
                        'attribute' => 'array',
                        'value' => 'test',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'notHas',
                        'attribute' => 'array',
                        'value' => 'hello',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testIn(): void
    {
        $map = [
            'value' => 'test',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'in',
                        'attribute' => 'value',
                        'value' => ['test'],
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'in',
                        'attribute' => 'value',
                        'value' => ['hello'],
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testNotIn(): void
    {
        $map = [
            'value' => 'test',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'notIn',
                        'attribute' => 'value',
                        'value' => ['test'],
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'notIn',
                        'attribute' => 'value',
                        'value' => ['hello'],
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testStartsWith(): void
    {
        $map = [
            'string' => '_test+',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'startsWith',
                        'attribute' => 'string',
                        'value' => '_',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'startsWith',
                        'attribute' => 'string',
                        'value' => '+',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testEndsWith(): void
    {
        $map = [
            'string' => '_test+',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'endsWith',
                        'attribute' => 'string',
                        'value' => '+',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'endsWith',
                        'attribute' => 'string',
                        'value' => '_',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testMatches(): void
    {
        $map = [
            'string' => '11111-222222-33',
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'matches',
                        'attribute' => 'string',
                        'value' => '/^\d{5}-\d{6}-\d{2}$/',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'matches',
                        'attribute' => 'string',
                        'value' => '/^\d{5}-\d{6}-\d{3}$/',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGreaterThan(): void
    {
        $map = [
            'value' => 1,
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'greaterThan',
                        'attribute' => 'value',
                        'value' => 0,
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'greaterThan',
                        'attribute' => 'value',
                        'value' => 1,
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'greaterThan',
                        'attribute' => 'value',
                        'value' => 2,
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLessThan(): void
    {
        $map = [
            'value' => 1,
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'lessThan',
                        'attribute' => 'value',
                        'value' => 2,
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'lessThan',
                        'attribute' => 'value',
                        'value' => 1,
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'lessThan',
                        'attribute' => 'value',
                        'value' => 0,
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGreaterThanOrEquals(): void
    {
        $map = [
            'value' => 1,
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'greaterThanOrEquals',
                        'attribute' => 'value',
                        'value' => 0,
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'greaterThanOrEquals',
                        'attribute' => 'value',
                        'value' => 1,
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'greaterThanOrEquals',
                        'attribute' => 'value',
                        'value' => 2,
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLessThanOrEquals(): void
    {
        $map = [
            'value' => 1,
        ];

        $checker = new ConditionChecker($this->initEntity($map));

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'lessThanOrEquals',
                        'attribute' => 'value',
                        'value' => 2,
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'lessThanOrEquals',
                        'attribute' => 'value',
                        'value' => 1,
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'lessThanOrEquals',
                        'attribute' => 'value',
                        'value' => 0,
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testIsToday(): void
    {
        $map = [
            'dateTime' => '2025-01-01 15:00:00',
            'dateTimeNext' => '2025-01-02 15:00:00',
            'date' => '2025-01-01',
            'dateNext' => '2025-01-02',
        ];

        $checker = new ConditionChecker(
            entity: $this->initEntity($map),
            clock: $this->createClock(),
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isToday',
                        'attribute' => 'dateTime',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isToday',
                        'attribute' => 'date',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isToday',
                        'attribute' => 'dateTimeNext',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'isToday',
                        'attribute' => 'dateNext',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testInFuture(): void
    {
        $map = [
            'dateTime' => '2025-01-01 10:00:00',
            'dateTimeNext' => '2025-01-02 15:00:00',
            'date' => '2025-01-01',
            'dateNext' => '2025-01-02',
        ];

        $checker = new ConditionChecker(
            entity: $this->initEntity($map),
            clock: $this->createClock(),
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'inFuture',
                        'attribute' => 'dateTime',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'inFuture',
                        'attribute' => 'date',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'inFuture',
                        'attribute' => 'dateTimeNext',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'inFuture',
                        'attribute' => 'dateNext',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testInPast(): void
    {
        $map = [
            'dateTime' => '2025-01-01 10:00:00',
            'dateTimePrevious' => '2025-01-01 01:00:00',
            'date' => '2025-01-01',
            'datePrevious' => '2024-12-31',
        ];

        $checker = new ConditionChecker(
            entity: $this->initEntity($map),
            clock: $this->createClock(),
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'inPast',
                        'attribute' => 'dateTime',
                    ]
                ])
            )
        );

        $this->assertFalse(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'inPast',
                        'attribute' => 'date',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'inPast',
                        'attribute' => 'dateTimePrevious',
                    ]
                ])
            )
        );

        $this->assertTrue(
            $checker->check(
                Item::fromGroupDefinition([
                    (object) [
                        'type' => 'inPast',
                        'attribute' => 'datePrevious',
                    ]
                ])
            )
        );
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUserId(): void
    {
        $map = [];

        $user = $this->createMock(User::class);

        $user->expects($this->any())
            ->method('getId')
            ->willReturn('ID');

        $checker = new ConditionChecker($this->initEntity($map), $user);

        $item = Item::fromItemDefinition((object) [
            'type' => 'equals',
            'attribute' => '$user.id',
            'value' => 'ID',
        ]);

        $this->assertTrue($checker->check($item));

        $item = Item::fromItemDefinition((object) [
            'type' => 'equals',
            'attribute' => '$user.id',
            'value' => 'BAD',
        ]);

        $this->assertFalse($checker->check($item));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUserTeamsIds(): void
    {
        $map = [];

        $user = $this->createMock(User::class);

        $user->expects($this->any())
            ->method('getTeamIdList')
            ->willReturn(['ID']);

        $checker = new ConditionChecker($this->initEntity($map), $user);

        $item = Item::fromItemDefinition((object) [
            'type' => 'contains',
            'attribute' => '$user.teamsIds',
            'value' => 'ID',
        ]);

        $this->assertTrue($checker->check($item));
    }
}
