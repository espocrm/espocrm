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

namespace Espo\Core\Utils;

use Espo\Core\{
    Exceptions\Error,
    Utils\File\Manager as FileManager,
    Utils\File\FileUnifier,
};

/**
 * Gets module parameters.
 */
class Module
{
    private $fileManager;

    private $useCache;

    private $unifier;

    protected $data = null;

    protected $cacheFile = 'data/cache/application/modules.php';

    protected $paths = [
        'corePath' => 'application/Espo/Resources/module.json',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/module.json',
        'customPath' => 'custom/Espo/Custom/Resources/module.json',
    ];

    public function __construct(FileManager $fileManager, bool $useCache = false)
    {
        $this->fileManager = $fileManager;

        $this->unifier = new FileUnifier($this->fileManager);

        $this->useCache = $useCache;
    }

    /**
     * Get module parameters.
     */
    public function get($key = '', $returns = null)
    {
        if (!isset($this->data)) {
            $this->init();
        }

        if (empty($key)) {
            return $this->data;
        }

        return Util::getValueByKey($this->data, $key, $returns);
    }

    /**
     * Get parameters of all modules.
     */
    public function getAll()
    {
        return $this->get();
    }

    protected function init()
    {
        if (file_exists($this->cacheFile) && $this->useCache) {
            $this->data = $this->fileManager->getPhpContents($this->cacheFile);

            return;
        }

        $this->data = $this->unifier->unify($this->paths, true);

        if ($this->useCache) {
            $result = $this->fileManager->putPhpContents($this->cacheFile, $this->data, false, true);

            if ($result === false) {
                throw new Error('Module: Could not save unified module data.');
            }
        }
    }
}
