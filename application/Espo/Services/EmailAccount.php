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

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class EmailAccount extends Record
{	
	protected $internalFields = array('password');
	
	protected $readOnlyFields = array('assignedUserId', 'fetchData');
	
	public function getFolders($params)
	{		
		$password = $params['password'];
		
		if (!empty($params['id'])) {
			$entity = $this->getEntityManager()->getEntity('EmailAccount', $params['id']);
			if ($entity) {
				$password = $entity->get('password');
			}
		}		
		
		$imapParams = array(
			'host' => $params['host'],
			'port' => $params['port'],
			'user' => $params['username'],
			'password' => $password,
		);
		
		if (!empty($params['ssl'])) {
			$imapParams['ssl'] = 'SSL';
		}	
		
		$foldersArr = array();	
	
		$storage = new \Zend\Mail\Storage\Imap($imapParams);	
		
		$folders = new \RecursiveIteratorIterator($storage->getFolders(), \RecursiveIteratorIterator::SELF_FIRST);
		foreach ($folders as $name => $folder) {		
			$foldersArr[] =  $folder->getGlobalName();
		}
		return $foldersArr;
	}
	
	public function fetchFromMailServer(Entity $emailAccount)
	{
		if ($emailAccount->get('status') != 'Active') {
			throw new Error();
		}
		
		$importer = \Espo\Core\Mail\Importer($this->getEntityManager());
		
		$user = $this->getEntityManager()->getEntity('User', $emailAccount->get('assignedUserId'));
		
		if (!$user) {
			throw new Error();
		}
		
		$userId = $user->id;
		$teamId = $user->get('defaultTeam');
		
		$fetchData = json_decode($emailAccount->get('fetchData'), true);
		if (empty($fetchData)) {
			$fetchData = array();
		}
		if (!array_key_exists('lastUIDs', $fetchData)) {
			$fetchData['lastUIDs'] = array();
		}
		
		$imapParams = array(
			'host' => $emailAccount->get('host'),
			'port' => $emailAccount->get('port'),
			'user' => $emailAccount->get('username'),
			'password' => $emailAccount->get('password'),
		);
		
		if ($emailAccount->get('ssl')) {
			$imapParams['ssl'] = 'SSL';
		}
		
		$storage = new \Espo\Core\Mail\Storage\Imap($imapParams);		
		
		$monitoredFolders = $emailAccount->get('monitoredFolders');		
		if (empty($monitoredFolders)) {
			throw new Error();		
		}
		
		$monitoredFoldersArr = explode(',', $monitoredFolders);				
		foreach ($monitoredFoldersArr as $folder) {
			$folder = trim($folder);
			
			$storage->selectFolder($folder);
			
			$lastUID = 0;
			if (!empty($fetchData['lastUIDs'][$folder])) {
				$lastUID = $fetchData['lastUIDs'][$folder];
			}

			$ids = $storage->getIdsFromUID();
			
			print_r($ids); 
			
			foreach ($ids as $k => $id) {
				$message = $storage->getMessage($id);												
				
				$importer->importMessage($message, $userId, array($teamId));
								
				if ($k == count($ids) - 1) {
					$lastUID = $storage->getUniqueId($id);
				}
			}		
									
			$fetchData['lastUIDs'][$folder] = $lastUID;
			
			print_r($fetchData);
			
		}		
	}
	

	


}

