<?php

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;

class Email extends Record
{
	
	protected function init()
	{
		$this->dependencies[] = 'mailSender';
	}
	
	protected function getMailSender()
	{
		return $this->injections['mailSender'];
	}
	
	public function createEntity(Entity $data)
	{
		$entity = parent::createEntity($data);
		
		if ($entity && $entity->get('status') == 'Sending') {
			$sent = $this->getMailSender()->send($entity);
			if ($sent) {
				$this->getEntityManager()->saveEntity($entity);
			} else {
				throw new Error();
			}
		}
		
		return $result;
	}
		
	public function getEntity($id = null)
	{
		$entity = parent::getEntity($id);
		if (!empty($id)) {
			
			if ($entity->get('fromEmailAddressName')) {
				$entity->set('from', $entity->get('fromEmailAddressName'));
			}
		
			$entity->loadLinkMultipleField('toEmailAddresses');
			$entity->loadLinkMultipleField('ccEmailAddresses');
			$entity->loadLinkMultipleField('bccEmailAddresses');
			
			$names = $entity->get('toEmailAddressesNames');			
			if (!empty($names)) {
				$arr = array();
				foreach ($names as $id => $address) {
					$arr[] = $address;
				}
				$entity->set('to', implode('; ', $arr)); 
			}
			
			$names = $entity->get('ccEmailAddressesNames');			
			if (!empty($names)) {
				$arr = array();
				foreach ($names as $id => $address) {
					$arr[] = $address;
				}
				$entity->set('cc', implode('; ', $arr)); 
			}
			
			$names = $entity->get('bccEmailAddressesNames');			
			if (!empty($names)) {
				$arr = array();
				foreach ($names as $id => $address) {
					$arr[] = $address;
				}
				$entity->set('bcc', implode('; ', $arr)); 
			}			
			
		}
		return $entity;
	}
}

