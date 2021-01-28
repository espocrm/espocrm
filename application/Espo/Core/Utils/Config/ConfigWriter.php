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

namespace Espo\Core\Utils\Config;

use Espo\Core\{
    Utils\Config,
    Exceptions\Error,
};

use Exception;

/**
 * Writes into the config.
 */
class ConfigWriter
{
    private $changedData = [];

    private $removeParamList = [];

    protected $associativeArrayAttributeList = [
        'currencyRates',
        'database',
        'logger',
        'defaultPermissions',
    ];

    private $cacheTimestampParam = 'cacheTimestamp';

    protected $config;

    protected $fileManager;

    protected $helper;

    public function __construct(Config $config, ConfigWriterFileManager $fileManager, ConfigWriterHelper $helper)
    {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->helper = $helper;
    }

    /**
     * Set a parameter.
     */
    public function set(string $name, $value)
    {
        if (in_array($name, $this->associativeArrayAttributeList) && is_object($value)) {
            $value = (array) $value;
        }

        $this->changedData[$name] = $value;
    }

    /**
     * Set multiple parameters.
     */
    public function setMultiple(array $params)
    {
        foreach ($params as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Remove a parameter.
     */
    public function remove(string $name)
    {
        $this->removeParamList[] = $name;
    }

    /**
     * Save config changes to the file.
     */
    public function save()
    {
        $changedData = $this->changedData;

        if (!isset($changedData[$this->cacheTimestampParam])) {
            $changedData[$this->cacheTimestampParam] = $this->generateCacheTimestamp();
        }

        $configPath = $this->config->getConfigPath();

        if (!$this->fileManager->isFile($configPath)) {
            throw new Error("Config file '{$configPath}' was not found.");
        }

        $data = $this->fileManager->getPhpContents($configPath);

        if (!is_array($data)) {
            $data = $this->fileManager->getPhpContents($configPath);
        }

        if (!is_array($data)) {
            throw new Error("Could not read config.");
        }

        foreach ($changedData as $key => $value) {
            $data[$key] = $value;
        }

        foreach ($this->removeParamList as $key) {
            unset($data[$key]);
        }

        if (!is_array($data)) {
            throw new Error("Invalid config data while saving.");
        }

        $data['microtime'] = $microtime = $this->helper->generateMicrotime();

        try {
            $this->fileManager->putPhpContents($configPath, $data);
        }
        catch (Exception $e) {
            throw new Error("Could not save config.");
        }

        $reloadedData = $this->fileManager->getPhpContents($configPath);

        if (
            !is_array($reloadedData) ||
            $microtime !== ($reloadedData['microtime'] ?? null)
        ) {
            try {
                $this->fileManager->putPhpContentsNoRenaming($configPath, $data);
            }
            catch (Exception $e) {
                throw new Error("Could not save config.");
            }
        }

        $this->changedData = [];
        $this->removeParamList = [];

        $this->config->update();
    }

    /**
     * Update the cache timestamp.
     *
     * @todo Remove? Saving re-writes the cache timestamp anyway.
     */
    public function updateCacheTimestamp()
    {
        $this->set($this->cacheTimestampParam, $this->generateCacheTimestamp());
    }

    protected function generateCacheTimestamp() : int
    {
        return $this->helper->generateCacheTimestamp();
    }
}
