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

namespace Espo\Core\Api;

use Espo\Core\{
    Api\Request,
    Api\Response,
    Exceptions\Conflict,
    Exceptions\Error,
};

use Throwable;

/**
 * Processes an error output. If an exception occured, it will be passed to here.
 */
class ErrorOutput
{
    protected $errorDescriptions = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Page Not Found',
        409 => 'Conflict',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    protected $allowedStatusCodeList = [
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

    protected $ignorePrintXStatusReasonExceptionClassNameList = [
        'PDOException',
    ];

    protected $request;

    protected $route;

    public function __construct(Request $request, ?string $route = null)
    {
        $this->request = $request;
        $this->route = $route;
    }

    public function process(Response $response, Throwable $exception, bool $toPrintBody = false)
    {
        $message = $exception->getMessage() ?? '';
        $statusCode = $exception->getCode();

        if ($this->route) {
            $this->processRoute($response, $exception);
        }

        $logLevel = 'error';

        $messageLineFile = null;

        $messageLineFile = 'line: ' . $exception->getLine() . ', file: ' . $exception->getFile();

        if (!empty($exception->logLevel)) {
            $logLevel = $exception->logLevel;
        }

        $logMessageItemList = [];

        if ($message) {
            $logMessageItemList[] = "{$message}";
        }

        $logMessageItemList[] = $this->request->getMethod() . ' ' . $this->request->getResourcePath();

        if ($messageLineFile) {
            $logMessageItemList[] = $messageLineFile;
        }

        $logMessage = "($statusCode) " . implode("; ", $logMessageItemList);

        $GLOBALS['log']->log($logLevel, $logMessage);

        $toPrintBodyXStatusReason = true;

        if (
            in_array(
                get_class($exception), $this->ignorePrintXStatusReasonExceptionClassNameList
            )
        ) {
            $toPrintBodyXStatusReason = false;
        }

        if (!in_array($statusCode, $this->allowedStatusCodeList)) {
            $statusCode = 500;
        }

        $response->setStatus($statusCode);

        if ($toPrintBodyXStatusReason) {
            $response->setHeader('X-Status-Reason', $this->stripInvalidCharactersFromHeaderValue($message));
        }

        if ($this->doesExceptionHaveBody($exception)) {
            $response->writeBody($exception->getBody());

            $toPrintBody = false;
        }

        if ($toPrintBody) {
            $statusText = $this->getCodeDescription($statusCode);

            $statusText = isset($statusText) ?
                $statusCode . ' '. $statusText :
                'HTTP ' . $statusCode;

            if ($message) {
                $message = htmlspecialchars($message);
            }

            $response->writeBody(self::generateErrorBody($statusText, $message));
        }
    }

    protected function doesExceptionHaveBody(Throwable $exception) : bool
    {
        if (
            ! $exception instanceof Error
            &&
            ! $exception instanceof Conflict
        ) {
            return false;
        }

        $exceptionBody = null;

        if (method_exists($exception, 'getBody')) {
            $exceptionBody = $exception->getBody();
        }

        return $exceptionBody !== null;
    }

    protected function getCodeDescription(int $statusCode) : ?string
    {
        if (isset($this->errorDescriptions[$statusCode])) {
            return $this->errorDescriptions[$statusCode];
        }

        return null;
    }

    protected function clearPasswords(string $string) : string
    {
        return preg_replace('/"(.*?password.*?)":".*?"/i', '"$1":"*****"', $string);
    }

    protected static function generateErrorBody(string $header, string $text) : string
    {
        $body = "<h1>" . $header . "</h1>";
        $body .= $text;

        return $body;
    }

    protected function stripInvalidCharactersFromHeaderValue(string $value) : string
    {
        $pattern = "/[^ \t\x21-\x7E\x80-\xFF]/";

        $value = preg_replace($pattern, ' ', $value);

        return $value;
    }

    protected function processRoute(Response $response, Throwable $exception)
    {
        $requestBodyString = $this->request->getBodyContents();
        $requestBodyString = $this->clearPasswords($requestBodyString);

        $message = $exception->getMessage() ?? '';
        $statusCode = $exception->getCode();

        $routeParams = $this->request->getRouteParams();

        $logMessage = "API ($statusCode) ";

        $logMessageItemList = [];

        if ($message) {
            $logMessageItemList[] = $message;
        }

        $logMessageItemList[] .= $this->request->getMethod() . ' ' . $this->request->getResourcePath();

        if ($requestBodyString) {
            $logMessageItemList[] = "Input data: " . $requestBodyString;
        }

        $logMessageItemList[] = "Route pattern: " . $this->route;

        if (!empty($routeParams)) {
            $logMessageItemList[] = "Route params: " . print_r($routeParams, true);
        }

        $logMessage .= implode("; ", $logMessageItemList);

        $GLOBALS['log']->log('debug', $logMessage);
    }
}
