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

namespace Espo\Core\Formula\Functions;

use Espo\ORM\Entity;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Evaluatable;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\BadArgumentValue;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\ExecutionException;
use Espo\Core\Formula\Exceptions\NotPassedEntity;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Processor;
use Espo\Core\Utils\Log;

use stdClass;

/**
 * A base abstract function. Avoid extending. Use Func interface instead.
 */
abstract class BaseFunction
{
    protected function getVariables(): stdClass
    {
        return $this->variables ?? (object) [];
    }

    /**
     * Get a target entity.
     *
     * @throws NotPassedEntity
     */
    protected function getEntity(): Entity
    {
        if (!$this->entity) {
            throw new NotPassedEntity('function: ' . $this->name);
        }

        return $this->entity;
    }

    public function __construct(
        protected string $name,
        protected Processor $processor,
        private ?Entity $entity = null,
        private ?stdClass $variables = null,
        protected ?Log $log = null
    ) {}

    /**
     * Evaluates a function.
     *
     * @return mixed A result of the function.
     * @throws Error
     * @throws ExecutionException
     */
    public abstract function process(ArgumentList $args);

    /**
     * Evaluates an argument or argument list.
     *
     * @param Evaluatable $item Argument or ArgumentList.
     * @return mixed A result of evaluation. An array if an argument list was passed.
     * @throws Error
     * @throws ExecutionException
     */
    protected function evaluate(Evaluatable $item)
    {
        return $this->processor->process($item);
    }

    /**
     * Throws TooFewArguments exception.
     *
     * @return never
     * @throws TooFewArguments
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
     *
     * @return never
     * @throws BadArgumentType
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
     *
     * @return never
     * @throws BadArgumentValue
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
     *
     * @return never
     * @throws Error
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
    protected function logBadArgumentType(int $index, string $type): void
    {
        if (!$this->log) {
            return;
        }

        $this->log->warning("Formula function: {$this->name}, argument {$index} should be '{$type}'.");
    }

    /**
     * Log a message.
     */
    protected function log(string $msg, string $level = 'notice'): void
    {
        if (!$this->log) {
            return;
        }

        $this->log->log($level, 'Formula function: ' . $this->name . ', ' . $msg);
    }
}
