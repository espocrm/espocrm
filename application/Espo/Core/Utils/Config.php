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
    private string $systemConfigPath = 'application/Espo/Resources/defaults/systemConfig.php';
    private string $configPath = 'data/config.php';
    private string $internalConfigPath = 'data/config-internal.php';
    private string $overrideConfigPath = 'data/config-override.php';
    private string $internalOverrideConfigPath = 'data/config-internal-override.php';
    private string $cacheTimestamp = 'cacheTimestamp';
    /** @var string[] */
    protected $associativeArrayAttributeList = [
        'currencyRates',
        'database',
        'logger',
        'defaultPermissions',
    ];

    /** @var ?array<string, mixed> */
    private $data = null;
    /** @var array<string, mixed> */
    private $changedData = [];
    /** @var string[] */
    private $removeData = [];
    /** @var string[] */
    private $internalParamList = [];

    public function __construct(private ConfigFileManager $fileManager)
    {}

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
     * @deprecated As of v7.0. Use ConfigWriter instead.
     *
     * @param string|array<string, mixed>|stdClass $name
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
     * @deprecated As of v7.0. Use ConfigWriter instead.
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
     * @deprecated As of v7.0. Use ConfigWriter instead.
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
            /** @noinspection PhpDeprecationInspection */
            $values = array_merge($this->updateCacheTimestamp(true) ?? [], $values);
        }

        $removeData = empty($this->removeData) ? null : $this->removeData;

        $configPath = $this->getConfigPath();

        if (!$this->fileManager->isFile($configPath)) {
            throw new RuntimeException("Config file '$configPath' is not found.");
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

        $data['microtime'] = microtime(true);

        $this->fileManager->putPhpContents($configPath, $data);

        $this->changedData = [];
        $this->removeData = [];

        $this->load();

        return true;
    }

    private function isLoaded(): bool
    {
        return !empty($this->data);
    }

    /**
     * @return array<string, mixed>
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
        $data = $this->readFile($this->configPath);
        $internalData = $this->readFile($this->internalConfigPath);
        $overrideData = $this->readFile($this->overrideConfigPath);
        $internalOverrideData = $this->readFile($this->internalOverrideConfigPath);

        $this->data = $this->mergeData(
            $systemData,
            $data,
            $internalData,
            $overrideData,
            $internalOverrideData
        );

        $this->internalParamList = array_values(array_merge(
            array_keys($internalData),
            array_keys($internalOverrideData)
        ));

        $this->fileManager->setConfig($this);
    }

    /**
     * @param array<string, mixed> $systemData
     * @param array<string, mixed> $data
     * @param array<string, mixed> $internalData
     * @param array<string, mixed> $overrideData
     * @param array<string, mixed> $internalOverrideData
     * @return array<string, mixed>
     */
    private function mergeData(
        array $systemData,
        array $data,
        array $internalData,
        array $overrideData,
        array $internalOverrideData
    ): array {

        /** @var array<string, mixed> $mergedData */
        $mergedData = Util::merge($systemData, $data);

        /** @var array<string, mixed> $mergedData */
        $mergedData = Util::merge($mergedData, $internalData);

        /** @var array<string, mixed> $mergedData */
        $mergedData = Util::merge($mergedData, $overrideData);

        /** @var array<string, mixed> */
        return Util::merge($mergedData, $internalOverrideData);
    }

    /**
     * @return array<string, mixed>
     */
    private function readFile(string $path): array
    {
        return $this->fileManager->isFile($path) ?
            $this->fileManager->getPhpContents($path) : [];
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
     * @deprecated As of 7.0. Use ConfigWriter instead.
     * @param array<string, mixed> $data
     * @return void
     */
    public function setData($data)
    {
        if (is_object($data)) {
            /** @noinspection PhpParamsInspection */
            $data = get_object_vars($data);
        }

        /** @noinspection PhpDeprecationInspection */
        $this->set($data);
    }

    /**
     * @deprecated As of 7.0. Use ConfigWriter instead.
     * @return ?array<string, int>
     */
    public function updateCacheTimestamp(bool $returnOnlyValue = false)
    {
        $timestamp = [
            $this->cacheTimestamp => time()
        ];

        if ($returnOnlyValue) {
            return $timestamp;
        }

        /** @noinspection PhpDeprecationInspection */
        $this->set($timestamp);

        return null;
    }

    /**
     * @deprecated Use Espo\Core\Config\ApplicationConfig
     */
    public function getSiteUrl(): string
    {
        return rtrim($this->get('siteUrl'), '/');
    }
}
