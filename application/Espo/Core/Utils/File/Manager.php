<?php

namespace Espo\Core\Utils\File;

use Espo\Core\Utils;

class Manager
{
    /**
	* @var object - default permission settings
	*/
	protected $defaultPermissions;

	/**
	* @var object - default permission settings
	*/
	protected $appCache= 'application';


	private $params;


	public function __construct(array $params)
	{
		$this->params = $params;
	}

	protected function getParams()
	{
		return $this->params;
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
	function getFileList($path, $recursively=false, $filter='', $fileType='all', $isReturnSingleArray = false)
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
	public function checkCreateFile($filePath)
	{
		$defaultPermissions = $this->getDefaultPermissions();

		if (file_exists($filePath)) {

			if (!in_array($this->getCurrentPermission($filePath), array($defaultPermissions['file'], $defaultPermissions['dir']))) {
            	return $this->setDefaultPermissions($filePath, true);
			}
			return true;
		}

		$pathParts= pathinfo($filePath);
		if (!file_exists($pathParts['dirname'])) {
            $dirPermission= $defaultPermissions['dir'];
            $dirPermission= is_string($dirPermission) ? base_convert($dirPermission,8,10) : $dirPermission;

			if (!mkdir($pathParts['dirname'], $dirPermission, true)) {
                $GLOBALS['log']->add('FATAL', 'Permission denied: unable to generate a folder on the server - '.$pathParts['dirname']);
                return false;
			}
		}

		if (touch($filePath)) {
        	return $this->setDefaultPermissions($filePath, true);
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
	function getDirName($path)
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


    /**
     * Get default settings
	 *
	 * @return object
	 */
    public function getDefaultPermissions()
	{
    	if (isset($this->defaultPermissions) && is_array($this->defaultPermissions)) {
    		return $this->defaultPermissions;
    	}

		$params = $this->getParams();		
		$this->defaultPermissions = $params['defaultPermissions'];

		return $this->defaultPermissions;
	}

	/**
     * Set default permission
	 *
	 * @param string $path
	 * @param bool $recurse
	 *
	 * @return bool
	 */
    public function setDefaultPermissions($path, $recurse=false)
	{
		if (!file_exists($path)) {
			return false;
		}

        $permission= $this->getDefaultPermissions();

        $result= $this->chmod($path, array($permission['file'], $permission['dir']), $recurse);
		if (!empty($permission['user'])) {
        	$result&= $this->chown($path, $permission['user'], $recurse);
		}
		if (!empty($permission['group'])) {
        	$result&= $this->chgrp($path, $permission['group'], $recurse);
		}

        return $result;
	}


	/**
     * Get current permissions
	 *
	 * @param string $filename
	 * @return string | bool
	 */
	function getCurrentPermission($filePath)
	{
		if (!file_exists($filePath)) {
			return false;
		}

		$fileInfo= stat($filePath);

		return substr(base_convert($fileInfo['mode'],10,8), -4);
	}

	/**
     * Change permissions
	 *
	 * @param string $filename
	 * @param int | array $octal - ex. 0755, array(0644, 0755), array('file'=>0644, 'dir'=>0755)
	 * @param bool $recurse
	 *
	 * @return bool
	 */
	function chmod($path, $octal, $recurse=false)
	{
    	if (!file_exists($path)) {
			return false;
		}

		//check the input format
		$permission= array();
		if (is_array($octal)) {
			$count= 0;
			$rule= array('file', 'dir');
			foreach ($octal as $key => $val) {
				$pKey= strval($key);
				if (!in_array($pKey, $rule)) {
                	$pKey= $rule[$count];
				}

				if (!empty($pKey)) {
                	$permission[$pKey]= $val;
				}
                $count++;
			}
		}
		elseif (is_int((int)$octal)) {
        	$permission= array(
				'file' => $octal,
				'dir' => $octal,
			);
		}
		else {
			return false;
		}

		//conver to octal value
		foreach($permission as $key => $val) {
			if (is_string($val)) {
            	$permission[$key]= base_convert($val,8,10);
			}
		}

		//Set permission for non-recursive request
		if (!$recurse) {
			if (is_dir($path)) {
            	return $this->chmodReal($path, $permission['dir']);
			}
            return $this->chmodReal($path, $permission['file']);
		}

		//Recursive permission
        return $this->chmodRecurse($path, $permission['file'], $permission['dir']);
	}


    /**
     * Change permissions recirsive
	 *
	 * @param string $filename
	 * @param int $fileOctal - ex. 0644
	 * @param int $dirOctal - ex. 0755
	 *
	 * @return bool
	 */
	function chmodRecurse($path, $fileOctal=0644, $dirOctal=0755)
	{
		if (!file_exists($path)) {
			return false;
		}

		if (is_file($path)) {
			chmod($path, $fileOctal);
		}
		elseif (is_dir($path)) {
			$allFiles = scandir($path);
			$items = array_slice($allFiles, 2);

			foreach ($items as $item) {
				$this->chmodRecurse($path. Utils\Util::getSeparator() .$item, $fileOctal, $dirOctal);
			}

			$this->chmodReal($path, $dirOctal);
		}

		return true;
	}


	/**
     * Change permissions recirsive
	 *
	 * @param string $filename
	 * @param int $mode - ex. 0644
	 *
	 * @return bool
	 */
	function chmodReal($filename,  $mode)
	{
		$result= chmod($filename, $mode);

		if (!$result) {
			$this->chown($filename, $this->getDefaultOwner(true));
			$this->chgrp($filename, $this->getDefaultGroup(true));
			$result= chmod($filename, $mode);
		}

        return $result;
	}


	/**
     * Change owner permission
	 *
	 * @param string $path
	 * @param int | string $user
	 * @param bool $recurse
	 *
	 * @return bool
	 */
	function chown($path, $user='', $recurse=false)
	{
    	if (!file_exists($path)) {
			return false;
		}

		if (empty($user)) {
			$user= $this->getDefaultOwner();
		}

		//Set chown for non-recursive request
		if (!$recurse) {
            return chowm($path, $user);
		}

		//Recursive chown
        return $this->chownRecurse($path, $user);
	}

	/**
     * Change owner permission recirsive
	 *
	 * @param string $path
	 * @param string $user 
	 *
	 * @return bool
	 */
	function chownRecurse($path, $user) {

		if (!file_exists($path)) {
			return false;
		}

		$allFiles = scandir($path);
		$items = array_slice($allFiles, 2);

		foreach ($items as $item) {
			$this->chownRecurse($path. Utils\Util::getSeparator() .$item, $user);
		}

		return chowm($path, $user);
	}

	/**
     * Change group permission
	 *
	 * @param string $path
	 * @param int | string $group
	 * @param bool $recurse
	 *
	 * @return bool
	 */
	function chgrp($path, $group='', $recurse=false)
	{
    	if (!file_exists($path)) {
			return false;
		}

		if (empty($group)) {
			$group= $this->getDefaultGroup();
		}

		//Set chgrp for non-recursive request
		if (!$recurse) {
            return chgrp($path, $group);
		}

		//Recursive chown
        return $this->chgrpRecurse($path, $group);
	}

	/**
     * Change group permission recirsive
	 *
	 * @param string $filename
	 * @param int $fileOctal - ex. 0644
	 * @param int $dirOctal - ex. 0755
	 *
	 * @return bool
	 */
	function chgrpRecurse($path, $group) {

		if (!file_exists($path)) {
			return false;
		}

		$allFiles = scandir($path);
		$items = array_slice($allFiles, 2);

		foreach ($items as $item) {
			$this->chgrpRecurse($path. Utils\Util::getSeparator() .$item, $group);
		}

		return chgrp($path, $group);
	}

	/**
     * Get default owner user
	 *
	 * @return int  - owner id
	 */
	function getDefaultOwner($usePosix=false)
	{
		$defaultPermissions = $this->getDefaultPermissions();

		$owner = $defaultPermissions['user'];
    	if (empty($owner) && $usePosix) {
        	$owner = posix_getuid();
    	}

		if (empty($owner)) {
			return false;
		}

        return $owner;
	}

	/**
     * Get default group user
	 *
	 * @return int  - group id
	 */
	function getDefaultGroup($usePosix=false)
	{
		$defaultPermissions = $this->getDefaultPermissions();

		$group = $defaultPermissions['group'];
    	if (empty($group) && $usePosix) {
        	$group = posix_getegid();
    	}

		if (empty($group)) {
			return false;
		}

        return $group;
	}


}

