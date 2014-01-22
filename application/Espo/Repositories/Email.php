<?php

namespace Espo\Repositories;

use Espo\ORM\Entity;

class Email extends \Espo\Core\ORM\Repository
{	
	protected function prepareAddressess(Entity $entity, $type)
	{
		$eaRepositoty = $this->getEntityManager()->getRepository('EmailAddress');
		
		$address = $entity->get($type);		
		$ids = array();
		if (!empty($address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
			$arr = array_map(function ($e) {
				return trim($e);
			}, explode(';', $address));
			
			$ids = $eaRepositoty->getIds($arr);
		} 
		$entity->set($type . 'EmailAddressesIds', $ids);
	}
	
	protected function beforeSave(Entity $entity)
	{
		$eaRepositoty = $this->getEntityManager()->getRepository('EmailAddress');
		
		$from = trim($entity->get('from'));		
		if (!empty($from)) {
			$ids = $eaRepositoty->getIds(array($from));		
			if (!empty($ids)) {
				$entity->set('fromEmailAddressId', $ids[0]);
			}
		} else {
			$entity->set('fromEmailAddressId', null);
		}
		
		$this->prepareAddressess($entity, 'to');
		$this->prepareAddressess($entity, 'cc');
		$this->prepareAddressess($entity, 'bcc');
		
		parent::beforeSave($entity);
	}
}

