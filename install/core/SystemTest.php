<?php


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
