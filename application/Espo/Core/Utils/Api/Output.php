<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils\Api;

class Output
{
    private $slim;

    protected $errorDescriptions = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Page Not Found',
        409 => 'Conflict',
        500 => 'Internal Server Error',
    ];

    protected $allowedStatusCodeList = [
        200, 201, 400, 401, 403, 404, 409, 500
    ];

    protected $ignorePrintXStatusReasonExceptionClassNameList = [
        'PDOException'
    ];

    public function __construct(\Espo\Core\Utils\Api\Slim $slim)
    {
        $this->slim = $slim;
    }

    protected function getSlim()
    {
        return $this->slim;
    }

    public function render($data = null)
    {
        if (is_array($data)) {
            $dataArr = array_values($data);
            $data = empty($dataArr[0]) ? false : $dataArr[0];
        }

        ob_clean();
        echo $data;
    }

    public function processError(string $message = 'Error', int $statusCode = 500, bool $toPrint = false, $exception = null)
    {
        $currentRoute = $this->getSlim()->router()->getCurrentRoute();

        if (isset($currentRoute)) {
            $inputData = $this->getSlim()->request()->getBody();
            $inputData = $this->clearPasswords($inputData);

            $routePattern = $currentRoute->getPattern();
            $routeParams = $currentRoute->getParams();
            $method = $this->getSlim()->request()->getMethod();

            $logMessage = "API ($statusCode) ";
            $logMessageItemList = [];
            if ($message) $logMessageItemList[] = $message;
            $logMessageItemList[] .= "$method " . $_SERVER['REQUEST_URI'];
            if ($inputData) $logMessageItemList[] = "Input data: " . $inputData;
            if ($routePattern) $logMessageItemList[] = "Route pattern: ". $routePattern;
            if (!empty($routeParams)) $logMessageItemList[] = "Route params: ". print_r($routeParams, true);

            $logMessage .= implode("; ", $logMessageItemList);

            $GLOBALS['log']->log('debug', $logMessage);
        }

        $this->displayError($message, $statusCode, $toPrint, $exception);
    }

    public function displayError(string $text, int $statusCode = 500, bool $toPrint = false, $exception = null)
    {
        $logLevel = 'error';
        $messageLineFile = null;

        if ($exception) {
            $messageLineFile = 'line: ' . $exception->getLine() . ', file: ' . $exception->getFile();
        }

        if ($exception && !empty($exception->logLevel)) {
            $logLevel = $exception->logLevel;
        }

        $logMessageItemList = [];

        if ($text) $logMessageItemList[] = "{$text}";

        if (!empty($this->slim)) {
            $logMessageItemList[] = $this->getSlim()->request()->getMethod() . ' ' .$_SERVER['REQUEST_URI'];
        }

        if ($messageLineFile) {
            $logMessageItemList[] = $messageLineFile;
        }

        $logMessage = "($statusCode) " . implode("; ", $logMessageItemList);

        $GLOBALS['log']->log($logLevel, $logMessage);

        ob_clean();

        if (!empty($this->slim)) {
            $toPrintXStatusReason = true;
            if ($exception && in_array(get_class($exception), $this->ignorePrintXStatusReasonExceptionClassNameList)) {
                $toPrintXStatusReason = false;
            }

            if (!in_array($statusCode, $this->allowedStatusCodeList)) {
                $statusCode = 500;
            }

            $this->getSlim()->response()->setStatus($statusCode);
            if ($toPrintXStatusReason) {
                $this->getSlim()->response()->headers->set('X-Status-Reason', $text);
            }

            if ($toPrint) {
                $status = $this->getCodeDescription($statusCode);
                $status = isset($status) ? $statusCode.' '.$status : 'HTTP '.$statusCode;
                if ($text)
                    $text = htmlspecialchars($text);
                $this->getSlim()->printError($text, $status);
            }

            $this->getSlim()->stop();
        } else {
            $GLOBALS['log']->info('Could not get Slim instance. It looks like a direct call (bypass API). URL: '.$_SERVER['REQUEST_URI']);
            die($text);
        }
    }

    protected function getCodeDescription($statusCode)
    {
        if (isset($this->errorDescriptions[$statusCode])) {
            return $this->errorDescriptions[$statusCode];
        }

        return null;
    }

    protected function clearPasswords($inputData)
    {
        return preg_replace('/"(.*?password.*?)":".*?"/i', '"$1":"*****"', $inputData);
    }
}
