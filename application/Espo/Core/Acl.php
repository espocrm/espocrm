<?php

namespace Espo\Core;

class Acl
{
	private $data = array();

	private $cacheFile;

	private $actionList = array('read', 'edit', 'delete');

	private $levelList = array('all', 'team', 'own', 'no');

	public function __construct(\Espo\Entities\User $user)
	{
		$this->user = $user;

		$this->cacheFile = 'data/cache/acl/' . $user->id;

		if (file_exists($this->cacheFile)) {
			$cached = include $this->cacheFile;
		} else {
			$this->load();
			$this->buildCache();
		}
	}
	
	public function checkScope($subject, $action = null, $isOwner = null, $inTeam = null)
	{
		if (isset($this->data[$scope])) {			
			if ($this->data[$scope] === false) {
				return false;
			}
			if ($this->data[$scope] === true) {
				return true;
			}
			if ($action) {			
				if (isset($this->data[$scope][$action])) {
					$value = $this->data[$scope][$action];
			
					if ($value === 'all' || $value === true) {
						return true;					
					}
			
					if (!$value || $value === 'no') {
						return false;					
					}
				
					if ($isOwner === null) {
						return true;
					}
				
					if ($isOwner) {
						if ($value === 'own' || $value === 'team') {
							return true;
						}
					}
			
					if ($inTeam) {
						if ($value === 'team') {
							return true;
						}
					}
			
					return false;
				}
			}
			return false;
		}
		return true;		
	}

	public function check($subject, $action = null, $isOwner = null, $inTeam = null)
	{	
		if ($this->user->isAdmin()) {
			return true;
		}
		if (is_string($subject)) {
			return $this->checkScope($subject, $action = null, $isOwner = null, $inTeam = null);
		} else {
			$entity = $subject;
			$entityName = ltrim(get_class($entity), '\\');			
			
			return $this->checkScope($entityName, $action, $this->checkIsOwner($entity), $this->checkInTeam($entity));
		}
	}
	
	public function checkIsOwner($entity)
	{
		$userId = $this->user->getId();
		if ($userId === $entity->getAssignedUserId() || $userId === $entity->getCreatedById()) {
			return true;
		}
		return false;
	}
	
	public function checkInTeam($entity)
	{
		$userTeamIds = $this->user->getTeamIds();
		$teamIds = $entity->getTeamIds();
		
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
		file_put_contents($this->cacheFile, $contents);
	}
}

