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

namespace tests\unit\Espo\Core\Job;

use tests\unit\ReflectionHelper;

use Espo\Core\{
    Job\JobManager,
    ServiceFactory,
    Utils\Config,
    Utils\File\Manager as FileManager,
    Utils\ScheduledJob,
    ORM\EntityManager,
    InjectableFactory,
    Utils\Log,
};

class JobManagerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->serviceFactory = $this->getMockBuilder(ServiceFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()->getMock();

        $this->fileManager = $this->getMockBuilder(FileManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->scheduledJob = $this->getMockBuilder(ScheduledJob::class)
            ->disableOriginalConstructor()->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->injectableFactory = $this->getMockBuilder(InjectableFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->log = $this->createMock(Log::class);

        $this->manager = new JobManager(
            $this->config,
            $this->fileManager,
            $this->entityManager,
            $this->serviceFactory,
            $this->injectableFactory,
            $this->scheduledJob,
            $this->log
        );

        $this->reflection = new ReflectionHelper($this->manager);
    }

    protected function tearDown() : void
    {
        $this->manager = NULL;
    }

    public function testCheckLastRunTimeFileDoesnotExist()
    {
        $this->fileManager
            ->expects($this->once())
            ->method('getPhpContents')
            ->will($this->returnValue(false));

        $this->config
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue(50));

        $this->assertTrue($this->reflection->invokeMethod('checkLastRunTime', []));
    }

    public function testCheckLastRunTime()
    {
        $this->fileManager
            ->expects($this->once())
            ->method('getPhpContents')
            ->will(
                $this->returnValue([
                    'time' => time() - 60,
                ])
            );

        $this->config
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue(50));

        $this->assertTrue( $this->reflection->invokeMethod('checkLastRunTime', []));
    }

    public function testCheckLastRunTimeTooFrequency()
    {
        $this->fileManager
            ->expects($this->once())
            ->method('getPhpContents')
            ->will(
                $this->returnValue([
                    'time' => time() - 49,
                ])
            );

        $this->config
            ->expects($this->exactly(1))
            ->method('get')
            ->will($this->returnValue(50));

        $this->assertFalse($this->reflection->invokeMethod('checkLastRunTime', []));
    }
}
