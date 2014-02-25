<?php

namespace Espo\Core\Utils\File;

use Espo\Core\Utils;

class Manager
{
	private $permission;

	public function __construct(array $params = null)
	{
		$this->permission = new Permission($params);
	}

	public function getPermissionUtils()
	{
		return $this->permission;
	}



	/**
     * Get a list of files in specified directory
	 *
	 * @param string $path string - Folder path, Ex. myfolder
	 * @param bool | int $recursively - Find files in subfolders
	 * @param string $filter - Filter for files. Use regular expression, Ex. \.json$
	 * @param string $fileType [all, file, dir] - Filter for type of files/directories.
	 * @param bool $isReturnSingleArray - if need to return a single array of file list
	 *
	 * @return array
	 */
	public function getFileList($path, $recursively=false, $filter='', $fileType='all', $isReturnSingleArray = false)
	{
    	if (!file_exists($path)) {
            return false;
    	}

		$result = array();

		$cdir = scandir($path);
		foreach ($cdir as $key => $value)
		{
			if (!in_array($value,array(".","..")))
			{
				$add= false;
				if (is_dir($path . Utils\Util::getSeparator() . $value)) {
					if ($recursively || (is_int($recursively) && $recursively!=0) ) {
						$nextRecursively = is_int($recursively) ? ($recursively-1) : $recursively;
						$result[$value] = $this->getFileList($path.Utils\Util::getSeparator().$value, $nextRecursively, $filter, $fileType);
					}
					else if (in_array($fileType, array('all', 'dir'))){
						$add= true;
					}
				}
				else if (in_array($fileType, array('all', 'file'))) {
					$add= true;
				}

				if ($add) {
					if (!empty($filter)) {
						if (preg_match('/'.$filter.'/i', $value)) {
                           	$result[] = $value;
						}
					}
					else {
                       	$result[] = $value;
					}
				}

			}
		}

		if ($isReturnSingleArray) {
			return $this->getSingeFileList($result);
		}

		return $result;
	}

	/**
     * Convert file list to a single array
	 *
	 * @param aray $fileList
	 * @param string $parentDirName 
	 *
	 * @return aray
	 */
	protected function getSingeFileList(array $fileList, $parentDirName = '')
	{
		$singleFileList = array();
    	foreach($fileList as $dirName => $fileName) {		
		
        	if (is_array($fileName)) {		
			$currentDir = Utils\Util::concatPath($parentDirName, $dirName);
            		$singleFileList = array_merge($singleFileList, $this->getSingeFileList($fileName, $currentDir));
        	} else {
            	$singleFileList[] = Utils\Util::concatPath($parentDirName, $fileName);
        	}
    	}

		return $singleFileList;
	}

	/**
	 * Reads entire file into a string
	 * 
	 * @param  string | array  $paths  Ex. 'path.php' OR array('dir', 'path.php')
	 * @param  boolean $useIncludePath 
	 * @param  resource  $context          
	 * @param  integer $offset           	        
	 * @param  integer $maxlen           	        
	 * @return mixed                    
	 */
	public function getContents($paths, $useIncludePath = false, $context = null, $offset = -1, $maxlen = null)
	{
		$fullPath = $this->concatPaths($paths);

		if (file_exists($fullPath)) {

			if (strtolower(substr($fullPath, -4))=='.php') {
				return include($fullPath);
			} else {
				if (isset($maxlen)) {
					return file_get_contents($fullPath, $useIncludePath, $context, $offset, $maxlen);
				} else {
					return file_get_contents($fullPath, $useIncludePath, $context, $offset);	
				}            	
			}

		}

		return false;		
	}


	/**
	 * Write data to a file
	 * 
	 * @param  string | array  $paths   
	 * @param  mixed  $data    
	 * @param  integer $flags   
	 * @param  resource  $context 
	 * 
	 * @return bool           
	 */
	public function putContents($paths, $data, $flags = 0, $context = null)
	{
		$fullPath = $this->concatPaths($paths); //todo remove after changing the params

		if ($this->checkCreateFile($fullPath) === false) {
			return false;
		}

        return (file_put_contents($fullPath, $data, $flags, $context) !== FALSE);		
	}

	/**
     * Save PHP content to file
	 *
	 * @param string | array $paths
	 * @param string $data	 
	 *
	 * @return bool
	 */
	public function putContentsPHP($paths, $data)
	{
		return $this->putContents($paths, $this->getPHPFormat($data));
	}

	/**
     * Save JSON content to file
	 *
	 * @param string | array $paths
	 * @param string $data
	 *
	 * @return bool
	 */
	public function putContentsJSON($paths, $data)
	{
		if (!Utils\Json::isJSON($data)) {
        	$data= Utils\Json::encode($data);
        }

		return $this->putContents($paths, $data);
	}

    /**
     * Merge file content and save it to a file
	 *
	 * @param string | array $paths
	 * @param string $content JSON string
	 * @param bool $isJSON
	 *
	 * @return bool
	 */
	public function mergeContents($paths, $content, $isJSON = false)
	{
		$fileContent= $this->getContents($paths);

		$savedDataArray= $this->getArrayData($fileContent);
		$newDataArray= $this->getArrayData($content);

        $data= Utils\Util::merge($savedDataArray, $newDataArray);
		if ($isJSON) {
	        $data= Utils\Json::encode($data);
		}

        return $this->putContents($paths, $data);
	}

	/**
     * Merge PHP content and save it to a file
	 *
	 * @param string | array $paths
	 * @param string $content
	 * @param bool $onlyFirstLevel - Merge only first level. Ex. current: array('test'=>array('item1', 'item2')).  $content= array('test'=>array('item1'),). Result will be array('test'=>array('item1')).
	 *
	 * @return bool
	 */
	public function mergeContentsPHP($paths, $content, $onlyFirstLevel= false)
	{
        $fileContent= $this->getContents($paths);

		$savedDataArray= $this->getArrayData($fileContent);
		$newDataArray= $this->getArrayData($content);

		if ($onlyFirstLevel) {
        	foreach($newDataArray as $key => $val) {
				$setVal= is_array($val) ? array() : '';
	        	$savedDataArray[$key]= $setVal;
			}
		}

        $data= Utils\Util::merge($savedDataArray, $newDataArray);

        return $this->putContentsPHP($paths, $data);
	}

	/**
     * Append the content to the end of the file
	 *
	 * @param string | array $paths
	 * @param mixed $data
	 *
	 * @return bool
	 */
	public function appendContents($paths, $data)
	{
		return $this->putContents($paths, $data, FILE_APPEND | LOCK_EX);		
	}


	/**
	 * Concat paths
	 * @param  string | array  $paths Ex. array('pathPart1', 'pathPart2', 'pathPart3')
	 * @return string
	 */
	protected function concatPaths($paths)
	{
		if (is_string($paths)) {
			return $paths;
		}

		$fullPath = '';
		foreach ($paths as $path) {
			$fullPath = Utils\Util::concatPath($fullPath, $path);
		}

		return $fullPath;
	}


	/**
     * Create a new file if not exists with all folders in the path.
	 *
	 * @param string $filePath
	 * @return string
	 */
	protected function checkCreateFile($filePath)
	{
		$defaultPermissions = $this->getPermissionUtils()->getDefaultPermissions();

		if (file_exists($filePath)) {

			if (!in_array($this->getPermissionUtils()->getCurrentPermission($filePath), array($defaultPermissions['file'], $defaultPermissions['dir']))) {
            	return $this->getPermissionUtils()->setDefaultPermissions($filePath, true);
			}
			return true;
		}

		$pathParts= pathinfo($filePath);
		if (!file_exists($pathParts['dirname'])) {
            $dirPermission= $defaultPermissions['dir'];
            $dirPermission= is_string($dirPermission) ? base_convert($dirPermission,8,10) : $dirPermission;

			if (!mkdir($pathParts['dirname'], $dirPermission, true)) {
                $GLOBALS['log']->critical('Permission denied: unable to generate a folder on the server - '.$pathParts['dirname']);
                return false;
			}
		}

		if (touch($filePath)) {
        	return $this->getPermissionUtils()->setDefaultPermissions($filePath, true);
		}

		return false;
	}
	
	/**
     * Remove all files in defined directory
	 *
	 * @param array $filePaths - File paths list
	 * @param string $dirPath - directory path
	 * @return bool
	 */
	public function removeFiles($filePaths, $dirPath = null)
	{
		if (!is_array($filePaths)) {
			$filePaths = (array) $filePaths;
		}

		$result= true;
		foreach($filePaths as $filePath) {
			if (isset($dirPath)) {
            	$filePath= Utils\Util::concatPath($dirPath, $filePath);
			}

			if (file_exists($filePath) && is_file($filePath)) {
            	$result &= unlink($filePath);
			}
		}

		return $result;
	}

    /**
     * Remove all files in defined directory
	 *
	 * @param string $dirPath - directory path
	 * @param bool $removeWithDir - if remove with directory
	 * 
	 * @return bool
	 */
	public function removeInDir($dirPath, $removeWithDir = false)
	{
    	$fileList= $this->getFileList($dirPath, false);

    	$result = true;
    	foreach ($fileList as $file) { 
    		$fullPath = Utils\Util::concatPath($dirPath, $file);
    		if (is_dir($fullPath)) {
    			$result &= $this->removeInDir($fullPath, true);
    		} else {
    			$result &= unlink($fullPath);
    		}	        
	    }	

	    if ($removeWithDir) {
	    	rmdir($dirPath);
	    }	

		return $result;
	} 


    /**  //TODO remove
     * Get an array data (if JSON convert to array)
	 *
	 * @param mixed $data - can be JSON, array
	 *
	 * @return array
	 */
	protected function getArrayData($data)
	{
		if (is_array($data)) {
        	return $data;
		}
		else if (Utils\Json::isJSON($data)) {
        	return Utils\Json::decode($data, true);
        }

		return array();
	}


	/**
     * Get a filename without the file extension
	 *
	 * @param string $filename
	 * @param string $ext - extension, ex. '.json'
	 *
	 * @return array
	 */
	public function getFileName($fileName, $ext='')
	{		
		if (empty($ext)) {
			$fileName= substr($fileName, 0, strrpos($fileName, '.', -1));
		}
		else {
			if (substr($ext, 0, 1)!='.') {
            	$ext= '.'.$ext;
			}

			if (substr($fileName, -(strlen($ext)))==$ext) {
				$fileName= substr($fileName, 0, -(strlen($ext)));
			}
        }

        $exFileName = explode('/', Utils\Util::toFormat($fileName, '/'));        

		return end($exFileName);
	}


	/**
     * Get a directory name from the path
	 *
	 * @param string $path
	 *
	 * @return array
	 */
	public function getDirName($path)
	{
		$pieces= explode(Utils\Util::getSeparator(), $path);
        if (empty($pieces[count($pieces)-1])) {
        	unset($pieces[count($pieces)-1]);
        }

		if ($this->getFileName($path)!=$path) {
			return $pieces[count($pieces)-2];
		}

		return $pieces[count($pieces)-1];
	}


	/**
     * Return content of PHP file
	 *
	 * @param string $varName - name of variable which contains the content
	 * @param array $content
	 *
	 * @return string | false
	 */
	public function getPHPFormat($content)
	{
		if (empty($content)) {
            return false;
		}

        return '<?php

return '.var_export($content, true).';

?>';
	}

}

