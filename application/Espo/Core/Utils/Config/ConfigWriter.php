<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Utils\Config;

use Espo\Core\Utils\Config;

use Exception;
use RuntimeException;

/**
 * Writes into the config.
 */
class ConfigWriter
{
    /** @var array<string, mixed> */
    private $changedData = [];
    /** @var string[] */
    private $removeParamList = [];
    /** @var string[] */
    protected $associativeArrayAttributeList = [
        'currencyRates',
        'database',
        'logger',
        'defaultPermissions',
    ];

    private string $cacheTimestampParam = 'cacheTimestamp';

    public function __construct(
        private Config $config,
        private ConfigWriterFileManager $fileManager,
        private ConfigWriterHelper $helper,
        private InternalConfigHelper $internalConfigHelper
    ) {}

    /**
     * Set a parameter.
     *
     * @param mixed $value
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
     *
     * @param array<string, mixed> $params
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
            throw new RuntimeException("Config file '{$configPath}' not found.");
        }

        $data = $this->fileManager->getPhpContents($configPath);

        $dataInternal = $this->fileManager->isFile($internalConfigPath) ?
            $this->fileManager->getPhpContents($internalConfigPath) : [];

        if (!is_array($data)) {
            throw new RuntimeException("Could not read config.");
        }

        if (!is_array($dataInternal)) {
            throw new RuntimeException("Could not read config-internal.");
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

    /**
     * @param array<string, mixed> $data
     */
    private function saveData(string $path, array &$data, string $timeParam): void
    {
        $data[$timeParam] = $microtime = $this->helper->generateMicrotime();

        try {
            $this->fileManager->putPhpContents($path, $data);
        } catch (Exception) {
            throw new RuntimeException("Could not save config.");
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
        } catch (Exception) {
            throw new RuntimeException("Could not save config.");
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
