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
    Utils\Util,
    Utils\File\Manager as FileManager,
    Utils\Config,
    Utils\DataCache,
    Utils\Resource\Reader as ResourceReader,
    Utils\Resource\Reader\Params as ResourceReaderParams,
};

use Espo\{
    Entities\Preferences,
};

class Language
{
    private $data = [];

    private $deletedData = [];

    private $changedData = [];

    private $currentLanguage = null;

    protected $defaultLanguage = 'en_US';

    protected $useCache = false;

    protected $noCustom = false;

    private $customPath = 'custom/Espo/Custom/Resources/i18n/{language}';

    private $resourcePath = 'i18n/{language}';

    private $fileManager;

    private $resourceReader;

    private $dataCache;

    public function __construct(
        ?string $language,
        FileManager $fileManager,
        ResourceReader $resourceReader,
        DataCache $dataCache,
        bool $useCache = false,
        bool $noCustom = false
    ) {
        $this->currentLanguage = $language ?? $this->defaultLanguage;

        $this->fileManager = $fileManager;
        $this->resourceReader = $resourceReader;
        $this->dataCache = $dataCache;

        $this->useCache = $useCache;
        $this->noCustom = $noCustom;
    }

    public function getLanguage(): string
    {
        return $this->currentLanguage;
    }

    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    public static function detectLanguage(Config $config, ?Preferences $preferences = null): ?string
    {
        $language = null;

        if ($preferences) {
            $language = $preferences->get('language');
        }

        if (!$language) {
            $language = $config->get('language');
        }

        return $language;
    }


    public function setLanguage(string $language): void
    {
        $this->currentLanguage = $language;
    }

    private function getCacheKey(string $language = null): string
    {
        $language = $language ?? $this->currentLanguage;

        return 'languages/' . $language;
    }

    /**
     * Translate label/labels.
     *
     * @param string|array $label name of label
     * @param string $category
     * @param string $scope
     * @param array|null $requiredOptions List of required options.
     *  Ex., $requiredOptions = ['en_US', 'de_DE']
     *  "language" option has only ['en_US' => 'English (United States)']
     *  Result will be ['en_US' => 'English (United States)', 'de_DE' => 'de_DE'].
     * @return string|array
     */
    public function translate(
        $label,
        string $category = 'labels',
        string $scope = 'Global',
        ?array $requiredOptions = null
    ) {
        if (is_array($label)) {
            $translated = [];

            foreach ($label as $subLabel) {
                $translated[$subLabel] = $this->translate($subLabel, $category, $scope, $requiredOptions);
            }

            return $translated;
        }

        $key = $scope.'.'.$category.'.'.$label;
        $translated = $this->get($key);

        if (!isset($translated)) {
            $key = 'Global.'.$category.'.'.$label;
            $translated = $this->get($key, $label);
        }

        if (is_array($translated) && isset($requiredOptions)) {

            $translated = array_intersect_key($translated, array_flip($requiredOptions));

            $optionKeys = array_keys($translated);
            foreach ($requiredOptions as $option) {
                if (!in_array($option, $optionKeys)) {
                    $translated[$option] = $option;
                }
            }
        }

        return $translated;
    }

    public function translateOption($value, string $field, string $scope = 'Global')
    {
        $options = $this->get($scope. '.options.' . $field);

        if (is_array($options) && array_key_exists($value, $options)) {
            return $options[$value];
        }

        if ($scope !== 'Global') {
            $options = $this->get('Global.options.' . $field);

            if (is_array($options) && array_key_exists($value, $options)) {
                return $options[$value];
            }
        }

        return $value;
    }

    public function get($key = null, $returns = null)
    {
        $data = $this->getData();

        if (!isset($data)) {
            throw new Error('Language: current language '.$this->currentLanguage.' not found');
        }

        return Util::getValueByKey($data, $key, $returns);
    }

    public function getAll(): array
    {
        return $this->get();
    }

    /**
     * Save changes.
     */
    public function save(): bool
    {
        $path = str_replace('{language}', $this->currentLanguage, $this->customPath);

        $result = true;

        if (!empty($this->changedData)) {
            foreach ($this->changedData as $scope => $data) {
                if (empty($data)) {
                    continue;
                }

                $result &= $this->fileManager->mergeJsonContents($path . "/{$scope}.json", $data);

            }
        }

        if (!empty($this->deletedData)) {
            foreach ($this->deletedData as $scope => $unsetData) {
                if (empty($unsetData)) {
                    continue;
                }

                $result &= $this->fileManager->unsetJsonContents($path . "/{$scope}.json", $unsetData);
            }
        }

        $this->clearChanges();

        return (bool) $result;
    }

    /**
     * Clear unsaved changes.
     */
    public function clearChanges(): void
    {
        $this->changedData = [];
        $this->deletedData = [];

        $this->init(true);
    }

    private function getData(): ?array
    {
        $currentLanguage = $this->currentLanguage;

        if (!isset($this->data[$currentLanguage])) {
            $this->init();
        }

        return $this->data[$currentLanguage] ?? null;
    }

    /**
     * Set/change a label.
     *
     * @param string $scope
     * @param string $category
     * @param string|array $name
     * @param mixed $value
     */
    public function set($scope, $category, $name, $value): void
    {
        if (is_array($name)) {
            foreach ($name as $rowLabel => $rowValue) {
                $this->set($scope, $category, $rowLabel, $rowValue);
            }

            return;
        }

        $this->changedData[$scope][$category][$name] = $value;

        $currentLanguage = $this->currentLanguage;

        if (!isset($this->data[$currentLanguage])) {
            $this->init();
        }

        $this->data[$currentLanguage][$scope][$category][$name] = $value;

        $this->undelete($scope, $category, $name);
    }

    /**
     * Remove a label.
     *
     * @param string $scope
     * @param string $category
     * @param string|array $name
     */
    public function delete(string $scope, string $category, $name): void
    {
        if (is_array($name)) {
            foreach ($name as $rowLabel) {
                $this->delete($scope, $category, $rowLabel);
            }

            return;
        }

        $this->deletedData[$scope][$category][] = $name;

        $currentLanguage = $this->currentLanguage;

        if (!isset($this->data[$currentLanguage])) {
            $this->init();
        }

        if (isset($this->data[$currentLanguage][$scope][$category][$name])) {
            unset($this->data[$currentLanguage][$scope][$category][$name]);
        }

        if (isset($this->changedData[$scope][$category][$name])) {
            unset($this->changedData[$scope][$category][$name]);
        }
    }

    private function undelete($scope, $category, $name): void
    {
        if (isset($this->deletedData[$scope][$category])) {
            foreach ($this->deletedData[$scope][$category] as $key => $labelName) {
                if ($name === $labelName) {
                    unset($this->deletedData[$scope][$category][$key]);
                }
            }
        }
    }

    private function init(bool $reload = false): void
    {
        $this->data[$this->currentLanguage] = $this->getLanguageData($this->currentLanguage, $reload);
    }

    private function getDefaultLanguageData(bool $reload = false): array
    {
        return $this->getLanguageData($this->defaultLanguage, $reload);
    }

    private function getLanguageData(string $language, bool $reload = false): array
    {
        if (!$reload && isset($this->data[$language])) {
            return $this->data[$language] ?? [];
        }

        $cacheKey = $this->getCacheKey($language);

        if (!$this->useCache || !$this->dataCache->has($cacheKey) || $reload) {
            $readerParams = ResourceReaderParams::create()
                ->withAsArray()
                ->withNoCustom($this->noCustom);

            $path = str_replace('{language}', $language, $this->resourcePath);

            $data = $this->resourceReader->read($path, $readerParams);

            if (is_array($data)) {
                $this->sanitizeData($data);
            }

            if ($language != $this->defaultLanguage) {
                $data = Util::merge($this->getDefaultLanguageData($reload), $data);
            }

            $this->data[$language] = $data;

            if ($this->useCache) {
                $this->dataCache->store($cacheKey, $data);
            }
        }

        if ($this->useCache) {
            $this->data[$language] = $this->dataCache->get($cacheKey);
        }

        return $this->data[$language] ?? [];
    }

    private function sanitizeData(array &$data): void
    {
        foreach ($data as &$subData) {
            if (is_array($subData)) {
                $this->sanitizeData($subData);
            }
            else if (is_string($subData)) {
                $subData = str_replace('<', '&lt;', $subData);
                $subData = str_replace('>', '&gt;', $subData);
            }
        }
    }
}
