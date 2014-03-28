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
		} elseif ($this->checkModRewriteByUrl()) {
			return true;
		}

		return false;
	}

	protected function checkModRewriteByUrl()
	{
		$url = $this->getBaseUrl().$this->modRewriteUrl;

		if (!$this->isCurl()) {
			$httpCode = $this->getHttpCodeByCurl($url);
		}

		$httpCode = $this->getHttpCodeByHeader($url);

		if ($httpCode != false && !empty($httpCode)) {
			if ($httpCode == '200' || $httpCode == '401') {
				return true;
			}
		}

		return false;
	}
	
	public function getModRewriteUrl()
	{
		return $this->modRewriteUrl;
	}

	protected function getHttpCodeByCurl($url)
	{
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($response != false && !empty($httpCode)) {
			return $httpCode;
		}

		return false;
	}

	protected function getHttpCodeByHeader($url)
	{
		stream_context_set_default(
			array(
				'http' => array(
					'timeout' => 3.0,
				)
			)
		);
		$headers = get_headers($url);

		if ($headers != false) {

			preg_match('/HTTP.*([0-9]{3})/i', $headers[0], $match);

			return $httpCode = trim($match[1]);
		}

		return false;
	}

	protected function isCurl()
	{
		return function_exists('curl_version');
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


	/**
	 * Get web server name
	 *
	 * @return string Ex. "microsoft-iis", "nginx", "apache"
	 */
	public function getServerType()
	{
		$serverSoft = $_SERVER['SERVER_SOFTWARE'];

		preg_match('/^(.*)\//i', $serverSoft, $match);
		$serverName = strtolower( trim($match[1]) );

		return $serverName;
	}

}
