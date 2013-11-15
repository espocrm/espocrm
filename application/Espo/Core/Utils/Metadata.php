<?php

namespace Espo\Core\Utils;

use Doctrine\ORM\Tools;

class Metadata
{

	protected $metadataConfig;
	protected $doctrineMetadataName= 'defs'; //Metadata "defs" uses for creating the metadata of Doctri

	protected $scopes= array();

	private $entityManager;
	private $config;
	private $doctrineConverter;
	private $uniteFiles;
	private $fileManager;

	public function __construct(\Doctrine\ORM\EntityManager $entityManager, \Espo\Core\Utils\Config $config, \Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\Utils\File\UniteFiles $uniteFiles)
	{
		$this->entityManager = $entityManager;
		$this->config = $config;
		$this->uniteFiles = $uniteFiles;
		$this->fileManager = $fileManager;
		$this->doctrineConverter = new \Espo\Core\Doctrine\EspoConverter($this);       //TODO
	}


	protected function getEntityManager()
	{
		return $this->entityManager;
	}

	protected function getConfig()
	{
		return $this->config;
	}

	protected function getDoctrineConverter()
	{
		return $this->doctrineConverter;
	}

	protected function getUniteFiles()
	{
		return $this->uniteFiles;
	}

    protected function getFileManager()
	{
		return $this->fileManager;
	}


	/**
    * Get Metadata context
	*
	* @param $isJSON
	* @param bool $reload
	*
	* @return json | array
	*/
	//HERE --- ADD CREATING DOCTRINE METADATA
	public function get($isJSON=true, $reload=false)
	{
		$config= $this->getMetaConfig();

		if (!$this->getConfig()->get('useCache')) {
           	$reload = true;
		}

		if (!file_exists($config->cacheFile) || $reload) {
        	$data= $this->getMetadataOnly(false, true);
			if ($data === false) {
				return false;
			}

			//save medatada to cache files
	        $this->getFileManager()->setContentPHP($data, $this->getMetaConfig()->cacheFile);

			$GLOBALS['log']->add('Debug', 'Metadata:get() - converting to doctrine metadata');
            if ($this->convertToDoctrine($data)) {
            	$GLOBALS['log']->add('Debug', 'Metadata:get() - database rebuild');

				try{
	        		$this->rebuildDatabase();
			   	} catch (\Exception $e) {
				  	$GLOBALS['log']->add('EXCEPTION', 'Try to rebuildDatabase'.'. Details: '.$e->getMessage());
				}
            }
		}

		return $this->getMetadataOnly($isJSON, false);
	}



	/**
    * Get Metadata only without saving it to the a file and database sync
	*
	* @param $isJSON
	* @param bool $reload
	*
	* @return json | array
	*/

	public function getMetadataOnly($isJSON=true, $reload=false)
	{
		$config= $this->getMetaConfig();

		if (!$this->getConfig()->get('useCache')) {
        	$reload = true;
		}

		$data = false;
		if (!file_exists($config->cacheFile) || $reload) {
        	$data= $this->uniteFiles($config, true);

			if ($data === false) {
            	$GLOBALS['log']->add('FATAL', 'Metadata:getMetadata() - metadata unite file cannot be created');
			}
		}
        else if (file_exists($config->cacheFile)) {
			$data= $this->getFileManager()->getContent($config->cacheFile);
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
		$fullPath= $this->getMetaConfig()->corePath;
		$moduleName= $this->getScopeModuleName($scope);

		if (!empty($moduleName)) {
        	$fullPath= str_replace('{*}', $moduleName, $this->getMetaConfig()->customPath);
		}
		$fullPath= Util::concatPath($fullPath, $type);

        //merge data with defaults values
        $defaults= $this->getUniteFiles()->loadDefaultValues($type, 'metadata');

        $decoded= Json::getArrayData($data);
        $mergedValues= Util::merge($defaults, $decoded);
		$data= Json::encode($mergedValues);
        //END: merge data with defaults values

		$result= $this->getFileManager()->setContent($data, $fullPath, $scope.'.json');

		//create classes only for "defs" metadata
		if ($type == $this->doctrineMetadataName) {
        	try{
	        	$this->generateEntities( array($this->getEntityPath($scope)) );
		   	} catch (\Exception $e) {
			 	$GLOBALS['log']->add('EXCEPTION', 'Try to generate Entities for '.$this->getEntityPath($scope).'. Details: '.$e->getMessage());
			}
		}

        return $result;
	}


    /**
	* Metadata conversion from Espo format into Doctrine
    *
	* @param object $metadata
	*
	* @return bool
	*/
	protected function convertToDoctrine($metadata)
	{
		$cacheDir= $this->getMetaConfig()->doctrineCache;

		//remove all existing files
		$this->getFileManager()->removeFilesInDir($cacheDir);

		//create files named like "Espo.Entities.User.php"
		$result= true;
        foreach($metadata[$this->doctrineMetadataName] as $entityName => $meta) {
	        $doctrineMetaWithName= $this->getDoctrineConverter()->convert($entityName, $meta, true);

            if (empty($doctrineMetaWithName)) {
	        	$GLOBALS['log']->add('FATAL', 'Metadata:convertToDoctrine(), Entity:'.$entityName.' - metadata cannot be converted into Doctrine format');
				return false;
			}

			//create a doctrine metadata file
			$fileName= str_replace('\\', '.', $doctrineMetaWithName['name']).'.php';
            $result&= $this->getFileManager()->setContent($this->getFileManager()->getPHPFormat($doctrineMetaWithName['meta']), $cacheDir, $fileName);
			//END: create a doctrine metadata file
        } 

        return $result;
	}

	/**
	* Rebuild a database accordinly to metadata
    *
	* @return bool
	*/
	public function rebuildDatabase()
	{
		$tool = new \Doctrine\ORM\Tools\SchemaTool($this->getEntityManager());

        $cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
	    $cmf->setEntityManager($this->getEntityManager()); // $em is EntityManager instance
	    $classes = $cmf->getAllMetadata();

		$tool->updateSchema($classes);

		return true;  //always true, because updateSchema just returns the VOID
	}

	/**
	* Rebuild a database accordinly to metadata
    *
	* @return bool
	*/
	public function generateEntities($classNames)
	{
    	if (!is_array($classNames)) {
    		$classNames= (array) $classNames;
    	}

		$cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
	    $cmf->setEntityManager($this->getEntityManager()); // $em is EntityManager instance

		$metadata= array();
		foreach($classNames as $className) {
        	$metadata[]=  $cmf->getMetadataFor($className);
		}

		if (!empty($metadata)) {
			$generator = new \Doctrine\ORM\Tools\EntityGenerator();
		    $generator->setGenerateAnnotations(false);
		    $generator->setGenerateStubMethods(true);
		    $generator->setRegenerateEntityIfExists(false);
		    $generator->setUpdateEntityIfExists(false);
		    $generator->generate($metadata, 'application');

			return true; //always true, because generate just returns the VOID
		}

		return false;
	}

	/**
    * Unite file content to the file
	*
	* @param string $configParams - ["name", "cachePath", "corePath", "customPath"]
	* @param bool $recursively - Note: only for first level of sub directory, other levels of sub directories will be ignored
	*
	* @return array
	*/
	function uniteFiles($configParams, $recursively=false)
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
	public function getEntityPath($entityName, $delim= '\\')
	{
		$path = $this->getScopePath($entityName, $delim);

		return implode($delim, array($path, 'Entities', ucfirst($entityName)));
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
	public function getScopes($moduleName= '', $reload = false)
	{
    	if (!$reload && !empty($this->scopes)) {
    		return $this->scopes;
    	}

		$metadata = $this->getMetadataOnly(false);

        $scopes = array();
		foreach($metadata['scopes'] as $name => $details) {
        	$scopes[$name] = isset($details['module']) ? $details['module'] : '';
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
    	$scopeModuleMap= $this->getScopes();

		$lowerEntityName= strtolower($scopeName);
		foreach($scopeModuleMap as $rowEntityName => $rowModuleName) {
			if ($lowerEntityName==strtolower($rowEntityName)) {
				return $rowModuleName;
			}
		}

		return '';
	}


	/**
    * Get Scope path, ex. "Modules/Crm" for Account
    *
	* @param string $scopeName
	* @param string $delim - delimiter
	*
	* @return string
	*/
	public function getScopePath($scopeName, $delim= '/')
	{
    	$moduleName= $this->getScopeModuleName($scopeName);

    	$path= $this->getConfig()->get('espoPath');
		if (!empty($moduleName)) {
			$path= str_replace('{*}', $moduleName, $this->getConfig()->get('espoModulePath'));
		}

		if ($delim!='/') {
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
	public function getScopePathFull($scopeName, $delim= '/')
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
			if ($lowerEntityName==strtolower($rowEntityName)) {
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
	protected function getMetaConfig()
	{
		if (isset($this->metadataConfig) && is_object($this->metadataConfig)) {
    		return $this->metadataConfig;
    	}

		$this->metadataConfig = $this->getConfig()->get('metadataConfig');
		$this->metadataConfig->cacheFile= Util::concatPath($this->metadataConfig->cachePath, $this->metadataConfig->name).'.php';

		return $this->metadataConfig;
	}

}


?>