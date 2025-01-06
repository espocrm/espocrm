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

namespace Espo\Core\Log\Handler;

use Espo\Core\Api\Request;
use Espo\Core\ApplicationState;
use Espo\Core\ORM\EntityManagerProxy;
use Espo\Entities\AppLogRecord;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

class DatabaseHandler implements HandlerInterface
{
    public function __construct(
        private Level $level,
        private EntityManagerProxy $entityManager,
        private ApplicationState $applicationState
    ) {}

    public function isHandling(LogRecord $record): bool
    {
        if (!$this->applicationState->hasUser()) {
            return false;
        }

        if ($record->context['isSql'] ?? false) {
            return false;
        }

        return $record->level->value >= $this->level->value;
    }

    public function handle(LogRecord $record): bool
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        try {
            $logRecord = $this->entityManager->getRDBRepositoryByClass(AppLogRecord::class)->getNew();

            $level = ucfirst($record->level->toPsrLogLevel());
            $message = $this->interpolate($record, $record->message);

            $logRecord
                ->setLevel($level)
                ->setMessage($message);

            $this->setException($record, $logRecord);
            $this->setRequest($record, $logRecord);

            $this->entityManager->saveEntity($logRecord);
        } catch (Throwable) {
            // Nowhere to log.
        }

        return false;
    }

    private function interpolate(LogRecord $record, string $line): string
    {
        $replace = [];

        foreach ($record->context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($line, $replace);
    }

    /**
     * @param LogRecord[] $records
     */
    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    public function close(): void
    {}

    private function setException(LogRecord $record, AppLogRecord $logRecord): void
    {
        $exception = $record->context['exception'] ?? null;

        if (!$exception instanceof Throwable) {
            return;
        }

        $logRecord
            ->setExceptionClass(get_class($exception))
            ->setFile($exception->getFile())
            ->setLine($exception->getLine())
            ->setCode($exception->getCode());
    }

    private function setRequest(LogRecord $record, AppLogRecord $logRecord): void
    {
        $request = $record->context['request'] ?? null;

        if (!$request instanceof Request) {
            return;
        }

        $logRecord
            ->setRequestMethod($request->getMethod())
            ->setRequestResourcePath($request->getResourcePath());
    }
}
