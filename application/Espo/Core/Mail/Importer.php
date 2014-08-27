<?php

namespace Espo\Core\Mail;

use \Zend\Mime\Mime as Mime;

class Importer
{	
	private $entityManager;
	
	public function __construct($entityManager)
	{
		$this->entityManager = $entityManager;
	}
	
	protected function getEntityManager()
	{
		return $this->entityManager;
	}
	
	public function importMessage($message, $userId, $teamsIds = array())
	{
		try {
			$email = $this->getEntityManager()->getEntity('Email');
			
			$email->set('isHtml', false);		
			$email->set('name', $message->subject);
			$email->set('status', 'Archived');
			$email->set('attachmentsIds', array());			
			$email->set('assignedUserId', $userId);
			$email->set('teamsIds', $teamsIds);
			
			$fromArr = $this->getAddressListFromMessage($message, 'from');			
			if (isset($message->from)) {
				$email->set('fromName', $message->from);
			}			
			$email->set('from', $fromArr[0]);
			$email->set('to', implode(';', $this->getAddressListFromMessage($message, 'to')));		
			$email->set('cc', implode(';', $this->getAddressListFromMessage($message, 'cc')));
			
			if (isset($message->messageId) && !empty($message->messageId)) { 
				$email->set('messageId', $message->messageId);
				if (isset($message->deliveredTo)) {
					$email->set('messageIdInternal', $message->messageId . '-' . $message->deliveredTo);
				}
			}
			
			if ($this->checkIsDuplicate($email)) {
				return false;
			}			

			$dt = new \DateTime($message->date);
			if ($dt) {
				$dateSent = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');		
				$email->set('dateSent', $dateSent);
			}
			
			$inlineIds = array();
	
			if ($message->isMultipart()) {				
				foreach (new \RecursiveIteratorIterator($message) as $part) {
					$this->importPartDataToEmail($email, $part, $inlineIds);
				}			
			} else {
				$this->importPartDataToEmail($email, $message, $inlineIds);
			}
			
			$body = $email->get('body');
			if (!empty($body)) {
				foreach ($inlineIds as $cid => $attachmentId) {
					$body = str_replace('cid:' . $cid, '?entryPoint=attachment&amp;id=' . $attachmentId, $body);
				}
				$email->set('body', $body);
			}

			$this->getEntityManager()->saveEntity($email);
			
			return $email;
					
		} catch (\Exception $e) {}
	}
	
	protected function checkIsDuplicate($email)
	{
		if ($email->get('messageIdInternal')) {
			$duplicate = $this->getEntityManager()->getRepository('Email')->where(array(
				'messageIdInternal' => $email->get('messageIdInternal')
			))->findOne();
			if ($duplicate) {
				return true;
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
	
	protected function importPartDataToEmail(\Espo\Entities\Email $email, $part, &$inlineIds = array())
	{		
		try {
			$type = strtok($part->contentType, ';');
			$encoding = null;
			
			// TODO do not import if size exceeds limit
			
			switch ($type) {
				case 'text/plain':					
					$content = $this->getContentFromPart($part);				
					if (!$email->get('body')) {				
						$email->set('body', $content);
					}
					$email->set('bodyPlain', $content);
					break;
				case 'text/html':
					$content = $this->getContentFromPart($part);
					$email->set('body', $content);
					$email->set('isHtml', true);
					break;
				default:
					$content = $part->getContent();					
					$disposition = null;
					
					$fileName = null;
					$contentId = null;
							
					if (isset($part->ContentDisposition)) {				
						if (strpos($part->ContentDisposition, 'attachment') === 0) {
							if (preg_match('/filename="?([^"]+)"?/i', $part->ContentDisposition, $m)) {
								$fileName = $m[1];
								$disposition = 'attachment';
							}							
						} else if (strpos($part->ContentDisposition, 'inline') === 0) {
							$contentId = trim($part->contentID, '<>');
							$fileName = $contentId;
							$disposition = 'inline';
						}						
					}
					
					if (isset($part->contentTransferEncoding)) {
						$encoding = strtolower($part->getHeader('Content-Transfer-Encoding')->getTransferEncoding());
					}
					
					$attachment = $this->getEntityManager()->getEntity('Attachment');
					$attachment->set('name', $fileName);							
					$attachment->set('type', $type);
					$attachment->set('role', 'Inline Attachment');
							
					$this->getEntityManager()->saveEntity($attachment);
												
					$path = 'data/upload/' . $attachment->id;
							
					if ($encoding == 'base64') {
						$content = base64_decode($content);
					}
					// TODO store size
					$this->getFileManager()->putContents($path, $content);
					
					if ($disposition == 'attachment') {
						$attachmentsIds = $email->get('attachmentsIds');
						$attachmentsIds[] = $attachment->id;
						$email->set('attachmentsIds', $attachmentsIds);	
					} else if ($disposition == 'inline') {
						$inlineIds[$contentId] = $attachment->id;
					}		
			}
		} catch (\Exception $e){
			// TODO log	
		}		
	}
	
	protected function getContentFromPart($part)
	{
		if ($part instanceof \Zend\Mime\Part) {
			$content = $part->getRawContent();
			if (strtolower($part->charset) != 'utf-8') {
				$content = mb_convert_encoding($content, 'UTF-8', $part->charset);
			}
		} else {
			$content = $part->getContent();
			
			$encoding = null;
			
			if (isset($part->contentTransferEncoding)) {
				$cteHeader = $part->getHeader('Content-Transfer-Encoding');
				$encoding = strtolower($cteHeader->getTransferEncoding());
			}
			
			if ($encoding == 'base64') {
				$content = base64_decode($content);
			}
			
			$charset = 'UTF-8';			
			
			if (isset($part->contentType)) {
				$ctHeader = $part->getHeader('Content-Type');
				$charsetParamValue = $ctHeader->getParameter('charset');
				if (!empty($charsetParamValue)) {
					$charset = strtoupper($charsetParamValue);
				}
			}
			
			if ($charset !== 'UTF-8') {
				$content = mb_convert_encoding($content, 'UTF-8', $charset);
			}			
			
			if (isset($part->contentTransferEncoding)) {
				$cteHeader = $part->getHeader('Content-Transfer-Encoding');			
				if ($cteHeader->getTransferEncoding() == 'quoted-printable') {					
					$content = quoted_printable_decode($content);
				}
			}			
		}
		return $content;
	}
}
