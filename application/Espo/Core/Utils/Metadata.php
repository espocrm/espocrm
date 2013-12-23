<?php

namespace Espo\Core\Utils;

class Metadata
{

	protected $metadataConfig;
	protected $meta;

	private $espoMetadata;

	protected $scopes= array();

	private $config;
	private $uniteFiles;
	private $fileManager;
	private $converter;

	public function __construct(\Espo\Core\Utils\Config $config, \Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\Utils\File\UniteFiles $uniteFiles)
	{
		$this->config = $config;
		$this->uniteFiles = $uniteFiles;
		$this->fileManager = $fileManager;

		$this->converter = new \Espo\Core\Utils\Database\Converter($this);
	}

	protected function getConfig()
	{
		return $this->config;
	}


	protected function getUniteFiles()
	{
		return $this->uniteFiles;
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

		if (file_exists($this->getMetaConfig()->metadataCacheFile)) {
			return true;
		}

		return false;
	}


    public function init($reload = false)
	{
       	$data= $this->getMetadataOnly(false, $reload);
		if ($data === false) {
			$GLOBALS['log']->add('FATAL', 'Metadata:init() - metadata has not been created');
		}

		$this->meta = $data;

		if ($reload) {
        	//save medatada to a cache file
	        $isSaved = $this->getFileManager()->setContentPHP($data, $this->getMetaConfig()->metadataCacheFile);
			if ($isSaved === false) {
	        	$GLOBALS['log']->add('FATAL', 'Metadata:init() - metadata has not been saved to a cache file');
			}
		}
	}


	/**
    * Get Metadata
	*
	* @param string $key
	* @param mixed $return
	*
	* @return array
	*/
	public function get($key = '', $returns = null)
	{
        if (empty($key)) {
        	return $this->meta;
        }

		$keys = explode('.', $key);

		$lastMeta = $this->meta;
		foreach($keys as $keyName) {
        	if (isset($lastMeta[$keyName]) && is_array($lastMeta)) {
            	$lastMeta = $lastMeta[$keyName];
        	} else {
        		return $returns;
        	}
		}

		return $lastMeta;
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
		$config= $this->getMetaConfig();

		$data = false;
		if (!file_exists($config->metadataCacheFile) || $reload) {
        	$data= $this->uniteFiles($config, true);

			if ($data === false) {
            	$GLOBALS['log']->add('FATAL', 'Metadata:getMetadata() - metadata unite file cannot be created');
			}
		}
        else if (file_exists($config->metadataCacheFile)) {
			$data= $this->getFileManager()->getContent($config->metadataCacheFile);
		}

		if ($isJSON) {
        	$data= Json::encode($data);
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
		$fullPath = $this->getMetaConfig()->corePath;
		$moduleName = $this->getScopeModuleName($scope);

		if ($moduleName !== false) {
        	$fullPath = str_replace('{*}', $moduleName, $this->getMetaConfig()->customPath);
		}
		$fullPath = Util::concatPath($fullPath, $type);

        //merge data with defaults values
        $defaults = $this->getUniteFiles()->loadDefaultValues($type, 'metadata');

        $decoded = Json::getArrayData($data);
        $this->meta = Util::merge($defaults, $decoded);
		$data= Json::encode($this->meta);
        //END: merge data with defaults values

		$result= $this->getFileManager()->setContent($data, $fullPath, $scope.'.json');

        return $result;
	}


	public function getOrmMetadata()
	{
		if (!empty($this->espoMetadata)) {
			return $this->espoMetadata;
		}

		$espoMetadataFile = Util::concatPath($this->getMetaConfig()->cachePath, 'ormMetadata.php');

		if (!file_exists($espoMetadataFile) || !$this->getConfig()->get('useCache')) {
        	$this->getConverter()->process();
		}

		$this->espoMetadata = $this->getFileManager()->getContent($espoMetadataFile);

        return $this->espoMetadata;
	}

	public function setOrmMetadata(array $espoMetadata)
	{
		 $result = $this->getFileManager()->setContentPHP($espoMetadata, $this->getMetaConfig()->cachePath, 'ormMetadata.php');
		 if ($result == false) {
		 	$GLOBALS['log']->add('EXCEPTION', 'Metadata::setOrmMetadata() - Cannot save ormMetadata to a file');
         	throw new \Espo\Core\Exceptions\Error();
		 }

		 $this->espoMetadata = $espoMetadata;

		 return $result;
	}


	/**
    * Unite file content to the file
	*
	* @param string $configParams - ["name", "cachePath", "corePath", "customPath"]
	* @param bool $recursively - Note: only for first level of sub directory, other levels of sub directories will be ignored
	*
	* @return array
	*/
	function uniteFiles($configParams, $recursively = false)
	{
		if (empty($configParams) || empty($configParams->name) || empty($configParams->cachePath) || empty($configParams->corePath)) {
			return false;
		}

		//merge matadata files
	   	$content= $this->getUniteFiles()->uniteFilesSingle($configParams->corePath, $configParams->name, $recursively);

		if (!empty($configParams->customPath)) {
			$customDir= strstr($configParams->customPath, '{*}', true);
        	$dirList= $this->getFileManager()->getFileList($customDir, false, '', 'dir');

			foreach($dirList as $dirName) {
				$curPath= str_replace('{*}', $dirName, $configParams->customPath);
                $content= Util::merge($content, $this->getUniteFiles()->uniteFilesSingle($curPath, $configParams->name, $recursively, $dirName));
			}
		}
        //END: merge matadata files

		return $content;
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
	* @param string $moduleName
	* @param bool $reload
	*
	* @return array
	*/
	//NEED TO CHANGE
	public function getScopes($moduleName = '', $reload = false)
	{
    	if (!$reload && !empty($this->scopes)) {
    		return $this->scopes;
    	}

		$metadata = $this->getMetadataOnly(false);

        $scopes = array();
		foreach($metadata['scopes'] as $name => $details) {
        	$scopes[$name] = isset($details['module']) ? $details['module'] : false;
		}

		return $this->scopes = $scopes;
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
    	$scopeModuleMap = $this->getScopes();

		$lowerEntityName = strtolower($scopeName);
		foreach ($scopeModuleMap as $rowEntityName => $rowModuleName) {
			if ($lowerEntityName == strtolower($rowEntityName)) {
				return $rowModuleName;
			}
		}

		return false;
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

    	$path = $this->getConfig()->get('espoPath');
		if ($moduleName !== false) {
			$path = str_replace('{*}', $moduleName, $this->getConfig()->get('espoModulePath'));
		}

		if ($delim != '/') {
           $path = str_replace('/', $delim, $path);
		}

		return $path;
	}

	/**
    * Get Full Scope path, ex. "application/Modules/Crm" for Account
    *
	* @param string $scopeName
	* @param string $delim - delimiter
	*
	* @return string
	*/
	public function getScopePathFull($scopeName, $delim = '/')
	{
		return Util::concatPath('application', $this->getScopePath($scopeName, $delim));
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

	/**
    * Get settings for Metadata
	*
	* @return object
	*/
	public function getMetaConfig()
	{
		if (isset($this->metadataConfig) && is_object($this->metadataConfig)) {
    		return $this->metadataConfig;
    	}

		$this->metadataConfig = $this->getConfig()->get('metadataConfig');
		$this->metadataConfig->metadataCacheFile= Util::concatPath($this->metadataConfig->cachePath, $this->metadataConfig->name).'.php';

		return $this->metadataConfig;
	}

}



