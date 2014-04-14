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


class SystemHelper
{

	protected $requirements = array(
		'phpVersion' => '5.4',

		'exts' => array(
			'json',
			'mcrypt',
		),
	);

	protected $modRewriteUrl = '/api/v1/Metadata';

	protected $systemConfig = 'data/config.php';


	public function initWritable()
	{
		$pathInfo = pathinfo($this->systemConfig);

		if (!is_writable($pathInfo['dirname'])) {
			return false;
		}

		if (!@touch($this->systemConfig)) {
			return false;
		}

		return true;
	}

	public function getSystemDir()
	{
		$pathInfo = pathinfo($this->systemConfig);

		return $pathInfo['dirname'];
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

	public function checkDbConnection($hostName, $dbUserName, $dbUserPass, $dbName, $dbDriver = 'pdo_mysql')
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

	public function getBaseUrl()
	{
		$pageUrl = ($_SERVER["HTTPS"] == 'on') ? 'https://' : 'http://';

		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageUrl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageUrl .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}

		$baseUrl = str_ireplace('/install/index.php', '', $pageUrl);

		return $baseUrl;
	}

	public function getModRewriteUrl()
	{
		return $this->modRewriteUrl;
	}

	/**
	 * Get web server name
	 *
	 * @return string Ex. "microsoft-iis", "nginx", "apache"
	 */
	public function getServerType()
	{
		$serverSoft = $_SERVER['SERVER_SOFTWARE'];

		preg_match('/^(.*)\//i', $serverSoft, $match);
		if (empty($match[1])) {
			preg_match('/^(.*)\/?/i', $serverSoft, $match);
		}
		$serverName = strtolower( trim($match[1]) );

		return $serverName;
	}

	/**
	 * Get Operating System of web server. Details http://en.wikipedia.org/wiki/Uname
	 *
	 * @return string  Ex. "windows", "mac", "linux"
	 */
	public function getOS()
	{
		$osList = array(
			'windows' => array(
				'win',
				'UWIN',
			),
			'mac' => array(
				'mac',
				'darwin',
			),
			'linux' => array(
				'linux',
				'cygwin',
				'GNU',
				'FreeBSD',
				'OpenBSD',
				'NetBSD',
			),
		);

		$sysOS = strtolower(PHP_OS);

		foreach ($osList as $osName => $osSystem) {
			if (preg_match('/^('.implode('|', $osSystem).')/i', $sysOS)) {
				return $osName;
			}
		}

		return false;
	}


	public function getRootDir()
	{
		$rootDir = dirname(__FILE__);

		$rootDir = preg_replace('/\/install\/core\/?/', '', $rootDir, 1);

		return $rootDir;
	}

	public function getPhpBin()
	{
		return (defined("PHP_BINDIR"))? PHP_BINDIR.DIRECTORY_SEPARATOR.'php' : 'php';
	}

	public function getChmodCommand($path, $permissions = array('755'), $isSudo = false, $isFile = null)
	{
		$path = $this->getFullPath($path);

		$sudoStr = $isSudo ? 'sudo ' : '';

		if (is_string($permissions)) {
			$permissions = (array) $permissions;
		}

		if (!isset($isFile) && count($permissions) == 1) {
			return $sudoStr.'chmod -R '.$permissions[0].' '.$path;
		}

		$bufPerm = (count($permissions) == 1) ?  array_fill(0, 2, $permissions[0]) : $permissions;

		$commands = array();
		$commands[] = $sudoStr.'chmod '.$bufPerm[0].' $(find '.$path.' -type f)';
		$commands[] = $sudoStr.'chmod '.$bufPerm[1].' $(find '.$path.' -type d)';

		if (count($permissions) >= 2) {
			return implode('; ', $commands);
		}

		return $isFile ? $commands[0] : $commands[1];
	}

	public function getChownCommand($path, $isSudo = false)
	{
		$owner = posix_getuid();
		$group = posix_getegid();

		$sudoStr = $isSudo ? 'sudo ' : '';

		if (empty($owner) || empty($group)) {
			return null;
		}

		return $sudoStr.'chown -R '.$owner.':'.$group.' '.$this->getFullPath($path);
	}

	public function getFullPath($path)
	{
		if (!empty($path)) {
			$path = DIRECTORY_SEPARATOR . $path;
		}

		return $this->getRootDir() . $path;
	}

	/**
	 * Get permission commands
	 *
	 * @param  string | array  $path
	 * @param  string | array  $permissions
	 * @param  boolean $isSudo
	 * @param  bool  $isFile
	 * @return string
	 */
	public function getPermissionCommands($path, $permissions = array('644', '755'), $isSudo = false, $isFile = null)
	{
		if (is_string($path)) {
			$path = array_fill(0, 2, $path);
		}
		list($chmodPath, $chownPath) = $path;

		$commands = array();
		$commands[] = $this->getChmodCommand($chmodPath, $permissions, $isSudo, $isFile);

		$chown = $this->getChownCommand($chownPath, $isSudo);
		if (isset($chown)) {
			$commands[] = $chown;
		}

		return implode('; ', $commands);
	}

}
