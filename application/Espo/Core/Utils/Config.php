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

class Config
{
	/**
	 * Path of default config file
	 *
	 * @access private
	 * @var string
	 */
	private $defaultConfigPath = 'application/Espo/Core/defaults/config.php';

	/**
	 * Array of admin items
	 *
	 * @access protected
	 * @var array
	 */
	protected $adminItems = array();


	/**
	 * Contains content of config
	 *
	 * @access private
	 * @var array
	 */
	private $configData;

	private $fileManager;


	public function __construct(\Espo\Core\Utils\File\Manager $fileManager) //TODO
	{
		$this->fileManager = $fileManager;
	}

	protected function getFileManager()
	{
		return $this->fileManager;
	}



	/**
	 * Get an option from config
	 *
	 * @param string $name
	 * @return string | array
	 */
	public function get($name)
	{
		$keys = explode('.', $name);

		$lastBranch = $this->loadConfig();
		foreach ($keys as $keyName) {
			if (isset($lastBranch[$keyName]) && is_array($lastBranch)) {
				$lastBranch = $lastBranch[$keyName];
			} else {
				return null;
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
	public function set($name, $value='')
	{
		if (is_array($name)) {
			return $this->setArray($name);
		}

		$content = array($name => $value);
		$status = $this->getFileManager()->mergeContentsPHP($this->get('configPath'), $content, true);
		$this->loadConfig(true);

		return $status;
	}


	/**
	 * Set options from array
	 *
	 * @param array $values
	 * @return bool
	 */
	protected function setArray($values)
	{
		if (!is_array($values)) {
			return false;
		}

		$status = $this->getFileManager()->mergeContentsPHP($this->get('configPath'), $values, true);
		$this->loadConfig(true);

		return $status;
	}

	/**
	 * Return an Object of all configs
	 * @param  boolean $reload
	 * @return array()
	 */
	protected function loadConfig($reload = false)
	{
		if (!$reload && isset($this->configData) && !empty($this->configData)) {
			return $this->configData;
		}

		$defaultConfig = $this->getFileManager()->getContents($this->defaultConfigPath);

		$config = $this->getFileManager()->getContents($defaultConfig['configPath']);
		if (empty($config)) {
			$config = array();
		}

		$this->configData =  Util::merge((array) $defaultConfig, (array) $config);
		$this->adminItems = $this->getRestrictItems();

		return $this->configData;
	}


	/**
	 * Get config acording to restrictions for a user
	 *
	 * @param $isAdmin
	 * @return array
	 */
	public function getData($isAdmin=false)
	{
		$configData = $this->loadConfig();

		$restrictedConfig = $configData;
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
	public function setData($data, $isAdmin=false)
	{
		$restrictItems = $this->getRestrictItems($isAdmin);

		$values = array();
		foreach($data as $key => $item) {
			if (!in_array($key, $restrictItems)) {
				$values[$key]= $item;
			}
		}

		return $this->setArray($values);
	}

	/**
	 * Get admin items
	 *
	 * @return object
	 */
	protected function getRestrictItems($onlySystemItems = false)
	{
		$configData = $this->loadConfig();

		if ($onlySystemItems) {
			return $configData['systemItems'];
		}

		if (empty($this->adminItems)) {
			$this->adminItems= Util::merge($configData['systemItems'], $configData['adminItems']);
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
	protected function isAllowed($name, $isAdmin=false)
	{
		if (in_array($name, $this->getRestrictItems($isAdmin))) {
			return false;
		}

		return true;
	}
}

?>
