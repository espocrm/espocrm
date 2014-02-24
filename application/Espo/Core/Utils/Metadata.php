<?php

namespace Espo\Core\Utils;

class Metadata
{
	protected $meta = null;	

	protected $scopes = array();

	private $config;
	private $unifier;
	private $fileManager;
	private $converter;

	/**
     * @var string - uses for loading default values
     */
	private $name = 'metadata';

	private $cacheFile = 'data/cache/application/metadata.php';	

	private $paths = array(
		'corePath' => 'application/Espo/Resources/metadata',
    	'modulePath' => 'application/Espo/Modules/{*}/Resources/metadata',
    	'customPath' => 'application/Espo/Custom/Resources/metadata',	                              			
	);  


	protected $ormMeta = null;

	private $ormCacheFile = 'data/cache/application/ormMetadata.php';


	
	private $moduleList = null;

	public function __construct(\Espo\Core\Utils\Config $config, \Espo\Core\Utils\File\Manager $fileManager)
	{
		$this->config = $config;
		$this->fileManager = $fileManager;

		$this->unifier = new \Espo\Core\Utils\File\Unifier($this->fileManager);

		$this->converter = new \Espo\Core\Utils\Database\Converter($this, $this->fileManager);		
		
		$this->init(!$this->isCached());
	}

	protected function getConfig()
	{
		return $this->config;
	}


	protected function getUnifier()
	{
		return $this->unifier;
	}

    protected function getFileManager()
	{
		return $this->fileManager;
	}

	protected function getConverter()
	{
		return $this->converter;
	}


	public function isCached()
	{
    	if (!$this->getConfig()->get('useCache')) {
           	return false;
		}
	
		if (file_exists($this->cacheFile)) {
			return true;
		}

		return false;
	}


    public function init($reload = false)
	{
       	$data = $this->getMetadataOnly(false, $reload);
		if ($data === false) {
			$GLOBALS['log']->emergency('Metadata:init() - metadata has not been created');
		}

		$this->meta = $data;

		if ($reload) {
        	//save medatada to a cache file    
	        $isSaved = $this->getFileManager()->putContentsPHP($this->cacheFile, $data);
			if ($isSaved === false) {
	        	$GLOBALS['log']->emergency('Metadata:init() - metadata has not been saved to a cache file');
			}
		}
	}

	/**
	 * Get unified metadata
	 * 
	 * @return array
	 */
	protected function getData()
	{
		if (!isset($this->meta)) {
			$this->init();
		}

		return $this->meta;
	}


	/**
    * Get Metadata
	*
	* @param string $key
	* @param mixed $return
	*
	* @return array
	*/
	public function get($key = null, $returns = null)
	{
		return Util::getValueByKey($this->getData(), $key, $returns);        
	}


	/**
    * Get All Metadata context
	*
	* @param $isJSON
	* @param bool $reload
	*
	* @return json | array
	*/
	public function getAll($isJSON = false, $reload = false)
	{
		if ($reload) {
			$this->init();
		}

		if ($isJSON) {
        	return Json::encode($this->meta);
        }
		return $this->meta;
	}



	/**
    * Get Metadata only without saving it to the a file and database sync
	*
	* @param $isJSON
	* @param bool $reload
	*
	* @return json | array
	*/
	public function getMetadataOnly($isJSON = true, $reload = false)
	{
		$data = false;
		if (!file_exists($this->cacheFile) || $reload) {
        	$data = $this->getUnifier()->unify($this->name, $this->paths, true);

			if ($data === false) {
            	$GLOBALS['log']->emergency('Metadata:getMetadata() - metadata unite file cannot be created');
			}
		}
        else if (file_exists($this->cacheFile)) {
			$data = $this->getFileManager()->getContents($this->cacheFile);
		}

		if ($isJSON) {
        	$data = Json::encode($data);
        }

		return $data;
	}



	/**
	* Set Metadata data
	* Ex. $type= menu, $scope= Account then will be created a file metadataFolder/menu/Account.json
    *
	* @param JSON string $data
	* @param string $type - ex. menu
	* @param string $scope - Account
	*
	* @return bool
	*/
	public function set($data, $type, $scope)
	{
		$path = $this->paths['corePath'];
		$moduleName = $this->getScopeModuleName($scope);

		if ($moduleName !== false) {
        	$path = str_replace('{*}', $moduleName, $this->paths['modulePath']);
		}	

		$result= $this->getFileManager()->putContents(array($path, $type, $scope.'.json'), $data);

        return $result;
	}


	public function getOrmMetadata()
	{
		if (!empty($this->ormMeta)) {
			return $this->ormMeta;
		}

		if (!file_exists($this->ormCacheFile) || !$this->getConfig()->get('useCache')) {
        	$this->getConverter()->process();
		}

		$this->ormMeta = $this->getFileManager()->getContents($this->ormCacheFile);

        return $this->ormMeta;
	}

	public function setOrmMetadata(array $ormMeta)
	{
		$result = $this->getFileManager()->putContentsPHP($this->ormCacheFile, $ormMeta);
		if ($result == false) {		 
         	throw new \Espo\Core\Exceptions\Error('Metadata::setOrmMetadata() - Cannot save ormMetadata to a file');
		}

		$this->ormMeta = $ormMeta;

		return $result;
	}


    /**
     * Get Entity path, ex. Espo.Entities.Account or Modules\Crm\Entities\MyModule
     *
	 * @param string $entityName
	 * @param bool $delim - delimiter
	 *
	 * @return string
	 */
	public function getEntityPath($entityName, $delim = '\\')
	{
		$path = $this->getScopePath($entityName, $delim);

		return implode($delim, array($path, 'Entities', Util::normilizeClassName(ucfirst($entityName))));
	}
	
	public function getRepositoryPath($entityName, $delim = '\\')
	{
		$path = $this->getScopePath($entityName, $delim);

		return implode($delim, array($path, 'Repositories', Util::normilizeClassName(ucfirst($entityName))));
	}


	/**
     * Get Scopes
	 *
	 * @return array
	 */
	public function getScopes()
	{
    	if (!empty($this->scopes)) {
    		return $this->scopes;
    	}

		$metadata = $this->getMetadataOnly(false);

        $scopes = array();
		foreach ($metadata['scopes'] as $name => $details) {
        	$scopes[$name] = isset($details['module']) ? $details['module'] : false;
		}

		return $this->scopes = $scopes;
	}
	
	/**
	 * Get Module List
	 * 
	 * @return array
	 */
	public function getModuleList()
	{
		if (is_null($this->moduleList)) {
			$this->moduleList = array();
			$scopes = $this->getScopes();

			// TODO order
			foreach ($scopes as $moduleName) {
				if (!empty($moduleName)) {
					if (!in_array($moduleName, $this->moduleList)) {
						$this->moduleList[] = $moduleName;
					}
				}
			}
		}
		return $this->moduleList;
	}


	/**
     * Get module name if it's a custom module or empty string for core entity
	 *
	 * @param string $scopeName
	 *
	 * @return string
	 */
	public function getScopeModuleName($scopeName)
	{
    	return $this->get('scopes.' . $scopeName . '.module', false);
	}


	/**
     * Get Scope path, ex. "Modules/Crm" for Account
     *
	 * @param string $scopeName
	 * @param string $delim - delimiter
	 *
	 * @return string
	 */
	public function getScopePath($scopeName, $delim = '/')
	{
    	$moduleName = $this->getScopeModuleName($scopeName);

    	$path = ($moduleName !== false) ? 'Espo/Modules/'.$moduleName : 'Espo';

		if ($delim != '/') {
           $path = str_replace('/', $delim, $path);
		}

		return $path;
	}

	/**
      * Check if scope exists
	  *
	  * @param string $scopeName
	  *
	  * @return bool
	  */
	public function isScopeExists($scopeName)
	{
    	$scopeModuleMap= $this->getScopes();

		$lowerEntityName= strtolower($scopeName);
		foreach($scopeModuleMap as $rowEntityName => $rowModuleName) {
			if ($lowerEntityName == strtolower($rowEntityName)) {
				return true;
			}
		}

		return false;
	}

}



