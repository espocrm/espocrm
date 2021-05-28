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

use Espo\Core\Formula\Evaluator;
use Espo\Core\InjectableFactory;

use Espo\Core\Utils\Log;

use tests\unit\ContainerMocker;

class EvaluatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Evaluator
     */
    private $evaluator;

    protected function setUp() : void
    {
        $log = $this->getMockBuilder(Log::class)->disableOriginalConstructor()->getMock();

        $containerMocker = new ContainerMocker($this);

        $container = $containerMocker->create([
            'log' => $log,
        ]);

        $injectableFactory = $injectableFactory = new InjectableFactory($container);

        $this->evaluator = new Evaluator($injectableFactory);
    }

    protected function tearDown() : void
    {
        $this->evaluator = null;
    }

    function testEvaluateMathExpression1()
    {
        $expression = "5 - (2 + 1)";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(2, $actual);
    }

    function testEvaluateList1()
    {
        $expression = "list()";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals([], $actual);
    }

    function testEvaluateList2()
    {
        $expression = "list(1)";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals([1], $actual);
    }

    function testEvaluateEmpty()
    {
        $expression = '';
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(null, $actual);
    }

    function testNotEqualsNull()
    {
        $expression = "5 != null";
        $actual = $this->evaluator->process($expression);
        $this->assertTrue($actual);
    }

    function testSummationOfMultipleIfThenElse()
    {
        $expression = "
            ifThenElse(
                true,
                (1 + 0 + 1) - 1 * 0.5,
                0
            )
            +
            ifThenElse(
                true,
                (1 - 0) * 0.5,
                0
            )
            +
            ifThenElse(
                true,
                (1 - 0) * 0.5,
                0
            )
        ";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(2.5, $actual);
    }

    function testStringPad()
    {
        $expression = "string\\pad('1', 3, '0')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('100', $actual);

        $expression = "string\\pad('1', 3)";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('1  ', $actual);

        $expression = "string\\pad('11', 4, '0', 'left')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('0011', $actual);

        $expression = "string\\pad('11', 4, '0', 'both')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('0110', $actual);
    }

    function testStringMatchAll()
    {
        $expression = "string\\matchAll('{token1} foo {token2} bar', '/{[^}]*}/')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(['{token1}', '{token2}'], $actual);

        $expression = "string\\matchAll('foo bar', '/{[^}]*}/')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(null, $actual);

        $expression = "string\\matchAll('{token1} foo {token2} bar', '/{[^}]*}/', 5)";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(['{token2}'], $actual);
    }

    function testStringMatch()
    {
        $expression = "string\\match('{token1} foo {token2} bar', '/{[^}]*}/')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('{token1}', $actual);

        $expression = "string\\match('foo bar', '/{[^}]*}/')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(null, $actual);

        $expression = "string\\match('{token1} foo {token2} bar', '/{[^}]*}/', 5)";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('{token2}', $actual);
    }

    function testStringReplace()
    {
        $expression = "string\\replace('hello {test} hello', '{test}', 'hello')";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals('hello hello hello', $actual);
    }

    function testArrayAt()
    {
        $expression = "array\\at(list(1, 2, 4, 8, 16), 2)";
        $actual = $this->evaluator->process($expression);
        $this->assertEquals(4, $actual);
    }

    public function testArrayJoin()
    {
        $expression = "array\\join(list('0', '1'), '-')";

        $expected = '0-1';

        $actual = $this->evaluator->process($expression);

        $this->assertEquals($expected, $actual);
    }

    public function testWhile()
    {
        $expression = "
            \$source = list(0, 1, 2);
            \$target = list();

            \$i = 0;
            while(\$i < array\\length(\$source),
                \$target = array\\push(
                    \$target,
                    array\\at(\$source, \$i)
                );
                \$i = \$i + 1;
            );
        ";

        $vars = (object) [];

        $this->evaluator->process($expression, null, $vars);

        $this->assertEquals([0, 1, 2], $vars->target);
    }

    public function testComment1()
    {
        $expression = "
            // test
            \$test = '1';
        ";

        $vars = (object) [];
        $this->evaluator->process($expression, null, $vars);
        $this->assertEquals('1', $vars->test);
    }

    public function testComment2()
    {
        $expression = "
            // test'test
            \$test = '1';
        ";

        $vars = (object) [];
        $this->evaluator->process($expression, null, $vars);
        $this->assertEquals('1', $vars->test);
    }

    public function testComment3()
    {
        $expression = "
            // test\"test
            \$test = '1';
        ";

        $vars = (object) [];
        $this->evaluator->process($expression, null, $vars);
        $this->assertEquals('1', $vars->test);
    }

    public function testComment4()
    {
        $expression = "
            // test)(test
            \$test = '1';
        ";

        $vars = (object) [];
        $this->evaluator->process($expression, null, $vars);
        $this->assertEquals('1', $vars->test);
    }

    public function testComment5()
    {
        $expression = "
            /* test'test
            */
            \$test = '1';
        ";

        $vars = (object) [];
        $this->evaluator->process($expression, null, $vars);
        $this->assertEquals('1', $vars->test);
    }

    public function testComment6()
    {
        $expression = "
            /* test(test
            */
            \$test = '1';
        ";

        $vars = (object) [];
        $this->evaluator->process($expression, null, $vars);
        $this->assertEquals('1', $vars->test);
    }

    public function testComment7()
    {
        $expression = "
            \$test = '/* 1 */';
        ";

        $vars = (object) [];
        $this->evaluator->process($expression, null, $vars);
        $this->assertEquals('/* 1 */', $vars->test);
    }

    public function testComment8()
    {
        $expression = "
            \$test = '// 1 */';
        ";

        $vars = (object) [];
        $this->evaluator->process($expression, null, $vars);
        $this->assertEquals('// 1 */', $vars->test);
    }

    public function testIntValue()
    {
        $expression = "0";

        $value = $this->evaluator->process($expression);

        $this->assertTrue(is_int($value));
    }

    public function testFloatZeroDecimals()
    {
        $expression = "1.0";

        $value = $this->evaluator->process($expression);

        $this->assertTrue(is_float($value));
    }

    public function testJsonRetrieve1()
    {
        $value = (object) [
            'a' => 'test',
        ];

        $expression = "json\\retrieve(\$value, 'a')";

        $result = $this->evaluator->process($expression, null, (object) [
            'value' => json_encode($value),
        ]);

        $this->assertEquals('test', $result);
    }

    public function testJsonRetrieve2()
    {
        $value =  [
            0 => 'test',
        ];

        $expression = "json\\retrieve(\$value, '0')";

        $result = $this->evaluator->process($expression, null, (object) [
            'value' => json_encode($value),
        ]);

        $this->assertEquals('test', $result);
    }

    public function testJsonRetrieve3()
    {
        $value = (object) [
            'a' => [
                'ab' => 'test'
            ],
        ];

        $expression = "json\\retrieve(\$value, 'a.ab')";

        $result = $this->evaluator->process($expression, null, (object) [
            'value' => json_encode($value),
        ]);

        $this->assertEquals('test', $result);
    }

    public function testJsonRetrieve4()
    {
        $value = (object) [
            'a' => [
                'ab' => 'test'
            ],
        ];

        $expression = "json\\retrieve(\$value, 'b.c')";

        $result = $this->evaluator->process($expression, null, (object) [
            'value' => json_encode($value),
        ]);

        $this->assertEquals(null, $result);
    }

    public function testJsonRetrieve5()
    {
        $value = (object) [
            'a' => [
                'ab' => 'test'
            ],
        ];

        $expression = "json\\retrieve(\$value, '0')";

        $result = $this->evaluator->process($expression, null, (object) [
            'value' => json_encode($value),
        ]);

        $this->assertEquals(null, $result);
    }

    public function testJsonRetrieve6()
    {
        $value =  [
            0 => (object) [
                'a' => 'test'
            ],
        ];

        $expression = "json\\retrieve(\$value, '0.a')";

        $result = $this->evaluator->process($expression, null, (object) [
            'value' => json_encode($value),
        ]);

        $this->assertEquals('test', $result);
    }

    public function testJsonRetrieve7()
    {
        $value = (object) [
            'a.b' => (object) [
                'c' => 'test'
            ],
        ];

        $expression = "json\\retrieve(\$value, 'a\\.b.c')";

        $result = $this->evaluator->process($expression, null, (object) [
            'value' => json_encode($value),
        ]);

        $this->assertEquals('test', $result);
    }

    public function testNegate1()
    {
        $expression = "!string\contains('hello', 'test')";

        $result = $this->evaluator->process($expression);

        $this->assertTrue($result);
    }

    public function testLogicalProority()
    {
        $expression = "0 && 0 || 1";

        $result = $this->evaluator->process($expression);

        $this->assertTrue($result);
    }

    public function testGenerateId()
    {
        $expression = "util\generateId()";

        $id = $this->evaluator->process($expression);

        $this->assertIsString($id);

        $this->assertNotEmpty($id);
    }

    public function testModulo1() : void
    {
        $expression = "123 % 5";
        $actual = $this->evaluator->process($expression);

        $this->assertEquals(123 % 5, $actual);
    }

    public function testModulo2() : void
    {
        $expression = "124 % 5";
        $actual = $this->evaluator->process($expression);

        $this->assertEquals(124 % 5, $actual);
    }

    function testParentheses1()
    {
        $expression = "
            \$test = 1;

            ifThen(
                true,
                (
                    \$hello = 2;
                    \$test = \$hello;
                )
            );
        ";

        $vars = (object) [];

        $this->evaluator->process($expression, null, $vars);

        $this->assertEquals(2, $vars->test);

    }
}
