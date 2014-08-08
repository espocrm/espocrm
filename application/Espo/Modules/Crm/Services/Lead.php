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

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

use \Espo\ORM\Entity;

class Lead extends \Espo\Services\Record
{	
	protected function getDuplicateWhereClause(Entity $entity)
	{
		return array(
			'OR' => array(
				array(
					'firstName' => $entity->get('firstName'),
					'lastName' => $entity->get('lastName'),
				),
				array(
					'emailAddress' => $entity->get('emailAddress'),
				),
			),
		);
	}
	
	public function convert($id, $recordsData)
	{
    	$lead = $this->getEntity($id);
    	
    	if (!$this->getAcl()->check($lead, 'edit')) {
    		throw new Forbidden();
    	}

    	$entityManager = $this->getEntityManager();


    	if (!empty($recordsData->Account)) {    	
    		$account = $entityManager->getEntity('Account');
    		$account->set(get_object_vars($recordsData->Account));
    		$entityManager->saveEntity($account);
    		$lead->set('createdAccountId', $account->id);
    	}
    	if (!empty($recordsData->Opportunity)) {
    		$opportunity = $entityManager->getEntity('Opportunity');
    		$opportunity->set(get_object_vars($recordsData->Opportunity));
    		if (isset($account)) {
    			$opportunity->set('accountId', $account->id);
    		}
    		$entityManager->saveEntity($opportunity);
    		$lead->set('createdOpportunityId', $opportunity->id);
    	}
    	if (!empty($recordsData->Contact)) {
    		$contact = $entityManager->getEntity('Contact');
    		$contact->set(get_object_vars($recordsData->Contact));
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
    	
    	if ($meetings = $lead->get('meetings')) {
    		foreach ($meetings as $meeting) {
    			if (!empty($contact)) {
    				$entityManager->getRepository('Meeting')->relate($meeting, 'contacts', $contact);
    			}    			
    			
    			if (!empty($opportunity)) {
    				$meeting->set('parentId', $opportunity->id);
    				$meeting->set('parentType', 'Opportunity');
    				$entityManager->saveEntity($meeting);
    			} else if (!empty($account)) {
    				$meeting->set('parentId', $account->id);
    				$meeting->set('parentType', 'Account');
    				$entityManager->saveEntity($meeting);
    			}
    		}
    	}
    	if ($calls = $lead->get('calls')) {
    		foreach ($calls as $call) {
    			if (!empty($contact)) {
    				$entityManager->getRepository('Call')->relate($call, 'contacts', $contact);
    			}
    			if (!empty($opportunity)) {
    				$call->set('parentId', $opportunity->id);
    				$call->set('parentType', 'Opportunity');
    				$entityManager->saveEntity($call);
    			} else if (!empty($account)) {
    				$call->set('parentId', $account->id);
    				$call->set('parentType', 'Account');
    				$entityManager->saveEntity($call);
    			}
    		}
    	} 

    	return $lead;
	}
}

