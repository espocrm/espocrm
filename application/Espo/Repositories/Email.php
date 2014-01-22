<?php

namespace Espo\Repositories;

use Espo\ORM\Entity;

class Email extends \Espo\Core\ORM\Repository
{	
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
		
		$address = $entity->get('to');		
		$ids = array();
		if (!empty($address)) {
			$arr = array_map(function ($e) {
				return trim($e);
			}, explode(';', $address));
			
			$ids = $eaRepositoty->getIds($arr);
		} 
		$entity->set('toEmailAddressesIds', $ids);
		
		parent::beforeSave($entity);		
	}
}

