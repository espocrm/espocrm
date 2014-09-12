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

namespace Espo\Core;

use \Espo\Core\Exceptions\Error;

class Acl
{
	private $data = array();

	private $cacheFile;

	private $actionList = array('read', 'edit', 'delete');

	private $levelList = array('all', 'team', 'own', 'no');
	
	protected $fileManager;
	
	protected $metadata;

	public function __construct(\Espo\Entities\User $user, $config = null, $fileManager = null, $metadata = null)
	{
		$this->user = $user;
		
		$this->metadata = $metadata;		
		
		if (!$this->user->isFetched()) {
			throw new Error();
		}
		
		$this->user->loadLinkMultipleField('teams');
		
		if ($fileManager) {
			$this->fileManager = $fileManager;
		}
			
		$this->cacheFile = 'data/cache/application/acl/' . $user->id . '.php';
		
		if ($config && $config->get('useCache') && file_exists($this->cacheFile)) {
			$cached = include $this->cacheFile;
			$this->data = $cached;
			$this->initSolid();
		} else {
			$this->load();
			$this->initSolid();
			if ($config && $fileManager && $config->get('useCache')) {
				$this->buildCache();
			}
		}

	}
	
	public function checkScope($scope, $action = null, $isOwner = null, $inTeam = null, $entity = null)
	{
		if (array_key_exists($scope, $this->data)) {			
			if ($this->data[$scope] === false) {
				return false;
			}
			if ($this->data[$scope] === true) {
				return true;
			}
			if (!is_null($action)) {		
				if (array_key_exists($action, $this->data[$scope])) {
					$value = $this->data[$scope][$action];
			
					if ($value === 'all' || $value === true) {
						return true;					
					}
			
					if (!$value || $value === 'no') {
						return false;					
					}					
				
					if (is_null($isOwner)) {
						return true;
					}
				
					if ($isOwner) {
						if ($value === 'own' || $value === 'team') {
							return true;
						}
					}
					if ($inTeam === null && $entity) {
						$inTeam = $this->checkInTeam($entity);
					}
			
					if ($inTeam) {
						if ($value === 'team') {
							return true;
						}
					}
			
					return false;
				}
			}
			return true;
		}
		return true;		
	}
	
	public function toArray()
	{
		return $this->data;
	}

	public function check($subject, $action = null, $isOwner = null, $inTeam = null)
	{	
		if ($this->user->isAdmin()) {
			return true;
		}
		if (is_string($subject)) {
			return $this->checkScope($subject, $action, $isOwner, $inTeam);
		} else {
			$entity = $subject;
			$entityName = $entity->getEntityName();			
			return $this->checkScope($entityName, $action, $this->checkIsOwner($entity), $inTeam, $entity);
		}
	}
			
	public function checkReadOnlyTeam($scope)
	{
		if (isset($this->data[$scope]) && isset($this->data[$scope]['read'])) {
			return $this->data[$scope]['read'] === 'team';
		}
		return false;
	}
	
	public function checkReadOnlyOwn($scope)
	{
		if ($this->user->isAdmin()) {
			return false;
		}
		if (isset($this->data[$scope]) && isset($this->data[$scope]['read'])) {
			return $this->data[$scope]['read'] === 'own';
		}
		return false;
	}
	
	public function checkIsOwner($entity)
	{
		if ($this->user->isAdmin()) {
			return false;
		}
		$userId = $this->user->id;
		if ($userId === $entity->get('assignedUserId') || $userId === $entity->get('createdById')) {
			return true;
		}
		return false;
	}
	
	public function checkInTeam($entity)
	{
		$userTeamIds = $this->user->get('teamsIds');
		
		if (!$entity->hasRelation('teams') || !$entity->hasField('teamsIds')) {			
			return false;
		}
		
		if (!$entity->has('teamsIds')) {
			$entity->loadLinkMultipleField('teams');
		}
				
		$teamIds = $entity->get('teamsIds');		
		
		if (empty($teamIds)) {
			return false;
		}
		
		foreach ($userTeamIds as $id) {
			if (in_array($id, $teamIds)) {
				return true;
			}
		}		
		return false;
	}

	private function load()
	{
		$aclTables = array();

		$userRoles = $this->user->get('roles');
		
		foreach ($userRoles as $role) {
			$aclTables[] = json_decode($role->get('data'));
		}

		$teams = $this->user->get('teams');
		foreach ($teams as $team) {
			$teamRoles = $team->get('roles');
			foreach ($teamRoles as $role) {
				$aclTables[] = json_decode($role->get('data'));
			}
		}

		$this->data = $this->merge($aclTables);
	}
	
	private function initSolid()
	{
		$data = $this->metadata->get('app.acl.solid', array());
		
		foreach ($data as $entityName => $item) {
			$this->data[$entityName] = $item;
		}
	}

	private function merge($tables)
	{
		$data = array();
		foreach ($tables as $table) {
			foreach ($table as $scope => $row) {
				if ($row == false) {
					if (!isset($data[$scope])) {
						$data[$scope] = false;
					}
				} else {
					if (!isset($data[$scope])) {
						$data[$scope] = array();
					}
					if ($data[$scope] == false) {
						$data[$scope] = array();
					}
					foreach ($row as $action => $level) {
						if (!isset($data[$scope][$action])) {
							$data[$scope][$action] = $level;
						} else {
							if (array_search($data[$scope][$action], $this->levelList) > array_search($level, $this->levelList)) {
								$data[$scope][$action] = $level;
							}
						}
					}
				}
			}
		}
		return $data;
	}

	private function buildCache()
	{
		$contents = '<' . '?'. 'php return ' .  var_export($this->data, true)  . ';';
		$this->fileManager->putContents($this->cacheFile, $contents);
	}
}

