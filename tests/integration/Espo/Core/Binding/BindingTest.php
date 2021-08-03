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

namespace tests\integration\Espo\Core\Binding;

use Espo\Core\{
    Container\ContainerBuilder,
    Binding\BindingLoader,
    Binding\BindingData,
    Binding\Binding,
};

use tests\integration\testClasses\Binding\{
    SomeInterface,
    SomeImplementation,
    SomeClass,
    SomeService,
    SomeClassRequiringService,
    SomeClassRequiringValue,
    SomeFactory,
};

class BindingTest extends \tests\integration\Core\BaseTestCase
{
    public function testImplementation(): void
    {
        $bindingLoader = new class() implements BindingLoader
        {
            public function load(): BindingData
            {
                $data = new BindingData();

                $data->addGlobal(
                    SomeInterface::class,
                    Binding::createFromImplementationClassName(
                        SomeImplementation::class
                    )
                );

                return $data;
            }
        };

        $container = (new ContainerBuilder())
            ->withBindingLoader($bindingLoader)
            ->build();

        $injectableFactory = $container->get('injectableFactory');

        $obj = $injectableFactory->create(SomeClass::class);

        $this->assertNotNull($obj);

        $this->assertInstanceOf(
            SomeImplementation::class,
            $obj->get()
        );
    }

    public function testFactory(): void
    {
        $bindingLoader = new class() implements BindingLoader
        {
            public function load(): BindingData
            {
                $data = new BindingData();

                $data->addGlobal(
                    SomeInterface::class,
                    Binding::createFromFactoryClassName(
                        SomeFactory::class
                    )
                );

                return $data;
            }
        };

        $container = (new ContainerBuilder())
            ->withBindingLoader($bindingLoader)
            ->build();

        $injectableFactory = $container->get('injectableFactory');

        $obj = $injectableFactory->create(SomeClass::class);

        $this->assertNotNull($obj);

        $this->assertInstanceOf(
            SomeImplementation::class,
            $obj->get()
        );
    }

    public function testCallback()
    {
        $bindingLoader = new class() implements BindingLoader
        {
            public function load() : BindingData
            {
                $data = new BindingData();

                $data->addGlobal(
                    SomeInterface::class,
                    Binding::createFromCallback(
                        function (SomeImplementation $some) {
                            return $some;
                        }
                    )
                );

                return $data;
            }
        };

        $container = (new ContainerBuilder())
            ->withBindingLoader($bindingLoader)
            ->build();

        $injectableFactory = $container->get('injectableFactory');

        $obj = $injectableFactory->create(SomeClass::class);

        $this->assertNotNull($obj);

        $this->assertInstanceOf(
            SomeImplementation::class,
            $obj->get()
        );
    }

    public function testService()
    {
        $bindingLoader = new class() implements BindingLoader
        {
            public function load() : BindingData
            {
                $data = new BindingData();

                $data->addGlobal(
                    SomeService::class,
                    Binding::createFromServiceName('someService')
                );

                return $data;
            }
        };

        $someService = new SomeService();

        $container = (new ContainerBuilder())
            ->withServices([
                'someService' => $someService,
            ])
            ->withBindingLoader($bindingLoader)
            ->build();

        $injectableFactory = $container->get('injectableFactory');

        $obj = $injectableFactory->create(SomeClassRequiringService::class);

        $this->assertNotNull($obj);

        $this->assertSame(
            $someService,
            $obj->getService()
        );
    }

    public function testValue()
    {
        $bindingLoader = new class() implements BindingLoader
        {
            public function load() : BindingData
            {
                $data = new BindingData();

                $data->addContext(
                    SomeClassRequiringValue::class,
                    '$value',
                    Binding::createFromValue('TEST_VALUE')
                );

                return $data;
            }
        };

        $container = (new ContainerBuilder())
            ->withBindingLoader($bindingLoader)
            ->build();

        $injectableFactory = $container->get('injectableFactory');

        $obj = $injectableFactory->create(SomeClassRequiringValue::class);

        $this->assertNotNull($obj);

        $this->assertSame(
            'TEST_VALUE',
            $obj->getValue()
        );
    }
}
