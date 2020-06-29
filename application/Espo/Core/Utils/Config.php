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

use Espo\Core\Exceptions\Error;

use Espo\Core\Utils\File\Manager as FileManager;

/**
 * Reads and writes the main config file.
 */
class Config
{
    private $defaultConfigPath = 'application/Espo/Core/defaults/config.php';

    private $systemConfigPath = 'application/Espo/Core/defaults/systemConfig.php';

    protected $configPath = 'data/config.php';

    private $cacheTimestamp = 'cacheTimestamp';

    protected $adminItems = [];

    protected $associativeArrayAttributeList = [
        'currencyRates',
        'database',
        'logger',
        'defaultPermissions',
    ];

    private $data;

    private $changedData = [];

    private $removeData = [];

    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    public function getConfigPath()
    {
        return $this->configPath;
    }

    /**
     * Get a parameter value.
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        $keys = explode('.', $name);

        $lastBranch = $this->loadConfig();
        foreach ($keys as $keyName) {
            if (isset($lastBranch[$keyName]) && (is_array($lastBranch) || is_object($lastBranch))) {
                if (is_array($lastBranch)) {
                    $lastBranch = $lastBranch[$keyName];
                } else {
                    $lastBranch = $lastBranch->$keyName;
                }
            } else {
                return $default;
            }
        }

        return $lastBranch;
    }

    /**
     * Whether a parameter is set.
     */
    public function has(string $name) : bool
    {
        $keys = explode('.', $name);

        $lastBranch = $this->loadConfig();
        foreach ($keys as $keyName) {
            if (isset($lastBranch[$keyName]) && (is_array($lastBranch) || is_object($lastBranch))) {
                if (is_array($lastBranch)) {
                    $lastBranch = $lastBranch[$keyName];
                } else {
                    $lastBranch = $lastBranch->$keyName;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Set a parameter or multiple parameters.
     */
    public function set($name, $value = null, bool $dontMarkDirty = false)
    {
        if (is_object($name)) {
            $name = get_object_vars($name);
        }

        if (!is_array($name)) {
            $name = array($name => $value);
        }

        foreach ($name as $key => $value) {
            if (in_array($key, $this->associativeArrayAttributeList) && is_object($value)) {
                $value = (array) $value;
            }
            $this->data[$key] = $value;
            if (!$dontMarkDirty) {
                $this->changedData[$key] = $value;
            }
        }
    }

    /**
     * Remove a parameter..
     */
    public function remove(string $name) : bool
    {
        if (array_key_exists($name, $this->data)) {
            unset($this->data[$name]);
            $this->removeData[] = $name;
            return true;
        }

        return false;
    }

    /**
     * Save config changes to the file.
     */
    public function save()
    {
        $values = $this->changedData;

        if (!isset($values[$this->cacheTimestamp])) {
            $values = array_merge($this->updateCacheTimestamp(true), $values);
        }

        $removeData = empty($this->removeData) ? null : $this->removeData;

        $configPath = $this->getConfigPath();

        if (!file_exists($configPath)) {
            throw new Error('Config file ['. $configPath .'] is not found.');
        }

        $data = include($configPath);
        if (!is_array($data)) {
            $data = include($configPath);
        }

        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $data[$key] = $value;
            }
        }

        if (is_array($removeData)) {
            foreach ($removeData as $key) {
                unset($data[$key]);
            }
        }

        if (!is_array($data)) {
            $GLOBALS['log']->error('Invalid config data ['. var_export($data, true) .'] while saving to ['. $configPath .'].');
            throw new Error('Invalid config data while saving.');
        }

        $data['microtime'] = $microtime = microtime(true);

        $result = $this->getFileManager()->putPhpContents($configPath, $data, true, true);

        if ($result) {
            $reloadedData = include($configPath);
            if (!is_array($reloadedData) || $microtime !== ($reloadedData['microtime'] ?? null)) {
                $result = $this->getFileManager()->putPhpContents($configPath, $data, true, false);
            }
        }

        if ($result) {
            $this->changedData = array();
            $this->removeData = array();
            $this->loadConfig(true);
        }

        return $result;
    }

    /**
     * Get system default config parameters.
     */
    public function getDefaults() : array
    {
        return $this->getFileManager()->getPhpContents($this->defaultConfigPath);
    }

    protected function loadConfig(bool $reload = false)
    {
        if (!$reload && isset($this->data) && !empty($this->data)) {
            return $this->data;
        }

        $configPath = file_exists($this->configPath) ? $this->configPath : $this->defaultConfigPath;

        $this->data = $this->getFileManager()->getPhpContents($configPath);

        $systemConfig = $this->getFileManager()->getPhpContents($this->systemConfigPath);
        $this->data = Util::merge($systemConfig, $this->data);

        return $this->data;
    }

    /**
     * Get all parameters.
     */
    public function getAllData() : \StdClass
    {
        return (object) $this->loadConfig();
    }

    /** @deprecated */
    public function getData()
    {
        $data = $this->loadConfig();

        return $data;
    }

    /** @deprecated */
    public function setData($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        return $this->set($data);
    }

    /**
     * Update cache timestamp.
     *
     * @param $returnOnlyValue - To return an array with timestamp.
     * @return bool | array
     */
    public function updateCacheTimestamp(bool $returnOnlyValue = false)
    {
        $timestamp = [
            $this->cacheTimestamp => time()
        ];

        if ($returnOnlyValue) {
            return $timestamp;
        }

        return $this->set($timestamp);
    }

    public function getAdminOnlyItemList() : array
    {
        return $this->get('adminItems', []);
    }

    public function getSuperAdminOnlyItemList() : array
    {
        return $this->get('superAdminItems', []);
    }

    public function getSystemOnlyItemList() : array
    {
        return $this->get('systemItems', []);
    }

    public function getSuperAdminOnlySystemItemList() : array
    {
        return $this->get('superAdminSystemItems', []);
    }

    public function getUserOnlyItemList() : array
    {
        return $this->get('userItems', []);
    }

    public function getSiteUrl() : string
    {
        return rtrim($this->get('siteUrl'), '/');
    }
}
