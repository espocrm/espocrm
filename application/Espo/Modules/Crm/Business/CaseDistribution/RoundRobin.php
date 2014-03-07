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

namespace Espo\Modules\Crm\Business\CaseDistribution;

class RoundRobin
{
	protected $entityManager;
	
	public function __construct($entityManager)
	{
		$this->entityManager = $entityManager;
	}
	
	protected function getEntityManager()
	{
		return $this->entityManager;
	}
	
	public function getUser($team)
	{
		$userList = $team->get('users');		
		if (count($userList) == 0) {
			return false;
		}	
				
		$userIdList = array();
		
		foreach ($userList as $user) { 
			$userIdList[] = $user->id;
		}
	
		
		$case = $this->getEntityManager()->getRepository('Case')->where(array(
			'assignedUserId' => $userIdList,
		))->order('createdAt', 'DESC')->findOne();				
		
		if (empty($case)) {
			$num = 0;
		} else {		
			$num = array_search($case->get('assignedUserId'), $userIdList);
			if ($num === false || $num == count($userIdList) - 1) {
				$num = 0;
			} else {
				$num++;
			}
		}
				
		return $this->getEntityManager()->getEntity('User', $userIdList[$num]);		
	}
}

