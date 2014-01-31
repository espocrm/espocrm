<?php

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class InboundEmail extends \Espo\Services\Record
{
	
	
	
	protected function init()
	{
		$this->dependencies[] = 'fileManager';
		$this->dependencies[] = 'mailSender';
	}
	
	protected function getFileManager()
	{
		return $this->injections['fileManager'];
	}
	
	protected function getMailSender()
	{
		return $this->injections['mailSender'];
	}
	
	protected function findFolder($storage, $path)
	{
		$arr = explode('.', $path);
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
		if (empty($trashFolder)) {
			$trashFolder = 'INBOX.Trash';
		}
		try {
			$trash = $this->findFolder($storage, $trashFolder);
		} catch (\Exception $e) {
			throw new Error("No trash folder '{$trashFolder}' found for Inbound Email {$id}");
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
					$storage->moveMessage(1, $trash);
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
			if ($inboundEmail->get('teamId')) {
				$email->set('teamsIds', array($inboundEmail->get('teamId')));
			}
			
			$email->set('isHtml', false);		
			$email->set('name', $message->subject);
			$email->set('attachmentsIds', array());
			
			$userId = $this->getUser()->id;		
			if ($inboundEmail->get('assignToUserId')) {
				$userId = $inboundEmail->get('assignToUserId');
			}
			$email->set('assignedUserId', $userId);			
		
			$fromArr = $this->getAddressListFromMessage($message, 'from');
			
			if (isset($message->from)) {
				$email->set('fromName', $message->from);
			}
			
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

			if ($inboundEmail->get('createCase')) {
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
					$params = array(
						'caseDistribution' => $inboundEmail->get('caseDistribution'),
						'teamId' => $inboundEmail->get('teamId'),
						'userId' => $inboundEmail->get('assignToUserId'),
					);
					$case = $this->emailToCase($email, $params);
					$user = $this->getEntityManager()->getEntity('User', $case->get('assignedUserId'));
					if ($inboundEmail->get('reply')) {
						$this->autoReply($inboundEmail, $email, $case, $user);
					}
				}
			} else {
				if ($inboundEmail->get('reply')) {
					$user = $this->getEntityManager()->getEntity('User', $userId);
					$this->autoReply($inboundEmail, $email, $user);
				}
			}
			
			$result = true;
			
		} catch (\Exception $e){
			// TODO log			
		}
		
		return $result;
	}
	
	protected function assignRoundRobin($case, $team)
	{
		$roundRobin = new \Espo\Modules\Crm\Business\CaseDistribution\RoundRobin($this->getEntityManager());
		$user = $roundRobin->getUser($team);
		if ($user) {
			$case->set('assignedUserId', $user->id);
		}
	}
	
	protected function assignLeastBusy($case, $team)
	{
		$leastBusy = new \Espo\Modules\Crm\Business\CaseDistribution\LeastBusy($this->getEntityManager());
		$user = $leastBusy->getUser($team);
		if ($user) {
			$case->set('assignedUserId', $user->id);
		}
	}
	
	protected function emailToCase(\Espo\Entities\Email $email, array $params = array())
	{
		$case = $this->getEntityManager()->getEntity('Case');		
		$case->populateDefaults();		
		$case->set('name', $email->get('name'));
		$case->set('description', $email->get('bodyPlain'));
		
		$userId = $this->getUser()->id;
		if (!empty($params['userId'])) {
			$userId = $params['userId'];
		}		
		$case->set('assignedUserId', $userId);
		
		$teamId = false;
		if (!empty($params['teamId'])) {
			$teamId = $params['teamId'];
		}		
		if ($teamId) {
			$case->set('teamsIds', array($teamId));
		}
		
		$caseDistribution = 'Direct-Assignment';
		if (!empty($params['caseDistribution'])) {
			$caseDistribution = $params['caseDistribution'];
		}
		
		switch ($caseDistribution) {
			case 'Round-Robin':
				if ($teamId) {
					$team = $this->getEntityManager()->getEntity('Team', $teamId);
					if ($team) {					
						$this->assignRoundRobin($case, $team);
					}
				}
				break;
			case 'Least-Busy':				
				if ($teamId) {
					$team = $this->getEntityManager()->getEntity('Team', $teamId);
					if ($team) {					
						$this->assignLeastBusy($case, $team);
					}
				}
				break;
		}
			
		
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
		
		$case = $this->getEntityManager()->getEntity('Case', $case->id);			
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
	
	protected function autoReply($inboundEmail, $email, $case = null, $user = null)
	{	
		try {
			$replyEmailTemplateId = $inboundEmail->get('replyEmailTemplateId');		
			if ($replyEmailTemplateId) {
				$entityHash = array();
				if ($case) {
					$entityHash['Case'] = $case;
					if ($case->get('contactId')) {
						$contact = $this->getEntityManager()->getEntity('Contact', $case->get('contactId'));
					}
				}
				if (empty($contact)) {
					$contact = $this->getEntityManager()->getEntity('Contact');					
					$contact->set('name', $email->get('fromName')); 
				}
				
				
				$entityHash['Person'] = $contact;
				$entityHash['Contact'] = $contact;
				
				if ($user) {
					$entityHash['User'] = $user;
				}			
					
				$emailTemplateService = $this->getServiceFactory()->create('EmailTemplate');
				
				$replyData = $emailTemplateService->parse($replyEmailTemplateId, array('entityHash' => $entityHash), true);
				
				$reply = $this->getEntityManager()->getEntity('Email');
				$reply->set('to', $email->get('from'));
				$reply->set('subject', $replyData['subject']);
				$reply->set('body', $replyData['body']);
				$reply->set('isHtml', $replyData['isHtml']);
				$reply->set('attachmentsIds', $replyData['attachmentsIds']);
				
				$this->getEntityManager()->saveEntity($reply);
				
				$sender = $this->getMailSender()->useGlobal();				
				$senderParams = array();				
				if ($inboundEmail->get('replyFromAddress')) {
					$senderParams['fromAddress'] = $inboundEmail->get('replyFromAddress');
				}
				if ($inboundEmail->get('replyFromName')) {
					$senderParams['fromName'] = $inboundEmail->get('replyFromName');
				}
				$sender->setParams($senderParams);				
				$sender->send($reply);
				
				foreach ($reply->get('attachments') as $attachment) {
					$this->getEntityManager()->removeEntity($attachment);
				}
				
				$this->getEntityManager()->removeEntity($reply);
			}		
			
		} catch (\Exception $e){
			// TODO log	
		}
	}	
}

