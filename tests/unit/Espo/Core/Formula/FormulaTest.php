<?php /** @noinspection PhpUnhandledExceptionInspection */

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

/** @noinspection PhpUnhandledExceptionInspection */

namespace tests\unit\Espo\Core\Formula;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\FieldProcessing\SpecificFieldLoader;
use Espo\Core\Formula\AttributeFetcher;
use Espo\Core\Formula\Parser\Ast\Attribute;
use Espo\Core\Formula\Parser\Ast\Node;
use Espo\Core\Formula\Parser\Ast\Value;
use Espo\Core\Formula\Parser\Ast\Variable;
use Espo\Core\Formula\Processor;
use Espo\Core\Formula\Argument;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\NumberUtil;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\ORM\Entity as Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\InjectableFactory;
use Espo\ORM\Repository\RDBRelation;
use Espo\ORM\Repository\RDBRepository;
use PHPUnit\Framework\TestCase;
use stdClass;
use tests\unit\ContainerMocker;

class FormulaTest extends TestCase
{
    private $entity;
    private $entityManager;
    private $applicationConfig;
    private $container;

    protected function setUp() : void
    {
        $this->entity = $this->getEntityMock();
        $this->entityManager = $this->createMock(EntityManager::class);

        date_default_timezone_set('UTC');

        $dateTime = new DateTime();

        $number = new NumberUtil();

        $config = $this->createMock(Config::class);
        $config
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['timeZone', null, 'UTC']
            ]);

        $this->applicationConfig = $this->createMock(Config\ApplicationConfig::class);
        $this->applicationConfig
            ->expects($this->any())
            ->method('getTimeZone')
            ->willReturn('UTC');

        $user = $this->createMock(User::class);
        $log = $this->createMock(Log::class);

        $user->set('id', '1');

        $user
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['id', '1']
            ]);

        $containerMocker = new ContainerMocker($this);

        $this->container = $containerMocker->create([
            'entityManager' => $this->entityManager,
            'dateTime' => $dateTime,
            'number' => $number,
            'config' => $config,
            'user' => $user,
            'log' => $log,
        ]);
    }

    private static function stringToNode(string $string): mixed
    {
        $obj = json_decode($string);

        return self::stdClassToNode($obj);
    }

    private static function stdClassToNode(stdClass $obj): mixed
    {
        if ($obj->type === 'value') {
            return new Value($obj->value);
        }

        if ($obj->type === 'variable') {
            return new Variable($obj->value);
        }

        if ($obj->type === 'attribute') {
            return new Attribute($obj->value);
        }

        $childNodes = array_map(fn ($item) => self::stdClassToNode($item), $obj->value ?? []);

        return new Node($obj->type, $childNodes);
    }

    protected function createProcessor($variables = null, ?Entity $entity = null)
    {
        $injectableFactory = new InjectableFactory(
            $this->container,
            BindingContainerBuilder::create()
                ->bindInstance(Config\ApplicationConfig::class, $this->applicationConfig)
                ->build()
        );

        $fieldUtil = $this->createMock(FieldUtil::class);
        $loader = $this->createMock(SpecificFieldLoader::class);

        $attributeFetcher = new AttributeFetcher($this->entityManager, $fieldUtil, $loader);

        return new Processor(
            $injectableFactory,
            $attributeFetcher,
            null,
            $entity ?? $this->entity,
            $variables
        );
    }

    protected function getEntityMock()
    {
        return $this->getMockBuilder(Entity::class)->disableOriginalConstructor()->getMock();
    }

    protected function setEntityAttributes($entity, $attributes)
    {
        $map = [];
        foreach ($attributes as $key => $value) {
            $map[] = [$key, $value];
        }

        $entity
            ->expects($this->any())
            ->method('get')
            ->willReturnMap($map);
    }

    protected function setEntityFetchedAttributes($entity, $attributes)
    {
        $map = [];
        foreach ($attributes as $key => $value) {
            $map[] = [$key, $value];
        }

        $entity
            ->expects($this->any())
            ->method('getFetched')
            ->willReturnMap($map);
    }

    function testAttribute()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "attribute",
                "value": "name"
            }
        '));

        $this->setEntityAttributes($this->entity, [
            'name' => 'Test'
        ]);

        $result = $this->createProcessor()->process($item);

        $this->assertEquals('Test', $result);
    }

    function testEntityAttribute()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "entity\\\\attribute",
                "value": [
                    {
                        "type": "value",
                        "value": "name"
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, [
            'name' => 'Test'
        ]);

        $result = $this->createProcessor()->process($item);

        $this->assertEquals('Test', $result);
    }

    function testEntityAttributeFetched()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "entity\\\\attributeFetched",
                "value": [
                    {
                        "type": "value",
                        "value": "name"
                    }
                ]
            }
        '));

        $this->setEntityFetchedAttributes($this->entity, [
            'name' => 'Test'
        ]);

        $result = $this->createProcessor()->process($item);

        $this->assertEquals('Test', $result);
    }

    function testIsAttributeChanged()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "entity\\\\isAttributeChanged",
                "value": [
                    {
                        "type": "value",
                        "value": "name"
                    }
                ]
            }
        '));

        $this->setEntityFetchedAttributes($this->entity, [
            'name' => 'Test'
        ]);

        $this->entity
            ->expects($this->once())
            ->method('isAttributeChanged')
            ->willReturn(true);

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testIsAttributeNotChanged()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "entity\\\\isAttributeNotChanged",
                "value": [
                    {
                        "type": "value",
                        "value": "name"
                    }
                ]
            }
        '));

        $this->setEntityFetchedAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $this->entity
            ->expects($this->once())
            ->method('isAttributeChanged')
            ->willReturn(false);

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testAddLinkMultipleId()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "entity\\\\addLinkMultipleId",
                "value": [
                    {
                        "type": "value",
                        "value": "teams"
                    },
                    {
                        "type": "value",
                        "value": "1"
                    }
                ]
            }
        '));

        $entity = $this->createMock(CoreEntity::class);

        $this->setEntityAttributes($entity, [
            'teamsIds' => ['2']
        ]);

        $entity
            ->expects($this->any())
            ->method('addLinkMultipleId')
            ->with('teams', '1');

        $this->createProcessor(null, $entity)->process($item);

        $this->assertTrue(true);
    }

    function testRemoveLinkMultipleId()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "entity\\\\removeLinkMultipleId",
                "value": [
                    {
                        "type": "value",
                        "value": "teams"
                    },
                    {
                        "type": "value",
                        "value": "1"
                    }
                ]
            }
        '));

        $entity = $this->createMock(CoreEntity::class);

        $this->setEntityAttributes($entity, [
            'teamsIds' => ['1', '2']
        ]);

        $entity
            ->expects($this->any())
            ->method('removeLinkMultipleId')
            ->with('teams', '1');

        $this->createProcessor(null, $entity)->process($item);

        $this->assertTrue(true);
    }

    function testAnd()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "logical\\\\and",
                "value": [
                    {
                        "type": "comparison\\\\equals",
                        "value": [
                            {
                                "type": "attribute",
                                "value": "name"
                            },
                            {
                                "type": "value",
                                "value": "Test"
                            }
                        ]
                    },
                    {
                        "type": "comparison\\\\notEquals",
                        "value": [
                            {
                                "type": "attribute",
                                "value": "name"
                            },
                            {
                                "type": "value",
                                "value": "Hello"
                            }
                        ]
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testOr()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "logical\\\\or",
                "value": [
                    {
                        "type": "value",
                        "value": true
                    },
                    {
                        "type": "value",
                        "value": false
                    }
                ]
            }
        '));

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testAndFalse()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "logical\\\\and",
                "value": [
                    {
                        "type": "value",
                        "value": false
                    },
                    {
                        "type": "value",
                        "value": false
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertFalse($result);
    }

    function testNot()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "logical\\\\not",
                "value": [{
                    "type": "logical\\\\or",
                    "value": [
                        {
                            "type": "comparison\\\\equals",
                            "value": [
                                {
                                    "type": "value",
                                    "value": "Test"
                                },
                                {
                                    "type": "attribute",
                                    "value": "name"
                                }
                            ]
                        },
                        {
                            "type": "comparison\\\\notEquals",
                            "value": [
                                {
                                    "type": "value",
                                    "value": "Hello"
                                },
                                {
                                    "type": "attribute",
                                    "value": "name"
                                }
                            ]
                        }
                    ]
                }]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertFalse($result);
    }

    function testConcatenation()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\concatenation",
                "value": [
                    {
                        "type": "value",
                        "value": "Hello"
                    },
                    {
                        "type": "value",
                        "value": " "
                    },
                    {
                        "type": "attribute",
                        "value": "name"
                    },
                    {
                        "type": "string\\\\concatenation",
                        "value": [
                            {
                                "type": "value",
                                "value": " "
                            },
                            {
                                "type": "value",
                                "value": "1"
                            },
                            {
                                "type": "value",
                                "value": "2"
                            }
                        ]
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertEquals('Hello Test 12', $result);
    }

    function testStringLength()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\length",
                "value": [
                    {
                        "type": "value",
                        "value": "TestHello"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(9, $actual);
    }

    function testStringContains()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\contains",
                "value": [
                    {
                        "type": "value",
                        "value": "TestHello"
                    },
                    {
                        "type": "value",
                        "value": "Test"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertTrue($actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\contains",
                "value": [
                    {
                        "type": "value",
                        "value": "TestHello"
                    },
                    {
                        "type": "value",
                        "value": "Hello"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertTrue($actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\contains",
                "value": [
                    {
                        "type": "value",
                        "value": "TestHello"
                    },
                    {
                        "type": "value",
                        "value": "Hello1"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertFalse($actual);
    }

    function testStringTest()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\test",
                "value": [
                    {
                        "type": "value",
                        "value": "TestHelloMan"
                    },
                    {
                        "type": "value",
                        "value": "/hello/i"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertTrue($actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\test",
                "value": [
                    {
                        "type": "value",
                        "value": "TestHelloMan"
                    },
                    {
                        "type": "value",
                        "value": "/Nope/i"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertFalse($actual);
    }

    function testSummationAndDivision()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "numeric\\\\summation",
                "value": [
                    {
                        "type": "value",
                        "value": 2
                    },
                    {
                        "type": "value",
                        "value": 3
                    },
                    {
                        "type": "attribute",
                        "value": "amount"
                    },
                    {
                        "type": "numeric\\\\subtraction",
                        "value": [
                            {
                                "type": "value",
                                "value": 5
                            },
                            {
                                "type": "value",
                                "value": 2
                            }
                        ]
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 4
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertEquals(2 + 3 + 4 + (5 - 2), $result);
    }

    function testMultiplication()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "numeric\\\\multiplication",
                "value": [
                    {
                        "type": "value",
                        "value": 2
                    },
                    {
                        "type": "value",
                        "value": 3
                    },
                    {
                        "type": "attribute",
                        "value": "amount"
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 4.2
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertEquals(2 * 3 * 4.2, $result);
    }

    function testDivision()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "numeric\\\\division",
                "value": [
                    {
                        "type": "value",
                        "value": 3
                    },
                    {
                        "type": "value",
                        "value": 2
                    }
                ]
            }
        '));

        $result = $this->createProcessor()->process($item);

        $this->assertEquals(3 / 2, $result);
    }

    function testModulo()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "numeric\\\\modulo",
                "value": [
                    {
                        "type": "value",
                        "value": 124
                    },
                    {
                        "type": "value",
                        "value": 5
                    }
                ]
            }
        '));

        $result = $this->createProcessor()->process($item);

        $this->assertEquals(124 % 5, $result);
    }

    function testIfThenElse1()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "ifThenElse",
                "value": [
                    {
                        "type": "condition",
                        "value": [
                            {
                                "type": "logical\\\\and",
                                "value": [
                                    {
                                        "type": "comparison\\\\equals",
                                        "value": [
                                            {
                                                "type": "attribute",
                                                "value": "test"
                                            },
                                            {
                                                "type": "value",
                                                "value": true
                                            }
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "type": "value",
                        "value": 2
                    },
                    {
                        "type": "value",
                        "value": 1
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'test' => true
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertEquals(2, $result);
    }

    function testIfThenElse2()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "ifThenElse",
                "value": [
                    {
                        "type": "condition",
                        "value": [
                            {
                                "type": "logical\\\\and",
                                "value": [
                                    {
                                        "type": "comparison\\\\equals",
                                        "value": [
                                            {
                                                "type": "attribute",
                                                "value": "test"
                                            },
                                            {
                                                "type": "value",
                                                "value": true
                                            }
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "type": "value",
                        "value": 2
                    },
                    {
                        "type": "value",
                        "value": 1
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'test' => false,
            'amount' => 3
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertEquals(1, $result);
    }

    function testIfThen1()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "ifThen",
                "value": [
                    {
                        "type": "condition",
                        "value": [
                            {
                                "type": "logical\\\\and",
                                "value": [
                                    {
                                        "type": "comparison\\\\equals",
                                        "value": [
                                            {
                                                "type": "attribute",
                                                "value": "test"
                                            },
                                            {
                                                "type": "value",
                                                "value": true
                                            }
                                        ]
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "type": "value",
                        "value": 2
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, [
            'test' => true,
        ]);

        $result = $this->createProcessor()->process($item);

        $this->assertEquals(2, $result);
    }

    function testComparisonEquals()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\equals",
                "value": [
                    {
                        "type": "attribute",
                        "value": "amount"
                    },
                    {
                        "type": "value",
                        "value": 3
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testComparisonEqualsRelated()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\equals",
                "value": [
                    {
                        "type": "attribute",
                        "value": "parent.amount"
                    },
                    {
                        "type": "value",
                        "value": 3
                    }
                ]
            }
        '));

        $parent = $this->getEntityMock();

        $this->setEntityAttributes($parent, [
            'amount' => 3
        ]);

        $repository = $this->createMock(RDBRepository::class);
        $relation = $this->createMock(RDBRelation::class);

        $this->entityManager
            ->expects($this->once())
            ->method('getRDBRepository')
            ->with($this->entity->getEntityType())
            ->willReturn($repository);

        $repository
            ->expects($this->once())
            ->method('getRelation')
            ->with($this->entity, 'parent')
            ->willReturn($relation);

        $relation
            ->expects($this->once())
            ->method('findOne')
            ->willReturn($parent);

        $this->entity
            ->expects($this->any())
            ->method('hasRelation')
            ->with('parent')
            ->willReturn(true);

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testComparisonEqualsFalse()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\equals",
                "value": [
                    {
                        "type": "attribute",
                        "value": "amount"
                    },
                    {
                        "type": "value",
                        "value": 4
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertFalse($result);
    }

    function testComparisonNotEquals()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\notEquals",
                "value": [
                    {
                        "type": "attribute",
                        "value": "amount"
                    },
                    {
                        "type": "value",
                        "value": 4
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testComparisonNotEqualsNull()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\notEquals",
                "value": [
                    {
                        "type": "attribute",
                        "value": "amount"
                    },
                    {
                        "type": "value",
                        "value": null
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));
        $result = $this->createProcessor()->process($item);
        $this->assertTrue($result);
    }

    function testComparisonEqualsArray()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\equals",
                "value": [
                    {
                        "type": "list",
                        "value": [
                            {
                                "type": "value",
                                "value": "1"
                            },
                            {
                                "type": "value",
                                "value": "User"
                            }
                        ]
                    },
                    {
                        "type": "list",
                        "value": [
                            {
                                "type": "value",
                                "value": "1"
                            },
                            {
                                "type": "value",
                                "value": "User"
                            }
                        ]
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, [
            'parentId' => '1',
            'parentType' => 'User'
        ]);

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testComparisonGreaterThan()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\greaterThan",
                "value": [
                    {
                        "type": "attribute",
                        "value": "amount"
                    },
                    {
                        "type": "value",
                        "value": 2
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testComparisonLessThan()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\lessThan",
                "value": [
                    {
                        "type": "attribute",
                        "value": "amount"
                    },
                    {
                        "type": "value",
                        "value": 4
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testComparisonGreaterThanOrEquals()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\greaterThanOrEquals",
                "value": [
                    {
                        "type": "attribute",
                        "value": "amount"
                    },
                    {
                        "type": "value",
                        "value": 2
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 4
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testComparisonLessThanOrEquals()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\lessThanOrEquals",
                "value": [
                    {
                        "type": "attribute",
                        "value": "amount"
                    },
                    {
                        "type": "value",
                        "value": 4
                    }
                ]
            }
        '));

        $this->setEntityAttributes($this->entity, array(
            'amount' => 4
        ));

        $result = $this->createProcessor()->process($item);

        $this->assertTrue($result);
    }

    function testStringNewLine()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "value",
                "value": "test\\ntest"
            }
        '));

        $result = $this->createProcessor()->process($item);

        $this->assertEquals("test\ntest", $result);
    }

    function testVariable()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\equals",
                "value": [
                    {
                        "type": "variable",
                        "value": "counter"
                    },
                    {
                        "type": "value",
                        "value": 4
                    }
                ]
            }
        '));

        $result = $this->createProcessor((object)[
            'counter' => 4
        ])->process($item);

        $this->assertTrue($result);
    }

    function testAssign()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "assign",
                "value": [
                    {
                        "type": "value",
                        "value": "counter"
                    },
                    {
                        "type": "value",
                        "value": 5
                    }
                ]
            }
        '));

        $variables = (object)[
            'counter' => 4
        ];
        $this->createProcessor($variables)->process($item);

        $this->assertEquals(5, $variables->counter);
    }

    function testSetAttribute()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "setAttribute",
                "value": [
                    {
                        "type": "value",
                        "value": "amount"
                    },
                    {
                        "type": "variable",
                        "value": "counter"
                    }
                ]
            }
        '));

        $variables = (object)[
            'counter' => 4
        ];

        $this->entity
            ->expects($this->once())
            ->method('set')
            ->with('amount', 4);

        $this->createProcessor($variables)->process($item);
    }

    function testClearAttribute(): void
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "entity\\\\clearAttribute",
                "value": [
                    {
                        "type": "value",
                        "value": "amount"
                    }
                ]
            }
        '));

        $this->entity
            ->expects($this->once())
            ->method('clear')
            ->with('amount');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->createProcessor((object) [])->process($item);
    }

    function testCompareDates()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\equals",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-01"
                    },
                    {
                        "type": "attribute",
                        "value": "dateStart"
                    }
                ]
            }
        '));
        $this->setEntityAttributes($this->entity, array(
            'dateStart' => '2017-01-01'
        ));
        $result = $this->createProcessor()->process($item);
        $this->assertTrue($result);

        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\greaterThan",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-02"
                    },
                    {
                        "type": "attribute",
                        "value": "dateStart"
                    }
                ]
            }
        '));
        $this->setEntityAttributes($this->entity, array(
            'dateStart' => '2017-01-01'
        ));
        $result = $this->createProcessor()->process($item);
        $this->assertTrue($result);

        $item = new Argument(self::stringToNode('
            {
                "type": "comparison\\\\greaterThanOrEquals",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-01"
                    },
                    {
                        "type": "attribute",
                        "value": "dateStart"
                    }
                ]
            }
        '));
        $this->setEntityAttributes($this->entity, array(
            'dateStart' => '2017-01-01'
        ));
        $result = $this->createProcessor()->process($item);
        $this->assertTrue($result);
    }

    function testNumberAbs()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "number\\\\abs",
                "value": [
                    {
                        "type": "value",
                        "value": -20
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(20, $actual);
    }

    function testNumberCeil()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "number\\\\ceil",
                "value": [
                    {
                        "type": "value",
                        "value": 20.4
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(21, $actual);
    }

    function testNumberFloor()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "number\\\\floor",
                "value": [
                    {
                        "type": "value",
                        "value": 20.4
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(20, $actual);
    }

    function testNumberFormat()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "number\\\\format",
                "value": [
                    {
                        "type": "value",
                        "value": 20
                    },
                    {
                        "type": "value",
                        "value": 2
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('20.00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "number\\\\format",
                "value": [
                    {
                        "type": "value",
                        "value": 20
                    },
                    {
                        "type": "value",
                        "value": 2
                    },
                    {
                        "type": "value",
                        "value": ","
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('20,00', $actual);
    }

    function testNumberRound()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "number\\\\round",
                "value": [
                    {
                        "type": "value",
                        "value": 1.12
                    },
                    {
                        "type": "value",
                        "value": 1
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(1.1, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "number\\\\round",
                "value": [
                    {
                        "type": "value",
                        "value": 2.65
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(3, $actual);
    }

    function testNumberRandomInt()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "number\\\\randomInt",
                "value": [
                    {
                        "type": "value",
                        "value": 0
                    },
                    {
                        "type": "value",
                        "value": 10
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);

        $this->assertIsInt($actual);
    }

    public function testDatetimeNow(): void
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\now"
            }
        '));

        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(date('Y-m-d H:i:s'), $actual);
    }

    public function testDatetimeToday(): void
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\today"
            }
        '));

        /** @noinspection PhpUnhandledExceptionInspection */
        $actual = $this->createProcessor()->process($item);

        $this->assertEquals(date('Y-m-d'), $actual);
    }

    function testDatetimeFormat()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\format",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20 14:15"
                    },
                    {
                        "type": "value",
                        "value": null
                    },
                    {
                        "type": "value",
                        "value": "YYYY-MM-DD hh:mm a"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-10-20 02:15 pm', $actual);
    }

    function testDatetimeYear()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\year",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20 14:15"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(2017, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\year",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(2017, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\year",
                "value": [
                    {
                        "type": "value",
                        "value": ""
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(0, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\year",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-12-31 23:00"
                    },
                    {
                        "type": "value",
                        "value": "Europe/Kiev"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(2018, $actual);
    }

    function testDatetimeMonth()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\month",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20 14:15"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(10, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\month",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(10, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\month",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-30 23:15"
                    },
                    {
                        "type": "value",
                        "value": "Europe/Kiev"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(12, $actual);
    }

    function testDatetimeDate()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\date",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-02 14:15"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(2, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\date",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(20, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\date",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-02 23:15"
                    },
                    {
                        "type": "value",
                        "value": "Europe/Kiev"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(3, $actual);
    }

    function testDatetimeHour()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\hour",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-02 14:15"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(14, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\hour",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(0, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\hour",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-02 14:15"
                    },
                    {
                        "type": "value",
                        "value": "Europe/Kiev"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(16, $actual);
    }

    function testDatetimeMinute()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\minute",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-02 14:05"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(5, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\hour",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(0, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\minute",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-02 14:05"
                    },
                    {
                        "type": "value",
                        "value": "Europe/Kiev"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(5, $actual);
    }

    function testDatetimeDayOfWeek()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-05-05 14:15"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(5, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-05-07"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(0, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": ""
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(-1, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-05-12"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(5, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-05-05 23:15"
                    },
                    {
                        "type": "value",
                        "value": "Europe/Kiev"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(6, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": "2020-05-15"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(5, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": "2020-07-18 14:02:50"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(6, $actual);
    }

    function testDatetimeDiff()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\diff",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20 14:15"
                    },
                    {
                        "type": "value",
                        "value": "2017-10-20 14:20"
                    },
                    {
                        "type": "value",
                        "value": "minutes"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(-5, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\diff",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20 14:15"
                    },
                    {
                        "type": "value",
                        "value": "2017-10-20 11:00"
                    },
                    {
                        "type": "value",
                        "value": "hours"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(3, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\diff",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20 11:00"
                    },
                    {
                        "type": "value",
                        "value": "2017-09-20"
                    },
                    {
                        "type": "value",
                        "value": "months"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(1, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\diff",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-09-20 12:15"
                    },
                    {
                        "type": "value",
                        "value": "2017-09-20 11:00"
                    },
                    {
                        "type": "value",
                        "value": "minutes"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(75, $actual);
    }

    function testDatetimeOperations()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\addDays",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-01"
                    },
                    {
                        "type": "value",
                        "value": 2
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-01-03', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\addDays",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-01 10:00:05"
                    },
                    {
                        "type": "value",
                        "value": 2
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-01-03 10:00:05', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\addMonths",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-01 10:00:05"
                    },
                    {
                        "type": "value",
                        "value": -1
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2016-12-01 10:00:05', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\addWeeks",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-01 10:00:05"
                    },
                    {
                        "type": "value",
                        "value": 1
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-01-08 10:00:05', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\addYears",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-01"
                    },
                    {
                        "type": "value",
                        "value": 1
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2018-01-01', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\addHours",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-01"
                    },
                    {
                        "type": "value",
                        "value": 2
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-01-01 02:00:00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\addMinutes",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-01-01 20:00:00"
                    },
                    {
                        "type": "value",
                        "value": -30
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-01-01 19:30:00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\addSeconds",
                "value": [
                    {
                        "type": "value",
                        "value": "2025-01-01 20:00:00"
                    },
                    {
                        "type": "value",
                        "value": -30
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2025-01-01 19:59:30', $actual);
    }

    function testDatetimeClosestTime()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10:00"
                    },
                    {
                        "type": "value",
                        "value": "time"
                    },
                    {
                        "type": "value",
                        "value": "16:15"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-16 16:15', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10:00"
                    },
                    {
                        "type": "value",
                        "value": "time"
                    },
                    {
                        "type": "value",
                        "value": "12:00"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-17 12:00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10"
                    },
                    {
                        "type": "value",
                        "value": "time"
                    },
                    {
                        "type": "value",
                        "value": "16:15"
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-15 16:15', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 12:00"
                    },
                    {
                        "type": "value",
                        "value": "time"
                    },
                    {
                        "type": "value",
                        "value": "16:15"
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-15 16:15', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 12:00"
                    },
                    {
                        "type": "value",
                        "value": "time"
                    },
                    {
                        "type": "value",
                        "value": "00:00"
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-16 00:00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16"
                    },
                    {
                        "type": "value",
                        "value": "time"
                    },
                    {
                        "type": "value",
                        "value": "12:00"
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-15 12:00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 12:10"
                    },
                    {
                        "type": "value",
                        "value": "time"
                    },
                    {
                        "type": "value",
                        "value": "12:10"
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-16 12:10', $actual);
    }

    function testDatetimeClosestHour()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10"
                    },
                    {
                        "type": "value",
                        "value": "hour"
                    },
                    {
                        "type": "value",
                        "value": 10
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-17 10:00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10"
                    },
                    {
                        "type": "value",
                        "value": "hour"
                    },
                    {
                        "type": "value",
                        "value": 10
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-16 10:00', $actual);
    }

    function testDatetimeClosestMinute()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10:10"
                    },
                    {
                        "type": "value",
                        "value": "minute"
                    },
                    {
                        "type": "value",
                        "value": 10
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-16 15:10', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10"
                    },
                    {
                        "type": "value",
                        "value": "minute"
                    },
                    {
                        "type": "value",
                        "value": 10
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-16 15:10', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:20"
                    },
                    {
                        "type": "value",
                        "value": "minute"
                    },
                    {
                        "type": "value",
                        "value": 10
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-16 16:10', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:20"
                    },
                    {
                        "type": "value",
                        "value": "minute"
                    },
                    {
                        "type": "value",
                        "value": 59
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-16 15:59', $actual);
    }

    function testDatetimeClosestDayOfWeek()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10"
                    },
                    {
                        "type": "value",
                        "value": "dayOfWeek"
                    },
                    {
                        "type": "value",
                        "value": 1
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-20 00:00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16"
                    },
                    {
                        "type": "value",
                        "value": "dayOfWeek"
                    },
                    {
                        "type": "value",
                        "value": 1
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-13', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10"
                    },
                    {
                        "type": "value",
                        "value": "dayOfWeek"
                    },
                    {
                        "type": "value",
                        "value": 0
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-19 00:00', $actual);
    }

    function testDatetimeClosestDate()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10"
                    },
                    {
                        "type": "value",
                        "value": "date"
                    },
                    {
                        "type": "value",
                        "value": 30
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-30 00:00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-12-16"
                    },
                    {
                        "type": "value",
                        "value": "date"
                    },
                    {
                        "type": "value",
                        "value": 31
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-10-31', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16 15:10"
                    },
                    {
                        "type": "value",
                        "value": "date"
                    },
                    {
                        "type": "value",
                        "value": 1
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-12-01 00:00', $actual);
    }

    function testDatetimeClosestMonth()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16"
                    },
                    {
                        "type": "value",
                        "value": "month"
                    },
                    {
                        "type": "value",
                        "value": 1
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2018-01-01', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-01"
                    },
                    {
                        "type": "value",
                        "value": "month"
                    },
                    {
                        "type": "value",
                        "value": 11
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-01', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-01 10:00"
                    },
                    {
                        "type": "value",
                        "value": "month"
                    },
                    {
                        "type": "value",
                        "value": 11
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2018-11-01 00:00', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "datetime\\\\closest",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-11-16"
                    },
                    {
                        "type": "value",
                        "value": "month"
                    },
                    {
                        "type": "value",
                        "value": 11
                    },
                    {
                        "type": "value",
                        "value": true
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('2017-11-01', $actual);
    }

    function testList()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "list",
                "value": [
                    {
                        "type": "value",
                        "value": "Test"
                    },
                    {
                        "type": "value",
                        "value": "Hello"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(["Test", "Hello"], $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "list",
                "value": []
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals([], $actual);
    }

    function testArrayIncludes()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "array\\\\includes",
                "value": [
                    {
                        "type": "list",
                        "value": [
                            {
                                "type": "value",
                                "value": "Test"
                            },
                            {
                                "type": "value",
                                "value": "Hello"
                            }
                        ]
                    },
                    {
                        "type": "value",
                        "value": "Test"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertTrue($actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "array\\\\includes",
                "value": [
                    {
                        "type": "value",
                        "value": false
                    },
                    {
                        "type": "value",
                        "value": ""
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertFalse($actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "array\\\\includes",
                "value": [
                    {
                        "type": "list",
                        "value": [
                            {
                                "type": "value",
                                "value": "Test"
                            },
                            {
                                "type": "value",
                                "value": "Hello"
                            }
                        ]
                    },
                    {
                        "type": "value",
                        "value": "Yok"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertFalse($actual);
    }

    function testArrayPush()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "array\\\\push",
                "value": [
                    {
                        "type": "list",
                        "value": [
                            {
                                "type": "value",
                                "value": "Test"
                            },
                            {
                                "type": "value",
                                "value": "Hello"
                            }
                        ]
                    },
                    {
                        "type": "value",
                        "value": "1"
                    },
                    {
                        "type": "value",
                        "value": "2"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(['Test', 'Hello', '1', '2'], $actual);
    }

    function testArrayLength()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "array\\\\length",
                "value": [
                    {
                        "type": "list",
                        "value": [
                            {
                                "type": "value",
                                "value": "Test"
                            },
                            {
                                "type": "value",
                                "value": "Hello"
                            }
                        ]
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(2, $actual);
    }

    function testEnvUserAttribute()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "env\\\\userAttribute",
                "value": [
                    {
                        "type": "value",
                        "value": "id"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('1', $actual);
    }

    function testTrim()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\trim",
                "value": [
                    {
                        "type": "value",
                        "value": " test "
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('test', $actual);
    }

    function testLowerCase()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\lowerCase",
                "value": [
                    {
                        "type": "value",
                        "value": " TeSt "
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(' test ', $actual);
    }

    function testUpperCase()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\upperCase",
                "value": [
                    {
                        "type": "value",
                        "value": "test"
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('TEST', $actual);
    }

    function testSubstring()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\substring",
                "value": [
                    {
                        "type": "value",
                        "value": "1234"
                    },
                    {
                        "type": "value",
                        "value": 1
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('234', $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\substring",
                "value": [
                    {
                        "type": "value",
                        "value": "1234"
                    },
                    {
                        "type": "value",
                        "value": 0
                    },
                    {
                        "type": "value",
                        "value": 2
                    }
                ]
            }
        '));
        $actual = $this->createProcessor()->process($item);
        $this->assertEquals('12', $actual);
    }

    function testPos()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\pos",
                "value": [
                    {
                        "type": "value",
                        "value": "1234"
                    },
                    {
                        "type": "value",
                        "value": 23
                    }
                ]
            }
        '));

        $actual = $this->createProcessor()->process($item);
        $this->assertEquals(1, $actual);

        $item = new Argument(self::stringToNode('
            {
                "type": "string\\\\pos",
                "value": [
                    {
                        "type": "value",
                        "value": "1234"
                    },
                    {
                        "type": "value",
                        "value": 54
                    }
                ]
            }
        '));

        $actual = $this->createProcessor()->process($item);
        $this->assertFalse($actual);
    }

    function testBundle()
    {
        $item = new Argument(self::stringToNode('
            {
                "type": "bundle",
                "value": [
                    {
                        "type": "assign",
                        "value": [
                            {
                                "type": "value",
                                "value": "counter"
                            },
                            {
                                "type": "value",
                                "value": 5
                            }
                        ]
                    },
                    {
                        "type": "assign",
                        "value": [
                            {
                                "type": "value",
                                "value": "test"
                            },
                            {
                                "type": "attribute",
                                "value": "test"
                            }
                        ]
                    }
                ]
            }
        '));

        $variables = (object)[];
        $this->setEntityAttributes($this->entity, array(
            'test' => 'hello'
        ));

        $this->createProcessor($variables)->process($item);

        $this->assertEquals(5, $variables->counter);
        $this->assertEquals('hello', $variables->test);
    }
}
