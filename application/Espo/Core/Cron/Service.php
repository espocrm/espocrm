<?php

namespace Espo\Core\Cron;

use Espo\Core\Utils\Json,
	Espo\Core\Exceptions\NotFound;

class Service
{
	private $serviceFactory;

	public function __construct(\Espo\Core\ServiceFactory $serviceFactory)
	{
		$this->serviceFactory = $serviceFactory;
	}

	protected function getServiceFactory()
	{
		return $this->serviceFactory;
	}



	public function run($job)
	{
		$serviceName = $job['service_name'];

		if (!$this->getServiceFactory()->checkExists($serviceName)) {
			throw new NotFound(); 										
		}

		$service = $this->getServiceFactory()->create($serviceName);	
		$serviceMethod = $job['method'];	

		if (!method_exists($service, $serviceMethod)) {
			throw new NotFound();					
		}	

		$data = $job['data'];
		if (Json::isJSON($data)) {
			$data = Json::decode($data, true);
		}

		$service->$serviceMethod($data);		
	}

}