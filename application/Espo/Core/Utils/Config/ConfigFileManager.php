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

namespace Espo\Core\Utils\Config;

use Espo\Core\{
    Utils\File\Manager as FileManager,
    Utils\Config,
};

class ConfigFileManager
{
    protected $fileManager;

    public function __construct()
    {
        $this->fileManager = new FileManager();
    }

    public function setConfig(Config $config)
    {
        $this->fileManager = new FileManager(
            $config->get('defaultPermissions')
        );
    }

    public function isFile(string $filePath) : bool
    {
        return $this->fileManager->isFile($filePath);
    }

    public function putPhpContents(string $path, $data, bool $useRenaming = false)
    {
        return $this->fileManager->putPhpContents($path, $data, true, $useRenaming);
    }

    public function getPhpContents(string $path)
    {
        return $this->fileManager->getPhpContents($path);
    }
}

