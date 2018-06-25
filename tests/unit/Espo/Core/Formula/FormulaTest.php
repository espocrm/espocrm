<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace tests\unit\Espo\Core\Formula;

use \Espo\ORM\Entity;

class FormulaTest extends \PHPUnit\Framework\TestCase
{

    protected function setUp()
    {
        $container = $this->container = $this->getMockBuilder('\\Espo\\Core\\Container')->disableOriginalConstructor()->getMock();

        $this->functionFactory = new \Espo\Core\Formula\FunctionFactory($container);
        $this->formula = new \Espo\Core\Formula\Formula($this->functionFactory);

        $this->entity = $this->getEntityMock();
        $this->entityManager = $this->getMockBuilder('\\Espo\\ORM\\EntityManager')->disableOriginalConstructor()->getMock();
        $this->repository = $this->getMockBuilder('\\Espo\\ORM\\Repositories\\RDB')->disableOriginalConstructor()->getMock();

        date_default_timezone_set('UTC');

        $this->dateTime = new \Espo\Core\Utils\DateTime();

        $this->number = new \Espo\Core\Utils\NumberUtil();

        $this->config = $this->getMockBuilder('\\Espo\\Core\\Utils\\Config')->disableOriginalConstructor()->getMock();
        $this->config
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['timeZone', null, 'UTC']
            ]));

        $this->user = new \tests\unit\testData\Entities\User();

        $this->user->id = '1';

        $container
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['entityManager', $this->entityManager],
                ['dateTime', $this->dateTime],
                ['number', $this->number],
                ['config', $this->config],
                ['user', $this->user]
            ]));
    }

    protected function tearDown()
    {
        $this->container = null;
        $this->functionFactory = null;
        $this->formula = null;
        $this->entity = null;
        $this->entityManager = null;
        $this->repository = null;
        $this->config = null;
    }

    protected function getEntityMock()
    {
        return $this->getMockBuilder('\\Espo\\Core\\ORM\\Entity')->disableOriginalConstructor()->getMock();
    }

    protected function setEntityAttributes($entity, $attributes)
    {
        $map = [];
        foreach ($attributes as $key => $value) {
            $map[] = [$key, [], $value];
        }

        $entity
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));
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
            ->will($this->returnValueMap($map));
    }

    function testAttribute()
    {
        $item = json_decode('
            {
                "type": "attribute",
                "value": "name"
            }
        ');

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals('Test', $result);
    }

    function testEntityAttribute()
    {
        $item = json_decode('
            {
                "type": "entity\\\\attribute",
                "value": [
                    {
                        "type": "value",
                        "value": "name"
                    }
                ]
            }
        ');

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals('Test', $result);
    }

    function testEntityAttributeFetched()
    {
        $item = json_decode('
            {
                "type": "entity\\\\attributeFetched",
                "value": [
                    {
                        "type": "value",
                        "value": "name"
                    }
                ]
            }
        ');

        $this->setEntityFetchedAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals('Test', $result);
    }

    function testIsAttributeChanged()
    {
        $item = json_decode('
            {
                "type": "entity\\\\isAttributeChanged",
                "value": [
                    {
                        "type": "value",
                        "value": "name"
                    }
                ]
            }
        ');

        $this->setEntityFetchedAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $this->entity
            ->expects($this->once())
            ->method('isAttributeChanged')
            ->will($this->returnValue(true));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testIsAttributeNotChanged()
    {
        $item = json_decode('
            {
                "type": "entity\\\\isAttributeNotChanged",
                "value": [
                    {
                        "type": "value",
                        "value": "name"
                    }
                ]
            }
        ');

        $this->setEntityFetchedAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $this->entity
            ->expects($this->once())
            ->method('isAttributeChanged')
            ->will($this->returnValue(false));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testHasValue()
    {
        $item = json_decode('
            {
                "type": "entity\\\\isRelated",
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
        ');

        $this->entity
            ->expects($this->any())
            ->method('getEntityType')
            ->will($this->returnValue('Test'));

        $this->repository
            ->expects($this->any())
            ->method('isRelated')
            ->will($this->returnValueMap([
                [$this->entity, 'teams', '1', true]
            ]));

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testAddLinkMultipleId()
    {
        $item = json_decode('
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
        ');

        $entity = $this->entity;

        $this->setEntityAttributes($entity, array(
            'teamsIds' => ['2']
        ));

        $entity
            ->expects($this->any())
            ->method('addLinkMultipleId')
            ->with('teams', '1');

        $this->formula->process($item, $this->entity);

        $this->assertTrue(true);
    }

    function testRemoveLinkMultipleId()
    {
        $item = json_decode('
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
        ');

        $entity = $this->entity;

        $this->setEntityAttributes($entity, array(
            'teamsIds' => ['1', '2']
        ));

        $entity
            ->expects($this->any())
            ->method('removeLinkMultipleId')
            ->with('teams', '1');

        $this->formula->process($item, $this->entity);

        $this->assertTrue(true);
    }

    function testAnd()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testOr()
    {
        $item = json_decode('
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
        ');

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testAndFalse()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertFalse($result);
    }

    function testNot()
    {
        $item = json_decode('
            {
                "type": "logical\\\\not",
                "value": {
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
                }
            }
        ');

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertFalse($result);
    }

    function testConcatenation()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'name' => 'Test'
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals('Hello Test 12', $result);
    }

    function testStringLength()
    {
        $item = json_decode('
            {
                "type": "string\\\\length",
                "value": [
                    {
                        "type": "value",
                        "value": "TestHello"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(9, $actual);
    }

    function testStringContains()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertTrue($actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertTrue($actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertFalse($actual);
    }

    function testSummationAndDivision()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 4
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals(2 + 3 + 4 + (5 - 2), $result);
    }

    function testMultiplication()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 4.2
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals(2 * 3 * 4.2, $result);
    }

    function testDivision()
    {
        $item = json_decode('
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
        ');

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals(3 / 2, $result);
    }

    function testIfThenElse1()
    {
        $item = json_decode('
            {
                "type": "ifThenElse",
                "value": [
                    {
                        "type": "condition",
                        "value": {
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'test' => true
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals(2, $result);
    }

    function testIfThenElse2()
    {
        $item = json_decode('
            {
                "type": "ifThenElse",
                "value": [
                    {
                        "type": "condition",
                        "value": {
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'test' => false,
            'amount' => 3
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals(1, $result);
    }

    function testComparisonEquals()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testComparisonEqualsRelated()
    {
        $item = json_decode('
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
        ');

        $parent = $this->getEntityMock();

        $this->setEntityAttributes($parent, array(
            'amount' => 3
        ));

        $this->setEntityAttributes($this->entity, array(
            'parent' => $parent
        ));


        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testComparisonEqualsFalse()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertFalse($result);
    }

    function testComparisonNotEquals()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testComparisonNotEqualsNull()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));
        $result = $this->formula->process($item, $this->entity);
        $this->assertTrue($result);
    }

    function testComparisonEqualsArray()
    {
        $item = json_decode('
            {
                "type": "comparison\\\\equals",
                "value": [
                    {
                        "type": "attribute",
                        "value": ["parentId", "parentType"]
                    },
                    {
                        "type": "value",
                        "value": ["1", "User"]
                    }
                ]
            }
        ');

        $this->setEntityAttributes($this->entity, array(
            'parentId' => '1',
            'parentType' => 'User'
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testComparisonGreaterThan()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testComparisonLessThan()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 3
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testComparisonGreaterThanOrEquals()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 4
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testComparisonLessThanOrEquals()
    {
        $item = json_decode('
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
        ');

        $this->setEntityAttributes($this->entity, array(
            'amount' => 4
        ));

        $result = $this->formula->process($item, $this->entity);

        $this->assertTrue($result);
    }

    function testStringNewLine()
    {
        $item = json_decode('
            {
                "type": "value",
                "value": "test\\ntest"
            }
        ');

        $result = $this->formula->process($item, $this->entity);

        $this->assertEquals("test\ntest", $result);
    }

    function testVariable()
    {
        $item = json_decode('
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
        ');

        $result = $this->formula->process($item, $this->entity, (object)[
            'counter' => 4
        ]);

        $this->assertTrue($result);
    }

    function testAssign()
    {
        $item = json_decode('
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
        ');

        $variables = (object)[
            'counter' => 4
        ];
        $this->formula->process($item, $this->entity, $variables);

        $this->assertEquals(5, $variables->counter);
    }

    function testSetAttribute()
    {
        $item = json_decode('
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
        ');

        $variables = (object)[
            'counter' => 4
        ];

        $this->entity
            ->expects($this->once())
            ->method('set')
            ->with('amount', 4);

        $this->formula->process($item, $this->entity, $variables);
    }

    function testCompareDates()
    {
        $item = json_decode('
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
        ');
        $this->setEntityAttributes($this->entity, array(
            'dateStart' => '2017-01-01'
        ));
        $result = $this->formula->process($item, $this->entity);
        $this->assertTrue($result);

        $item = json_decode('
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
        ');
        $this->setEntityAttributes($this->entity, array(
            'dateStart' => '2017-01-01'
        ));
        $result = $this->formula->process($item, $this->entity);
        $this->assertTrue($result);

        $item = json_decode('
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
        ');
        $this->setEntityAttributes($this->entity, array(
            'dateStart' => '2017-01-01'
        ));
        $result = $this->formula->process($item, $this->entity);
        $this->assertTrue($result);
    }

    function testNumberAbs()
    {
        $item = json_decode('
            {
                "type": "number\\\\abs",
                "value": [
                    {
                        "type": "value",
                        "value": -20
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(20, $actual);
    }

    function testNumberCeil()
    {
        $item = json_decode('
            {
                "type": "number\\\\ceil",
                "value": [
                    {
                        "type": "value",
                        "value": 20.4
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(21, $actual);
    }

    function testNumberFloor()
    {
        $item = json_decode('
            {
                "type": "number\\\\floor",
                "value": [
                    {
                        "type": "value",
                        "value": 20.4
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(20, $actual);
    }

    function testNumberFormat()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('20.00', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('20,00', $actual);
    }

    function testNumberRound()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(1.1, $actual);

        $item = json_decode('
            {
                "type": "number\\\\round",
                "value": [
                    {
                        "type": "value",
                        "value": 2.65
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(3, $actual);
    }

    function testDatetime()
    {
        $item = json_decode('
            {
                "type": "datetime\\\\now"
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(date('Y-m-d H:i:s'), $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\today"
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(date('Y-m-d'), $actual);
    }

    function testDatetimeFormat()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-10-20 02:15 pm', $actual);
    }

    function testDatetimeYear()
    {
        $item = json_decode('
            {
                "type": "datetime\\\\year",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20 14:15"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(2017, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\year",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(2017, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\year",
                "value": [
                    {
                        "type": "value",
                        "value": ""
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(0, $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(2018, $actual);
    }

    function testDatetimeMonth()
    {
        $item = json_decode('
            {
                "type": "datetime\\\\month",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20 14:15"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(10, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\month",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(10, $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(12, $actual);
    }

    function testDatetimeDate()
    {
        $item = json_decode('
            {
                "type": "datetime\\\\date",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-02 14:15"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(2, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\date",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(20, $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(3, $actual);
    }

    function testDatetimeHour()
    {
        $item = json_decode('
            {
                "type": "datetime\\\\hour",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-02 14:15"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(14, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\hour",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(0, $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(16, $actual);
    }

    function testDatetimeMinute()
    {
        $item = json_decode('
            {
                "type": "datetime\\\\minute",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-02 14:05"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(5, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\hour",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-10-20"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(0, $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(5, $actual);
    }

    function testDatetimeDayOfWeek()
    {
        $item = json_decode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-05-05 14:15"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(5, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-05-07"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(0, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": ""
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(-1, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\dayOfWeek",
                "value": [
                    {
                        "type": "value",
                        "value": "2017-05-12"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(5, $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item);
        $this->assertEquals(6, $actual);
    }

    function testDatetimeDiff()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(-5, $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(3, $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(1, $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(75, $actual);
    }

    function testDatetimeOperations()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-01-03', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-01-03 10:00:05', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2016-12-01 10:00:05', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-01-08 10:00:05', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2018-01-01', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-01-01 02:00:00', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-01-01 19:30:00', $actual);
    }

    function testDatetimeClosestTime()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-16 16:15', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-17 12:00', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-15 16:15', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-15 16:15', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-16 00:00', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-15 12:00', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-16 12:10', $actual);
    }

    function testDatetimeClosestHour()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-17 10:00', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-16 10:00', $actual);
    }

    function testDatetimeClosestMinute()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-16 15:10', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-16 15:10', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-16 16:10', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-16 15:59', $actual);
    }

    function testDatetimeClosestDayOfWeek()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-20 00:00', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-13', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-19 00:00', $actual);
    }

    function testDatetimeClosestDate()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-30 00:00', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-10-31', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-12-01 00:00', $actual);
    }

    function testDatetimeClosestMonth()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2018-01-01', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-01', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2018-11-01 00:00', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('2017-11-01', $actual);
    }

    function testList()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(["Test", "Hello"], $actual);

        $item = json_decode('
            {
                "type": "list",
                "value": []
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals([], $actual);
    }

    function testArrayIncludes()
    {
        $item = json_decode('
            {
                "type": "array\\\\includes",
                "value": [
                    {
                        "type": "value",
                        "value": ["Test", "Hello"]
                    },
                    {
                        "type": "value",
                        "value": "Test"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertTrue($actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertFalse($actual);

        $item = json_decode('
            {
                "type": "array\\\\includes",
                "value": [
                    {
                        "type": "value",
                        "value": ["Test", "Hello"]
                    },
                    {
                        "type": "value",
                        "value": "Yok"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertFalse($actual);
    }

    function testArrayPush()
    {
        $item = json_decode('
            {
                "type": "array\\\\push",
                "value": [
                    {
                        "type": "value",
                        "value": ["Test", "Hello"]
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(['Test', 'Hello', '1', '2'], $actual);
    }

    function testArrayLength()
    {
        $item = json_decode('
            {
                "type": "array\\\\length",
                "value": [
                    {
                        "type": "value",
                        "value": ["Test", "Hello"]
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(2, $actual);
    }

    function testEnvUserAttribute()
    {
        $item = json_decode('
            {
                "type": "env\\\\userAttribute",
                "value": [
                    {
                        "type": "value",
                        "value": "id"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('1', $actual);
    }

    function testTrim()
    {
        $item = json_decode('
            {
                "type": "string\\\\trim",
                "value": [
                    {
                        "type": "value",
                        "value": " test "
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('test', $actual);
    }

    function testLowerCase()
    {
        $item = json_decode('
            {
                "type": "string\\\\lowerCase",
                "value": [
                    {
                        "type": "value",
                        "value": " TeSt "
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals(' test ', $actual);
    }

    function testUpperCase()
    {
        $item = json_decode('
            {
                "type": "string\\\\upperCase",
                "value": [
                    {
                        "type": "value",
                        "value": "test"
                    }
                ]
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('TEST', $actual);
    }

    function testSubstring()
    {
        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('234', $actual);

        $item = json_decode('
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
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals('12', $actual);
    }

    function testBundle()
    {
        $item = json_decode('
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
        ');

        $variables = (object)[];
        $this->setEntityAttributes($this->entity, array(
            'test' => 'hello'
        ));

        $this->formula->process($item, $this->entity, $variables);

        $this->assertEquals(5, $variables->counter);
        $this->assertEquals('hello', $variables->test);
    }
}