<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

class InstallerConfig
{
    private $data;

    private $fileManager;

    protected $configPath = 'install/config.php'; //full path: install/config.php

    public function __construct()
    {
        $this->fileManager = new \Espo\Core\Utils\File\Manager();
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function loadData()
    {
        if (file_exists($this->configPath)) {
            $data = include($this->configPath);
            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    public function getAllData()
    {
        if (!$this->data) {
            $this->data = $this->loadData();
        }

        return $this->data;
    }

    public function get($name, $default = [])
    {
        if (!$this->data) {
            $this->data = $this->loadData();
        }

        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        return $default;
    }

    public function set($name, $value = null)
    {
        if (!is_array($name)) {
            $name = array($name => $value);
        }

        foreach ($name as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    public function save()
    {
        $data = $this->loadData();

        if (is_array($this->data)) {
            foreach ($this->data as $key => $value) {
                $data[$key] = $value;
            }
        }

        try {
            $result = $this->getFileManager()->putPhpContents($this->configPath, $data);
        } catch (\Exception $e) {
            $GLOBALS['log']->warning($e->getMessage());
            $result = false;
        }


        if ($result) {
            $this->data = null;
        }

        return $result;
    }
}
