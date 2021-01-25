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
};

use Espo\Core\Exceptions\Error;

/**
 * Processes console commands. A console command can be run in CLI by runnig `php command.php`.
 */
class CommandManager
{
    protected $injectableFactory;
    protected $metadata;

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function run(array $argv)
    {
        $command = isset($argv[1]) ? trim($argv[1]) : null;

        if (!$command) {
            $msg = "Command name is not specifed.";

            echo $msg . "\n";

            exit;
        }

        $command = ucfirst(Util::hyphenToCamelCase($command));

        $params = $this->getParams($argv);

        $options = $params['options'];
        $flagList = $params['flagList'];
        $argumentList = $params['argumentList'];

        $className = $this->getClassName($command);

        $obj = $this->injectableFactory->create($className);

        return $obj->run($options, $flagList, $argumentList);
    }

    protected function getClassName(string $command) : string
    {
        $className =
            $this->metadata->get(['app', 'consoleCommands', lcfirst($command), 'className']) ??
            'Espo\\Core\\Console\\Commands\\' . $command;

        if (!class_exists($className)) {
            $msg = "Command '{$command}' does not exist.";

            echo $msg . "\n";

            exit;
        }

        return $className;
    }

    protected function getParams(array $argv) : array
    {
        $argumentList = [];
        $options = [];
        $flagList = [];

        $skipIndex = 1;

        if (isset($argv[0]) && preg_match('/command\.php$/', $argv[0])) {
            $skipIndex = 2;
        }

        foreach ($argv as $i => $item) {
            if ($i < $skipIndex) continue;

            if (strpos($item, '--') === 0 && strpos($item, '=') > 2) {
                list($name, $value) = explode('=', substr($item, 2));
                $name = Util::hyphenToCamelCase($name);
                $options[$name] = $value;
            } else if (strpos($item, '--') === 0) {
                $flagList[] = Util::hyphenToCamelCase(substr($item, 2));
            } else if (strpos($item, '-') === 0) {
                $flagList[] = substr($item, 1);
            } else {
                $argumentList[] = $item;
            }
        }

        return [
            'argumentList' => $argumentList,
            'options' => $options,
            'flagList' => $flagList,
        ];
    }
}
