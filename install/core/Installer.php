<?php


class Installer
{
	protected $app = null;

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
		$config = $this->app->getContainer()->get('config');

		$initData = include('install/core/init/config.php');

		$data = array(
			'database' => $database,				
		);

		$data = array_merge($data, $initData);
		$result = $config->set($data);

		$meta = array(
			'fields' => array(
				'language' => array(
					'default' => $language,
				),
			),
		);
		$result &= $this->app->getMetadata()->set($meta, 'entityDefs', 'Preferences');

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

}
