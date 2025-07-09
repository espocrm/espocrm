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

namespace Espo\Core\Log;

use Espo\Core\Application\ApplicationParams;
use Espo\Core\ApplicationState;
use Espo\Core\Log\Handler\DatabaseHandler;
use Espo\Core\Log\Handler\EspoFileHandler;
use Espo\Core\Log\Handler\EspoRotatingFileHandler;
use Espo\Core\ORM\EntityManagerProxy;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;

use Monolog\ErrorHandler as MonologErrorHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

class LogLoader
{
    private const PATH = 'data/logs/espo.log';

    private const MAX_FILE_NUMBER = 30;
    private const DEFAULT_LEVEL = 'WARNING';

    public function __construct(
        private readonly Config $config,
        private readonly HandlerListLoader $handlerListLoader,
        private readonly EntityManagerProxy $entityManagerProxy,
        private readonly ApplicationState $applicationState,
        private readonly ApplicationParams $applicationParams,
    ) {}

    public function load(): Log
    {
        $log = new Log('Espo');

        $handlerDataList = $this->config->get('logger.handlerList') ?? null;

        if ($handlerDataList) {
            $level = $this->config->get('logger.level');

            $handlerList = $this->handlerListLoader->load($handlerDataList, $level);
        } else {
            $handlerList = [$this->createDefaultHandler()];
        }

        if ($this->config->get('logger.databaseHandler')) {
            $handlerList[] = $this->createDatabaseHandler();
        }

        foreach ($handlerList as $handler) {
            $log->pushHandler($handler);
        }

        if (!$this->applicationParams->noErrorHandler) {
            $errorHandler = new MonologErrorHandler($log);

            $errorHandler->registerExceptionHandler([], false);
            $errorHandler->registerErrorHandler([], false);
        }

        return $log;
    }

    private function createDefaultHandler(): HandlerInterface
    {
        $path = $this->config->get('logger.path') ?? self::PATH;
        $level = $this->config->get('logger.level') ?? self::DEFAULT_LEVEL;
        $rotation = $this->config->get('logger.rotation') ?? true;

        $levelCode = Logger::toMonologLevel($level);

        if ($rotation) {
            $maxFileNumber = $this->config->get('logger.maxFileNumber') ?? self::MAX_FILE_NUMBER;

            $handler = new EspoRotatingFileHandler($this->config, $path, $maxFileNumber, $levelCode, true);
        } else {
            $handler = new EspoFileHandler($this->config, $path, $levelCode, true);
        }

        $formatter = new DefaultFormatter($this->printTrace());

        $handler->setFormatter($formatter);

        return $handler;
    }

    private function printTrace(): bool
    {
        return (bool) $this->config->get('logger.printTrace');
    }

    private function createDatabaseHandler(): HandlerInterface
    {
        $rawLevel = $this->config->get('logger.databaseHandlerLevel') ??
            $this->config->get('logger.level') ??
            self::DEFAULT_LEVEL;

        $level = Logger::toMonologLevel($rawLevel);

        return new DatabaseHandler($level, $this->entityManagerProxy, $this->applicationState);
    }
}
