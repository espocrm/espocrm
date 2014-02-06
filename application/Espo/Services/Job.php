<?php

namespace Espo\Services;

use \PDO;

class Job extends Record
{	

	public function getPendingJobs()
	{
		$jobConfigs = (array) $this->getConfig()->get('cron');				

		$currentTime = time();
		$periodTime = $currentTime - intval($jobConfigs['jobPeriod']);
		$limit = empty($jobConfigs['maxJobNumber']) ? '' : 'LIMIT '.$jobConfigs['maxJobNumber'];
					
		$query = "SELECT * FROM job WHERE
					`status` = 'Pending' 
					AND execute_time BETWEEN '".date('Y-m-d H:i:s', $periodTime)."' AND '".date('Y-m-d H:i:s', $currentTime)."' 
					ORDER BY execute_time DESC ".$limit;	

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

	public function getJobByScheduledJob($scheduledJobId, $date) 
	{
		$query = "SELECT * FROM job WHERE
					scheduled_job_id = '".$scheduledJobId."'
					AND execute_time = '".$date."'
					LIMIT 1";	

		$pdo = $this->getEntityManager()->getPDO();
		$sth = $pdo->prepare($query);		
		$sth->execute();

		$scheduledJob = $sth->fetchAll(PDO::FETCH_ASSOC);

		return $scheduledJob;
	}



	//todo remove, used for tests
	public function testMethod($data)
	{		
			    
	}	

	//todo remove, used for tests
	public function testFailed($data)
	{		
		throw new \Espo\Core\Exceptions\Error();   
	}
	
	
}

