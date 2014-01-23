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
	
	public function createEntity($data)
	{
		$entity = parent::createEntity($data);
		
		if ($entity && $entity->get('status') == 'Sending') {
			$emailSender = $this->getMailSender();
			
			if (strtolower($this->getUser()->get('emailAddress')) == strtolower($entity->get('from'))) {			
				//$emailSender->useSmtp(); // TODO use smtp
			} else {
				if (!$this->getConfig()->get('outboundEmailIsShared')) {
					throw new Error('Can not use system smtp. outboundEmailIsShared is false.');					
				}
			}
			
			$emailSender->send($entity);		
			
			$this->getEntityManager()->saveEntity($entity);
		}
		
		return $entity;
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

