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

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class Note extends Record
{	
	public function getEntity($id = null)
	{
		$entity = parent::getEntity($id);		
		if (!empty($id)) {
			$entity->loadAttachments();
		}		
		return $entity;
	}
	
	protected function fetchMentionDataFromPost($post)
	{
		$data = new \stdClass();
		
		preg_match_all('/(@\w+)/', $post, $matches);
		
		$userList = array();
		
		if (is_array($matches) && !empty($matches[0]) && is_array($matches[0])) {
			foreach ($matches[0] as $item) {
				$userName = substr($item, 1);
				$user = $this->getEntityManager()->getRepository('User')->where(array('userName' => $userName))->findOne();
				if ($user) {
					$userList[] = $user;
				}
			}
		}
		
		

		die;
	}	
	
	public function createEntity($data)
	{
		if (!empty($data['parentType']) && !empty($data['parentId'])) {
			$entity = $this->getEntityManager()->getEntity($data['parentType'], $data['parentId']);
			if ($entity) {				
				if (!$this->getAcl()->check($entity, 'read')) {
					throw new Forbidden();
				}
			}
		}
		$mentionData = $this->fetchMentionDataFromPost($data['post']);
		
		$entity = parent::createEntity($data);
		
		
		return $entity;
	}
	
	public function updateEntity($id, $data)
	{
		$entity = parent::updateEntity($id, $data);
		
		return $entity;
		
	}

}

