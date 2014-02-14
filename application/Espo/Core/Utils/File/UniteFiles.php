<?php

namespace Espo\Core\Utils\File;

use Espo\Core\Utils;

class UniteFiles
{
	private $fileManager;
	private $params;

	public function __construct(\Espo\Core\Utils\File\Manager $fileManager, array $params)
	{
    	$this->fileManager = $fileManager;
    	$this->params = $params;
	}


	protected function getFileManager()
	{
		return $this->fileManager;
	}

	protected function getParams()
	{
		return $this->params;
	}




	/**
    * Unite file content to the file
	*
	* @param string $configParams - array('name', 'cachePath', 'corePath', 'customPath')
	* @param bool $recursively - Note: only for first level of sub directory, other levels of sub directories will be ignored
	*
	* @return array
	*/
	public function uniteFiles($configParams, $recursively=false)
	{
		//EXAMPLE OF IMPLEMENTATION IN METADATA CLASS
		/*if (empty($configParams) || empty($configParams['name']) || empty($configParams['cachePath']) || empty($configParams['corePath'])) {
			return false;
		}

		//merge matadata files
	   	$content= $this->uniteFilesSingle($configParams['corePath'], $configParams['name'], $recursively);

		if (!empty($configParams['customPath'])) {
			$customDir= strstr($configParams['customPath'], '{*}', true);
        	$dirList= $this->getFileList($customDir, false, '', 'dir');

			foreach($dirList as $dirName) {
				$curPath= str_replace('{*}', $dirName, $configParams['customPath']);
                //$content= array_merge($content, $this->uniteFilesSingle($curPath, $recursively));
                $content= Utils\Util::merge($content, $this->uniteFilesSingle($curPath, $configParams['name'], $recursively));
			}
		}
        //END: merge matadata files

		//save medatada to cache files
		$jsonData= $this->getObject('JSON')->encode($content);

		$cacheFile= Utils\Util::concatPath($configParams['cachePath'], $configParams['name']);
        $result= $this->setContent($jsonData, $cacheFile.'.json');
        $result&= $this->setContent($this->getFileManager()->getPHPFormat($content), $cacheFile.'.php');
		//END: save medatada to cache files

		return $result; */
	}

    /**
    * Unite file content to the file for one directory [NOW ONLY FOR METADATA, NEED TO CHECK FOR LAYOUTS AND OTHERS]
	*
	* @param string $dirPath
	* @param string $type - name of type array("metadata", "layouts"), ex. metadataConfig['name']
	* @param bool $recursively - Note: only for first level of sub directory, other levels of sub directories will be ignored
	* @param string $moduleName - name of module if exists
	*
	* @return string - content of the files
	*/
	public function uniteFilesSingle($dirPath, $type, $recursively=false, $moduleName= '')
	{
		if (empty($dirPath) || !file_exists($dirPath)) {
			return false;
		}
		$params = $this->getParams();
        $unsetFileName = $params['unsetFileName']; 
        //$unsetFileName = $this->getConfig('unsetFileName');

		//get matadata files
		$fileList = $this->getFileManager()->getFileList($dirPath, $recursively, '\.json$');

		//print_r($fileList);
		//echo '<hr />';

		$defaultValues = $this->loadDefaultValues($this->getFileManager()->getDirName($dirPath), $type);

		$content= array();
		$unsets= array();
		foreach($fileList as $dirName => $fileName) {

			if (is_array($fileName)) {  /*get content from files in a sub directory*/
                $content[$dirName]= $this->uniteFilesSingle(Utils\Util::concatPath($dirPath,$dirName), $type, false, $moduleName); //only first level of a sub directory

			} else { /*get content from a single file*/
				if ($fileName == $unsetFileName) {
					$fileContent = $this->getFileManager()->getContent($dirPath, $fileName);
					$unsets = Utils\Json::getArrayData($fileContent);
					continue;
				} /*END: Save data from unset.json*/

				$mergedValues = $this->uniteFilesGetContent($dirPath, $fileName, $defaultValues);

				if (!empty($mergedValues)) {
                   	$name = $this->getFileManager()->getFileName($fileName, '.json');
					$content[$name] = $mergedValues;
				}
			}
		}

		//unset content
        $content= Utils\Util::unsetInArray($content, $unsets);
		//END: unset content

		/*print_r($content);
		print_r($unsets);
		echo '<hr />'; */

		return $content;
	}

    /**
    * Helpful method for get content from files for unite Files
	*
	* @param string $folderPath string - Folder path, Ex. myfolder
	* @param bool $filePath - File path, Ex. file.json
	* @param string | array() $defaults - It can be a string like ["metadata","layouts"] OR an array with default values
	*
	* @return array
	*/
	public function uniteFilesGetContent($folderPath, $fileName, $defaults)
	{
		$fileContent= $this->getFileManager()->getContent($folderPath, $fileName);
		$decoded= Utils\Json::getArrayData($fileContent);

		if (empty($decoded) && !is_array($decoded)) {
        	$GLOBALS['log']->add('FATAL EXCEPTION', 'Syntax error or empty file - '.Utils\Util::concatPath($folderPath, $fileName));
		}
		else {
            //Default values
            if (is_string($defaults) && !empty($defaults)) {
            	$defType= $defaults;
				unset($defaults);
            	$name= $this->getFileManager()->getFileName($fileName, '.json');

				$defaults= $this->loadDefaultValues($name, $defType);
			}
            $mergedValues= Utils\Util::merge($defaults, $decoded);
            //END: Default values

           	return $mergedValues;
		}

		return array();
	}

	/**
    * Load default values for selected type [metadata, layouts]
	*
	* @param string $name
	* @param string $type - [metadata, layouts]
	*
	* @return array
	*/
	function loadDefaultValues($name, $type='metadata')
	{
		$params = $this->getParams();
        $defaultPath= $params['defaultsPath'];
        //$defaultPath= $this->getConfig('defaultsPath');

		$defaultValue= $this->getFileManager()->getContent( Utils\Util::concatPath($defaultPath, $type), $name.'.json');
		if ($defaultValue!==false) {
        	//return default array
			return Utils\Json::decode($defaultValue, true);
		}

        return array();
	}

}

?>