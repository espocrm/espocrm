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
use \Espo\Core\Exceptions\NotFound;

class EmailTemplate extends Record
{

	protected function init()
	{
		$this->dependencies[] = 'fileManager';
	}
	
	protected function getFileManager()
	{
		return $this->injections['fileManager'];
	}
	
	public function parse($id, array $params = array(), $copyAttachments = false)
	{			
		$emailTemplate = $this->getEntity($id);
		if (empty($emailTemplate)) {
			throw new NotFound();
		}
		
		$entityList = array();
		if (!empty($params['entityHash']) && is_array($params['entityHash'])) {
			$entityList = $params['entityHash'];
		}		

		if (!empty($params['emailAddress'])) {
			$emailAddress = $this->getEntityManager()->getRepository('EmailAddress')->where(array(
				'lower' => $params['emailAddress']
			))->findOne();
			

			if (!empty($emailAddress)) {
				$pdo = $this->getEntityManager()->getPDO();
				$sql = "
					SELECT * FROM `entity_email_address`
					WHERE 
						`primary` = 1 AND `deleted` = 0 AND `email_address_id` = " . $pdo->quote($emailAddress->id). "
				";
				$sth = $pdo->prepare($sql);
				$sth->execute();				
				if ($row = $sth->fetch()) {
					if (!empty($row['entity_id'])) {
						$entity = $this->getEntityManager()->getEntity($row['entity_type'], $row['entity_id']);
						if (!empty($entity::$person)) {
							$entityList['Person'] = $entity;
						}
					}
				}
			}
		}
		
		if (!empty($params['parentId']) && !empty($params['parentType'])) {
			$parent = $this->getEntityManager()->getEntity($params['parentType'], $params['parentId']);
			if (!empty($parent)) {
				$entityList[$params['parentType']] = $parent;
				$entityList['Parent'] = $parent;
				
				if (empty($entityList['Person']) && !empty($parent::$person)) {
					$entityList['Person'] = $parent;
				}
			}
		}
		
		$subject = $emailTemplate->get('subject');
		$body = $emailTemplate->get('body');
		
		foreach ($entityList as $type => $entity) {
			$subject = $this->parseText($type, $entity, $subject);
		}
		foreach ($entityList as $type => $entity) {
			$body = $this->parseText($type, $entity, $body);
		}
		
		$attachmentsIds = array();
		
		if ($copyAttachments) {
			$attachmentList = $emailTemplate->get('attachments');		
			if (!empty($attachmentList)) {
				foreach ($attachmentList as $attachment) {
					$clone = $this->getEntityManager()->getEntity('Attachment');
					$data = $attachment->toArray();
					unset($data['parentType']);
					unset($data['parentId']);
					unset($data['id']);
					$clone->set($data);
					$this->getEntityManager()->saveEntity($clone);
						
					$contents = $this->getFileManager()->getContents('data/upload/' . $attachment->id);
					if (empty($contents)) {
						continue;
					}
					$this->getFileManager()->putContents('data/upload/' . $clone->id, $contents);			
			
					$attachmentsIds[] = $clone->id;
				}
			}
		}		
		
		return array(
			'subject' => $subject,
			'body' => $body,
			'attachmentsIds' => $attachmentsIds,
			'isHtml' => $emailTemplate->get('isHtml')
		);		
	}
	
	protected function parseText($type, Entity $entity, $text)
	{		
		$fields = array_keys($entity->getFields());
		foreach ($fields as $field) {
			$text = str_replace('{' . $type . '.' . $field . '}', $entity->get($field), $text);
		}
		return $text;
	}
}

