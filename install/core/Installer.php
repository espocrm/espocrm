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


class Installer
{
	protected $app = null;
	protected $i18n = null;	

	protected $isAuth = false;

	protected $writableList = array(
		'data',
	);

	protected $writableListError;

	/**
	 * Ajax Urls, pairs: url:directory (if bad permission)
	 * 
	 * @var array
	 */
	protected $ajaxUrls = array(		
		'api/v1/Settings' => 'api',
		'api/v1' => 'api',
		'client/res/templates/login.tpl' => 'client/res/templates',
	);


	protected $settingList = array(
		'dateFormat',
		'timeFormat',
		'timeZone',
		'weekStart',
		'defaultCurrency' => array(
			'currencyList', 'defaultCurrency',
		),		
		'smtpSecurity',	
		'language',	
	);



	public function __construct()
	{
		$this->app = new \Espo\Core\Application();
		$this->writableList[] = $this->app->getContainer()->get('config')->get('configPath');
	}

	protected function getEntityManager()
	{
		return $this->app->getContainer()->get('entityManager');
	}


	protected function auth()
	{
		if (!$this->isAuth) {
			$auth = new \Espo\Core\Utils\Auth($this->app->getContainer());
			$auth->useNoAuth();	

			$this->isAuth = true;
		}		

		return $this->isAuth;		
	}

	public function isInstalled()
	{
		return $this->app->isInstalled(false);
	}


	public function getLastWritableError()
	{
		return $this->writableListError;
	}

	protected function getI18n()
	{
		if (!isset($this->i18n)) {
			$this->i18n = $this->app->getContainer()->get('i18n');	
		}

		return $this->i18n;
	}

	public function getLanguageList()
	{
		$config = $this->app->getContainer()->get('config');

		$languageList = array(
			'options' => $config->get('languageList'),
		);
		$translated = $this->translateSetting('language', $languageList);		

		return $translated['options'];
	}

	/**
	 * Save data 
	 * 
	 * @param  array $database 
	 * array (
	 *   'driver' => 'pdo_mysql',
	 *   'host' => 'localhost',
	 *   'dbname' => 'espocrm_test',
	 *   'user' => 'root',
	 *   'password' => '',
	 * ),
	 * @param  string $language     
	 * @return bool          
	 */
	public function saveData($database, $language)
	{
		$initData = include('install/core/init/config.php');

		$data = array(
			'database' => $database,				
			'language' => $language,				
		);

		$data = array_merge($data, $initData);
		$result = $this->saveConfig($data);

		return $result;
	}


	public function saveConfig($data)
	{
		$config = $this->app->getContainer()->get('config');
		
		$result = $config->set($data);

		return $result;
	}


	public function buildDatabase()
	{
		$user = $this->getEntityManager()->getEntity('User');
		$this->app->getContainer()->setUser($user);

		try {
			$this->app->getContainer()->get('schema')->rebuild();
		} catch (\Exception $e) {	

		}

		$this->auth();

		return $this->app->getContainer()->get('schema')->rebuild();
	}

	public function setPreferences($preferences)
	{
		return $this->saveConfig($preferences);
	}

	public function createUser($userName, $password)
	{
		$this->auth();

		$userId = '1';

		$entity = $this->getEntityManager()->getEntity('User', $userId);

		if (!isset($entity)) {
			$pdo = $this->getEntityManager()->getPDO();	

			$sql = "SELECT id FROM `user` WHERE `id` = '".$userId."'";
			$sth = $pdo->prepare($sql);
			$sth->execute();

			$deletedUser = $sth->fetch(\PDO::FETCH_ASSOC);					

			if ($deletedUser) {				 
				$sql = "UPDATE `user` SET deleted = '0' WHERE `id` = '".$userId."'";								
				$pdo->prepare($sql)->execute();	

				$entity = $this->getEntityManager()->getEntity('User', $userId);
			}												
		}		

		if (!isset($entity)) {		
			$entity = $this->getEntityManager()->getEntity('User');		
			$entity->set('id', $userId);								
		}	

		$entity->set('userName', $userName);			
		$entity->set('password', md5($password));			
		$entity->set('lastName', 'Administrator');			

		$userId = $this->getEntityManager()->saveEntity($entity);
		
		return is_string($userId);
	}

	public function isWritable()
	{
		$this->writableListError = array();

		$fileManager = $this->app->getContainer()->get('fileManager');

		$result = true;
		foreach ($this->writableList as $item) {

			if (!file_exists($item)) {
				$item = $fileManager->getDirName($item);
			}
			
			if (file_exists($item) && !is_writable($item)) {

				$fileManager->getPermissionUtils()->setDefaultPermissions($item);
				if (!is_writable($item)) {
					$result = false;
					$this->writableListError[] = $item;
				}				
			}	
		}

		return $result;
	}


	public function getAjaxUrls()
	{
		return array_keys($this->ajaxUrls);
	}


	public function fixAjaxPermission($url = null)
	{
		$permission = array(0644, 0755);

		$fileManager = $this->app->getContainer()->get('fileManager');

		$result = false;
		if (!isset($url)) {
			$uniqueList = array_unique($this->ajaxUrls);
			foreach ($uniqueList as $url => $path) {
				$result = $fileManager->getPermissionUtils()->chmod($path, $permission, true);
			}	
		} else {	
			if (isset($this->ajaxUrls[$url])) {
				$path = $this->ajaxUrls[$url];				
				$result = $fileManager->getPermissionUtils()->chmod($path, $permission, true);
			}
		}		
		
		return $result;
	}

	public function setSuccess()
	{
		$config = $this->app->getContainer()->get('config');
		$result = $config->set('isInstalled', true);

		return $result;
	}


	public function getSettingDefaults()
	{
		$defaults = array();	

		$settingDefs = $this->app->getMetadata()->get('entityDefs.Settings.fields');				

		foreach ($this->settingList as $fieldName => $field) {

			if (is_array($field)) {
				$fieldDefaults = array();
				foreach ($field as $subField) {
					if (isset($settingDefs[$subField])) {
						$fieldDefaults = array_merge($fieldDefaults, $this->translateSetting($subField, $settingDefs[$subField])); 	
					}
				}
				$defaults[$fieldName] = $fieldDefaults;

			} else if (isset($settingDefs[$field])) {

				$defaults[$field] = $this->translateSetting($field, $settingDefs[$field]);	
			}
		}

		return $defaults;
	}

	protected function translateSetting($name, array $settingDefs)
	{
		if (isset($settingDefs['options'])) {
			$optionLabel = $this->getI18n()->translate($name, 'options', 'Settings');

			if ($optionLabel == $name) {
				$optionLabel = $this->getI18n()->translate($name, 'options', 'Global');	
			}

			if ($optionLabel == $name) {
				$optionLabel = array();
				foreach ($settingDefs['options'] as $key => $value) {
					$optionLabel[$value] = $value;
				}
			}

			$settingDefs['options'] = $optionLabel;	
		}		
	
		return $settingDefs;
	}


}
