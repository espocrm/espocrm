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

namespace tests\unit\Espo\Core\Binding;

use Espo\Core\{
    Binding\BindingContainer,
    Binding\BindingLoader,
    Binding\BindingData,
    Binding\Binder,
    Binding\Binding,
};

use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;

class BindingContainerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->loader = $this->getMockBuilder(BindingLoader::class)->disableOriginalConstructor()->getMock();

        $this->data = new BindingData();

        $this->binder = new Binder($this->data);

        $this->loader
            ->expects($this->any())
            ->method('load')
            ->willReturn($this->data);
    }

    protected function createClassMock(string $className) : ReflectionClass
    {
        $class = $this->getMockBuilder(ReflectionClass::class)->disableOriginalConstructor()->getMock();

        $class
            ->expects($this->any())
            ->method('getName')
            ->willReturn($className);

        return $class;
    }

    protected function createParamMock(string $name, ?string $className = null) : ReflectionParameter
    {
        $param = $this->getMockBuilder(ReflectionParameter::class)->disableOriginalConstructor()->getMock();

        $class = null;

        $type = $this->getMockBuilder(ReflectionNamedType::class)->disableOriginalConstructor()->getMock();

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

    protected function createContainer()
    {
        return new BindingContainer($this->loader);
    }

    public function testHasTrue()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertTrue(
            $this->createContainer()->has($class, $param)
        );
    }

    public function testHasNoContextTrue()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertTrue(
            $this->createContainer()->has(null, $param)
        );
    }

    public function testHasFalse()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Hello');

        $this->assertFalse(
            $this->createContainer()->has($class, $param)
        );
    }

    public function testHasNoContextFalse()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $param = $this->createParamMock('test', 'Espo\\Hello');

        $this->assertFalse(
            $this->createContainer()->has(null, $param)
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
            $this->createContainer()->has($class, $param)
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
            $this->createContainer()->has($class, $param)
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
            $this->createContainer()->has($class, $param)
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
            $this->createContainer()->has($class, $param)
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
            $this->createContainer()->has($class, $param)
        );
    }

    public function testGetClassName()
    {
        $this->binder->bindImplementation('Espo\\Test', 'Espo\\ImplTest');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::IMPLEMENTATION_CLASS_NAME, $binding->getType());

        $this->assertEquals('Espo\\ImplTest', $binding->getValue());
    }

    public function testGetService()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->get($class, $param);

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

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::CALLBACK, $binding->getType());

        $this->assertIsCallable($binding->getValue());
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

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::CALLBACK, $binding->getType());

        $this->assertIsCallable($binding->getValue());
    }

    public function testRebindGlobal()
    {
        $this->binder->bindService('Espo\\Test', 'test');

        $this->binder->bindService('Espo\\Test', 'testHello');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('testHello', $binding->getValue());
    }

    public function testBindInterfaceWithParamNameGlobal()
    {
        $this->binder->bindService('Espo\\Test $name', 'testName');

        $this->binder->bindService('Espo\\Test', 'test');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('name', 'Espo\\Test');

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('testName', $binding->getValue());

        $param = $this->createParamMock('hello', 'Espo\\Test');

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('test', $binding->getValue());
    }

    public function testContextGetClassName()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindImplementation('Espo\\Test', 'Espo\\ImplTest');

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::IMPLEMENTATION_CLASS_NAME, $binding->getType());

        $this->assertEquals('Espo\\ImplTest', $binding->getValue());
    }


    public function testNoContextClassName()
    {
        $this->binder
            ->for('Espo\\Context')
            ->bindImplementation('Espo\\Test', 'Espo\\ImplTest');

        $class = $this->createClassMock('Espo\\AnotherContext');

        $param = $this->createParamMock('test', 'Espo\\Test');

        $this->assertFalse(
            $this->createContainer()->has($class, $param)
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

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('testName', $binding->getValue());

        $param = $this->createParamMock('hello', 'Espo\\Test');

        $binding = $this->createContainer()->get($class, $param);

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

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::VALUE, $binding->getType());

        $this->assertEquals('Test Value', $binding->getValue());
    }

    public function testGetContextInterfaceValue()
    {
        $instance = (object) [];

        $this->binder
            ->for('Espo\\Context')
            ->bindValue('Espo\\SomeClass', $instance);

        $class = $this->createClassMock('Espo\\Context');

        $param = $this->createParamMock('test', 'Espo\\SomeClass');

        $binding = $this->createContainer()->get($class, $param);

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

        $binding = $this->createContainer()->get($class, $param);

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

        $binding = $this->createContainer()->get($class, $param);

        $this->assertEquals(Binding::CONTAINER_SERVICE, $binding->getType());

        $this->assertEquals('testHello', $binding->getValue());
    }
}
