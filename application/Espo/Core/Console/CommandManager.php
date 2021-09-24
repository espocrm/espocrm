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

namespace Espo\Core\Console;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Core\Console\Exceptions\CommandNotSpecified;
use Espo\Core\Console\Exceptions\CommandNotFound;
use Espo\Core\Console\Command\Params;

/**
 * Processes console commands.
 */
class CommandManager
{
    private $injectableFactory;

    private $metadata;

    private const DEFAULT_COMMAND = 'Help';

    private const DEFAULT_COMMAND_FLAG = 'help';

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function run(array $argv): void
    {
        $command = $this->getCommandNameFromArgv($argv);
        $params = $this->createParamsFromArgv($argv);

        if (
            $command === null &&
            (
                $params->hasFlag(self::DEFAULT_COMMAND_FLAG) ||
                count($params->getFlagList()) === 0 &&
                count($params->getOptions()) === 0 &&
                count($params->getArgumentList()) === 0
            )
        ) {
            $command = self::DEFAULT_COMMAND;
        }

        if ($command === null) {
            throw new CommandNotSpecified("Command name is not specified.");
        }

        $io = new IO();

        $commandObj = $this->createCommand($command);

        if (!$commandObj instanceof Command) {
            // for backward compatibility
            $commandObj->run($params->getOptions(), $params->getFlagList(), $params->getArgumentList());

            return;
        }

        $commandObj->run($params, $io);
    }

    private function getCommandNameFromArgv(array $argv): ?string
    {
        $command = isset($argv[1]) ? trim($argv[1]) : null;

        if ($command === null && count($argv) < 2) {
            return null;
        }

        if (!$command || !ctype_alpha($command[0])) {
            return null;
        }

        return ucfirst(Util::hyphenToCamelCase($command));
    }

    private function createCommand(string $command): object
    {
        $className = $this->getClassName($command);

        return $this->injectableFactory->create($className);
    }

    private function getClassName(string $command): string
    {
        $className =
            $this->metadata->get(['app', 'consoleCommands', lcfirst($command), 'className']);

        if ($className) {
            return $className;
        }

        $className = 'Espo\\Core\\Console\\Commands\\' . $command;

        if (!class_exists($className)) {
            throw new CommandNotFound("Command '" . Util::camelCaseToHyphen($command) ."' does not exist.");
        }

        return $className;
    }

    private function createParamsFromArgv(array $argv): Params
    {
        return Params::fromArgs(array_slice($argv, 1));
    }
}
