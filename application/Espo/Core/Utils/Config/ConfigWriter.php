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

use Espo\Core\Utils\Config;
use Espo\Core\Exceptions\Error;

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

    private $config;

    private $fileManager;

    private $helper;

    private $internalConfigHelper;

    public function __construct(
        Config $config,
        ConfigWriterFileManager $fileManager,
        ConfigWriterHelper $helper,
        InternalConfigHelper $internalConfigHelper
    ) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->helper = $helper;
        $this->internalConfigHelper = $internalConfigHelper;
    }

    /**
     * Set a parameter.
     */
    public function set(string $name, $value): void
    {
        if (in_array($name, $this->associativeArrayAttributeList) && is_object($value)) {
            $value = (array) $value;
        }

        $this->changedData[$name] = $value;
    }

    /**
     * Set multiple parameters.
     */
    public function setMultiple(array $params): void
    {
        foreach ($params as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Remove a parameter.
     */
    public function remove(string $name): void
    {
        $this->removeParamList[] = $name;
    }

    /**
     * Save config changes to the file.
     */
    public function save(): void
    {
        $changedData = $this->changedData;

        if (!isset($changedData[$this->cacheTimestampParam])) {
            $changedData[$this->cacheTimestampParam] = $this->generateCacheTimestamp();
        }

        $configPath = $this->config->getConfigPath();
        $internalConfigPath = $this->config->getInternalConfigPath();

        if (!$this->fileManager->isFile($configPath)) {
            throw new Error("Config file '{$configPath}' was not found.");
        }

        $data = $this->fileManager->getPhpContents($configPath);

        $dataInternal = $this->fileManager->isFile($internalConfigPath) ?
            $this->fileManager->getPhpContents($internalConfigPath) : [];

        if (!is_array($data)) {
            $data = $this->fileManager->getPhpContents($configPath);
        }

        if (!is_array($data)) {
            throw new Error("Could not read config.");
        }

        if (!is_array($dataInternal)) {
            throw new Error("Could not read config-internal.");
        }

        $toSaveInternal = false;

        foreach ($changedData as $key => $value) {
            if ($this->internalConfigHelper->isParamForInternalConfig($key)) {
                $dataInternal[$key] = $value;
                unset($data[$key]);

                $toSaveInternal = true;

                continue;
            }

            $data[$key] = $value;
        }

        foreach ($this->removeParamList as $key) {
            if ($this->internalConfigHelper->isParamForInternalConfig($key)) {
                unset($dataInternal[$key]);

                $toSaveInternal = true;

                continue;
            }

            unset($data[$key]);
        }

        if ($toSaveInternal) {
            $this->saveData($internalConfigPath, $dataInternal, 'microtimeInternal');
        }

        $this->saveData($configPath, $data, 'microtime');

        $this->changedData = [];
        $this->removeParamList = [];

        $this->config->update();
    }

    private function saveData(string $path, array &$data, string $timeParam): void
    {
        $data[$timeParam] = $microtime = $this->helper->generateMicrotime();

        try {
            $this->fileManager->putPhpContents($path, $data);
        }
        catch (Exception $e) {
            throw new Error("Could not save config.");
        }

        $reloadedData = $this->fileManager->getPhpContents($path);

        if (
            is_array($reloadedData) &&
            $microtime === ($reloadedData[$timeParam] ?? null)
        ) {
            return;
        }

        try {
            $this->fileManager->putPhpContentsNoRenaming($path, $data);
        }
        catch (Exception $e) {
            throw new Error("Could not save config.");
        }
    }

    /**
     * Update the cache timestamp.
     *
     * @todo Remove? Saving re-writes the cache timestamp anyway.
     */
    public function updateCacheTimestamp(): void
    {
        $this->set($this->cacheTimestampParam, $this->generateCacheTimestamp());
    }

    protected function generateCacheTimestamp(): int
    {
        return $this->helper->generateCacheTimestamp();
    }
}
