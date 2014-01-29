<?php

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class InboundEmail extends \Espo\Services\Record
{
	
	protected function init()
	{
		$this->dependencies[] = 'fileManager';
	}
	
	protected function getFileManager()
	{
		return $this->injections['fileManager'];
	}
	
	protected function findFolder($storage, $path)
	{
		$arr = explode('/', $path);
		$pointer = $storage->getFolders();
		foreach ($arr as $folderName) {
			$pointer = $pointer->$folderName;
		}
		return $pointer;
	}
	
	// TODO try catch this in cron
	public function fetchFromMailServer($id)
	{
		$inboundEmail = $this->getEntity($id);
		$inboundEmail->loadLinkMultipleField('teams');
		
		if ($inboundEmail->get('status') != 'Active') {
			throw new Error();
		}
		
		$storage = new \Zend\Mail\Storage\Imap(array(
			'host' => $inboundEmail->get('host'),
			'port' => $inboundEmail->get('port'),
			'user' => $inboundEmail->get('username'),
			'password' => $inboundEmail->get('password'),
		));
		
		if (empty($storage)) {
			throw new Error("Could not connect to IMAP of Inbound Email {$inboundEmail->id}.");
		}
		
		$trash = null;
		$trashFolder = $inboundEmail->get('trashFolder');		
		if ($trashFolder) {
			$trash = $this->findFolder($storage, $trashFolder);
		}

		$monitoredFolders = $inboundEmail->get('monitoredFolders');
		if (empty($monitoredFolders)) {
			$monitoredFolders = 'INBOX';
		}
		
		$monitoredFoldersArr = explode(',', $monitoredFolders);				
		foreach ($monitoredFoldersArr as $path) {
			$toRemove = array();
			$path = trim($path);			
			
			$folder = $this->findFolder($storage, $path);			
			$storage->selectFolder($folder);
			
			foreach ($storage as $number => $message) {
				$this->importMessage($inboundEmail, $message);				
			}
			
			while ($storage->countMessages()) {
				if ($trash) {
					$folder->moveMessage(1, $trash);
				} else {
					$storage->removeMessage(1);
				}
			}

		}	
	}
	
	protected function getAddressListFromMessage($message, $type)
	{
		$addressList = array();
		if (isset($message->$type)) {
			
			$list = $message->getHeader($type)->getAddressList();
			foreach ($list as $address) {
				$addressList[] = $address->getEmail();
			}
		}
		return $addressList;
	}
	
	protected function importMessage($inboundEmail, $message)
	{
		$result = false;
		
		try {
			$email = $this->getEntityManager()->getEntity('Email');
			$email->set('teamsIds', $inboundEmail->get('teamsIds'));
			$email->set('isHtml', false);		
			$email->set('name', $message->subject);
			$email->set('attachmentsIds', array());
		
			$email->set('assignedUserId', $this->getUser()->id);
		
			$fromArr = $this->getAddressListFromMessage($message, 'from');
			$email->set('from', $fromArr[0]);
			$email->set('to', implode(';', $this->getAddressListFromMessage($message, 'to')));		
			$email->set('cc', implode(';', $this->getAddressListFromMessage($message, 'cc')));
			$email->set('bcc', implode(';', $this->getAddressListFromMessage($message, 'bcc')));
		
			$email->set('status', 'Archived');

			$dt = new \DateTime($message->date);
			if ($dt) {
				$dateSent = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');		
				$email->set('dateSent', $dateSent);
			}
	
			if ($message->isMultipart()) {
				foreach (new \RecursiveIteratorIterator($message) as $part) {
					$this->importPartDataToEmail($email, $part);
				}
			
			} else {
				$this->importPartDataToEmail($email, $message);
			}

			$this->getEntityManager()->saveEntity($email);
			echo $email->id ."<br>";
			

			if ($inboundEmail->get('createCase')) {
				// TODO check case exists
				
				if (preg_match('/\[#([0-9]+)[^0-9]*\]/', $email->get('name'), $m)) {
					$caseNumber = $m[1];
					$case = $this->getEntityManager()->getRepository('Case')->where(array(
						'number' => $caseNumber
					))->findOne();
					if ($case) {
						$email->set('parentType', 'Case');
						$email->set('parentId', $case->id);
						$this->getEntityManager()->saveEntity($email);
						$this->getServiceFactory()->create('Stream')->noteEmail($case, $email);
					}
				} else {
					$case = $this->emailToCase($email, $inboundEmail->get('caseDistribution'));
					// TODO auto-reply
				}
			} else {
				// TODO auto-reply
			}
			
			$result = true;
			
		} catch (\Exception $e){
			// TODO log
			
		}
		
		return $result;
	}
	
	protected function emailToCase(\Espo\Entities\Email $email, $caseDistribution = 'Round-Robin')
	{
		$case = $this->getEntityManager()->getEntity('Case');		
		$case->populateDefaults();		
		$case->set('name', $email->get('name'));
		$case->set('description', $email->get('bodyPlain'));		
		$case->set('teamsIds', $email->get('teamsIds'));
		
		// TODO distribution
		$case->set('assignedUserId', $this->getUser()->id);
		
		
		$contact = $this->getEntityManager()->getRepository('Contact')->where(array(
			'EmailAddress.id' => $email->get('fromEmailAddressId')
		))->findOne();
		if ($contact) {
			$case->set('contactId', $contact->id);	
			if ($contact->get('accountId')) {
				$case->set('accountId', $contact->get('accountId'));
			}
		}
		
		$this->getEntityManager()->saveEntity($case);
		
		$email->set('parentType', 'Case');
		$email->set('parentId', $case->id);
		$this->getEntityManager()->saveEntity($email);
	
		return $case;		
	}
	
	protected function importPartDataToEmail(\Espo\Entities\Email $email, $part)
	{		
		try {
			$type = strtok($part->contentType, ';');
			switch ($type) {
				case 'text/plain':
					if (!$email->get('body')) {				
						$email->set('body', $part->getContent());
					}
					$email->set('bodyPlain', $part->getContent());
					break;
				case 'text/html': 
					$email->set('body', $part->getContent());
					$email->set('isHtml', true);
					break;
				default:	
					if (isset($part->ContentDisposition)) {
						if (preg_match('/filename="?([^"]+)"?/i', $part->ContentDisposition, $m)) {
							$fileName = $m[1];
							$attachment = $this->getEntityManager()->getEntity('Attachment');
							$attachment->set('name', $fileName);							
							$attachment->set('type', $type);
							
							$this->getEntityManager()->saveEntity($attachment);
												
							$path = 'data/upload/' . $attachment->id;						
							$content = base64_decode($part->getContent());
							$this->getFileManager()->setContent($content, $path);
							$attachmentsIds = $email->get('attachmentsIds');
							$attachmentsIds[] = $attachment->id;
							$email->set('attachmentsIds', $attachmentsIds);
						}
					}
			}
		} catch (\Exception $e){
			// TODO log	
		}		
	}	
}

