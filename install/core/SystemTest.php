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


class SystemTest
{

	protected $requirements = array(
		'phpVersion' => '5.4',
		
		'exts' => array(
			'json',
			'mcrypt',
		),
	);
	
	
	public function __construct()
	{
		
	}
	
	
	public function checkRequirements()
	{
		$result['success'] = true;
		if (!empty($this->requirements)) {
			if (!empty($this->requirements['phpVersion']) && version_compare(PHP_VERSION, $this->requirements['phpVersion']) == -1) {
				$result['errors']['phpVersion'] = $this->requirements['phpVersion'];
				$result['success'] = false;
			}
			if (!empty($this->requirements['exts'])) {
				foreach ($this->requirements['exts'] as $extName) {
					if (!extension_loaded($extName)) {
						$result['errors']['exts'][] = $extName;
						$result['success'] = false;
					}
				}
			}
		}
		
		return $result;
	}
	
	function checkDbConnection($hostName, $dbUserName, $dbUserPass, $dbName, $dbDriver = 'pdo_mysql')
	{
		$result['success'] = true;
		
		switch ($dbDriver) {
			case 'mysqli':
				$mysqli = new mysqli($hostName, $dbUserName, $dbUserPass, $dbName);
				if (!$mysqli->connect_errno) {
					$mysqli->close();
				}
				else {
					$result['errors']['dbConnect']['errorCode'] = $mysqli->connect_errno;
					$result['errors']['dbConnect']['errorMsg'] = $mysqli->connect_error;
					$result['success'] = false;
				}
				break;
				
			case 'pdo_mysql':
				try {
					$dbh = new PDO("mysql:host={$hostName};dbname={$dbName}", $dbUserName, $dbUserPass);
					$dbh = null;
				} catch (PDOException $e) {
				
					$result['errors']['dbConnect']['errorCode'] = $e->getCode();
					$result['errors']['dbConnect']['errorMsg'] = $e->getMessage();
					$result['success'] = false;
				}
				break;
		}
		
		return $result;
	}
	
	public function checkModRewrite()
	{
		if ( function_exists('apache_get_modules') && in_array('mod_rewrite',apache_get_modules()) ) {
			return true;
		} elseif (isset($_SERVER['IIS_UrlRewriteModule'])) {
		    return true;
		} elseif (getenv('ESPO_MR')=='On' ) {
			return true;
		} elseif (isset($_SERVER['ESPO_MR'])) {
			return true;
		}
		
		return false;
	}
	
}
