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

use \PDO;

class Job extends Record
{	

	public function getPendingJobs()
	{
		$jobConfigs = $this->getConfig()->get('cron');				

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

