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

namespace Espo\Core\Log\Handler;

use Monolog\{
    Logger,
    Handler\StreamHandler as MonologStreamHandler,
};

use Espo\Core\Utils\File\Manager as FileManager;

use LogicException;
use UnexpectedValueException;

class EspoFileHandler extends MonologStreamHandler
{
    protected $fileManager;

    protected $maxErrorMessageLength = 5000;

    protected $configPath = 'data/config.php';

    public function __construct(string $filename, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($filename, $level, $bubble);

        $defaultPermissions = null;

        if (file_exists($this->configPath)) {
            $configData = include $this->configPath;

            $defaultPermissions = $configData['defaultPermissions'] ?? null;
        }

        $this->fileManager = new FileManager($defaultPermissions);
    }

    protected function write(array $record): void
    {
        if (!$this->url) {
            throw new LogicException(
                'Missing logger path. Check logger params in the data/config.php.'
            );
        }

        $this->errorMessage = null;

        if (!is_writable($this->url)) {
            $this->fileManager->checkCreateFile($this->url);
        }

        if (is_writable($this->url)) {
            set_error_handler([$this, 'customErrorHandler']);

            $this->fileManager->appendContents($this->url, $this->pruneMessage($record));

            restore_error_handler();
        }

        if (isset($this->errorMessage)) {
            throw new UnexpectedValueException(
                sprintf('File "%s" could not be opened: ' . $this->errorMessage, $this->url)
            );
        }
    }

    private function customErrorHandler($code, $msg)
    {
        $this->errorMessage = $msg;
    }

    protected function pruneMessage(array $record)
    {
        $message = (string) $record['message'];

        if (strlen($message) > $this->maxErrorMessageLength) {
            $record['message'] = substr($message, 0, $this->maxErrorMessageLength) . '...';
            $record['formatted'] = $this->getFormatter()->format($record);
        }

        return (string) $record['formatted'];
    }
}
