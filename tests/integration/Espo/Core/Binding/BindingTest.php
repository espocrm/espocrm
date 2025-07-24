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

namespace tests\integration\Espo\Core\Binding;

use Espo\Core\Application\ApplicationParams;
use Espo\Core\Binding\Binding;
use Espo\Core\Binding\BindingData;
use Espo\Core\Binding\BindingLoader;
use Espo\Core\Container\ContainerBuilder;

use tests\integration\Core\BaseTestCase;
use tests\integration\testClasses\Binding\SomeClass;
use tests\integration\testClasses\Binding\SomeClassRequiringService;
use tests\integration\testClasses\Binding\SomeClassRequiringValue;
use tests\integration\testClasses\Binding\SomeFactory;
use tests\integration\testClasses\Binding\SomeImplementation;
use tests\integration\testClasses\Binding\SomeInterface;
use tests\integration\testClasses\Binding\SomeService;

class BindingTest extends BaseTestCase
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
            ->withParams(new ApplicationParams(noErrorHandler: true))
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
            ->withParams(new ApplicationParams(noErrorHandler: true))
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
            ->withParams(new ApplicationParams(noErrorHandler: true))
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
            ->withParams(new ApplicationParams(noErrorHandler: true))
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
            ->withParams(new ApplicationParams(noErrorHandler: true))
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
