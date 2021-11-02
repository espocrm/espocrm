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

namespace Espo\Core\Formula\Functions;

use Espo\ORM\Entity;

use Espo\Core\Formula\{
    Processor,
    ArgumentList,
    Evaluatable,
    Exceptions\TooFewArguments,
    Exceptions\BadArgumentType,
    Exceptions\BadArgumentValue,
    Exceptions\NotPassedEntity,
    Exceptions\Error,
};

use Espo\Core\Utils\Log;

use StdClass;

abstract class BaseFunction
{
    protected $processor;

    private $entity;

    private $variables;

    protected $name;

    protected $log;

    protected function getVariables(): StdClass
    {
        return $this->variables;
    }

    protected function getEntity(): Entity
    {
        if (!$this->entity) {
            throw new NotPassedEntity('function: ' . $this->name);
        }

        return $this->entity;
    }

    public function __construct(
        string $name,
        Processor $processor,
        ?Entity $entity = null,
        ?StdClass $variables = null,
        ?Log $log = null
    ) {
        $this->name = $name;
        $this->processor = $processor;
        $this->entity = $entity;
        $this->variables = $variables;
        $this->log = $log;
    }

    /**
     * Evaluates a function.
     *
     * @return mixed A result of the function.
     */
    public abstract function process(ArgumentList $args);

    /**
     * Evaluates an argument or argument list.
     *
     * @param Evaluatable $item Argument or ArgumentList.
     * @return mixed A result of evaluation. An array if an argument list was passed.
     */
    protected function evaluate(Evaluatable $item)
    {
        return $this->processor->process($item);
    }

    /**
     * Throw TooFewArguments exception.
     */
    protected function throwTooFewArguments(?int $number = null)
    {
        $msg = 'function: ' . $this->name;
        if ($number !== null) {
            $msg .= ', needs: ' . $number;
        }

        throw new TooFewArguments($msg);
    }

    /**
     * Throw BadArgumentType exception.
     */
    protected function throwBadArgumentType(?int $index = null, ?string $type = null)
    {
        $msg = 'function: ' . $this->name;

        if ($index !== null) {
            $msg .= ', index: ' . $index;

            if ($type) {
                $msg .= ', should be: ' . $type;
            }
        }

        throw new BadArgumentType($msg);
    }

    /**
     * Throw BadArgumentValue exception.
     */
    protected function throwBadArgumentValue(?int $index = null, ?string $msg = null)
    {
        $string = 'function: ' . $this->name;

        if ($index !== null) {
            $string .= ', index: ' . $index;

            if ($msg) {
                $string .= ', ' . $msg;
            }
        }

        throw new BadArgumentValue($string);
    }

    /**
     * Throw Error exception.
     */
    protected function throwError(?string $msg = null)

    {
        $string = 'function: ' . $this->name;
        if ($msg) {
            $string .= ', ' . $msg;
        }

        throw new Error($string);
    }

    /**
     * Log a bad argument type.
     */
    protected function logBadArgumentType(int $index, string $type)
    {
        if (!$this->log) {
            return;
        }

        $this->log->warning("Formula function: {$this->name}, argument {$index} should be '{$type}'.");
    }

    /**
     * Log a message.
     */
    protected function log(string $msg, string $level = 'notice')
    {
        if (!$this->log) {
            return;
        }

        $this->log->log($level, 'Formula function: ' . $this->name . ', ' . $msg);
    }
}
