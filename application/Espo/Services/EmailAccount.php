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


use \Zend\Mime\Mime as Mime;

class EmailAccount extends Record
{
	
	protected $internalFields = array('password');
	
	protected $readOnlyFields = array('assignedUserId');
	
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
		
		$imapParams = array(
			'host' => $emailAccount->get('host'),
			'port' => $emailAccount->get('port'),
			'user' => $emailAccount->get('username'),
			'password' => $emailAccount->get('password'),
		);
		
		if ($emailAccount->get('ssl')) {
			$imapParams['ssl'] = 'SSL';
		}
		
	}

}

