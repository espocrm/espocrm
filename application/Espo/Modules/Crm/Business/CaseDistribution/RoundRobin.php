<?php

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

