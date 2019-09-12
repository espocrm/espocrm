<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Utils\Util,
    Espo\Core\Exceptions\NotFound,
    Espo\Core\Exceptions\Error;

class Language
{
    private $fileManager;

    private $metadata;

    private $unifier;

    private $data = [];

    private $deletedData = [];

    private $changedData = [];

    private $currentLanguage = null;

    protected $cacheFile = 'data/cache/application/languages/{language}.php';

    protected $defaultLanguage = 'en_US';

    protected $useCache = false;

    protected $noCustom = false;

    protected $paths = [
        'corePath' => 'application/Espo/Resources/i18n/{language}',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/i18n/{language}',
        'customPath' => 'custom/Espo/Custom/Resources/i18n/{language}',
    ];

    public function __construct($language = null, File\Manager $fileManager, Metadata $metadata, $useCache = false, $noCustom = false)
    {
        if ($language) {
            $this->currentLanguage = $language;
        } else {
            $this->currentLanguage = $this->defaultLanguage;
        }

        $this->fileManager = $fileManager;
        $this->metadata = $metadata;

        $this->useCache = $useCache;
        $this->noCustom = $noCustom;

        $this->unifier = new \Espo\Core\Utils\File\Unifier($this->fileManager, $this->metadata);
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getUnifier()
    {
        return $this->unifier;
    }

    public function getDefaultLanguage()
    {
        return $this->defaultLanguage;
    }

    public static function detectLanguage(\Espo\Core\Utils\Config $config, $preferences = null)
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

    public function getLanguage()
    {
        return $this->currentLanguage;
    }

    public function setLanguage($language)
    {
        $this->currentLanguage = $language;
    }

    protected function getCacheFile(string $language = null) : string
    {
        $language = $language ?? $this->getLanguage();

        $langCacheFile = str_replace('{language}', $language, $this->cacheFile);

        return $langCacheFile;
    }

    /**
     * Translate label/labels
     *
     * @param  string $label name of label
     * @param  string $category
     * @param  string $scope
     * @param  array $requiredOptions List of required options.
     *  Ex., $requiredOptions = array('en_US', 'de_DE')
     *  "language" option has only array('en_US' => 'English (United States)',)
     *  Result will be array('en_US' => 'English (United States)', 'de_DE' => 'de_DE',)
     * @return string | array
     */
    public function translate($label, $category = 'labels', $scope = 'Global', $requiredOptions = null)
    {
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

    public function translateOption($value, $field, $scope = 'Global')
    {
        $options = $this->get($scope. '.options.' . $field);
        if (is_array($options) && array_key_exists($value, $options)) {
            return $options[$value];
        } else if ($scope !== 'Global') {
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

        if (!isset($data) || $data === false) {
            throw new Error('Language: current language '.$this->getLanguage().' not found');
        }

        return Util::getValueByKey($data, $key, $returns);
    }

    public function getAll()
    {
        return $this->get();
    }

    /**
     * Save changes
     */
    public function save() : bool
    {
        $language = $this->getLanguage();

        $path = $this->paths['customPath'];
        $path = str_replace('{language}', $language, $path);

        $result = true;
        if (!empty($this->changedData)) {
            foreach ($this->changedData as $scope => $data) {
                if (!empty($data)) {
                    $result &= $this->getFileManager()->mergeContents([$path, $scope.'.json'], $data, true);
                }
            }
        }

        if (!empty($this->deletedData)) {
            foreach ($this->deletedData as $scope => $unsetData) {
                if (!empty($unsetData)) {
                    $result &= $this->getFileManager()->unsetContents([$path, $scope.'.json'], $unsetData, true);
                }
            }
        }

        $this->clearChanges();

        return (bool) $result;
    }

    /**
     * Clear unsaved changes
     *
     * @return void
     */
    public function clearChanges()
    {
        $this->changedData = [];
        $this->deletedData = [];
        $this->init(true);
    }

    protected function getData()
    {
        $currentLanguage = $this->getLanguage();

        if (!isset($this->data[$currentLanguage])) {
            $this->init();
        }

        return $this->data[$currentLanguage];
    }

    /**
     * Set/change a label
     *
     * @param string $scope
     * @param string $category
     * @param string | array $name
     * @param mixed $value
     *
     * @return void
     */
    public function set($scope, $category, $name, $value)
    {
        if (is_array($name)) {
            foreach ($name as $rowLabel => $rowValue) {
                $this->set($scope, $category, $rowLabel, $rowValue);
            }
            return;
        }

        $this->changedData[$scope][$category][$name] = $value;

        $currentLanguage = $this->getLanguage();
        if (!isset($this->data[$currentLanguage])) {
            $this->init();
        }
        $this->data[$currentLanguage][$scope][$category][$name] = $value;

        $this->undelete($scope, $category, $name);
    }

    /**
     * Remove a label
     *
     * @param  string $name
     * @param  string $category
     * @param  string $scope
     *
     * @return void
     */
    public function delete($scope, $category, $name)
    {
        if (is_array($name)) {
            foreach ($name as $rowLabel) {
                $this->delete($scope, $category, $rowLabel);
            }
            return;
        }

        $this->deletedData[$scope][$category][] = $name;

        $currentLanguage = $this->getLanguage();
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

    protected function undelete($scope, $category, $name)
    {
        if (isset($this->deletedData[$scope][$category])) {
            foreach ($this->deletedData[$scope][$category] as $key => $labelName) {
                if ($name === $labelName) {
                    unset($this->deletedData[$scope][$category][$key]);
                }
            }
        }
    }

    protected function init(bool $reload = false)
    {
        $this->data[$this->currentLanguage] = $this->getLanguageData($this->currentLanguage, $reload);
    }

    protected function getDefaultLanguageData(bool $reload = false)
    {
        return $this->getLanguageData($this->defaultLanguage, $reload);
    }

    protected function getLanguageData(string $language, bool $reload = false)
    {
        if ($reload || !isset($this->data[$language])) {

            $cacheFile = $this->getCacheFile($language);

            if (!$this->useCache || !file_exists($cacheFile) || $reload) {

                $paths = $this->paths;

                foreach ($paths as $k => &$path) {
                    $path = str_replace('{language}', $language, $path);
                }

                if ($this->noCustom) {
                    unset($paths['customPath']);
                }

                $data = $this->getUnifier()->unify('i18n', $paths, true);

                if (is_array($data)) {
                    $this->sanitizeData($data);
                }

                if ($language != $this->defaultLanguage) {
                    $data = Util::merge($this->getDefaultLanguageData($reload), $data);
                }

                $this->data[$language] = $data;

                if ($this->useCache) {
                    $putResult = $this->getFileManager()->putPhpContents($cacheFile, $data);
                    if (!$putResult) {
                        $GLOBALS['log']->error("Language: Could not store cache file for {$language}");
                    }
                }
            }

            if ($this->useCache) {
                $this->data[$language] = $this->getFileManager()->getPhpContents($cacheFile);
            }
        }

        return $this->data[$language] ?? [];
    }

    protected function sanitizeData(array &$data)
    {
        foreach ($data as $key => &$subData) {
            if (is_array($subData)) {
                $this->sanitizeData($subData);
            } else {
                if (is_string($subData)) {
                    $subData = str_replace('<', '&lt;', $subData);
                    $subData = str_replace('>', '&gt;', $subData);
                }
            }
        }
    }
}
