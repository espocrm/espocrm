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

/** @noinspection PhpUnhandledExceptionInspection */

namespace tests\unit\Espo\Core\Formula;

use Espo\Core\Formula\Exceptions\SyntaxError;
use Espo\Core\Formula\Parser;
use Espo\Core\Formula\Parser\Ast\Attribute;
use Espo\Core\Formula\Parser\Ast\Node;
use Espo\Core\Formula\Parser\Ast\Value;
use Espo\Core\Formula\Parser\Ast\Variable;
use PHPUnit\Framework\TestCase;
use stdClass;

class ParserTest extends TestCase
{
    private $parser;

    protected function setUp() : void
    {
        $this->parser = new Parser();
    }

    private function parse(string $expression): stdClass
    {
        $node = $this->parser->parse($expression);

        return self::toStdClass($node);
    }

    private static function toStdClass(mixed $node): stdClass
    {
        if ($node instanceof Node) {
            $nodes = $node->getChildNodes();

            return (object) [
                'type' => $node->getType(),
                'value' => array_map(fn ($item) => self::toStdClass($item), $nodes),
            ];
        }

        if ($node instanceof Variable) {
            return (object) [
                'type' => 'variable',
                'value' => $node->getName(),
            ];
        }

        if ($node instanceof Attribute) {
            return (object) [
                'type' => 'attribute',
                'value' => $node->getName(),
            ];
        }

        if ($node instanceof Value) {
            return (object) [
                'type' => 'value',
                'value' => $node->getValue(),
            ];
        }

        throw new \RuntimeException();
    }

    function testValue()
    {
        $expression = "isActive = true";
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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

        $actual = $this->parse($expression);

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

        $actual = $this->parse($expression);

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

        $actual = $this->parse($expression);

        $expected = (object) [
            'type' => 'logical\\not',
            'value' => [
                (object) [
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
                            'value' => [
                                (object) [
                                    'type' => 'attribute',
                                    'value' => 'isActive'
                                ]
                            ],
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $actual);

        $expression = "!value * 10";
        $actual = $this->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\multiplication',
            'value' => [
                (object) [
                    'type' => 'logical\\not',
                    'value' => [
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

        $expression = "!functionName(10)";
        $actual = $this->parse($expression);
        $expected = (object) [
            'type' => 'logical\\not',
            'value' => [
                (object) [
                    'type' => 'functionName',
                    'value' => [
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

    function testParse4()
    {
        $expression = "-(value - 10)";
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
        $expected = (object) [
            'type' => 'value',
            'value' => 'test + test'
        ];
        $this->assertEquals($expected, $actual);

        $expression = "\"test\\\" + \\\"test\"";
        $actual = $this->parse($expression);
        $expected = (object) [
            'type' => 'value',
            'value' => 'test" + "test'
        ];
        $this->assertEquals($expected, $actual);

        $expression = "\"test' + 'test\"";
        $actual = $this->parse($expression);
        $expected = (object) [
            'type' => 'value',
            'value' => "test' + 'test"
        ];
        $this->assertEquals($expected, $actual);

        $expression = "numeric\\summation('test + test')";
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
        $this->assertEquals($expected, $actual);

        $expression = " \"test\" ";
        $expected = (object) [
            'type' => 'value',
            'value' => 'test'
        ];
        $actual = $this->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseNewLine()
    {
        $expression = " \n \"test\n\thello\" ";
        $expected = (object) [
            'type' => 'value',
            'value' => "test\n\thello"
        ];
        $actual = $this->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment1()
    {
        $expression = "// \"test\"\n \"//test\" ";
        $expected = (object) [
            'type' => 'value',
            'value' => "//test"
        ];
        $actual = $this->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment2()
    {
        $expression = "\"test\" // test\n ";
        $expected = (object) [
            'type' => 'value',
            'value' => "test",
        ];
        $actual = $this->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment3()
    {
        $expression = "\"test\" /* test\n */";
        $expected = (object) [
            'type' => 'value',
            'value' => "test",
        ];
        $actual = $this->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment4()
    {
        $expression = "/* test\n */ \"/*test*/\"";
        $expected = (object) [
            'type' => 'value',
            'value' => "/*test*/",
        ];
        $actual = $this->parse($expression);
        $this->assertEquals($expected, $actual);
    }

    function testParseComment5()
    {
        $expression = "\"test\" /* test\n */ /* test */ ";
        $expected = (object) [
            'type' => 'value',
            'value' => "test",
        ];
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    function testParseFunction()
    {
        $expression = "numeric\\summation (10, parent.amountConverted, 0.1)";
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
        $expected = (object) [
            'type' => 'numeric\\summation',
            'value' => []
        ];
        $this->assertEquals($expected, $actual);

    }

    function testParseVariable()
    {
        $expression = "10 + \$counter";
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);
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
        $actual = $this->parse($expression);

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

        if ($variable instanceof stdClass) {
            $result = "(object) " . $this->varExport(get_object_vars($variable), $level);
        }
        else if (is_array($variable)) {
            $array = [];

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
        $actual = $this->parse($expression);

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
        $actual = $this->parse($expression);

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

    public function testIfStatement1(): void
    {
        $expression = "
            if (true) {
                \$test = 1;
            }
        ";

        $expected = (object) [
            'type' => 'ifThen',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => true
                ],
                (object) [
                    'type' => 'assign',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'test'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement2(): void
    {
        $expression = "
            if (test() == 1) {
                \$test1 = 1;
                \$test2 = 2;
            }
        ";

        $expected = (object) [
            'type' => 'ifThen',
            'value' => [
                (object) [
                    'type' => 'comparison\\equals',
                    'value' => [
                        (object) [
                            'type' => 'test',
                            'value' => []
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
                (object) [
                    'type' => 'bundle',
                    'value' => [
                        (object) [
                            'type' => 'assign',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 'test1'
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 1
                                ]
                            ]
                        ],
                        (object) [
                            'type' => 'assign',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 'test2'
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 2
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement3(): void
    {
        $expression = "
            if (true) {
                if (true) {
                    \$test = 1;
                }
            }
        ";

        $expected = (object) [
            'type' => 'ifThen',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => true
                ],
                (object) [
                    'type' => 'ifThen',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => true
                        ],
                        (object) [
                            'type' => 'assign',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 'test'
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

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement4(): void
    {
        $expression = "
            aif(true);

            if (true) {
                \$test = 1;
            }

            if (true) {
                \$test = 2;
            }
        ";

        $expected = (object) [
            'type' => 'bundle',
            'value' => [
                (object) [
                    'type' => 'aif',
                    'value' => [
                        0 => (object) [
                            'type' => 'value',
                            'value' => true
                        ]
                    ]
                ],
                (object) [
                    'type' => 'ifThen',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => true
                        ],
                        (object) [
                            'type' => 'assign',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 'test'
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 1
                                ]
                            ]
                        ]
                    ]
                ],
                (object) [
                    'type' => 'ifThen',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => true
                        ],
                        (object) [
                            'type' => 'assign',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 'test'
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 2
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement5(): void
    {
        $expression = "
            if (true) {
                \$test = 1;
            } else {
                \$test = 2;
            }
        ";

        $expected = (object) [
            'type' => 'ifThenElse',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => true
                ],
                (object) [
                    'type' => 'assign',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'test'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
                (object) [
                    'type' => 'assign',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'test'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 2
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement6(): void
    {
        $expression = "
            \$a = 1;

            if (true) {
                \$test = 1;
            } else {
                if (true) {
                    \$test = 2;
                }
                else {
                    \$test = 3;
                }

                \$test = 4;
            }

            \$b = 1;
        ";

        $expected = (object) [
            'type' => 'bundle',
            'value' => [
                (object) [
                    'type' => 'assign',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'a'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
                (object) [
                    'type' => 'ifThenElse',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => true
                        ],
                        (object) [
                            'type' => 'assign',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 'test'
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 1
                                ]
                            ]
                        ],
                        (object) [
                            'type' => 'bundle',
                            'value' => [
                                (object) [
                                    'type' => 'ifThenElse',
                                    'value' => [
                                        (object) [
                                            'type' => 'value',
                                            'value' => true
                                        ],
                                        (object) [
                                            'type' => 'assign',
                                            'value' => [
                                                (object) [
                                                    'type' => 'value',
                                                    'value' => 'test'
                                                ],
                                                (object) [
                                                    'type' => 'value',
                                                    'value' => 2
                                                ]
                                            ]
                                        ],
                                        (object) [
                                            'type' => 'assign',
                                            'value' => [
                                                (object) [
                                                    'type' => 'value',
                                                    'value' => 'test'
                                                ],
                                                (object) [
                                                    'type' => 'value',
                                                    'value' => 3
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                (object) [
                                    'type' => 'assign',
                                    'value' => [
                                        (object) [
                                            'type' => 'value',
                                            'value' => 'test'
                                        ],
                                        (object) [
                                            'type' => 'value',
                                            'value' => 4
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                (object) [
                    'type' => 'assign',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'b'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement7(): void
    {
        $expression = "
            if (1) {
                \$test = 1;
            } else if (2) {
                \$test = 2;
            } else {
                \$test = 3;
            }
        ";

        $expected = (object) [
            'type' => 'ifThenElse',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 1
                ],
                (object) [
                    'type' => 'assign',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'test'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
                (object) [
                    'type' => 'ifThenElse',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 2
                        ],
                        (object) [
                            'type' => 'assign',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 'test'
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 2
                                ]
                            ]
                        ],
                        (object) [
                            'type' => 'assign',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 'test'
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 3
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement8(): void
    {
        $expression = "
            if (1) {}
            else if (2) {}
            else {}
        ";

        $expected = (object) [
            'type' => 'ifThenElse',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 1
                ],
                (object) [
                    'type' => 'value',
                    'value' => NULL
                ],
                (object) [
                    'type' => 'ifThenElse',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 2
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement9(): void
    {
        $expression = "
            if (1) {}
            else if (2) {}
            else if (3) {}
            else {}
        ";

        $expected = (object) [
            'type' => 'ifThenElse',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 1
                ],
                (object) [
                    'type' => 'value',
                    'value' => NULL
                ],
                (object) [
                    'type' => 'ifThenElse',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 2
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ],
                        (object) [
                            'type' => 'ifThenElse',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 3
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => NULL
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => NULL
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement10a(): void
    {
        $expression1 = "
            if (1) {}
            else if (2) {}
            else {}
        ";

        $expression2 = "
            if(1){}else if(2){}else{}
        ";

        $actual1 = $this->parse($expression1);
        $actual2 = $this->parse($expression2);

        $this->assertEquals($actual1, $actual2);
    }

    public function testIfStatement10b(): void
    {
        $expression1 = "
            if (
                1
            ) {}
            else if (2) {}
            else {}
        ";

        $expression2 = "
            if(1){}else if(2){}else{}
        ";

        $actual1 = $this->parse($expression1);
        $actual2 = $this->parse($expression2);

        $this->assertEquals($actual1, $actual2);
    }

    public function testIfStatement11(): void
    {
        $expression = "
            if (1)
            {}
            else if (2) {
                if (21) {} else {}
            }
            else {}
        ";

        $expected = (object) [
            'type' => 'ifThenElse',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 1
                ],
                (object) [
                    'type' => 'value',
                    'value' => NULL
                ],
                (object) [
                    'type' => 'ifThenElse',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 2
                        ],
                        (object) [
                            'type' => 'ifThenElse',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 21
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => NULL
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => NULL
                                ]
                            ]
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testIfStatement12(): void
    {
        $expression = "
            if (
                if () {1}
            ) {}
        ";

        $this->expectException(SyntaxError::class);

        $this->parse($expression);
    }

    public function testIfStatement13(): void
    {
        $expression = "
            test(
                if () {}
            );
        ";

        $this->expectException(SyntaxError::class);

        $this->parse($expression);
    }

    public function testIfStatement14(): void
    {
        $expression = "
            if (1) {
                \$test = 1;
            }else if (2) {
                \$test = 2;
            }
        ";

        $expected = (object) [
            'type' => 'ifThenElse',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => 1
                ],
                (object) [
                    'type' => 'assign',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'test'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
                (object) [
                    'type' => 'ifThen',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 2
                        ],
                        (object) [
                            'type' => 'assign',
                            'value' => [
                                (object) [
                                    'type' => 'value',
                                    'value' => 'test'
                                ],
                                (object) [
                                    'type' => 'value',
                                    'value' => 2
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testWhileStatement(): void
    {
        $expression = "
            while (\$i < 1) {
                \$i = \$i + 1;
            }
        ";

        $expected = (object) [
            'type' => 'while',
            'value' => [
                (object) [
                    'type' => 'comparison\\lessThan',
                    'value' => [
                        (object) [
                            'type' => 'variable',
                            'value' => 'i'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
                (object) [
                    'type' => 'assign',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => 'i'
                        ],
                        (object) [
                            'type' => 'numeric\\summation',
                            'value' => [
                                (object) [
                                    'type' => 'variable',
                                    'value' => 'i'
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

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testWhileStatement2(): void
    {
        $expression1 = "
            while (\$i < 1) {
                \$i = \$i + 1;
            }
        ";

        $expression2 = "
            while(\$i < 1){\$i = \$i + 1;}
        ";

        $actual1 = $this->parse($expression1);
        $actual2 = $this->parse($expression2);

        $this->assertEquals($actual1, $actual2);
    }

    public function testWhileStatement3(): void
    {
        $expression = "
            \$i + 1;
            while (false) {}
        ";

        $expected = (object) [
            'type' => 'bundle',
            'value' => [
                (object) [
                    'type' => 'numeric\\summation',
                    'value' => [
                        (object) [
                            'type' => 'variable',
                            'value' => 'i'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
                (object) [
                    'type' => 'while',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => false
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testWhileStatement4(): void
    {
        $expression = "
            \$i + 1;
            while (false) {}
            \$i + 1;
        ";

        $expected = (object) [
            'type' => 'bundle',
            'value' => [
                (object) [
                    'type' => 'numeric\\summation',
                    'value' => [
                        (object) [
                            'type' => 'variable',
                            'value' => 'i'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
                (object) [
                    'type' => 'while',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => false
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ],
                (object) [
                    'type' => 'numeric\\summation',
                    'value' => [
                        (object) [
                            'type' => 'variable',
                            'value' => 'i'
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => 1
                        ]
                    ]
                ],
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testWhileStatement5(): void
    {
        $expression = "
            while (false) {}
            while (false) {}
            while (false) {}
        ";

        $expected = (object) [
            'type' => 'bundle',
            'value' => [
                (object) [
                    'type' => 'while',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => false
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ],
                (object) [
                    'type' => 'while',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => false
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ],
                (object) [
                    'type' => 'while',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => false
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testWhileStatement6(): void
    {
        $expression = "
            while (false) {
            };
            while (false) {}
            while (false) {}
        ";

        $expected = (object) [
            'type' => 'bundle',
            'value' => [
                (object) [
                    'type' => 'while',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => false
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ],
                (object) [
                    'type' => 'while',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => false
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ],
                (object) [
                    'type' => 'while',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => false
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testWhileStatement7(): void
    {
        $expression = "
            if (
                while () {1}
            ) {}
        ";

        $this->expectException(SyntaxError::class);

        $this->parse($expression);
    }

    public function testWhileStatement8(): void
    {
        $expression = "
            while (true) {
                while (true) {
                }
            }
        ";

        $expected = (object) [
            'type' => 'while',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => true
                ],
                (object) [
                    'type' => 'while',
                    'value' => [
                        (object) [
                            'type' => 'value',
                            'value' => true
                        ],
                        (object) [
                            'type' => 'value',
                            'value' => NULL
                        ]
                    ]
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testWhileStatement9(): void
    {
        $expression = "
            while (false) {
            };
        ";

        $expected = (object) [
            'type' => 'while',
            'value' => [
                (object) [
                    'type' => 'value',
                    'value' => false
                ],
                (object) [
                    'type' => 'value',
                    'value' => NULL
                ]
            ]
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testWhileStatement10(): void
    {
        $expression1 = "
            while(false) {
                while(false) {

                }
            }
        ";

        $expression2 = "
            while(false){while(false){}}
        ";

        $actual1 = $this->parse($expression1);
        $actual2 = $this->parse($expression2);

        $this->assertEquals($actual1, $actual2);
    }

    public function testStatementAfterExpression1(): void
    {
        $expression = "
            1 + 1 if () then {}
        ";

        $this->expectException(SyntaxError::class);

        $this->parse($expression);
    }

    public function testCommentIf1(): void
    {
        $expression = "
            /*if (1) {}*/
        ";

        $expected = (object) [
            'type' => 'value',
            'value' => NULL
        ];

        $actual = $this->parse($expression);

        $this->assertEquals($expected, $actual);
    }
}
