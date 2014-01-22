<?php

namespace Espo\Services;

class Email extends Record
{
	public function getEntity($id = null)
	{
		$entity = parent::getEntity($id);
		if (!empty($id)) {
			
			if ($entity->get('fromEmailAddressName')) {
				$entity->set('from', $entity->get('fromEmailAddressName'));
			}
		
			$entity->loadLinkMultipleField('toEmailAddresses');
			//$entity->loadLinkMultipleField('ccEmailAddresses');
			//$entity->loadLinkMultipleField('bccEmailAddresses');
			
			$names = $entity->get('toEmailAddressesNames');
			if (is_array($names)) {
				$arr = array();
				foreach ($names as $id => $address) {
					$arr[] = $address;
				}
				$entity->set('to', implode(';', $arr)); 
			}
		}
		return $entity;
	}
}

