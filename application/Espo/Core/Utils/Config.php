<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils;

class Config
{
    /**
     * Path of default config file
     *
     * @access private
     * @var string
     */
    private $defaultConfigPath = 'application/Espo/Core/defaults/config.php';

    private $systemConfigPath = 'application/Espo/Core/defaults/systemConfig.php';

    protected $configPath = 'data/config.php';

    private $cacheTimestamp = 'cacheTimestamp';

    /**
     * Array of admin items
     *
     * @access protected
     * @var array
     */
    protected $adminItems = array();

    protected $associativeArrayAttributeList = [
        'currencyRates',
        'database',
        'logger',
        'defaultPermissions',
    ];


    /**
     * Contains content of config
     *
     * @access private
     * @var array
     */
    private $data;

    private $changedData = array();
    private $removeData = array();

    private $fileManager;


    public function __construct(\Espo\Core\Utils\File\Manager $fileManager) //TODO
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
     * Get an option from config
     *
     * @param string $name
     * @param string $default
     * @return string | array
     */
    public function get($name, $default = null)
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
     * Set an option to the config
     *
     * @param string $name
     * @param string $value
     * @return bool
     */
    public function set($name, $value = null, $dontMarkDirty = false)
    {
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
     * Remove an option in config
     *
     * @param  string $name
     * @return bool | null - null if an option doesn't exist
     */
    public function remove($name)
    {
        if (array_key_exists($name, $this->data)) {
            unset($this->data[$name]);
            $this->removeData[] = $name;
            return true;
        }

        return null;
    }

    public function save()
    {
        $values = $this->changedData;

        if (!isset($values[$this->cacheTimestamp])) {
            $values = array_merge($this->updateCacheTimestamp(true), $values);
        }

        $removeData = empty($this->removeData) ? null : $this->removeData;

        $data = include($this->configPath);

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

        $result = $this->getFileManager()->putPhpContents($this->configPath, $data, true);

        if ($result) {
            $this->changedData = array();
            $this->removeData = array();
            $this->loadConfig(true);
        }

        return $result;
    }

    public function getDefaults()
    {
        return $this->getFileManager()->getPhpContents($this->defaultConfigPath);
    }

    /**
     * Return an Object of all configs
     * @param  boolean $reload
     * @return array()
     */
    protected function loadConfig($reload = false)
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
     * Get config acording to restrictions for a user
     *
     * @param $isAdmin
     * @return array
     */
    public function getData($isAdmin = false)
    {
        $data = $this->loadConfig();

        $restrictedConfig = $data;
        foreach($this->getRestrictItems($isAdmin) as $name) {
            if (isset($restrictedConfig[$name])) {
                unset($restrictedConfig[$name]);
            }
        }

        return $restrictedConfig;
    }


    /**
     * Set JSON data acording to restrictions for a user
     *
     * @param $isAdmin
     * @return bool
     */
    public function setData($data, $isAdmin = false)
    {
        $restrictItems = $this->getRestrictItems($isAdmin);

        $values = array();
        foreach ($data as $key => $item) {
            if (!in_array($key, $restrictItems)) {
                $values[$key] = $item;
            }
        }

        return $this->set($values);
    }

    /**
     * Update cache timestamp
     *
     * @param $onlyValue - If need to return just timestamp array
     * @return bool | array
     */
    public function updateCacheTimestamp($onlyValue = false)
    {
        $timestamp = array(
            $this->cacheTimestamp => time(),
        );

        if ($onlyValue) {
            return $timestamp;
        }

        return $this->set($timestamp);
    }

    /**
     * Get admin items
     *
     * @return object
     */
    protected function getRestrictItems($onlySystemItems = false)
    {
        $data = $this->loadConfig();

        if ($onlySystemItems) {
            return $data['systemItems'];
        }

        if (empty($this->adminItems)) {
            $this->adminItems = array_merge($data['systemItems'], $data['adminItems']);
        }

        return $this->adminItems;
    }


    /**
     * Check if an item is allowed to get and save
     *
     * @param $name
     * @param $isAdmin
     * @return bool
     */
    protected function isAllowed($name, $isAdmin = false)
    {
        if (in_array($name, $this->getRestrictItems($isAdmin))) {
            return false;
        }

        return true;
    }

    public function getSiteUrl()
    {
        return rtrim($this->get('siteUrl'), '/');
    }
}

?>
