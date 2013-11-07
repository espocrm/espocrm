<?php

namespace Espo\Utils;

use Espo\Utils as Utils,
	Espo\Core,
	Doctrine\ORM\Tools;

class Metadata extends FileManager
{

	protected $metadataConfig;
	protected $doctrineMetadataName= 'defs'; //Metadata "defs" uses for creating the metadata of Doctri


	/**
    * Get Metadata context
	*
	* @param $isJSON
	* @param bool $reload
	*
	* @return json | array
	*/
	//HERE --- ADD CREATING DOCTRINE METADATA
	public function getMetadata($isJSON=true, $reload=false)
	{
		$config= $this->getConfig();

		if (!$this->getObject('Configurator')->get('useCache')) {
           	$reload = true;
		}

		if (!file_exists($config->cacheFile) || $reload) {
        	$data= $this->getMetadataOnly(false, true);
			if ($data === false) {
				return false;
			}

			//save medatada to cache files
	        $this->setContentPHP($data, $this->getConfig()->cacheFile);

			$this->getObject('Log')->add('Debug', 'Metadata:getMetadata() - converting to doctrine metadata');
            if ($this->convertToDoctrine($data)) {
            	$this->getObject('Log')->add('Debug', 'Metadata:getMetadata() - database rebuild');

				try{
	        		$this->rebuildDatabase();
			   	} catch (\Exception $e) {
				  	$this->getObject('Log')->add('EXCEPTION', 'Try to rebuildDatabase'.'. Details: '.$e->getMessage());
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
		$config= $this->getConfig();

		if (!$this->getObject('Configurator')->get('useCache')) {
        	$reload = true;
		}

		$data = false;
		if (!file_exists($config->cacheFile) || $reload) {
        	$data= $this->uniteFiles($config, true);

			if ($data === false) {
            	$this->getObject('Log')->add('FATAL', 'Metadata:getMetadata() - metadata unite file cannot be created');
			}
		}
        else if (file_exists($config->cacheFile)) {
			$data= $this->getContent($config->cacheFile);
		}

		if ($isJSON) {
        	$data= $this->getObject('JSON')->encode($data);
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
	function setMetadata($data, $type, $scope)
	{
		$fullPath= $this->getConfig()->corePath;
		$moduleName= $this->getScopeModuleName($scope);

		if (!empty($moduleName)) {
        	$fullPath= str_replace('{*}', $moduleName, $this->getConfig()->customPath);
		}
		$fullPath= $this->concatPath($fullPath, $type);

        //merge data with defaults values
        $defaults= $this->loadDefaultValues($type, 'metadata');

        $decoded= $this->getArrayData($data);
        $mergedValues= $this->merge($defaults, $decoded);
		$data= $this->getObject('JSON')->encode($mergedValues);
        //END: merge data with defaults values

		$result= $this->setContent($data, $fullPath, $scope.'.json');

		//create classes only for "defs" metadata
		if ($type==$this->doctrineMetadataName) {
        	try{
	        	$this->generateEntities( array($this->getEntityPath($scope)) );
		   	} catch (\Exception $e) {
			 	$this->getObject('Log')->add('EXCEPTION', 'Try to generate Entities for '.$this->getEntityPath($scope).'. Details: '.$e->getMessage());
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
		$cacheDir= $this->getConfig()->doctrineCache;

		//remove all existing files
		$this->removeFilesInDir($cacheDir);

		//create files named like "Espo.Entities.User.php"
		$result= true;
        foreach($metadata[$this->doctrineMetadataName] as $entityName => $meta) {
	        $doctrineMetaWithName= $this->getObject('DoctrineConverter')->convert($entityName, $meta, true);

            if (empty($doctrineMetaWithName)) {
	        	$this->getObject('Log')->add('FATAL', 'Metadata:convertToDoctrine(), Entity:'.$entityName.' - metadata cannot be converted into Doctrine format');
				return false;
			}

			//create a doctrine metadata file
			$fileName= str_replace('\\', '.', $doctrineMetaWithName['name']).'.php';
            $result&= $this->setContent($this->getPHPFormat($doctrineMetaWithName['meta']), $cacheDir, $fileName);
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
    	global $base;
		if (!is_object($base)) {
			$base= \Espo\Core\Base::start();
		}

		$tool = new \Doctrine\ORM\Tools\SchemaTool($base->em);

        $cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
	    $cmf->setEntityManager($base->em); // $em is EntityManager instance
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

    	global $base;
		if (!is_object($base)) {
			$base= \Espo\Core\Base::start();
		}

		$cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
	    $cmf->setEntityManager($base->em); // $em is EntityManager instance

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
	   	$content= $this->uniteFilesSingle($configParams->corePath, $configParams->name, $recursively);

		if (!empty($configParams->customPath)) {
			$customDir= strstr($configParams->customPath, '{*}', true);
        	$dirList= $this->getFileList($customDir, false, '', 'dir');

			foreach($dirList as $dirName) {
				$curPath= str_replace('{*}', $dirName, $configParams->customPath);
                $content= $this->merge($content, $this->uniteFilesSingle($curPath, $configParams->name, $recursively, $dirName));
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
    	$moduleName= $this->getScopeModuleName($entityName);

    	$path= 'Espo';
		if (!empty($moduleName)) {
            $path= 'Modules'.$delim.$moduleName;
		}

		return implode($delim, array($path, 'Entities', $entityName));
	}


	/**
    * Get settings for Metadata
	*
	* @return object
	*/
	function getConfig()
	{
		if (isset($this->metadataConfig) && is_object($this->metadataConfig)) {
    		return $this->metadataConfig;
    	}

		$this->metadataConfig = $this->getObject('Configurator')->get('metadataConfig');
		$this->metadataConfig->cacheFile= $this->concatPath($this->metadataConfig->cachePath, $this->metadataConfig->name).'.php';

		return $this->metadataConfig;
	}

}


?>