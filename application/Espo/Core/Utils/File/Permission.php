<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/ 

namespace Espo\Core\Utils\File;

use Espo\Core\Utils,
	Espo\Core\Exceptions\Error;

class Permission
{
	protected $params = array(
		'defaultPermissions' => array (
		    'dir' => '0775',
		    'file' => '0664',
		    'user' => '',
		    'group' => '',
		),
	);


	public function __construct(array $params = null)
	{
		if (isset($params)) {
			$this->params = $params;
		}
	}

	protected function getParams()
	{
		return $this->params;
	}


    /**
     * Get default settings
	 *
	 * @return object
	 */
    public function getDefaultPermissions()
	{
		$params = $this->getParams();
		return $params['defaultPermissions'];
	}


	/**
     * Set default permission
	 *
	 * @param string $path
	 * @param bool $recurse
	 *
	 * @return bool
	 */
    public function setDefaultPermissions($path, $recurse = false)
	{
		if (!file_exists($path)) {
			return false;
		}

        $permission = $this->getDefaultPermissions();

        $result = $this->chmod($path, array($permission['file'], $permission['dir']), $recurse);
		if (!empty($permission['user'])) {
        	$result &= $this->chown($path, $permission['user'], $recurse);
		}
		if (!empty($permission['group'])) {
        	$result &= $this->chgrp($path, $permission['group'], $recurse);
		}

        return $result;
	}


	/**
     * Get current permissions
	 *
	 * @param string $filename
	 * @return string | bool
	 */
	public function getCurrentPermission($filePath)
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
	public function chmod($path, $octal, $recurse = false)
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
     * Change permissions recursive
	 *
	 * @param string $filename
	 * @param int $fileOctal - ex. 0644
	 * @param int $dirOctal - ex. 0755
	 *
	 * @return bool
	 */
	protected function chmodRecurse($path, $fileOctal = 0644, $dirOctal = 0755)
	{
		if (!file_exists($path)) {
			return false;
		}

		if (is_file($path)) {
			return $this->chmodReal($path, $fileOctal);
		}

		if (is_dir($path)) {
			$allFiles = scandir($path);
			$items = array_slice($allFiles, 2);

			foreach ($items as $item) {
				$this->chmodRecurse($path. Utils\Util::getSeparator() .$item, $fileOctal, $dirOctal);
			}

			return $this->chmodReal($path, $dirOctal);
		}

		return false;
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
	public function chown($path, $user='', $recurse=false)
	{
    	if (!file_exists($path)) {
			return false;
		}

		if (empty($user)) {
			$user = $this->getDefaultOwner();
		}

		//Set chown for non-recursive request
		if (!$recurse) {
            return $this->chownReal($path, $user);
		}

		//Recursive chown
        return $this->chownRecurse($path, $user);
	}

	/**
     * Change owner permission recursive
	 *
	 * @param string $path
	 * @param string $user
	 *
	 * @return bool
	 */
	protected function chownRecurse($path, $user)
	{
		if (!file_exists($path)) {
			return false;
		}

		$allFiles = scandir($path);
		$items = array_slice($allFiles, 2);

		foreach ($items as $item) {
			$this->chownRecurse($path. Utils\Util::getSeparator() .$item, $user);
		}

		return $this->chownReal($path, $user);
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
	public function chgrp($path, $group = null, $recurse = false)
	{
    	if (!file_exists($path)) {
			return false;
		}

		if (!isset($group)) {
			$group = $this->getDefaultGroup();
		}

		//Set chgrp for non-recursive request
		if (!$recurse) {
            return $this->chgrpReal($path, $group);
		}

		//Recursive chown
        return $this->chgrpRecurse($path, $group);
	}

	/**
     * Change group permission recursive
	 *
	 * @param string $filename
	 * @param int $fileOctal - ex. 0644
	 * @param int $dirOctal - ex. 0755
	 *
	 * @return bool
	 */
	protected function chgrpRecurse($path, $group) {

		if (!file_exists($path)) {
			return false;
		}

		$allFiles = scandir($path);
		$items = array_slice($allFiles, 2);

		foreach ($items as $item) {
			$this->chgrpRecurse($path. Utils\Util::getSeparator() .$item, $group);
		}

		return $this->chgrpReal($path, $group);
	}


	/**
     * Change permissions recursive
	 *
	 * @param string $filename
	 * @param int $mode - ex. 0644
	 *
	 * @return bool
	 */
	protected function chmodReal($filename,  $mode)
	{
		try {
			$result = chmod($filename, $mode);	
		} catch (\Exception $e) {
			$result = false;
		}

		if (!$result) {
			$this->chown($filename, $this->getDefaultOwner(true));
			$this->chgrp($filename, $this->getDefaultGroup(true));

			try {
				$result = chmod($filename, $mode);	
			} catch (\Exception $e) {
				throw new Error($e->getMessage());
			}			
		}

        return $result;
	}

	protected function chownReal($path, $user)
	{
		try {
			$result = chown($path, $user);	
		} catch (\Exception $e) {
			throw new Error($e->getMessage());				
		}
		
        return $result;
	}

	protected function chgrpReal($path, $group)
	{
		try {
			$result = chgrp($path, $group);	
		} catch (\Exception $e) {
			throw new Error($e->getMessage());				
		}
		
        return $result;
	}

	/**
     * Get default owner user
	 *
	 * @return int  - owner id
	 */
	public function getDefaultOwner($usePosix = false)
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
	public function getDefaultGroup($usePosix = false)
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

