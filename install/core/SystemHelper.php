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


class SystemHelper extends \Espo\Core\Utils\System
{

	protected $requirements = array(
		'phpVersion' => '5.4',

		'exts' => array(
			'json',
			'mcrypt',
			'pdo_mysql',
		),
	);

	protected $modRewriteUrl = '/api/v1/Metadata';

	protected $writableDir = 'data';

	protected $combineOperator = '&&';


	public function initWritable()
	{
		if (is_writable($this->writableDir)) {
			return true;
		}

		return false;
	}

	public function getWritableDir()
	{
		return $this->writableDir;
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

	public function checkDbConnection($hostName, $port, $dbUserName, $dbUserPass, $dbName, $dbDriver = 'pdo_mysql', $isCreateDatabase = true)
	{
		$result['success'] = true;

		switch ($dbDriver) {
			case 'mysqli':
				$mysqli = (empty($port)) ? new mysqli($hostName, $dbUserName, $dbUserPass, $dbName) : new mysqli($hostName, $dbUserName, $dbUserPass, $dbName, $port);
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
					$dsn = "mysql:host={$hostName};" . ((!empty($port)) ? "port={$port};" : '') . "dbname={$dbName}";
					$dbh = new PDO($dsn, $dbUserName, $dbUserPass);
					$dbh = null;
				} catch (PDOException $e) {

					$result['errors']['dbConnect']['errorCode'] = $e->getCode();
					$result['errors']['dbConnect']['errorMsg'] = $e->getMessage();
					$result['success'] = false;
				}

				/** try to create a database */
				if ($isCreateDatabase && !$result['success'] && $result['errors']['dbConnect']['errorCode'] == '1049') {

					$dsn = "mysql:host={$hostName};" . ((!empty($port)) ? "port={$port}" : '');
					$pdo = new PDO($dsn, $dbUserName, $dbUserPass);

					$isCreated = true;
					try {
						$pdo->query("CREATE DATABASE IF NOT EXISTS `$dbName`");
					} catch (PDOException $e) {
						$isCreated = false;
					}

					if ($isCreated) {
						return $this->checkDbConnection($hostName, $port, $dbUserName, $dbUserPass, $dbName, $dbDriver, false);
					}
				}
				/** END: try to create a database */



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

	public function getChownCommand($path, $isSudo = false, $isCd = true)
	{
		$path = empty($path) ? '.' : $path;
		if (is_array($path)) {
			$path = implode(' ', $path);
		}

		$owner = posix_getuid();
		$group = posix_getegid();

		$sudoStr = $isSudo ? 'sudo ' : '';

		if (empty($owner) || empty($group)) {
			return null;
		}

		$cd = '';
		if ($isCd) {
			$cd = $this->getCd(true);
		}

		//$path = $this->getFullPath($path;
		return $cd.$sudoStr.'chown -R '.$owner.':'.$group.' '.$path;
	}


	public function getChmodCommand($path, $permissions = array('755'), $isSudo = false, $isFile = null, $isCd = true)
	{
		//$path = $this->getFullPath($path);

		$path = empty($path) ? '.' : $path;
		if (is_array($path)) {
			$path = implode(' ', $path);
		}

		$sudoStr = $isSudo ? 'sudo ' : '';

		$cd = $isCd ? $this->getCd(true) : '';

		if (is_string($permissions)) {
			$permissions = (array) $permissions;
		}

		if (!isset($isFile) && count($permissions) == 1) {
			return $cd.'find '.$path.' -type d -exec ' . $sudoStr . 'chmod '.$permissions[0].' {} +';
		}

		$bufPerm = (count($permissions) == 1) ?  array_fill(0, 2, $permissions[0]) : $permissions;

		$commands = array();

		if ($isCd) {
			$commands[] = $this->getCd();
		}

		$commands[] = 'find '.$path.' -type f -exec ' .$sudoStr.'chmod '.$bufPerm[0].' {} +';//.'chmod '.$bufPerm[0].' $(find '.$path.' -type f)';
		$commands[] = 'find '.$path.' -type d -exec ' .$sudoStr. 'chmod '.$bufPerm[1].' {} +';//.'chmod '.$bufPerm[1].' $(find '.$path.' -type d)';

		if (count($permissions) >= 2) {
			return implode(' ' . $this->combineOperator . ' ', $commands);
		}

		return $isFile ? $commands[0] : $commands[1];
	}

	public function getFullPath($path)
	{
		if (is_array($path)) {
			$pathList = array();
			foreach ($path as $pathItem) {
				$pathList[] = $this->getFullPath($pathItem);
			}
			return $pathList;
		}

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
	public function getPermissionCommands($path, $permissions = array('644', '755'), $isSudo = false, $isFile = null, $changeOwner = true)
	{
		if (is_string($path)) {
			$path = array_fill(0, 2, $path);
		}
		list($chmodPath, $chownPath) = $path;

		$commands = array();
		$commands[] = $this->getChmodCommand($chmodPath, $permissions, $isSudo, $isFile);

		if ($changeOwner) {
			$chown = $this->getChownCommand($chownPath, $isSudo, false);
			if (isset($chown)) {
				$commands[] = $chown;
			}
		}
		return implode(' ' . $this->combineOperator . ' ', $commands).';';
	}

	protected function getCd($isCombineOperator = false)
	{
		$cd = 'cd '.$this->getRootDir();

		if ($isCombineOperator) {
			$cd .= ' '.$this->combineOperator.' ';
		}

		return $cd;
	}

	public function getRewriteRules()
	{
		$serverType = $this->getServerType();

		$rules = array(
			'nginx' => "location /api/v1/ {\n    if (!-e " . '$request_filename' . "){\n        rewrite ^/api/v1/(.*)$ /api/v1/index.php last; break;\n    }\n}\n\nlocation / {\n    rewrite reset/?$ reset.html break;\n}\n\nlocation /(data|api) {\n    if (-e " . '$request_filename' . "){\n        return 403;\n    }\n}\n\nlocation /data/logs {\n    return 403;\n}\nlocation /data/config.php$ {\n    return 403;\n}\nlocation /data/cache {\n    return 403;\n}\nlocation /data/upload {\n    return 403;\n}\nlocation /application {\n    return 403;\n}\nlocation /custom {\n    return 403;\n}\nlocation /vendor {\n    return 403;\n}",
		);

		if (isset($rules[$serverType])) {
			return $rules[$serverType];
		}

		return '';
	}

}
