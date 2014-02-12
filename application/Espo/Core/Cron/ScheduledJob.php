<?php

namespace Espo\Core\Cron;

use Espo\Core\Exceptions\NotFound,
	Espo\Core\Utils\Util;

class ScheduledJob
{
	private $container;

	protected $data = null;

	protected $cacheFile = 'data/cache/application/jobs.php';

	protected $allowedMethod = 'run';

	/**
     * @var array - path to cron job files
     */
	private $paths = array(
		'corePath' => 'application/Espo/Jobs',
    	'modulePath' => 'application/Espo/Modules/{*}/Jobs',
    	'customPath' => 'application/Espo/Custom/Jobs',	                              			
	);


	public function __construct(\Espo\Core\Container $container)
	{
		$this->container = $container;
	}

	protected function getContainer()
	{
		return $this->container;
	}

	protected function getEntityManager()
	{
		return $this->container->get('entityManager');
	}




	public function run(array $job)
	{
		$jobName = $job['method'];

		$className = $this->getClassName($jobName);
		if ($className === false) {
			throw new NotFound(); 
		}

		$jobClass = new $className($this->container);
		$method = $this->allowedMethod;

		$jobClass->$method();		
	}


	protected function getClassName($name)
	{
		$name = Util::normilizeClassName($name);
		
		if (!isset($this->data)) {
			$this->init();
		}

		$name = ucfirst($name);
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}
		
        return false; 
	}

	/**
	 * Load scheduler classes. It loads from ...Jobs, ex. \Espo\Jobs
	 * @return null
	 */
	protected function init()
	{
		$classParser = $this->getContainer()->get('classParser');
		$classParser->setAllowedMethods( array($this->allowedMethod) );
		$this->data = $classParser->getData($this->paths, $this->cacheFile);
	}	

}