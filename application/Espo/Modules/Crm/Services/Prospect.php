<?php

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class Prospect extends \Espo\Services\Record
{
	public function convert($id)
	{
    	$entityManager = $this->getEntityManager();    	
    	$prospect = $this->getEntity($id);
    	
    	if (!$this->getAcl()->check($prospect, 'delete')) {
    		throw new Forbidden();
    	}
    	if (!$this->getAcl()->check('Lead', 'read')) {
    		throw new Forbidden();
    	} 	
    	
    	$lead = $entityManager->getEntity('Lead');    	
    	$lead->set($prospect->toArray());		
		
		$entityManager->removeEntity($prospect);
    	$entityManager->saveEntity($lead);

    	return $lead;
	}
}

