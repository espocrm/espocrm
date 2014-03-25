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

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;

class Stream extends \Espo\Core\Hooks\Base
{
	protected $streamService = null;	
	
	protected $auditedFieldsCache = array();
	
	protected $statusDefs = array(
		'Lead' => 'status',
		'Case' => 'status',
		'Opportunity' => 'stage',	
	);
	
	protected function init()
	{
		$this->dependencies[] = 'serviceFactory';
	}
	
	protected function getServiceFactory()
	{
		return $this->getInjection('serviceFactory');
	}
	
	protected function checkHasStream(Entity $entity)
	{
		$entityName = $entity->getEntityName();
		return $this->getMetadata()->get("scopes.{$entityName}.stream");
	}
	
	public function afterRemove(Entity $entity)
	{
		if ($this->checkHasStream($entity)) {
			$this->getStreamService()->unfollowAllUsersFromEntity($entity);
		}
	}
	
	public function afterSave(Entity $entity)
	{
		$entityName = $entity->getEntityName();
		
		if ($this->checkHasStream($entity)) {
			if (!$entity->isFetched()) {
				
				$assignedUserId = $entity->get('assignedUserId');
				$createdById = $entity->get('createdById');
				
				if (!empty($createdById)) {
					$this->getStreamService()->followEntity($entity, $createdById);
				}
				
				if (!empty($assignedUserId) && $createdById != $assignedUserId) {
					$this->getStreamService()->followEntity($entity, $assignedUserId);
				}	
				$this->getStreamService()->noteCreate($entity);							
			} else {
				if ($entity->isFieldChanged('assignedUserId')) {
					$assignedUserId = $entity->get('assignedUserId');
					if (!empty($assignedUserId)) {
						$this->getStreamService()->followEntity($entity, $assignedUserId);
						$this->getStreamService()->noteAssign($entity);
					}
				}				
				$this->getStreamService()->handleAudited($entity);
				
				if (array_key_exists($entityName, $this->statusDefs)) {
					$field = $this->statusDefs[$entityName];
					$value = $entity->get($field);
					if (!empty($value) && $value != $entity->getFetched($field)) {
						$this->getStreamService()->noteStatus($entity, $field);
					}
				}			
			}			

		}	
	}
	
	protected function getStreamService()
	{
		if (empty($this->streamService)) {
			$this->streamService = $this->getServiceFactory()->create('Stream');
		}
		return $this->streamService;		
	}	

}

