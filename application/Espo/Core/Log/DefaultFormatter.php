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

use Espo\Core\Api\Request;
use Espo\Core\Exceptions\HasBody;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;
use Throwable;

class DefaultFormatter extends LineFormatter
{
    private const LINE_FORMAT = "[%datetime%] %level_name%: %code% %message% %request% %exception%\n";
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private bool $includeTraces = false,
    ) {
        parent::__construct(
            format: self::LINE_FORMAT,
            dateFormat: self::DATE_FORMAT,
            ignoreEmptyContextAndExtra: true,
            includeStacktraces: $this->includeTraces,
        );
    }

    public function format(LogRecord $record): string
    {
        $line = parent::format($record);

        $line = $this->interpolate($record, $line);
        $line = $this->addCode($record, $line);
        $line = $this->addRequest($record, $line);
        $line = $this->addException($record, $line);

        return trim($line) . "\n";
    }

    private function addCode(LogRecord $record, string $line): string
    {
        $exception = $record->context['exception'] ?? null;

        if (!$exception instanceof Throwable) {
            return str_replace('%code% ', '', $line);
        }

        $codePart = "({$exception->getCode()})";

        return str_replace('%code% ', $codePart . ' ', $line);
    }

    private function addException(LogRecord $record, string $line): string
    {
        $exception = $record->context['exception'] ?? null;

        if (!$exception instanceof Throwable) {
            return str_replace('%exception%', '', $line);
        }

        if (!$exception instanceof HasBody || !$exception->getBody()) {
            $part = ":: {$exception->getFile()}({$exception->getLine()})";

            $line = str_replace('%exception%', $part, $line);
        } else {
            $line = str_replace('%exception%', '', $line);
        }

        if (!$this->includeTraces) {
            return $line;
        }

        $line .= $this->normalizeException($exception);

        return $line;
    }

    private function addRequest(LogRecord $record, string $line): string
    {
        $request = $record->context['request'] ?? null;

        if (!$request instanceof Request) {
            return str_replace('%request% ', '', $line);
        }

        $requestPart = ":: {$request->getMethod()} {$request->getResourcePath()}";

        return str_replace('%request%', $requestPart, $line);
    }

    private function interpolate(LogRecord $record, mixed $line): string
    {
        $replace = [];

        foreach ($record->context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        $line = strtr($line, $replace);
        return $line;
    }
}
