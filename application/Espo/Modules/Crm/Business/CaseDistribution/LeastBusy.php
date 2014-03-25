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

class LeastBusy
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
				
		$countHash = array();	
		
		foreach ($userList as $user) {
			$count = $this->getEntityManager()->getRepository('Case')->where(array(
				'assignedUserId' => $user->id,
				'status<>' => array('Closed', 'Rejected', 'Duplicated')
			))->count();
			$countHash[$user->id] = $count;		
		}
		
		$foundUserId = false;
		$min = false;
		foreach ($countHash as $userId => $count) {
			if ($min === false) {
				$min = $count;
				$foundUserId = $userId;
			} else {
				if ($count < $min) {
					$min = $clunt;
					$foundUserId = $userId;
				}
			}			
		}
		
		if ($foundUserId !== false) {						
			return $this->getEntityManager()->getEntity('User', $foundUserId);
		}				
	}
}

