<?php

namespace Espo\Services;

use \PDO;

class ScheduledJob extends \Espo\Services\Record
{	

	public function getActiveJobs()
	{
		$query = "SELECT * FROM scheduled_job WHERE
					`status` = 'Active'
					AND deleted = 0";	

		$pdo = $this->getEntityManager()->getPDO();
		$sth = $pdo->prepare($query);		
		$sth->execute();

		$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		$list = array();
		foreach ($rows as $row) {
			$list[] = $row;
		}

		return $list;					
	} 

	/**
	 * Add record to ScheduledJobLogRecord about executed job
	 * @param string $scheduledJobId 
	 * @param string $status         
	 *
	 * @return string Id of created ScheduledJobLogRecord
	 */
	public function addLogRecord($scheduledJobId, $status)
	{
		$lastRun = date('Y-m-d H:i:s');

		$entityManager = $this->getEntityManager();

		$scheduledJob = $entityManager->getEntity('ScheduledJob', $scheduledJobId);
		$scheduledJob->set('lastRun', $lastRun);
		$entityManager->saveEntity($scheduledJob);

		$scheduledJobLog = $entityManager->getEntity('ScheduledJobLogRecord');
		$scheduledJobLog->set(array(
			'scheduledJobId' => $scheduledJobId,
			'name' => $scheduledJob->get('name'),
			'status' => $status,
			'executionTime' => $lastRun,
		));
		$scheduledJobLogId = $entityManager->saveEntity($scheduledJobLog);
		//$entityManager->getRepository('ScheduledJobLogRecord')->relate($scheduledJobLog, 'scheduledJob', $scheduledJob);		

		return $scheduledJobLogId;		
	}  
	
	
	
}

