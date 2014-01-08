<?php

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class Lead extends \Espo\Services\Record
{
	public function convert($id, $recordsData)
	{
    	$lead = $this->getEntity($id);
    	
    	if (!$this->getAcl()->check($lead, 'edit')) {
    		throw new Forbidden();
    	}

    	$entityManager = $this->getEntityManager();

    	if (!empty($recordsData['Account'])) {
    		$account = $entityManager->getEntity('Account');
    		$account->set($recordsData['Account']);
    		$entityManager->saveEntity($account);
    		$lead->set('createdAccountId', $account->id);
    	}
    	if (!empty($recordsData['Opportunity'])) {
    		$opportunity = $entityManager->getEntity('Opportunity');
    		$opportunity->set($recordsData['Opportunity']);
    		if (isset($account)) {
    			$opportunity->set('accountId', $account->id);
    		}
    		$entityManager->saveEntity($opportunity);
    		$lead->set('createdOpportunityId', $opportunity->id);
    	}
    	if (!empty($recordsData['Contact'])) {
    		$contact = $entityManager->getEntity('Contact');
    		$contact->set($recordsData['Contact']);
    		if (isset($account)) {
    			$contact->set('accountId', $account->id);
    		}
    		$entityManager->saveEntity($contact);
    		if (isset($opportunity)) {
    			$entityManager->getRepository('Contact')->relate($contact, 'opportunities', $opportunity);
    		}
    		$lead->set('createdContactId', $contact->id);
    	}

		$lead->set('status', 'Converted');		
    	$entityManager->saveEntity($lead);

    	return $lead;
	}
}

