<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\File;

use Espo\Core\Utils;
use Espo\Core\Exceptions\Error;

class Manager
{
    private $permission;

    private $permissionDeniedList = array();

    public function __construct(\Espo\Core\Utils\Config $config = null)
    {
        $params = null;
        if (isset($config)) {
            $params = array(
                'defaultPermissions' => $config->get('defaultPermissions'),
                'permissionMap' => $config->get('permissionMap'),
            );
        }

        $this->permission = new Permission($this, $params);
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
     * @param bool $onlyFileType [null, true, false] - Filter for type of files/directories. If TRUE - returns only file list, if FALSE - only directory list
     * @param bool $isReturnSingleArray - if need to return a single array of file list
     *
     * @return array
     */
    public function getFileList($path, $recursively = false, $filter = '', $onlyFileType = null, $isReturnSingleArray = false)
    {
        $path = $this->concatPaths($path);

        $result = array();

        if (!file_exists($path) || !is_dir($path)) {
            return $result;
        }

        $cdir = scandir($path);
        foreach ($cdir as $key => $value)
        {
            if (!in_array($value,array(".", "..")))
            {
                $add = false;
                if (is_dir($path . Utils\Util::getSeparator() . $value)) {
                    if ($recursively || (is_int($recursively) && $recursively!=0) ) {
                        $nextRecursively = is_int($recursively) ? ($recursively-1) : $recursively;
                        $result[$value] = $this->getFileList($path . Utils\Util::getSeparator() . $value, $nextRecursively, $filter, $onlyFileType);
                    }
                    else if (!isset($onlyFileType) || !$onlyFileType){ /*save only directories*/
                        $add = true;
                    }
                }
                else if (!isset($onlyFileType) || $onlyFileType) { /*save only files*/
                    $add = true;
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
            return $this->getSingeFileList($result, $onlyFileType, $path);
        }

        return $result;
    }

    /**
     * Convert file list to a single array
     *
     * @param aray $fileList
     * @param bool $onlyFileType [null, true, false] - Filter for type of files/directories.
     * @param string $parentDirName
     *
     * @return aray
     */
    protected function getSingeFileList(array $fileList, $onlyFileType = null, $basePath = null, $parentDirName = '')
    {
        $singleFileList = array();
        foreach($fileList as $dirName => $fileName) {

            if (is_array($fileName)) {
                $currentDir = Utils\Util::concatPath($parentDirName, $dirName);

                if (!isset($onlyFileType) || $onlyFileType == $this->isFile($currentDir, $basePath)) {
                    $singleFileList[] = $currentDir;
                }

                $singleFileList = array_merge($singleFileList, $this->getSingeFileList($fileName, $onlyFileType, $basePath, $currentDir));

            } else {
                $currentFileName = Utils\Util::concatPath($parentDirName, $fileName);

                if (!isset($onlyFileType) || $onlyFileType == $this->isFile($currentFileName, $basePath)) {
                    $singleFileList[] = $currentFileName;
                }
            }
        }

        return $singleFileList;
    }

    /**
     * Reads entire file into a string
     *
     * @param  string | array  $path  Ex. 'path.php' OR array('dir', 'path.php')
     * @return mixed
     */
    public function getContents($path)
    {
        $fullPath = $this->concatPaths($path);

        if (file_exists($fullPath)) {
            return file_get_contents($fullPath);
        }

        return false;
    }

    /**
     * Get PHP array from PHP file
     *
     * @param  string | array $path
     * @return array | bool
     */
    public function getPhpContents($path)
    {
        $fullPath = $this->concatPaths($path);

        if (file_exists($fullPath) && strtolower(substr($fullPath, -4)) == '.php') {
            $phpContents = include($fullPath);
            return $phpContents;
        }

        return false;
    }

    /**
     * Write data to a file
     *
     * @param  string | array  $path
     * @param  mixed  $data
     * @param  integer $flags
     *
     * @return bool
     */
    public function putContents($path, $data, $flags = 0)
    {
        $fullPath = $this->concatPaths($path); //todo remove after changing the params

        if ($this->checkCreateFile($fullPath) === false) {
            throw new Error('Permission denied for '. $fullPath);
        }

        $res = (file_put_contents($fullPath, $data, $flags) !== FALSE);
        if ($res && function_exists('opcache_invalidate')) {
            @opcache_invalidate($fullPath);
        }

        return $res;
    }

    /**
     * Save PHP content to file
     *
     * @param string | array $path
     * @param string $data
     *
     * @return bool
     */
    public function putPhpContents($path, $data, $withObjects = false)
    {
        return $this->putContents($path, $this->wrapForDataExport($data, $withObjects), LOCK_EX);
    }

    /**
     * Save JSON content to file
     *
     * @param string | array $path
     * @param string $data
     * @param  integer $flags
     * @param  resource  $context
     *
     * @return bool
     */
    public function putContentsJson($path, $data)
    {
        if (!Utils\Json::isJSON($data)) {
            $data = Utils\Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->putContents($path, $data, LOCK_EX);
    }

    /**
     * Merge file content and save it to a file
     *
     * @param string | array $path
     * @param string $content JSON string
     * @param bool $isReturnJson
     * @param string | array $removeOptions - List of unset keys from content
     * @param bool $isPhp - Is merge php files
     *
     * @return bool | array
     */
    public function mergeContents($path, $content, $isReturnJson = false, $removeOptions = null, $isPhp = false)
    {
        if ($isPhp) {
            $fileContent = $this->getPhpContents($path);
        } else {
            $fileContent = $this->getContents($path);
        }

        $fullPath = $this->concatPaths($path);
        if (file_exists($fullPath) && ($fileContent === false || empty($fileContent))) {
            throw new Error('FileManager: Failed to read file [' . $fullPath .'].');
        }

        $savedDataArray = Utils\Json::getArrayData($fileContent);
        $newDataArray = Utils\Json::getArrayData($content);

        if (isset($removeOptions)) {
            $savedDataArray = Utils\Util::unsetInArray($savedDataArray, $removeOptions);
            $newDataArray = Utils\Util::unsetInArray($newDataArray, $removeOptions);
        }

        $data = Utils\Util::merge($savedDataArray, $newDataArray);

        if ($isReturnJson) {
            $data = Utils\Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($isPhp) {
            return $this->putPhpContents($path, $data);
        }

        return $this->putContents($path, $data);
    }

    /**
     * Merge PHP content and save it to a file
     *
     * @param string | array $path
     * @param string $content JSON string
     * @param string | array $removeOptions - List of unset keys from content
     * @return bool
     */
    public function mergePhpContents($path, $content, $removeOptions = null)
    {
        return $this->mergeContents($path, $content, false, $removeOptions, true);
    }

    /**
     * Append the content to the end of the file
     *
     * @param string | array $path
     * @param mixed $data
     *
     * @return bool
     */
    public function appendContents($path, $data)
    {
        return $this->putContents($path, $data, FILE_APPEND | LOCK_EX);
    }

    /**
     * Unset some element of content data
     *
     * @param  string | array $path
     * @param  array | string $unsets
     * @return bool
     */
    public function unsetContents($path, $unsets, $isJSON = true)
    {
        $currentData = $this->getContents($path);
        if (!isset($currentData) || !$currentData) {
            return true;
        }

        $currentDataArray = Utils\Json::getArrayData($currentData);

        $unsettedData = Utils\Util::unsetInArray($currentDataArray, $unsets, true);

        if (is_null($unsettedData) || (is_array($unsettedData) && empty($unsettedData))) {
            $fullPath = $this->concatPaths($path);
            if (!file_exists($fullPath)) {
                return true;
            }
            return $this->unlink($fullPath);
        }

        if ($isJSON) {
            return $this->putContentsJson($path, $unsettedData);
        }

        return $this->putContents($path, $unsettedData);
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
     * Create a new dir
     *
     * @param  string | array $path
     * @param  int $permission - ex. 0755
     * @param  bool $recursive
     *
     * @return bool
     */
    public function mkdir($path, $permission = null, $recursive = false)
    {
        $fullPath = $this->concatPaths($path);

        if (file_exists($fullPath) && is_dir($path)) {
            return true;
        }

        $defaultPermissions = $this->getPermissionUtils()->getDefaultPermissions();

        if (!isset($permission)) {
            $permission = (string) $defaultPermissions['dir'];
            $permission = base_convert($permission, 8, 10);
        }

        try {
            $result = mkdir($fullPath, $permission, true);

            if (!empty($defaultPermissions['user'])) {
                $this->getPermissionUtils()->chown($fullPath);
            }

            if (!empty($defaultPermissions['group'])) {
                $this->getPermissionUtils()->chgrp($fullPath);
            }
        } catch (\Exception $e) {
            $GLOBALS['log']->critical('Permission denied: unable to create the folder on the server - '.$fullPath);
        }

        return isset($result) ? $result : false;
    }

    /**
     * Copy files from one direcoty to another
     * Ex. $sourcePath = 'data/uploads/extensions/file.json', $destPath = 'data/uploads/backup', result will be data/uploads/backup/data/uploads/backup/file.json.
     *
     * @param  string  $sourcePath
     * @param  string  $destPath
     * @param  boolean $recursively
     * @param  array $fileList - list of files that should be copied
     * @param  boolean $copyOnlyFiles - copy only files, instead of full path with directories, Ex. $sourcePath = 'data/uploads/extensions/file.json', $destPath = 'data/uploads/backup', result will be 'data/uploads/backup/file.json'
     * @return boolen
     */
    public function copy($sourcePath, $destPath, $recursively = false, array $fileList = null, $copyOnlyFiles = false)
    {
        $sourcePath = $this->concatPaths($sourcePath);
        $destPath = $this->concatPaths($destPath);

        if (!isset($fileList)) {
            $fileList = is_file($sourcePath) ? (array) $sourcePath : $this->getFileList($sourcePath, $recursively, '', true, true);
        }

        /** Check permission before copying */
        $permissionDeniedList = array();
        foreach ($fileList as $file) {

            if ($copyOnlyFiles) {
                $file = pathinfo($file, PATHINFO_BASENAME);
            }

            $destFile = $this->concatPaths(array($destPath, $file));

            $isFileExists = file_exists($destFile);

            if ($this->checkCreateFile($destFile) === false) {
                $permissionDeniedList[] = $destFile;
            } else if (!$isFileExists) {
                $this->removeFile($destFile);
            }
        }
        /** END */

        if (!empty($permissionDeniedList)) {
            $betterPermissionList = $this->getPermissionUtils()->arrangePermissionList($permissionDeniedList);
            throw new Error("Permission denied for <br>". implode(", <br>", $betterPermissionList));
        }

        $res = true;
        foreach ($fileList as $file) {

            if ($copyOnlyFiles) {
                $file = pathinfo($file, PATHINFO_BASENAME);
            }

            $sourceFile = is_file($sourcePath) ? $sourcePath : $this->concatPaths(array($sourcePath, $file));
            $destFile = $this->concatPaths(array($destPath, $file));

            if (file_exists($sourceFile) && is_file($sourceFile)) {
                $res &= copy($sourceFile, $destFile);
                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate($destFile);
                }
            }
        }

        return $res;
    }

    /**
     * Create a new file if not exists with all folders in the path.
     *
     * @param string $filePath
     * @return string
     */
    public function checkCreateFile($filePath)
    {
        $defaultPermissions = $this->getPermissionUtils()->getDefaultPermissions();

        if (file_exists($filePath)) {
            if (!is_writable($filePath) && !in_array($this->getPermissionUtils()->getCurrentPermission($filePath), array($defaultPermissions['file'], $defaultPermissions['dir']))) {
                return $this->getPermissionUtils()->setDefaultPermissions($filePath, true);
            }
            return true;
        }

        $pathParts = pathinfo($filePath);
        if (!file_exists($pathParts['dirname'])) {
            $dirPermission = $defaultPermissions['dir'];
            $dirPermission = is_string($dirPermission) ? base_convert($dirPermission,8,10) : $dirPermission;

            if (!$this->mkdir($pathParts['dirname'], $dirPermission, true)) {
                throw new Error('Permission denied: unable to create a folder on the server - ' . $pathParts['dirname']);
            }
        }

        if (touch($filePath)) {
            return $this->getPermissionUtils()->setDefaultPermissions($filePath, true);
        }

        return false;
    }

    /**
     * Remove file/files by given path
     *
     * @param array $filePaths - File paths list
     * @return bool
     */
    public function unlink($filePaths)
    {
        return $this->removeFile($filePaths);
    }

    public function rmdir($dirPaths)
    {
        if (!is_array($dirPaths)) {
            $dirPaths = (array) $dirPaths;
        }

        $result = true;
        foreach ($dirPaths as $dirPath) {
            if (is_dir($dirPath) && is_writable($dirPath)) {
                $result &= rmdir($dirPath);
            }
        }

        return (bool) $result;
    }

    public function removeDir($dirPaths)
    {
        return $this->rmdir($dirPaths);
    }

    /**
     * Remove file/files by given path
     *
     * @param array $filePaths - File paths list
     * @param string $dirPath - directory path
     * @return bool
     */
    public function removeFile($filePaths, $dirPath = null)
    {
        if (!is_array($filePaths)) {
            $filePaths = (array) $filePaths;
        }

        $result = true;
        foreach ($filePaths as $filePath) {
            if (isset($dirPath)) {
                $filePath = Utils\Util::concatPath($dirPath, $filePath);
            }

            if (file_exists($filePath) && is_file($filePath)) {
                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate($filePath, true);
                }
                $result &= unlink($filePath);
            }
        }

        return $result;
    }

    /**
     * Remove all files inside given path
     *
     * @param string $dirPath - directory path
     * @param bool $removeWithDir - if remove with directory
     *
     * @return bool
     */
    public function removeInDir($dirPath, $removeWithDir = false)
    {
        $fileList = $this->getFileList($dirPath, false);

        $result = true;
        if (is_array($fileList)) {
            foreach ($fileList as $file) {
                $fullPath = Utils\Util::concatPath($dirPath, $file);
                if (is_dir($fullPath)) {
                    $result &= $this->removeInDir($fullPath, true);
                } else if (file_exists($fullPath)) {
                    $result &= unlink($fullPath);
                }
            }
        }

        if ($removeWithDir && $this->isDirEmpty($dirPath)) {
            $result &= $this->rmdir($dirPath);
        }

        return (bool) $result;
    }

    /**
     * Remove items (files or directories)
     *
     * @param  string | array $items
     * @param  string $dirPath
     * @return boolean
     */
    public function remove($items, $dirPath = null, $removeEmptyDirs = false)
    {
        if (!is_array($items)) {
            $items = (array) $items;
        }

        $removeList = array();
        $permissionDeniedList = array();
        foreach ($items as $item) {
            if (isset($dirPath)) {
                $item = Utils\Util::concatPath($dirPath, $item);
            }

            if (!file_exists($item)) {
                continue;
            }

            $removeList[] = $item;

            if (!is_writable($item)) {
                $permissionDeniedList[] = $item;
            } else if (!is_writable(dirname($item))) {
                $permissionDeniedList[] = dirname($item);
            }
        }

        if (!empty($permissionDeniedList)) {
            $betterPermissionList = $this->getPermissionUtils()->arrangePermissionList($permissionDeniedList);
            throw new Error("Permission denied for <br>". implode(", <br>", $betterPermissionList));
        }

        $result = true;
        foreach ($removeList as $item) {
            if (is_dir($item)) {
                $result &= $this->removeInDir($item, true);
            } else {
                $result &= $this->removeFile($item);
            }

            if ($removeEmptyDirs) {
                $result &= $this->removeEmptyDirs($item);
            }
        }

        return (bool) $result;
    }

    /**
     * Remove empty parent directories if they are empty
     * @param  string $path
     * @return bool
     */
    protected function removeEmptyDirs($path)
    {
        $parentDirName = $this->getParentDirName($path);

        $res = true;
        if ($this->isDirEmpty($parentDirName)) {
            $res &= $this->rmdir($parentDirName);
            $res &= $this->removeEmptyDirs($parentDirName);
        }

        return (bool) $res;
    }

    /**
     * Check if $dirname is directory.
     *
     * @param  string  $dirname
     * @param  string  $basePath
     *
     * @return boolean
     */
    public function isDir($dirname, $basePath = null)
    {
        if (!empty($basePath)) {
            $dirname = $this->concatPaths([$basePath, $dirname]);
        }

        return is_dir($dirname);
    }

    /**
     * Check if $filename is file. If $filename doesn'ot exist, check by pathinfo
     *
     * @param  string  $filename
     * @param  string  $basePath
     *
     * @return boolean
     */
    public function isFile($filename, $basePath = null)
    {
        if (!empty($basePath)) {
            $filename = $this->concatPaths([$basePath, $filename]);
        }

        if (file_exists($filename)) {
            return is_file($filename);
        }

        $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!empty($fileExtension)) {
            return true;
        }

        return false;
    }

    /**
     * Check if directory is empty
     * @param  string  $path
     * @return boolean
     */
    public function isDirEmpty($path)
    {
        if (is_dir($path)) {
            $fileList = $this->getFileList($path, true);

            if (is_array($fileList) && empty($fileList)) {
                return true;
            }
        }

        return false;
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
     * @param bool $isFullPath
     *
     * @return array
     */
    public function getDirName($path, $isFullPath = true, $useIsDir = true)
    {
        $dirName = preg_replace('/\/$/i', '', $path);
        $dirName = ($useIsDir && is_dir($dirName)) ? $dirName : pathinfo($dirName, PATHINFO_DIRNAME);

        if (!$isFullPath) {
            $pieces = explode('/', $dirName);
            $dirName = $pieces[count($pieces)-1];
        }

        return $dirName;
    }

    /**
     * Get parent dir name/path
     *
     * @param  string  $path
     * @param  boolean $isFullPath
     * @return string
     */
    public function getParentDirName($path, $isFullPath = true)
    {
        return $this->getDirName($path, $isFullPath, false);
    }

    /**
     * Return content of PHP file
     *
     * @param string $varName - name of variable which contains the content
     * @param array $content
     *
     * @return string | false
     */
    public function wrapForDataExport($content, $withObjects = false)
    {
        if (!isset($content)) {
            return false;
        }

        if (!$withObjects) {
            return "<?php\n".
            "return " . var_export($content, true) . ";\n".
            "?>";
        } else {
            return "<?php\n".
            "return " . $this->varExport($content) . ";\n".
            "?>";
        }
    }

    public function varExport($variable, $level = 0)
    {
        $tab = '';
        $tabElement = '    ';
        for ($i = 0; $i <= $level; $i++) {
            $tab .= $tabElement;
        }
        $prevTab = substr($tab, 0, strlen($tab) - strlen($tabElement));

        if ($variable instanceof \StdClass) {
            $result = "(object) " . $this->varExport(get_object_vars($variable), $level);
        } else if (is_array($variable)) {
            $array = array();
            foreach ($variable as $key => $value) {
                $array[] = var_export($key, true) . " => " . $this->varExport($value, $level + 1);
            }
            $result = "[\n" . $tab . implode(",\n" . $tab, $array) . "\n" . $prevTab . "]";
        } else {
            $result = var_export($variable, true);
        }

        return $result;
    }

    /**
     * Check if $paths are writable. Permission denied list are defined in getLastPermissionDeniedList()
     *
     * @param  array   $paths
     *
     * @return boolean
     */
    public function isWritableList(array $paths)
    {
        $permissionDeniedList = array();

        $result = true;
        foreach ($paths as $path) {
            $rowResult = $this->isWritable($path);
            if (!$rowResult) {
                $permissionDeniedList[] = $path;
            }
            $result &= $rowResult;
        }

        if (!empty($permissionDeniedList)) {
            $this->permissionDeniedList = $this->getPermissionUtils()->arrangePermissionList($permissionDeniedList);
        }

        return (bool) $result;
    }

    /**
     * Get last permission denied list
     *
     * @return array
     */
    public function getLastPermissionDeniedList()
    {
        return $this->permissionDeniedList;
    }

    /**
     * Check if $path is writable
     *
     * @param  string | array  $path
     *
     * @return boolean
     */
    public function isWritable($path)
    {
        $existFile = $this->getExistsPath($path);

        return is_writable($existFile);
    }

    /**
     * Check if $path is writable
     *
     * @param  string | array  $path
     *
     * @return boolean
     */
    public function isReadable($path)
    {
        $existFile = $this->getExistsPath($path);

        return is_readable($existFile);
    }

    /**
     * Get exists path. Ex. if check /var/www/espocrm/custom/someFile.php and this file doesn't extist, result will be /var/www/espocrm/custom
     *
     * @param  string | array $path
     *
     * @return string
     */
    protected function getExistsPath($path)
    {
        $fullPath = $this->concatPaths($path);

        if (!file_exists($fullPath)) {
            $fullPath = $this->getExistsPath(pathinfo($fullPath, PATHINFO_DIRNAME));
        }

        return $fullPath;
    }
}
