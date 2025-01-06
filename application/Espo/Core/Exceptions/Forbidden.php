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

namespace Espo\Core\Exceptions;

use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Utils\Log;
use Throwable;
use Exception;

/**
 * A forbidden exception. Main purpose is for the 403 Forbidden HTTP error.
 * If uncaught within an API request, the message will be printed to the X-Status-Reason header.
 */
class Forbidden extends Exception implements HasBody, HasLogLevel
{
    /** @var int */
    protected $code = 403;
    private ?string $body = null;

    final public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create with a body (supposed to be sent to the frontend).
     * Body object is supported since v8.1.
     */
    public static function createWithBody(string $message, string|Body $body): self
    {
        if ($body instanceof Body) {
            $body = $body->encode();
        }

        $exception = new static($message);
        $exception->body = $body;

        return $exception;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getLogLevel(): string
    {
        return Log::LEVEL_WARNING;
    }
}
