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

use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;

use Monolog\Handler\StreamHandler as MonologStreamHandler;
use Monolog\Level;
use Monolog\LogRecord;

use RuntimeException;
use Throwable;

class EspoFileHandler extends MonologStreamHandler
{
    protected FileManager $fileManager;

    protected int $maxErrorMessageLength = 10000;

    public function __construct(
        Config $config,
        string $filename,
        Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($filename, $level, $bubble);

        $defaultPermissions = $config->get('defaultPermissions');

        $this->fileManager = new FileManager($defaultPermissions);
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->url) {
            throw new RuntimeException("Missing a logger file path. Check logger params in config.");
        }

        try {
            if (!is_writable($this->url)) {
                $checkFileResult = $this->fileManager->checkCreateFile($this->url);

                if (!$checkFileResult) {
                    return;
                }
            }

            $this->fileManager->appendContents(
                $this->url,
                $this->pruneMessage($record)
            );
        } catch (Throwable $e) {
            $msg = "Could not write file `$this->url`.";

            if ($e->getMessage()) {
                $msg .= " Error message: " . $e->getMessage();
            }

            throw new RuntimeException($msg);
        }
    }

    private function pruneMessage(LogRecord $record): string
    {
        if (strlen($record->message) <= $this->maxErrorMessageLength) {
            return $record->formatted;
        }

        $message = substr($record->message, 0, $this->maxErrorMessageLength) . '...';

        $record = $record->with(message: $message);

        return $this->getFormatter()->format($record);
    }
}
