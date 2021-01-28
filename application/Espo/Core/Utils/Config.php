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

namespace Espo\Core\Utils;

use Espo\Core\{
    Exceptions\Error,
    Utils\Config\ConfigFileManager,
};

use StdClass;
use E_USER_DEPRECATED;

/**
 * Access to the application config parameters.
 */
class Config
{
    private $defaultConfigPath = 'application/Espo/Resources/defaults/config.php';

    private $systemConfigPath = 'application/Espo/Resources/defaults/systemConfig.php';

    protected $configPath = 'data/config.php';

    private $cacheTimestamp = 'cacheTimestamp';

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

    public function __construct(ConfigFileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * A path to the config file.
     *
     * @todo Move to ConfigData.
     */
    public function getConfigPath() : string
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
     * Re-load data.
     *
     * @todo Get rid of this method. Use ConfigData as a dependency.
     * `$configData->update();`
     */
    public function update()
    {
        $this->loadConfig(true);
    }

    /**
     * @deprecated Since v6.2.0.
     */
    public function set($name, $value = null, bool $dontMarkDirty = false)
    {
        if (is_object($name)) {
            $name = get_object_vars($name);
        }

        if (!is_array($name)) {
            $name = [$name => $value];
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
     * @deprecated Since v6.2.0.
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
     * @deprecated Since v6.2.0.
     */
    public function save()
    {
        trigger_error(
            "Config::save is deprecated. Use `Espo\Core\Utils\Config\ConfigWriter` to save the config.",
            E_USER_DEPRECATED
        );

        $values = $this->changedData;

        if (!isset($values[$this->cacheTimestamp])) {
            $values = array_merge($this->updateCacheTimestamp(true), $values);
        }

        $removeData = empty($this->removeData) ? null : $this->removeData;

        $configPath = $this->getConfigPath();

        if (!$this->fileManager->isFile($configPath)) {
            throw new Error("Config file '{$configPath}' is not found.");
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
            $GLOBALS['log']->error("Invalid config data while saving to '{$configPath}'.");

            throw new Error('Invalid config data while saving.');
        }

        $data['microtime'] = $microtime = microtime(true);

        $result = $this->fileManager->putPhpContents($configPath, $data, true);

        if ($result) {
            $reloadedData = include($configPath);

            if (!is_array($reloadedData) || $microtime !== ($reloadedData['microtime'] ?? null)) {
                $result = $this->fileManager->putPhpContents($configPath, $data, false);
            }
        }

        if ($result) {
            $this->changedData = [];
            $this->removeData = [];

            $this->loadConfig(true);
        }

        return $result;
    }

    /**
     * Get system default config parameters.
     *
     * @deprecated
     * @todo Move to `Espo\Core\Utils\Config\ConfigDefaults`.
     */
    public function getDefaults() : array
    {
        return $this->fileManager->getPhpContents($this->defaultConfigPath);
    }

    protected function loadConfig(bool $reload = false)
    {
        if (!$reload && isset($this->data) && !empty($this->data)) {
            return $this->data;
        }

        $configPath = $this->fileManager->isFile($this->configPath) ?
                $this->configPath :
                $this->defaultConfigPath;

        $this->data = $this->fileManager->getPhpContents($configPath);

        $systemConfig = $this->fileManager->getPhpContents($this->systemConfigPath);

        $this->data = Util::merge($systemConfig, $this->data);

        $this->fileManager->setConfig($this);

        return $this->data;
    }

    /**
     * Get all parameters.
     */
    public function getAllData() : StdClass
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
     * @deprecated
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

    /**
     * @todo Remove.
     * @deprecated
     */
    public function getAdminOnlyItemList() : array
    {
        return $this->get('adminItems', []);
    }

    /**
     * @todo Remove.
     * @deprecated
     */
    public function getSuperAdminOnlyItemList() : array
    {
        return $this->get('superAdminItems', []);
    }

    /**
     * @todo Remove.
     * @deprecated
     */
    public function getSystemOnlyItemList() : array
    {
        return $this->get('systemItems', []);
    }

    /**
     * @todo Remove.
     * @deprecated
     */
    public function getSuperAdminOnlySystemItemList() : array
    {
        return $this->get('superAdminSystemItems', []);
    }

    /**
     * @todo Remove.
     * @deprecated
     */
    public function getUserOnlyItemList() : array
    {
        return $this->get('userItems', []);
    }

    /**
     * @todo Move to another class `Espo\Core\Utils\Config\ApplicationConfigProvider`.
     * @deprecated
     */
    public function getSiteUrl() : string
    {
        return rtrim($this->get('siteUrl'), '/');
    }
}
