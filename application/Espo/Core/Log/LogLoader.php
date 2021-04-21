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

namespace Espo\Core\Log;

use Espo\Core\{
    InjectableFactory,
    Utils\Config,
    Utils\Log,
    Log\HandlerListLoader,
    Log\Handler\EspoRotatingFileHandler,
    Log\Handler\EspoFileHandler,
};

use Monolog\{
    Logger,
    ErrorHandler as MonologErrorHandler,
    Formatter\LineFormatter,
    Handler\HandlerInterface,
};

class LogLoader
{
    const LINE_FORMAT = "[%datetime%] %level_name%: %message% %context% %extra%\n";

    const DATE_FORMAT = 'Y-m-d H:i:s';

    const PATH = 'data/logs/espo.log';

    const MAX_FILE_NUMBER = 30;

    const DEFAULT_LEVEL = 'WARNING';

    protected $config;

    protected $injectableFactory;

    public function __construct(Config $config, InjectableFactory $injectableFactory)
    {
        $this->config = $config;
        $this->injectableFactory = $injectableFactory;
    }

    public function load(): Log
    {
        $log = new Log('Espo');

        $handlerDataList = $this->config->get('logger.handlerList') ?? null;

        if ($handlerDataList) {
            $level = $this->config->get('logger.level');

            $loader = $this->injectableFactory->create(HandlerListLoader::class);

            $handlerList = $loader->load($handlerDataList, $level);
        }
        else {
            $handler = $this->createDefaultHandler();

            $handlerList = [$handler];
        }

        foreach ($handlerList as $handler) {
            $log->pushHandler($handler);
        }

        $errorHandler = new MonologErrorHandler($log);

        $errorHandler->registerExceptionHandler(null, false);
        $errorHandler->registerErrorHandler([], false);

        return $log;
    }

    protected function createDefaultHandler(): HandlerInterface
    {
        $path = $this->config->get('logger.path') ?? self::PATH;
        $rotation = $this->config->get('logger.rotation') ?? true;
        $level = $this->config->get('logger.level') ?? self::DEFAULT_LEVEL;

        $levelCode = Logger::toMonologLevel($level);

        if ($rotation) {
            $maxFileNumber = $this->config->get('logger.maxFileNumber') ?? self::MAX_FILE_NUMBER;

            $handler = new EspoRotatingFileHandler($this->config, $path, $maxFileNumber, $levelCode, true);
        }
        else {
            $handler = new EspoFileHandler($this->config, $path, $levelCode, true);
        }

        $formatter = new LineFormatter(
            self::LINE_FORMAT,
            self::DATE_FORMAT
        );

        $handler->setFormatter($formatter);

        return $handler;
    }
}
