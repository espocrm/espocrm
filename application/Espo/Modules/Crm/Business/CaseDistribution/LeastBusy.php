<?php

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

