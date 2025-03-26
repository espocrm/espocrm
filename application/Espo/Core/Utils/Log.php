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

namespace Espo\Core\Utils;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use DateTimeZone;
use Stringable;

class Log implements LoggerInterface
{
    public const LEVEL_DEBUG = LogLevel::DEBUG;
    public const LEVEL_NOTICE = LogLevel::NOTICE;
    public const LEVEL_WARNING = LogLevel::WARNING;
    public const LEVEL_ERROR = LogLevel::ERROR;

    private Logger $logger;

    /**
     * @param list<HandlerInterface> $handlers
     * @param callable[] $processors
     * @param ?DateTimeZone $timezone
     */
    public function __construct(
        string $name,
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    ) {
        $this->logger = new Logger($name, $handlers, $processors, $timezone);
    }

    public function pushHandler(HandlerInterface $handler): self
    {
        $this->logger->pushHandler($handler);

        return $this;
    }

    /**
     * @param mixed[] $context
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * @param mixed[] $context
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * @param mixed[] $context
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * @param mixed[] $context
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function error(Stringable|string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * @param mixed[] $context
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * @param mixed[] $context
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * @param mixed[] $context
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function info(Stringable|string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * @param mixed[] $context
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * @param mixed $level
     * @param mixed[] $context
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
