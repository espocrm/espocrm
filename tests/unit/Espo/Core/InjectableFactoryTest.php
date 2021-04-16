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

namespace tests\unit\Espo\Core;

use Espo\Core\{
    Binding\BindingData,
    Binding\Binder,
    Binding\BindingContainer,
    InjectableFactory,
    Container,
};

use tests\integration\testClasses\Binding\{
    SomeInterface,
    SomeClass,
};

use tests\unit\testClasses\Core\Binding\{
    SomeClass0,
    SomeClass1,
    SomeInterface1,
    SomeClass2,
    SomeInterface2,
};

class InjectableFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateWithBinding1(): void
    {
        $container = $this->createMock(Container::class);

        $injectableFactory = new InjectableFactory($container);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $instance = $this->createMock(SomeInterface::class);

        $binder->bindInstance(SomeInterface::class, $instance);

        $obj = $injectableFactory->createWithBinding(SomeClass::class, new BindingContainer($bindingData));

        $this->assertNotNull($obj);

        $this->assertSame($instance, $obj->get());
    }

    public function testCreateWithBinding2(): void
    {
        $container = $this->createMock(Container::class);

        $injectableFactory = new InjectableFactory($container);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindImplementation(SomeInterface1::class, SomeClass1::class)
            ->bindImplementation(SomeInterface2::class, SomeClass2::class);

        $obj = $injectableFactory->createWithBinding(SomeClass0::class, new BindingContainer($bindingData));

        $this->assertNotNull($obj);
    }
}
