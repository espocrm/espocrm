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

namespace Espo\Core\Utils\Log\Monolog\Handler;

use Monolog\Logger;

class StreamHandler extends \Monolog\Handler\StreamHandler
{
    protected $fileManager;

    protected $maxErrorMessageLength = 5000;

    public function __construct($url, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($url, $level, $bubble);

        $this->fileManager = new \Espo\Core\Utils\File\Manager();
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }


    protected function write(array $record)
    {
        if (!$this->url) {
            throw new \LogicException('Missing logger path, the stream can not be opened. Please check logger options in the data/config.php.');
        }

        $this->errorMessage = null;

        set_error_handler(array($this, 'customErrorHandler'));
        $this->getFileManager()->appendContents($this->url, $this->pruneMessage($record));
        restore_error_handler();

        if (isset($this->errorMessage)) {
            throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: '.$this->errorMessage, $this->url));
        }
    }

    private function customErrorHandler($code, $msg)
    {
        $this->errorMessage = $msg;
    }

    /**
     * Cut the error message depends on maxErrorMessageLength
     *
     * @param  array  $record
     * @return string
     */
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