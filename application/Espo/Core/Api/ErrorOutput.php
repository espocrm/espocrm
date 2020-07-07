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

namespace Espo\Core\Api;

use Espo\Core\{
    Api\Request,
    Api\Response,
};

class ErrorOutput
{
    protected $errorDescriptions = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Page Not Found',
        409 => 'Conflict',
        500 => 'Internal Server Error',
    ];

    protected $allowedStatusCodeList = [
        200, 201, 400, 401, 403, 404, 409, 500,
    ];

    protected $ignorePrintXStatusReasonExceptionClassNameList = [
        'PDOException',
    ];

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function process(
        Response $response,
        \Throwable $exception,
        bool $toPrint = false,
        ?array $route = null,
        ?array $routeParams = null
    ) {
        $message = $exception->getMessage() ?? '';
        $statusCode = $exception->getCode();

        if ($route) {
            $requestBodyString = $this->request->getBodyContents();
            $requestBodyString = $this->clearPasswords($requestBodyString);

            $routePattern = $route['route'];
            $routeParams = $routeParams ?? [];

            $logMessage = "API ($statusCode) ";
            $logMessageItemList = [];

            if ($message) $logMessageItemList[] = $message;

            $logMessageItemList[] .= $this->request->getMethod() . ' ' . $this->request->getResourcePath();

            if ($requestBodyString) $logMessageItemList[] = "Input data: " . $requestBodyString;

            if ($routePattern) $logMessageItemList[] = "Route pattern: ". $routePattern;
            if (!empty($routeParams)) $logMessageItemList[] = "Route params: ". print_r($routeParams, true);

            $logMessage .= implode("; ", $logMessageItemList);

            $GLOBALS['log']->log('debug', $logMessage);
        }

        $logLevel = 'error';
        $messageLineFile = null;

        if ($exception) {
            $messageLineFile = 'line: ' . $exception->getLine() . ', file: ' . $exception->getFile();
        }

        if ($exception && !empty($exception->logLevel)) {
            $logLevel = $exception->logLevel;
        }

        $logMessageItemList = [];

        if ($message) $logMessageItemList[] = "{$message}";

        $logMessageItemList[] = $this->request->getMethod() . ' ' . $this->request->getResourcePath();

        if ($messageLineFile) {
            $logMessageItemList[] = $messageLineFile;
        }

        $logMessage = "($statusCode) " . implode("; ", $logMessageItemList);

        $GLOBALS['log']->log($logLevel, $logMessage);

        $toPrintXStatusReason = true;
        if ($exception && in_array(get_class($exception), $this->ignorePrintXStatusReasonExceptionClassNameList)) {
            $toPrintXStatusReason = false;
        }

        if (!in_array($statusCode, $this->allowedStatusCodeList)) {
            $statusCode = 500;
        }

        $response->setStatus($statusCode);
        if ($toPrintXStatusReason) {
            $response->setHeader('X-Status-Reason', $message);
        }

        if ($toPrint) {
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
}
