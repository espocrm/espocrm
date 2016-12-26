<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class FormulaTest extends \PHPUnit_Framework_TestCase
{
    protected $now = '2017-10-10 10:10:10';

    protected $today = '2017-10-10 10:10:10';

    protected function setUp()
    {
        $container = $this->container = $this->getMockBuilder('\\Espo\\Core\\Container')->disableOriginalConstructor()->getMock();

        $this->functionFactory = new \Espo\Core\Formula\FunctionFactory($container);
        $this->formula = new \Espo\Core\Formula\Formula($this->functionFactory);

        $this->entity = $this->getEntityMock();
        $this->entityManager = $this->getMockBuilder('\\Espo\\ORM\\EntityManager')->disableOriginalConstructor()->getMock();
        $this->repository = $this->getMockBuilder('\\Espo\\ORM\\Repositories\\RDB')->disableOriginalConstructor()->getMock();

        $this->dateTime = $this->getMockBuilder('\\Espo\\Core\\Utils\\DateTime')->disableOriginalConstructor()->getMock();
        $this->dateTime
            ->expects($this->any())
            ->method('getInternalNowString')
            ->will($this->returnValue($this->now));
        $this->dateTime
            ->expects($this->any())
            ->method('getInternalTodayString')
            ->will($this->returnValue($this->today));
        $this->dateTime
            ->expects($this->any())
            ->method('getInternalDateTimeFormat')
            ->will($this->returnValue('Y-m-d H:i:s'));
        $this->dateTime
            ->expects($this->any())
            ->method('getInternalDateFormat')
            ->will($this->returnValue('Y-m-d'));

        $this->user = new \tests\unit\testData\Entities\User();

        $this->user->id = '1';

        $container
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['entityManager', $this->entityManager],
                ['dateTime', $this->dateTime],
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

    function testAttributeFetched()
    {
        $item = json_decode('
            {
                "type": "entity\\\\attributeFetched",
                "value": "name"
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
                "value": {
                    "type": "value",
                    "value": "name"
                }
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
                "value": {
                    "type": "value",
                    "value": "name"
                }
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

    function testDatetime()
    {
        $item = json_decode('
            {
                "type": "datetime\\\\now"
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals($this->now, $actual);

        $item = json_decode('
            {
                "type": "datetime\\\\today"
            }
        ');
        $actual = $this->formula->process($item, $this->entity);
        $this->assertEquals($this->today, $actual);
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