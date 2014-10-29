<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Core\Loaders;

use Espo\Core\Utils;
use Espo\Core\Utils\Log\Monolog\Handler;
use Monolog\ErrorHandler;

class Log extends
    Base
{

    public function load()
    {
        $logConfig = $this->getContainer()->get('config')->get('logger');
        $log = new Utils\Log('Espo');
        $levelCode = $log->getLevelCode($logConfig['level']);
        if ($logConfig['isRotate']) {
            $handler = new Handler\RotatingFileHandler($logConfig['path'], $logConfig['maxRotateFiles'], $levelCode);
        } else {
            $handler = new Handler\StreamHandler($logConfig['path'], $levelCode);
        }
        $log->pushHandler($handler);
        $errorHandler = new ErrorHandler($log);
        $errorHandler->registerExceptionHandler(null, false);
        $errorHandler->registerErrorHandler(array(), false);
        return $log;
    }
}

