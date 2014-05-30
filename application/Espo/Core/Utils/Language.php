<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

namespace Espo\Core\Utils;

use \Espo\Core\Utils\Util,
	\Espo\Core\Exceptions\NotFound,
	\Espo\Core\Exceptions\Error;

class Language
{
	private $fileManager;
	private $config;
	private $preferences;
	private $unifier;

	private $data = null;

	private $name = 'i18n';

	private $currentLanguage = null;

	protected $cacheFile = 'data/cache/application/languages/{*}.php';

	protected $defaultLanguage = 'en_US';

	/**
     * @var array
     */
	private $paths = array(
		'corePath' => 'application/Espo/Resources/i18n',
		'modulePath' => 'application/Espo/Modules/{*}/Resources/i18n',
		'customPath' => 'custom/Espo/Custom/Resources/i18n',
	);


	public function __construct(\Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\Utils\Config $config, \Espo\Entities\Preferences $preferences = null)
	{
		$this->fileManager = $fileManager;
		$this->config = $config;
		$this->preferences = $preferences;

		$this->unifier = new \Espo\Core\Utils\File\Unifier($this->fileManager);
	}

	protected function getFileManager()
	{
		return $this->fileManager;
	}

	protected function getConfig()
	{
		return $this->config;
	}

	protected function getPreferences()
	{
		return $this->preferences;
	}

	protected function getUnifier()
	{
		return $this->unifier;
	}


	public function getLanguage()
	{
		if (!isset($this->currentLanguage) && isset($this->preferences)) {
			$this->currentLanguage = $this->getPreferences()->get('language');
		}

		if (empty($this->currentLanguage)) {
			$this->currentLanguage = $this->getConfig()->get('language');
		}

		return $this->currentLanguage;
	}

	public function setLanguage($language)
	{
		$this->currentLanguage = $language;
	}

	protected function getLangCacheFile()
	{
		$langCacheFile = str_replace('{*}', $this->getLanguage(), $this->cacheFile);

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
			$translated = array();

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


	public function get($key = null, $returns = null)
	{
		$data = $this->getData();

		if (!isset($data) || $data === false) {
			throw new Error('Language: current language ['.$this->getLanguage().'] does not found');
		}

		return Util::getValueByKey($data, $key, $returns);
	}


	public function getAll()
	{
		return $this->get();
	}


	/**
	 * Get data of Unifier language files
	 *
	 * @return array
	 */
	protected function getData()
	{
		if (!isset($this->data)) {
			$this->init();
		}

		return $this->data;
	}

	public function set($label, $value, $category = 'labels', $scope = 'Global')
	{
		$path = $this->paths['customPath'];
		$currentLanguage = $this->getLanguage();

		$data = $this->normalizeDefs($label, $value, $category);

		$result = $this->getFileManager()->mergeContents(array($path, $currentLanguage, $scope.'.json'), $data, true);
		if ($result === false) {
			throw new Error("Error saving languages. See log file for details.");
		}

		$this->init(true);

        return $result;
	}

	public function delete($label, $category = 'labels', $scope = 'Global')
	{
		$path = $this->paths['customPath'];
		$currentLanguage = $this->getLanguage();

		$unsets = array(
			$category => $label,
		);

		$result = $this->getFileManager()->unsetContents(array($path, $currentLanguage, $scope.'.json'), $unsets, true);

		$this->init(true);

		return $result;
	}


	protected function init($reload = false)
	{
		if ($reload || !file_exists($this->getLangCacheFile()) || !$this->getConfig()->get('useCache')) {
			$this->fullData = $this->getUnifier()->unify($this->name, $this->paths, true);

			$result = true;
			foreach ($this->fullData as $i18nName => $i18nData) {
				$i18nCacheFile = str_replace('{*}', $i18nName, $this->cacheFile);

				if ($i18nName != $this->defaultLanguage) {
					$i18nData = Util::merge($this->fullData[$this->defaultLanguage], $i18nData);
				}
				$result &= $this->getFileManager()->putContentsPHP($i18nCacheFile, $i18nData);
			}

			if ($result == false) {
				throw new Error('Language::init() - Cannot save data to a cache');
			}
		}

		$this->data = $this->getFileManager()->getContents($this->getLangCacheFile());
	}

	protected function normalizeDefs($label, $value, $category)
	{
		if (!is_array($label)) {
			$label = array(
				$label => $value,
			);
		}

		$data = array(
			$category => $label,
		);

		return $data;
	}









}
