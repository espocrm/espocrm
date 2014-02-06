<?php

namespace Espo\Core;

class CronManager
{
	private $container;	
	private $config;
	private $fileManager;

	private $scheduledJobCron;
	private $serviceCron;

	private $jobFactory;
	private $scheduledJobFactory;

	protected $lastRunTime = 'data/cache/application/cronLastRunTime.php';	


	public function __construct(\Espo\Core\Container $container)
	{		
		$this->container = $container;

		$this->config = $this->container->get('config');		
		$this->fileManager = $this->container->get('fileManager');	

		$this->scheduledJobCron = new \Espo\Core\Cron\ScheduledJob( $this->container );	
		$this->serviceCron = new \Espo\Core\Cron\Service( $this->container->get('serviceFactory') );	

		$this->jobFactory = $this->container->get('serviceFactory')->create('job');
		$this->scheduledJobFactory = $this->container->get('serviceFactory')->create('scheduledJob');
	}

	protected function getContainer()
	{
		return $this->container;
	}

	protected function getConfig()
	{
		return $this->config;
	}

	protected function getFileManager()
	{
		return $this->fileManager;
	}

	protected function getJobFactory()
	{
		return $this->jobFactory;
	}

	protected function getScheduledJobFactory()
	{
		return $this->scheduledJobFactory;
	}

	protected function getScheduledJobCron()
	{
		return $this->scheduledJobCron;
	}

	protected function getServiceCron()
	{
		return $this->serviceCron;
	}


	protected function getLastRunTime()
	{
		$lastRunTime = $this->getFileManager()->getContent($this->lastRunTime);
		if (!is_int($lastRunTime)) {
			$lastRunTime = time() - (intval($this->getConfig()->get('cron.minExecutionTime')) + 60);	
		}

		return $lastRunTime;
	}

	protected function setLastRunTime($time) 
	{
		return $this->getFileManager()->setContentPHP($time, $this->lastRunTime);
	}

	protected function checkLastRunTime()
	{
		$currentTime = time();
		$lastRunTime = $this->getLastRunTime();
		$minTime = $this->getConfig()->get('cron.minExecutionTime');

		if ($currentTime > ($lastRunTime + $minTime) ) {
			return true;
		}

		return false;
	}


	public function run()
	{
		if (!$this->checkLastRunTime()) {
			$GLOBALS['log']->add('INFO', 'Cron Manager: Stop cron running, too frequency execution');
			return; //stop cron running, too frequency execution 
		}

		$this->setLastRunTime(time());

		//Check scheduled jobs and create related jobs 
		$this->createJobsFromScheduledJobs();


		$pendingJobs = $this->getJobFactory()->getPendingJobs();		

		foreach ($pendingJobs as $job) {

			$this->getJobFactory()->updateEntity($job['id'], array(
				'status' => 'Running',
			));				

			$isSuccess = true;

			try {
				if (!empty($job['scheduled_job_id'])) {
					$this->getScheduledJobCron()->run($job); 
				} else {
					$this->getServiceCron()->run($job);
				}	
			} catch (\Exception $e) {
				$isSuccess = false;
				$GLOBALS['log']->add('INFO', 'Failed job running, job ['.$job['id'].']. Error Details: '.$e->getMessage());
			}					

			$status = $isSuccess ? 'Success' : 'Failed';

			$this->getJobFactory()->updateEntity($job['id'], array(
				'status' => $status,
			));

			//set status in the schedulerJobLog
			if (!empty($job['scheduled_job_id'])) {
				$this->getScheduledJobFactory()->addLogRecord($job['scheduled_job_id'], $status); 
			}						
		}	

	}

	/**
	 * Check scheduled jobs and create related jobs 
	 * @return array List of created Jobs
	 */
	protected function createJobsFromScheduledJobs()
	{
		$activeScheduledJobs = $this->getScheduledJobFactory()->getActiveJobs(); 

		$createdJobs = array();
		foreach ($activeScheduledJobs as $scheduledJob) {
						
			$scheduling = $scheduledJob['scheduling'];			

			$cronExpression = \Cron\CronExpression::factory($scheduling);

			try {
				//$nextDate = $cronExpression->getNextRunDate()->format('Y-m-d H:i:s');
				$prevDate = $cronExpression->getPreviousRunDate()->format('Y-m-d H:i:s');		
			} catch (\Exception $e) {
				$GLOBALS['log']->add('Exception', 'ScheduledJob ['.$scheduledJob['id'].']: CronExpression - Impossible CRON expression ['.$scheduling.']');
				continue;
			}					
			
			if ($cronExpression->isDue()) {
				$prevDate = date('Y-m-d H:i:00');		
			}			

			$existsJob = $this->getJobFactory()->getJobByScheduledJob($scheduledJob['id'], $prevDate);					

			if (!isset($existsJob) || empty($existsJob)) {
				//create a job
				$data = array(
					'name' => $scheduledJob['name'],
					'status' => 'Pending',
					'scheduledJobId' => $scheduledJob['id'],
					'executeTime' => $prevDate,
					'method' => $scheduledJob['job'],
				);
				$createdJobs[] = $this->getJobFactory()->createEntity($data);					
			}			
		}	

		return $createdJobs;	
	}

	
}