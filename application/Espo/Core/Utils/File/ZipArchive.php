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
namespace Espo\Core\Utils\File;

use Espo\Core\Exceptions\Error;

class ZipArchive
{

    private $fileManager;

    public function __construct(Manager $fileManager = null)
    {
        if (!isset($fileManager)) {
            $fileManager = new Manager();
        }
        $this->fileManager = $fileManager;
    }

    public function zip($sourcePath, $file)
    {
    }

    /**
     * Unzip archive
     *
     * @param  string $file Path to .zip file
     * @param  [type] $destinationPath
     *
     * @throws Error
     * @return bool
     */
    public function unzip($file, $destinationPath)
    {
        if (!class_exists('\ZipArchive')) {
            throw new Error("Class ZipArchive does not installed. Cannot unzip the file.");
        }
        $zip = new \ZipArchive;
        $res = $zip->open($file);
        if ($res === true) {
            $this->getFileManager()->mkdir($destinationPath);
            $zip->extractTo($destinationPath);
            $zip->close();
            return true;
        }
        return false;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }
}