<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Loaders;

class Log extends Base
{
    public function load()
    {
        $config = $this->getContainer()->get('config');

        $path = $config->get('logger.path', 'data/logs/espo.log');
        $rotation = $config->get('logger.rotation', true);

        $log = new \Espo\Core\Utils\Log('Espo');
        $levelCode = $log->getLevelCode($config->get('logger.level', 'WARNING'));

        if ($rotation) {
            $maxFileNumber = $config->get('logger.maxFileNumber', 30);
            $handler = new \Espo\Core\Utils\Log\Monolog\Handler\RotatingFileHandler($path, $maxFileNumber, $levelCode);
        } else {
            $handler = new \Espo\Core\Utils\Log\Monolog\Handler\StreamHandler($path, $levelCode);
        }
        $log->pushHandler($handler);

        $errorHandler = new \Monolog\ErrorHandler($log);
        $errorHandler->registerExceptionHandler(null, false);
        $errorHandler->registerErrorHandler(array(), false);

        return $log;
    }
}

