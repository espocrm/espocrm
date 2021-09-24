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

namespace tests\unit\Espo\Core\Console;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Core\Console\CommandManager;
use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;

class CommandManagerTest extends \PHPUnit\Framework\TestCase
{
    private $injectableFactory;

    private $metadata;

    private $manager;

    protected function setUp() : void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);

        $this->metadata = $this->createMock(Metadata::class);

        $this->manager = new CommandManager($this->injectableFactory, $this->metadata);

        $this->command = $this->createMock(Command::class);
    }

    private function initTest(array $argv)
    {
        $className = 'Test';

        $this->metadata
            ->expects($this->once())
            ->method('get')
            ->with(['app', 'consoleCommands', 'commandName', 'className'])
            ->willReturn($className);

        $this->injectableFactory
            ->expects($this->once())
            ->method('create')
            ->with($className)
            ->willReturn($this->command);

        $expectedParams = new Params(
            [
               'optionOne' => 'test',
            ],
            ['flag', 'flagA', 'f'],
            ['a1', 'a2']
        );

        $io = new IO();

        $this->command
            ->expects($this->once())
            ->method('run')
            ->with($expectedParams, $io);

        $this->manager->run($argv);
    }

    public function testWithCommandPhp()
    {
        $argv = ['command.php', 'command-name', 'a1', 'a2', '--flag', '--flag-a', '-f', '--option-one=test'];

        $this->initTest($argv);
    }

    public function testWithoutCommandPhp()
    {
        $argv = ['bin/command', 'command-name', 'a1', 'a2', '--flag', '--flag-a', '-f', '--option-one=test'];

        $this->initTest($argv);
    }
}
