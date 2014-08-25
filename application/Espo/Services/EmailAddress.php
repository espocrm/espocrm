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

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;

class EmailAddress extends Record
{
	public function searchInAddressBook($query, $limit)
	{
		$result = array();		
		
		$where = array(
			array(
				'type' => 'or',
				'value' => array(
					array(
						'type' => 'like',
						'field' => 'name',
						'value' => $query . '%'
					),
					array(
						'type' => 'like',
						'field' => 'emailAddress',
						'value' => $query . '%'
					)
				)
			),
			array(
				'type' => 'notEquals',
				'field' => 'emailAddress',
				'value' => null
			)
		);
		
		$contactService = $this->getServiceFactory()->create('Contact');
		
		$r = $contactService->findEntities(array(
			'where' => $where,
			'maxSize' => $limit,
			'sortBy' => 'name'
		));		
		
		foreach ($r['collection'] as $contact) {
			$contact->loadLinkMultipleField('emailAddress');
			
			$emailAddress = $contact->get('emailAddress');
			
			$result[] = array(
				'emailAddress' => $emailAddress,
				'name' => $contact->get('name'),
				'entityType' => 'Contact'
			);
			

			$c = $contactService->getEntity($contact->id);
			$emailAddressData = $c->get('emailAddressData');
			foreach ($emailAddressData as $d) {
				if ($emailAddress != $d->emailAddress) {
					$emailAddress = $d->emailAddress;
					$result[] = array(
						'emailAddress' => $emailAddress,
						'name' => $contact->get('name'),
						'entityType' => 'Contact'
					);
				}
			}
		}
		
		$leadService = $this->getServiceFactory()->create('Lead');
		
		$r = $leadService->findEntities(array(
			'where' => $where,
			'maxSize' => $limit,
			'sortBy' => 'name'
		));
		foreach ($r['collection'] as $lead) {
			$lead->loadLinkMultipleField('emailAddress');
			
			$emailAddress = $lead->get('emailAddress');
			
			$result[] = array(
				'emailAddress' => $emailAddress,
				'name' => $lead->get('name'),
				'entityType' => 'Lead'
			);			

			$c = $leadService->getEntity($lead->id);
			$emailAddressData = $c->get('emailAddressData');
			foreach ($emailAddressData as $d) {
				if ($emailAddress != $d->emailAddress) {
					$result[] = array(
						'emailAddress' => $emailAddress,
						'name' => $lead->get('name'),
						'entityType' => 'Lead'
					);
					break;
				}
			}
		}
		
		$final = array();
		
		foreach ($result as $r) {
			foreach ($final as $f) {
				if ($f['emailAddress'] == $r['emailAddress'] && $f['name'] == $r['name']) {
					continue 2;
				}
			}
			$final[] = $r;
		}
		
		return $final;
	}
	
}

