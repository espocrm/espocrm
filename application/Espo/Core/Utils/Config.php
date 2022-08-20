<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Utils\Config\ConfigFileManager;

use stdClass;
use RuntimeException;

use const E_USER_DEPRECATED;

/**
 * Access to the application config parameters.
 */
class Config
{
    private string $configPath = 'data/config.php';

    private string $internalConfigPath = 'data/config-internal.php';

    private string $systemConfigPath = 'application/Espo/Resources/defaults/systemConfig.php';

    private string $cacheTimestamp = 'cacheTimestamp';

    /**
     * @var string[]
     */
    protected $associativeArrayAttributeList = [
        'currencyRates',
        'database',
        'logger',
        'defaultPermissions',
    ];

    /**
     * @var ?array<string,mixed>
     */
    private $data = null;

    /**
     * @var array<string,mixed>
     */
    private $changedData = [];

    /**
     * @var string[]
     */
    private $removeData = [];

    private ConfigFileManager $fileManager;

    /**
     * @var string[]
     */
    private $internalParamList = [];

    public function __construct(ConfigFileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    /**
     * A path to the config file.
     *
     * @todo Move to ConfigData.
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * A path to the internal config file.
     *
     * @todo Move to ConfigData.
     */
    public function getInternalConfigPath(): string
    {
        return $this->internalConfigPath;
    }

    /**
     * Get a parameter value.
     *
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        $keys = explode('.', $name);

        $lastBranch = $this->getData();

        foreach ($keys as $key) {
            if (!is_array($lastBranch) && !is_object($lastBranch)) {
                return $default;
            }

            if (is_array($lastBranch) && !array_key_exists($key, $lastBranch)) {
                return $default;
            }

            if (is_object($lastBranch) && !property_exists($lastBranch, $key)) {
                return $default;
            }

            if (is_array($lastBranch)) {
                $lastBranch = $lastBranch[$key];

                continue;
            }

            $lastBranch = $lastBranch->$key;
        }

        return $lastBranch;
    }

    /**
     * Whether a parameter is set.
     */
    public function has(string $name): bool
    {
        $keys = explode('.', $name);

        $lastBranch = $this->getData();

        foreach ($keys as $key) {
            if (!is_array($lastBranch) && !is_object($lastBranch)) {
                return false;
            }

            if (is_array($lastBranch) && !array_key_exists($key, $lastBranch)) {
                return false;
            }

            if (is_object($lastBranch) && !property_exists($lastBranch, $key)) {
                return false;
            }

            if (is_array($lastBranch)) {
                $lastBranch = $lastBranch[$key];

                continue;
            }

            $lastBranch = $lastBranch->$key;
        }

        return true;
    }

    /**
     * Re-load data.
     *
     * @todo Get rid of this method. Use ConfigData as a dependency.
     * `$configData->update();`
     */
    public function update(): void
    {
        $this->load();
    }

    /**
     * @deprecated Since v7.0.
     *
     * @param string|array<string,mixed>|\stdClass $name
     * @param mixed $value
     */
    public function set($name, $value = null, bool $dontMarkDirty = false): void
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
     * @deprecated Since v7.0.
     */
    public function remove(string $name): bool
    {
        assert($this->data !== null);

        if (array_key_exists($name, $this->data)) {
            unset($this->data[$name]);

            $this->removeData[] = $name;

            return true;
        }

        return false;
    }

    /**
     * @deprecated Since v7.0.
     *
     * @return bool
     */
    public function save()
    {
        trigger_error(
            "Config::save is deprecated. Use `Espo\Core\Utils\Config\ConfigWriter` to save the config.",
            E_USER_DEPRECATED
        );

        $values = $this->changedData;

        if (!isset($values[$this->cacheTimestamp])) {
            $values = array_merge($this->updateCacheTimestamp(true) ?? [], $values);
        }

        $removeData = empty($this->removeData) ? null : $this->removeData;

        $configPath = $this->getConfigPath();

        if (!$this->fileManager->isFile($configPath)) {
            throw new RuntimeException("Config file '{$configPath}' is not found.");
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
            throw new RuntimeException('Invalid config data while saving.');
        }

        $data['microtime'] = $microtime = microtime(true);

        $this->fileManager->putPhpContents($configPath, $data);

        $this->changedData = [];
        $this->removeData = [];

        $this->load();

        return true;
    }

    private function isLoaded(): bool
    {
        return isset($this->data) && !empty($this->data);
    }

    /**
     * @return array<string,mixed>
     */
    private function getData(): array
    {
        if (!$this->isLoaded()) {
            $this->load();
        }

        assert($this->data !== null);

        return $this->data;
    }

    private function load(): void
    {
        $systemData = $this->fileManager->getPhpContents($this->systemConfigPath);

        $data = $this->fileManager->isFile($this->configPath) ?
            $this->fileManager->getPhpContents($this->configPath) : [];

        $internalData = $this->fileManager->isFile($this->internalConfigPath) ?
            $this->fileManager->getPhpContents($this->internalConfigPath) : [];

        /** @var array<string,mixed> $mergedData */
        $mergedData = Util::merge(
            Util::merge($systemData, $data),
            $internalData
        );

        $this->data = $mergedData;

        $this->internalParamList = array_keys($internalData);

        $this->fileManager->setConfig($this);
    }

    /**
     * Get all parameters excluding those that are set in the internal config.
     */
    public function getAllNonInternalData(): stdClass
    {
        $data = (object) $this->getData();

        foreach ($this->internalParamList as $param) {
            unset($data->$param);
        }

        return $data;
    }

    /**
     * Whether a parameter is set in the internal config.
     */
    public function isInternal(string $name): bool
    {
        if (!$this->isLoaded()) {
            $this->load();
        }

        return in_array($name, $this->internalParamList);
    }

    /**
     * @deprecated
     * @param array<string,mixed> $data
     * @return void
     */
    public function setData($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        $this->set($data);
    }

    /**
     * Update cache timestamp.
     *
     * @deprecated
     * @return ?array<string,int>
     */
    public function updateCacheTimestamp(bool $returnOnlyValue = false)
    {
        $timestamp = [
            $this->cacheTimestamp => time()
        ];

        if ($returnOnlyValue) {
            return $timestamp;
        }

        $this->set($timestamp);

        return null;
    }

    /**
     * @todo Move to another class `Espo\Core\Utils\Config\ApplicationConfigProvider`.
     * @deprecated
     */
    public function getSiteUrl(): string
    {
        return rtrim($this->get('siteUrl'), '/');
    }
}
