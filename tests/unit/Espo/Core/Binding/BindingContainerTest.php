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

namespace tests\unit\Espo\Core\Binding;

use Espo\Core\Binding\Binder;
use Espo\Core\Binding\Binding;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Binding\BindingData;
use Espo\Core\Binding\BindingLoader;
use Espo\Core\Binding\ContextualBinder;
use Espo\Core\Binding\Key\NamedClassKey;
use Espo\Core\Binding\Key\NamedKey;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;

use tests\unit\testClasses\Core\Binding\Class0;
use tests\unit\testClasses\Core\Binding\Class1;
use tests\unit\testClasses\Core\Binding\SomeInterface1;
use tests\unit\testClasses\Core\Binding\SomeInterface2;
use tests\unit\testClasses\Core\Binding\SomeClass1;
use tests\unit\testClasses\Core\Binding\SomeClass2;

class BindingContainerTest extends TestCase
{
    /** @var Binder */
    private $binder;
    private $loader;

    protected function setUp(): void
    {
        $this->loader = $this->createMock(BindingLoader::class);

        $data = new BindingData();

        $this->binder = new Binder($data);

        $this->loader
            ->expects($this->any())
            ->method('load')
            ->willReturn($data);
    }

    protected function createClassMock(string $className) : ReflectionClass
    {
        $class = $this->createMock(ReflectionClass::class);

        $class
            ->expects($this->any())
            ->method('getName')
            ->willReturn($className);

        return $class;
    }

    protected function createParamMock(string $name, ?string $className = null) : ReflectionParameter
    {
        $param = $this->createMock(ReflectionParameter::class);

        $class = null;

        $type = $this->createMock(ReflectionNamedType::class);

        if ($className) {
            $class = $this->createClassMock($className);

            $type
                ->expects($this->any())
                ->method('isBuiltin')
                ->willReturn(false);

            $type
                ->expects($this->any())
                ->method('getName')
                ->willReturn($className);
        }

        $type
            ->expects($this->any())
            ->method('isBuiltin')
            ->willReturn(true);

        $param
            ->expects($this->any())
            ->method('getType')
            ->willReturn($type);

        $param
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        $param
            ->expects($this->any())
            ->method('getClass')
            ->willReturn($class);

        return $param;
    }

    protected function createContainer(): BindingContainer
    {
        return new BindingContainer($this->loader->load());
    }

    public function testHasTrue()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertTrue(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testHasNoContextTrue()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertTrue(
            $this->createContainer()->hasByParam(null, $param)
        );
    }

    public function testHasFalse()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Hello');

        $this->assertFalse(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testHasNoContextFalse()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $param = $this->createParamMock('test', 'Espo\\Hello');

        $this->assertFalse(
            $this->createContainer()->hasByParam(null, $param)
        );
    }

    public function testHasContextTrue0()
    {
        $this->binder
            ->inContext('Espo\\Context', function (ContextualBinder $binder): void {
                $binder->bindService('Espo\\Test', 'test');
            });

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertTrue(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testHasContextTrue1()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertTrue(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testHasContextTrue2()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindValue('$test', 'Test Value');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test');

        $this->assertTrue(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testHasContextTrue3()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindValue(NamedKey::create('test'), 'Test Value');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test');

        $this->assertTrue(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testHasContextTrue4()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindService(NamedClassKey::create('Espo\\Test', 'test'), 'service');

        $class = $this->createClassMock('Espo\\Context');
        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertTrue(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testHasContextFalse1()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');
        $param = $this->createParamMock('test', 'Espo\\Hello');

        $this->assertFalse(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testHasContextFalse2()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\ContextOther');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertFalse(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testHasContextFalse3()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindValue('$test', 'Test Value');

        $class = $this->createClassMock('Espo\\Other');

        $param = $this->createParamMock('test');

        $this->assertFalse(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testGetClassNameImplementation(): void
    {
        $this->binder->bindImplementation('Espo\\Test', 'Espo\\ImplTest');

        $class = $this->createClassMock('Espo\\Context');
        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::IMPLEMENTATION_CLASS_NAME, $binding->getType());
        $this->assertEquals('Espo\\ImplTest', $binding->getValue());
    }

    public function testGetClassNameFactory(): void
    {
        $this->binder->bindFactory('Espo\\Test', 'Espo\\TestFactory');

        $class = $this->createClassMock('Espo\\Context');
        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::FACTORY_CLASS_NAME, $binding->getType());
        $this->assertEquals('Espo\\TestFactory', $binding->getValue());
    }

    public function testGetService()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('test', $binding->getValue());
    }

    public function testGetCallback()
    {
        $this->binder->bindCallback(
            'Espo\\Test',
            function () {
                return 'test';
            }
        );

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CALLBACK, $binding->getType());

        $this->assertIsCallable($binding->getValue());
    }

    public function testBindInstance()
    {
        $className = 'Espo\\Core\\Application';

        $instance = $this->createMock($className);

        $this->binder->bindInstance($className, $instance);

        $param = $this->createParamMock('test', $className);

        $binding = $this->createContainer()->getByParam(null, $param);

        $this->assertEquals(Binding::VALUE, $binding->getType());

        $this->assertSame($instance, $binding->getValue());
    }

    public function testContextBindInstance()
    {
        $className = 'Espo\\Core\\Application';

        $instance = $this->createMock($className);

        $this->binder
            ->for('Espo\\Context')
            ->bindInstance($className, $instance);

        $param = $this->createParamMock('test', $className);

        $class = $this->createClassMock('Espo\\Context');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::VALUE, $binding->getType());

        $this->assertSame($instance, $binding->getValue());
    }

    public function testContextGetCallback()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindCallback(
                'Espo\\Test',
                function () {
                    return 'test';
                }
            );

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CALLBACK, $binding->getType());

        $this->assertIsCallable($binding->getValue());
    }

    public function testRebindGlobal()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $this->binder->bindService('Espo\\Test', 'testHello');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('testHello', $binding->getValue());
    }

    public function testBindInterfaceWithParamNameGlobal()
    {
        $this->binder->bindService('Espo\\Test $name', 'testName');

        $this->binder->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('name', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('testName', $binding->getValue());

        $param = $this->createParamMock('hello', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('test', $binding->getValue());
    }

    public function testContextGetClassNameImplementation(): void
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindImplementation('Espo\\Test', 'Espo\\ImplTest');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::IMPLEMENTATION_CLASS_NAME, $binding->getType());
        $this->assertEquals('Espo\\ImplTest', $binding->getValue());
    }

    public function testContextGetClassNameFactory(): void
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindFactory('Espo\\Test', 'Espo\\TestFactory');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::FACTORY_CLASS_NAME, $binding->getType());
        $this->assertEquals('Espo\\TestFactory', $binding->getValue());
    }

    public function testNoContextClassName()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindImplementation('Espo\\Test', 'Espo\\ImplTest');

        $class = $this->createClassMock('Espo\\AnotherContext');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertFalse(
            $this->createContainer()->hasByParam($class, $param)
        );
    }

    public function testBindContextInterfaceWithParamNameGlobal()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindService('Espo\\Test $name', 'testName');

        $this->binder->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('name', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('testName', $binding->getValue());

        $param = $this->createParamMock('hello', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('test', $binding->getValue());
    }

    public function testGetContextParamValue()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindValue('$test', 'Test Value');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::VALUE, $binding->getType());

        $this->assertEquals('Test Value', $binding->getValue());
    }

    public function testGetContextInterfaceValue1()
    {
        $instance = (object) [];

        $this->binder
            ->for('Espo\\Context')
            ->bindValue('Espo\\SomeClass $test', $instance);

        $class = $this->createClassMock('Espo\\Context');
        $param = $this->createParamMock('test', 'Espo\\SomeClass');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::VALUE, $binding->getType());
        $this->assertEquals($instance, $binding->getValue());
    }

    public function testGetContextInterfaceValue2()
    {
        $instance = (object) [];

        $this->binder
            ->for('Espo\\Context')
            ->bindValue(NamedClassKey::create('Espo\\SomeClass', 'test'), $instance);

        $class = $this->createClassMock('Espo\\Context');
        $param = $this->createParamMock('test', 'Espo\\SomeClass');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::VALUE, $binding->getType());
        $this->assertEquals($instance, $binding->getValue());
    }

    public function testGetContextService()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('test', $binding->getValue());
    }

    public function testRebindContextService()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindService('Espo\\Test', 'test');

        $this->binder
            ->for('Espo\\Context')
            ->bindService('Espo\\Test', 'testHello');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->getByParam($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('testHello', $binding->getValue());
    }

    public function testBindingContainerBuilder1(): void
    {
        $container = BindingContainerBuilder::create()
            ->bindImplementation(SomeInterface1::class, SomeClass1::class)
            ->inContext(SomeClass1::class, function (ContextualBinder $binder): void {
                $binder->bindImplementation(SomeInterface2::class, SomeClass2::class);
            })
            ->build();

        $param1 = $this->createParamMock('test', SomeInterface1::class);
        $this->assertTrue($container->hasByParam(null, $param1));

        $param2 = $this->createParamMock('test', SomeInterface2::class);
        $this->assertFalse($container->hasByParam(null, $param2));

        $class3 = $this->createClassMock(SomeClass1::class);
        $param3 = $this->createParamMock('test', SomeInterface2::class);
        $this->assertTrue($container->hasByParam($class3, $param3));
    }

    public function testTypedParamWithScalarBound1(): void
    {
        $container = BindingContainerBuilder::create()
            ->inContext(Class0::class, function (ContextualBinder $binder): void {
                $binder->bindValue('$dep', 'test');
            })
            ->build();

        $class = new ReflectionClass(Class0::class);

        $constructor = $class->getConstructor();

        $params = $constructor->getParameters();

        $this->assertFalse($container->hasByParam($class, $params[0]));
    }

    public function testTypedParamWithScalarBound2(): void
    {
        $container = BindingContainerBuilder::create()
            ->inContext(Class0::class, function (ContextualBinder $binder): void {
                $binder->bindValue('$dep', new Class1());
            })
            ->build();

        $class = new ReflectionClass(Class0::class);

        $constructor = $class->getConstructor();

        $params = $constructor->getParameters();

        $binding = $container->getByParam($class, $params[0]);

        $this->assertEquals(Binding::VALUE, $binding->getType());
        $this->assertInstanceOf(Class1::class, $binding->getValue());
    }
}
