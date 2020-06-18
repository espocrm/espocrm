<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Utils\Util;

class CommandManager
{
    protected $container;

    public function __construct(\Espo\Core\Container $container)
    {
        $this->container = $container;
    }

    public function run(string $command)
    {
        $command = ucfirst(Util::hyphenToCamelCase($command));

        $params = $this->getParams($_SERVER['argv']);

        $options = $params['options'];
        $flagList = $params['flagList'];
        $argumentList = $params['argumentList'];

        $className = $this->getClassName($command);

        $impl = new $className($this->container);
        return $impl->run($options, $flagList, $argumentList);
    }

    protected function getClassName(string $command)
    {
        $className = '\\Espo\\Core\\Console\\Commands\\' . $command;
        $className = $this->container->get('metadata')->get(['app', 'consoleCommands', $command, 'className'], $className);
        if (!class_exists($className)) {
            $msg = "Command '{$command}' does not exist.";
            echo $msg . "\n";
            throw new \Espo\Core\Exceptions\Error($msg);
        }

        return $className;
    }

    protected function getParams(array $argv)
    {
        $argumentList = [];
        $options = [];
        $flagList = [];

        $skipIndex = 1;
        if (isset($argv[0]) && $argv[0] === 'command.php') {
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
