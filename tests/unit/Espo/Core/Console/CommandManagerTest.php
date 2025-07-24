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

namespace tests\unit\Espo\Core\Console;

use Espo\Core\ApplicationUser;
use Espo\Core\Console\Exceptions\InvalidArgument;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Core\Console\CommandManager;
use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use PHPUnit\Framework\TestCase;

class CommandManagerTest extends TestCase
{
    private ?InjectableFactory $injectableFactory = null;
    private ?Metadata $metadata = null;
    private ?CommandManager $manager = null;

    private $command;

    protected function setUp() : void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);

        $applicationUser = $this->createMock(ApplicationUser::class);

        $this->manager = new CommandManager($this->injectableFactory, $this->metadata, $applicationUser);

        $this->command = $this->createMock(Command::class);
    }

    private function initTest(?array $allowedOptions = null, ?array $allowedFlags = null)
    {
        $className = 'Test';

        $map = [
            [['app', 'consoleCommands', 'commandName', 'className'], null, $className],
            [['app', 'consoleCommands', 'commandName', 'noSystemUser'], null, false],
        ];

        if ($allowedOptions !== null) {
            $map[] = [['app', 'consoleCommands', 'commandName', 'allowedOptions'], null, $allowedOptions];
        }

        if ($allowedFlags!== null) {
            $map[] = [['app', 'consoleCommands', 'commandName', 'allowedFlags'], null, $allowedFlags];
        }

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->willReturnMap($map);

        $this->injectableFactory
            ->expects($this->any())
            ->method('create')
            ->with($className)
            ->willReturn($this->command);
    }

    public function testWithCommandPhp(): void
    {
        $argv = ['command.php', 'command-name', 'a1', 'a2', '--flag', '--flag-a', '-f', '--option-one=test'];

        $this->initTest();

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

    public function testWithoutCommandPhp(): void
    {
        $argv = ['bin/command', 'command-name', 'a1', 'a2', '--flag', '--flag-a', '-f', '--option-one=test'];

        $this->initTest();

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

    public function testAllowedOptions(): void
    {
        $argv = ['bin/command', 'command-name', '--option-one=test'];

        $this->initTest(['optionOne']);

        $expectedParams = new Params(
            [
                'optionOne' => 'test',
            ],
            [],
            []
        );

        $io = new IO();

        $this->command
            ->expects($this->once())
            ->method('run')
            ->with($expectedParams, $io);

        $this->manager->run($argv);
    }

    public function testNotAllowedOptions(): void
    {
        $argv = ['bin/command', 'command-name', '--option-bad=test'];

        $this->initTest(['optionOne']);

        $this->expectException(InvalidArgument::class);

        $this->manager->run($argv);
    }
    public function testAllowedFlags(): void
    {
        $argv = ['bin/command', 'command-name', '--flagOne', '-a'];

        $this->initTest(null, ['flagOne', 'a']);

        $expectedParams = new Params(
            [],
            ['flagOne', 'a'],
            []
        );

        $io = new IO();

        $this->command
            ->expects($this->once())
            ->method('run')
            ->with($expectedParams, $io);

        $this->manager->run($argv);
    }

    public function testNotAllowedFlags1(): void
    {
        $argv = ['bin/command', 'command-name', '--bad-flag'];

        $this->initTest(null, ['flag1']);

        $this->expectException(InvalidArgument::class);

        $this->manager->run($argv);
    }

    public function testNotAllowedFlags2(): void
    {
        $argv = ['bin/command', 'command-name', '-b'];

        $this->initTest(null, ['a']);

        $this->expectException(InvalidArgument::class);

        $this->manager->run($argv);
    }
}
