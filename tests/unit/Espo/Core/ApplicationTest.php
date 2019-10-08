<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use tests\unit\ReflectionHelper;

/**
 * Contains tests for application class
 */
class ApplicationTest extends \PHPUnit\Framework\TestCase
{

    private $app = null;

    private $reflectionOfApp = null;

    public static function setUpBeforeClass(): void
    {
        
    }

    public function setUp(): void
    {
        $this->app = new \Espo\Core\Application();
        $this->reflectionOfApp = new ReflectionHelper($this->app);
    }

    /**
     * @test
     */
    public function createAuth()
    {
        $auth = $this->reflectionOfApp->invokeMethod("createAuth", [$this->app->getContainer()]);
        $this->assertInstanceOf(\Espo\Core\Utils\Auth::class, $auth);
    }

    /**
     * @test
     */
    public function getSlim()
    {
        $slim = $this->app->getSlim();
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function isInstalledTrue()
    {
        // Mock the config because config tells whether app is installed or not
        $configDouble = $this->getMockBuilder(\Espo\Core\Utils\Config::class)
                                ->disableOriginalConstructor()
                                ->setMethods(["getConfigPath", "get"])
                                ->getMock();

        // Mock getConfigPath to always return "application/Espo/Core/defaults/config.php" as return response
        $configDouble->method("getConfigPath")->willReturn("application/Espo/Core/defaults/config.php");

        // Mock get to always return true
        // With tells the exact parameters sent to the get function
        $configDouble->method("get")->with($this->stringContains("isInstalled"))->willReturn(true);

        // Use reflection helper to update the existing container config object with its test double.
        $containerReflection = new ReflectionHelper($this->app->getContainer());
        $containerReflection->setProperty("data", ["config" => $configDouble]);

        // Run the assertion
        $this->assertTrue($this->app->isInstalled());
    }

    /**
     * @test
     */
    public function isInstalledFalse()
    {
        // Mock the config because config tells whether app is installed or not
        $configDouble = $this->getMockBuilder(\Espo\Core\Utils\Config::class)
                                ->disableOriginalConstructor()
                                ->setMethods(["getConfigPath", "get"])
                                ->getMock();

        // Mock getConfigPath to always return "application/Espo/Core/defaults/config.php" as return response
        $configDouble->method("getConfigPath")->willReturn("application/Espo/Core/defaults/config.php");

        // Mock get to always return true
        $configDouble->method("get")->with($this->stringContains("isInstalled"))->willReturn(false);

        // Use reflection helper to update the existing container config object with its test double.
        $containerReflection = new ReflectionHelper($this->app->getContainer());
        $containerReflection->setProperty("data", ["config" => $configDouble]);

        // Run the assertion
        $this->assertFalse($this->app->isInstalled());
    }

    public function tearDown(): void
    {
    }

    public static function tearDownAfterClass(): void
    {

    }
}