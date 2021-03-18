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

use Espo\Core\{
    InjectableFactory,
    Utils\Metadata,
    Utils\Util,
    Console\Exceptions\CommandNotSpecified,
    Console\Exceptions\CommandNotFound,
};

/**
 * Processes console commands. A console command can be run in CLI by running `php command.php`.
 */
class CommandManager
{
    private $injectableFactory;

    private $metadata;

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function run(array $argv) : void
    {
        $command = $this->getCommandNameFromArgv($argv);

        $params = $this->createParams($argv);

        $io = new IO();

        $commandObj = $this->createCommand($command);

        if (!$commandObj instanceof Command) {
            // for backward compatibility

            $commandObj->run($params->getOptions(), $params->getFlagList(), $params->getArgumentList());

            return;
        }

        $commandObj->run($params, $io);
    }

    private function getCommandNameFromArgv(array $argv) : string
    {
        $command = isset($argv[1]) ? trim($argv[1]) : null;

        if (!$command) {
            throw new CommandNotSpecified("Command name is not specifed.");
        }

        return ucfirst(Util::hyphenToCamelCase($command));
    }

    private function createCommand(string $command) : object
    {
        $className = $this->getClassName($command);

        return $this->injectableFactory->create($className);
    }

    private function getClassName(string $command) : string
    {
        $className =
            $this->metadata->get(['app', 'consoleCommands', lcfirst($command), 'className']);

        if ($className) {
            return $className;
        }

        $className = 'Espo\\Core\\Console\\Commands\\' . $command;

        if (!class_exists($className)) {
            throw new CommandNotFound("Command '{$command}' does not exist.");
        }

        return $className;
    }

    private function createParams(array $argv) : Params
    {
        $argumentList = [];
        $options = [];
        $flagList = [];

        $itemList = array_slice($argv, 2);

        foreach ($itemList as $item) {
            if (strpos($item, '--') === 0 && strpos($item, '=') > 2) {
                list($name, $value) = explode('=', substr($item, 2));

                $name = Util::hyphenToCamelCase($name);

                $options[$name] = $value;
            }
            else if (strpos($item, '--') === 0) {
                $flagList[] = Util::hyphenToCamelCase(substr($item, 2));
            }
            else if (strpos($item, '-') === 0) {
                $flagList[] = substr($item, 1);
            }
            else {
                $argumentList[] = $item;
            }
        }

        return new Params([
            'argumentList' => $argumentList,
            'options' => $options,
            'flagList' => $flagList,
        ]);
    }
}
