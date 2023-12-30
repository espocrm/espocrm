<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Api;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\HasBody;
use Espo\Core\Exceptions\HasLogLevel;
use Espo\Core\Exceptions\HasLogMessage;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;

use Throwable;

/**
 * Processes an error output. If an exception occurred, it will be passed to here.
 */
class ErrorOutput
{
    /** @var array<int, string> */
    private $errorDescriptions = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Page Not Found',
        409 => 'Conflict',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    /** @var int[] */
    private $allowedStatusCodeList = [
        200,
        201,
        400,
        401,
        403,
        404,
        409,
        500,
        503,
    ];

    /** @var class-string<Throwable>[] */
    private array $printStatusReasonExceptionClassNameList = [
        Error::class,
        Forbidden::class,
        Conflict::class,
        BadRequest::class,
        NotFound::class,
    ];

    public function __construct(private Log $log, private Config $config)
    {}

    public function process(
        Request $request,
        Response $response,
        Throwable $exception,
        ?string $route = null
    ): void {

        $this->processInternal($request, $response, $exception, $route, false);
    }

    public function processWithBodyPrinting(
        Request $request,
        Response $response,
        Throwable $exception,
        ?string $route = null
    ): void {

        $this->processInternal($request, $response, $exception, $route, true);
    }

    private function processInternal(
        Request $request,
        Response $response,
        Throwable $exception,
        ?string $route = null,
        bool $toPrintBody = false
    ): void {

        $message = $exception->getMessage();
        $statusCode = $exception->getCode();

        if ($exception instanceof HasLogMessage) {
            $message = $exception->getLogMessage();
        }

        if ($route) {
            $this->processRoute($route, $request, $exception);
        }

        $logLevel = $exception instanceof HasLogLevel ?
            $exception->getLogLevel() :
            Log::LEVEL_ERROR;

        $messageLineFile =
            'line: ' . $exception->getLine() . ', ' .
            'file: ' . $exception->getFile();

        $logMessageItemList = [];

        if ($message) {
            $logMessageItemList[] = "{$message}";
        }

        $logMessageItemList[] = $request->getMethod() . ' ' . $request->getResourcePath();
        $logMessageItemList[] = $messageLineFile;

        $logMessage = "($statusCode) " . implode("; ", $logMessageItemList);

        if ($this->toPrintTrace()) {
            $logMessage .= " :: " . $exception->getTraceAsString();
        }

        $this->log->log($logLevel, $logMessage);

        if (!in_array($statusCode, $this->allowedStatusCodeList)) {
            $statusCode = 500;
        }

        $response->setStatus($statusCode);

        if ($this->toPrintExceptionStatusReason($exception)) {
            $response->setHeader('X-Status-Reason', $this->stripInvalidCharactersFromHeaderValue($message));
        }

        if ($exception instanceof HasBody && $this->exceptionHasBody($exception)) {
            $response->writeBody($exception->getBody() ?? '');

            $toPrintBody = false;
        }

        if ($toPrintBody) {
            $codeDescription = $this->getCodeDescription($statusCode);

            $statusText = isset($codeDescription) ?
                $statusCode . ' '. $codeDescription :
                'HTTP ' . $statusCode;

            if ($message) {
                $message = htmlspecialchars($message);
            }

            $response->writeBody(self::generateErrorBody($statusText, $message));
        }
    }

    private function exceptionHasBody(Throwable $exception): bool
    {
        if (!$exception instanceof HasBody) {
            return false;
        }

        $exceptionBody = $exception->getBody();

        return $exceptionBody !== null;
    }

    private function getCodeDescription(int $statusCode): ?string
    {
        if (isset($this->errorDescriptions[$statusCode])) {
            return $this->errorDescriptions[$statusCode];
        }

        return null;
    }

    private function clearPasswords(string $string): string
    {
        return preg_replace('/"(.*password.*)":".*"/i', '"$1":"*****"', $string) ?? $string;
    }

    private static function generateErrorBody(string $header, string $text): string
    {
        $body = "<h1>" . $header . "</h1>";
        $body .= $text;

        return $body;
    }

    private function stripInvalidCharactersFromHeaderValue(string $value): string
    {
        $pattern = "/[^ \t\x21-\x7E\x80-\xFF]/";

        /** @var string */
        return preg_replace($pattern, ' ', $value);
    }

    private function processRoute(string $route, Request $request, Throwable $exception): void
    {
        $requestBodyString = $this->clearPasswords($request->getBodyContents() ?? '');

        $message = $exception->getMessage();
        $statusCode = $exception->getCode();

        $routeParams = $request->getRouteParams();

        $logMessage = "API ($statusCode) ";

        $logMessageItemList = [];

        if ($message) {
            $logMessageItemList[] = $message;
        }

        $logMessageItemList[] = $request->getMethod() . ' ' . $request->getResourcePath();

        if ($requestBodyString) {
            $logMessageItemList[] = "Input data: " . $requestBodyString;
        }

        $logMessageItemList[] = "Route pattern: " . $route;

        if (!empty($routeParams)) {
            $logMessageItemList[] = "Route params: " . print_r($routeParams, true);
        }

        $logMessage .= implode("; ", $logMessageItemList);

        $this->log->log('debug', $logMessage);
    }

    private function toPrintTrace(): bool
    {
        return (bool) $this->config->get('logger.printTrace');
    }

    private function toPrintExceptionStatusReason(Throwable $exception): bool
    {
        foreach ($this->printStatusReasonExceptionClassNameList as $clasName) {

            if ($exception instanceof ($clasName)) {
                return true;
            }
        }

        return false;
    }
}
