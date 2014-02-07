<?php

namespace Espo\Modules\Crm\Jobs;

use \Espo\Core\Exceptions;

class CheckInboundEmails extends \Espo\Core\Jobs\Base
{
	public function run()
	{	
		$service = $this->getServiceFactory()->create('InboundEmail');
		
		$collection = $this->getEntityManager()->getRepository('InboundEmail')->find();
		foreach ($collection as $entity) {
			$service->fetchFromMailServer($entity->id);
		}
	}	
}

