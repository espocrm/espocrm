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

namespace Espo\Core\Log\Handler;

use Monolog\Logger;

use Espo\Core\{
    Utils\Config,
};

class EspoRotatingFileHandler extends EspoFileHandler
{
    protected $dateFormat = 'Y-m-d';

    protected $filenameFormat = '{filename}-{date}';

    protected $filename;

    protected $maxFiles;

    public function __construct(
        Config $config,
        string $filename,
        int $maxFiles = 0,
        $level = Logger::DEBUG,
        bool $bubble = true
    ) {
        $this->filename = $filename;
        $this->maxFiles = (int) $maxFiles;

        parent::__construct($config, $this->getTimedFilename(), $level, $bubble);

        $this->rotate();
    }

    protected function rotate()
    {
        if (0 === $this->maxFiles) {
            return; // unlimited number of files for 0
        }

        $filePattern = $this->getFilePattern();
        $dirPath = $this->fileManager->getDirName($this->filename);
        $logFiles = $this->fileManager->getFileList($dirPath, false, $filePattern, true);

        if (!empty($logFiles) && count($logFiles) > $this->maxFiles) {
            usort($logFiles, function ($a, $b) {
                return strcmp($b, $a);
            });

            $logFilesToBeRemoved = array_slice($logFiles, $this->maxFiles);

            $this->fileManager->removeFile($logFilesToBeRemoved, $dirPath);
        }
    }

    protected function getTimedFilename()
    {
        $fileInfo = pathinfo($this->filename);

        $timedFilename = str_replace(
            array('{filename}', '{date}'),
            array($fileInfo['filename'], date($this->dateFormat)),
            $fileInfo['dirname'] . '/' . $this->filenameFormat
        );

        if (!empty($fileInfo['extension'])) {
            $timedFilename .= '.'.$fileInfo['extension'];
        }

        return $timedFilename;
    }

    protected function getFilePattern()
    {
        $fileInfo = pathinfo($this->filename);

        $glob = str_replace(
            array('{filename}', '{date}'),
            array($fileInfo['filename'], '.*'),
            $this->filenameFormat
        );

        if (!empty($fileInfo['extension'])) {
            $glob .= '\.'.$fileInfo['extension'];
        }

        $glob = '^'.$glob.'$';

        return $glob;
    }
}
