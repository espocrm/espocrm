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

namespace tests\unit\Espo\Core\Formula;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->parser = new \Espo\Core\Formula\Parser();
    }

    protected function tearDown() : void
    {
        $this->parser = null;
    }

    function testValue()
    {
        $expression = "isActive = true";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'setAttribute',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 'isActive'
                ],
                (object) [
                    'type' => 'value',
                    'value' => true
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);

        $expression = "isActive == false";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'comparison\\equals',
            'value' => [
                (object) [
                    'type' => 'attribute',
                    'value' => 'isActive'
                ],
                (object) [
                    'type' => 'value',
                    'value' => false
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testNotEquals()
    {
        $expression = "isActive != false";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'comparison\\notEquals',
            'value' => [
                (object) [
                    'type' => 'attribute',
                    'value' => 'isActive'
                ],
                (object) [
                    'type' => 'value',
                    'value' => false
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testSplit()
    {
        $expression = "name == 'test';\nvalue > 0.5\n;";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'bundle',
            'value' => [
                (object) [
                    'type' => 'comparison\\equals',
                    'value' => [
                        (object) [
                            'type' => 'attribute',
                            'value' => 'name'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 'test'
                        ]
                    ]
                ],
                (object) [
                    'type' => 'comparison\\greaterThan',
                    'value' => [
                        (object) [
                            'type' => 'attribute',
                            'value' => 'value'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 0.5
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);

        $expression = "name == 'test'; value > 0.5";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'bundle',
            'value' => [
                (object) [
                    'type' => 'comparison\\equals',
                    'value' => [
                        (object) [
                            'type' => 'attribute',
                            'value' => 'name'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 'test'
                        ]
                    ]
                ],
                (object) [
                    'type' => 'comparison\\greaterThan',
                    'value' => [
                        (object) [
                            'type' => 'attribute',
                            'value' => 'value'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 0.5
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testParse1()
    {
        $expression = "((amountConverted + 10) * (0.1 + (10 / amountConverted + 1)))";

        $actual = $this->parser->parse($expression);

        $expected = (object) [
            'type' => 'numeric\\multiplication',
            'value' => [
                (object) [
                    'type' => 'numeric\\summation',
                    'value' => [
                        (object) [
                            'type' => 'attribute',
                            'value' => 'amountConverted'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 10
                        ]
                    ]
                ],
                (object) [
                    'type' => 'numeric\\summation',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 0.1
                        ],
                        (object) [
                            'type' => 'numeric\\summation',
                            'value' => [
                                (object) [
                                    'type' => 'numeric\\division',
                                    'value' => [
                                        (object) [
                                            'type' => 'value',
                                            'value' => 10
                                        ],
                                        (object) [
                                            'type' => 'attribute',
                                            'value' => 'amountConverted'
                                        ]
                                    ]
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 1
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $actual);
    }

    function testParse2()
    {
        $expression = "(name == 'test' || value > 0.5)";

        $actual = $this->parser->parse($expression);

        $expected = (object) [
            'type' => 'logical\\or',
            'value' => [
                (object) [
                    'type' => 'comparison\\equals',
                    'value' => [
                        (object) [
                            'type' => 'attribute',
                            'value' => 'name'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 'test'
                        ]
                    ]
                ],
                (object) [
                    'type' => 'comparison\\greaterThan',
                    'value' => [
                        (object) [
                            'type' => 'attribute',
                            'value' => 'value'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 0.5
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $actual);
    }

    function testParse3()
    {
        $expression = "!(name == 'test' || !isActive)";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'logical\\not',
            'value' => (object) [
                'type' => 'logical\\or',
                'value' => [
                    (object) [
                        'type' => 'comparison\\equals',
                        'value' => [
                            (object) [
                                'type' => 'attribute',
                                'value' => 'name'
                            ],
                            (object) [
                                'type' => 'value',
                                'value' => 'test'
                            ]
                        ]
                    ],
                    (object) [
                        'type' => 'logical\\not',
                        'value' => (object) [
                            'type' => 'attribute',
                            'value' => 'isActive'
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $actual);

        $expression = "!value * 10";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\multiplication',
            'value' => [
                (object) [
                    'type' => 'logical\\not',
                    'value' => (object) [
                        'type' => 'attribute',
                        'value' => 'value'
                    ]
                ],
                (object) [
                    'type' => 'value',
                    'value' => 10
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);

        $expression = "!functionName(10)";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'logical\\not',
            'value' => (object) [
                'type' => 'functionName',
                'value' => [
                    (object) [
                        'type' => 'value',
                        'value' => 10
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testParse4()
    {
        $expression = "-(value - 10)";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\subtraction',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 0
                ],
                (object) [
                    'type' => 'numeric\\subtraction',
                    'value' => [
                        (object) [
                            'type' => 'attribute',
                            'value' => 'value'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 10
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);

        $expression = "- value - 10";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\subtraction',
            'value' => [
                (object) [
                    'type' => 'numeric\\subtraction',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 0
                        ],
                        (object) [
                            'type' => 'attribute',
                            'value' => 'value'
                        ]
                    ]
                ],
                (object) [
                    'type' => 'value',
                    'value' => 10
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);

        $expression = "- value - (-10)";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\subtraction',
            'value' => [
                (object) [
                    'type' => 'numeric\\subtraction',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 0
                        ],
                        (object) [
                            'type' => 'attribute',
                            'value' => 'value'
                        ]
                    ]
                ],
                (object) [
                    'type' => 'numeric\\subtraction',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 0
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 10
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testParse5()
    {
        $expression = "'test + test'";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'value',
            'value' => 'test + test'
        ];
        $this->assertEquals($expected, $actual);

        $expression = "\"test\\\" + \\\"test\"";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'value',
            'value' => 'test\" + \"test'
        ];
        $this->assertEquals($expected, $actual);

        $expression = "\"test' + 'test\"";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'value',
            'value' => "test' + 'test"
        ];
        $this->assertEquals($expected, $actual);

        $expression = "numeric\\summation('test + test')";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\summation',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 'test + test'
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testParse6()
    {
        $expression = "numeric\\summation(')')";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\summation',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => ')'
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testParseString()
    {
        $expression = " 'test' ";
        $expected = (object) [
            'type' => 'value',
            'value' => 'test'
        ];
        $actual = $this->parser->parse($expression);
        $this->assertEquals($expected, $actual);

        $expression = " \"test\" ";
        $expected = (object) [
            'type' => 'value',
            'value' => 'test'
        ];
        $actual = $this->parser->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseNewLine()
    {
        $expression = " \n \"test\n\thello\" ";
        $expected = (object) [
            'type' => 'value',
            'value' => "test\n\thello"
        ];
        $actual = $this->parser->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment1()
    {
        $expression = "// \"test\"\n \"//test\" ";
        $expected = (object) [
            'type' => 'value',
            'value' => "//test"
        ];
        $actual = $this->parser->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment2()
    {
        $expression = "\"test\" // test\n ";
        $expected = (object) [
            'type' => 'value',
            'value' => "test",
        ];
        $actual = $this->parser->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment3()
    {
        $expression = "\"test\" /* test\n */";
        $expected = (object) [
            'type' => 'value',
            'value' => "test",
        ];
        $actual = $this->parser->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment4()
    {
        $expression = "/* test\n */ \"/*test*/\"";
        $expected = (object) [
            'type' => 'value',
            'value' => "/*test*/",
        ];
        $actual = $this->parser->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment5()
    {
        $expression = "\"test\" /* test\n */ /* test */ ";
        $expected = (object) [
            'type' => 'value',
            'value' => "test",
        ];
        $actual = $this->parser->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment6()
    {
        $expression = "/* test; */ test1 = 1 + 1; test2 = 2 * 2;";
        $expected = (object) [
            'type' => 'bundle',
            'value' => [
                (object) [
                    'type' => 'setAttribute',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'test1',
                        ],
                        (object) [
                            'type' => 'numeric\summation',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 1,
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 1,
                                ]
                            ],
                        ],
                    ],
                ],
                (object) [
                    'type' => 'setAttribute',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'test2',
                        ],
                        (object) [
                            'type' => 'numeric\multiplication',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 2,
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 2,
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $actual = $this->parser->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseFunction()
    {
        $expression = "numeric\\summation (10, parent.amountConverted, 0.1)";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\summation',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 10
                ],
                (object) [
                    'type' => 'attribute',
                    'value' => 'parent.amountConverted'
                ],
                (object) [
                    'type' => 'value',
                    'value' => 0.1
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);

        $expression = "numeric\\summation(10, numeric\\subtraction(5, 2) + 1, 0.1)";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\summation',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 10
                ],
                (object) [
                    'type' => 'numeric\\summation',
                    'value' => [
                        (object) [
                            'type' => 'numeric\\subtraction',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 5
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 2
                                ]
                            ]
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
                (object) [
                    'type' => 'value',
                    'value' => 0.1
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);

        $expression = "numeric\\summation(10)";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\summation',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 10
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);

        $expression = "numeric\\summation()";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\summation',
            'value' => []
        ];
        $this->assertEquals($expected, $actual);

    }

    function testParseVariable()
    {
        $expression = "10 + \$counter";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\summation',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 10
                ],
                (object) [
                    'type' => 'variable',
                    'value' => 'counter'
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }


    function testAssign()
    {
        $expression = "\$counter = 10 + \$counter";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'assign',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 'counter'
                ],
                (object) [
                    'type' => 'numeric\\summation',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 10
                        ],
                        (object) [
                            'type' => 'variable',
                            'value' => 'counter'
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testSetAttribute()
    {
        $expression = "amount = 10 + \$counter";
        $actual = $this->parser->parse($expression);
        $expected = (object) [
            'type' => 'setAttribute',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 'amount'
                ],
                (object) [
                    'type' => 'numeric\\summation',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 10
                        ],
                        (object) [
                            'type' => 'variable',
                            'value' => 'counter'
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testCase1()
    {
        $expression = "
            ifThenElse(
                status == 'New' && !assignedUserId
                ,
                assignedUserId = '1';
                assignedUserName = 'Admin'
                ,
                assignedUserId = '52bc41e60ccba';
                assignedUserName = 'Will Manager'
            );
        ";
        $actual = $this->parser->parse($expression);

        $this->assertNotEmpty($actual);
    }

    public function varExport($variable, $level = 0)
    {
        $tab = '';
        $tabElement = '    ';
        for ($i = 0; $i <= $level; $i++) {
            $tab .= $tabElement;
        }
        $prevTab = substr($tab, 0, strlen($tab) - strlen($tabElement));

        if ($variable instanceof \StdClass) {
            $result = "(object) " . $this->varExport(get_object_vars($variable), $level);
        } else if (is_array($variable)) {
            $array = array();
            foreach ($variable as $key => $value) {
                $array[] = var_export($key, true) . " => " . $this->varExport($value, $level + 1);
            }
            $result = "[\n" . $tab . implode(",\n" . $tab, $array) . "\n" . $prevTab . "]";
        } else {
            $result = var_export($variable, true);
        }

        return $result;
    }

    function testCommaInString()
    {
        $expression = "
            string\concatenate(
                lastName, ', ', firstName
            )
        ";
        $actual = $this->parser->parse($expression);

        $expected = (object) [
            'type' => 'string\concatenate',
            'value' => [
                (object) [
                    'type' => 'attribute',
                    'value' => 'lastName'
                ],
                (object) [
                    'type' => 'value',
                    'value' => ', '
                ],
                (object) [
                    'type' => 'attribute',
                    'value' => 'firstName'
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }

    function testBracesInString()
    {
        $expression = "
            string\concatenate(
                lastName, '(,)(\"test\")', firstName
            )
        ";
        $actual = $this->parser->parse($expression);

        $expected = (object) [
            'type' => 'string\concatenate',
            'value' => [
                (object) [
                    'type' => 'attribute',
                    'value' => 'lastName'
                ],
                (object) [
                    'type' => 'value',
                    'value' => '(,)("test")'
                ],
                (object) [
                    'type' => 'attribute',
                    'value' => 'firstName'
                ]
            ]
        ];
        $this->assertEquals($expected, $actual);
    }
}
