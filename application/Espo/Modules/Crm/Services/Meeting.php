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

namespace Espo\Modules\Crm\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class Meeting extends \Espo\Services\Record
{
	protected function init()
	{
		$this->dependencies[] = 'mailSender';
		$this->dependencies[] = 'preferences';
		$this->dependencies[] = 'i18n';
		$this->dependencies[] = 'dateTime';
	}
	
	protected function getMailSender()
	{
		return $this->injections['mailSender'];
	}

	protected function getPreferences()
	{
		return $this->injections['preferences'];
	}
	
	protected function getI18n()
	{
		return $this->injections['i18n'];
	}
	
	protected function getDateTime()
	{
		return $this->injections['dateTime'];
	}	
	
	protected function parseInvitationTemplate($contents, $entity, $invitee = null, $uid = null)
	{		
		$contents = str_replace('{name}', $entity->get('name'), $contents);
		$contents = str_replace('{eventType}', strtolower($this->getI18n()->translate($entity->getEntityName(), 'scopeNames')), $contents);		
		$contents = str_replace('{dateStart}', $this->getDateTime()->convertSystemDateTimeToGlobal($entity->get('dateStart')), $contents);
		if ($invitee) {
			$contents = str_replace('{inviteeName}', $invitee->get('name'), $contents);
		}
		if ($uid) {
			$siteUrl = rtrim($this->getConfig()->get('siteUrl'), '/');
			$contents = str_replace('{acceptLink}', $siteUrl . '?entryPoint=eventConfirmation&action=accept&uid=' . $uid->get('name'), $contents);
			$contents = str_replace('{declineLink}', $siteUrl . '?entryPoint=eventConfirmation&action=decline&uid=' . $uid->get('name'), $contents);
		}
		return $contents;
	}
	
	protected function sendInvitation(Entity $entity, Entity $invitee, $link)
	{
		
		$uid = $this->getEntityManager()->getEntity('UniqueId');		
		$uid->set('data', json_encode(array(	
			'eventType' => $entity->getEntityName(),
			'eventId' => $entity->id,
			'inviteeId' => $invitee->id,
			'inviteeType' => $invitee->getEntityName(),
			'link' => $link
		)));
		$this->getEntityManager()->saveEntity($uid);
		
		$email = $this->getEntityManager()->getEntity('Email');
		$email->set('to', $invitee->get('emailAddress'));
						
		$subjectTplFileName = 'custom/Espo/Custom/Resources/templates/InvitationSubject.tpl';
		if (!file_exists($subjectTplFileName)) {
			$subjectTplFileName = 'application/Espo/Modules/Crm/Resources/templates/InvitationSubject.tpl';
		}		
		$subjectTpl = file_get_contents($subjectTplFileName);
		
		$bodyTplFileName = 'custom/Espo/Custom/Resources/templates/InvitationBody.tpl';
		if (!file_exists($bodyTplFileName)) {
			$bodyTplFileName = 'application/Espo/Modules/Crm/Resources/templates/InvitationBody.tpl';
		}		
		$bodyTpl = file_get_contents($bodyTplFileName);
		
		$subject = $this->parseInvitationTemplate($subjectTpl, $entity, $invitee, $uid);
		$body = $this->parseInvitationTemplate($bodyTpl, $entity, $invitee, $uid);
		
		$email->set('subject', $subject);
		$email->set('body', $body);
		$email->set('isHtml', true);
		
		$emailSender = $this->getMailSender();
		
		$emailSender->send($email);		
	}
	
	public function sendInvitations(Entity $entity)
	{	
		$users = $entity->get('users');
		foreach ($users as $user) {
			$this->sendInvitation($entity, $user, 'users');
		}
		
		$contacts = $entity->get('contacts');
		foreach ($contacts as $contact) {
			$this->sendInvitation($entity, $contact, 'contacts');
		}
		
		$leads = $entity->get('leads');
		foreach ($leads as $lead) {
			$this->sendInvitation($entity, $lead, 'leads');
		}
		
		return true;		
	}
	
	protected function storeEntity(Entity $entity)
	{
		$assignedUserId = $entity->get('assignedUserId');
		if ($assignedUserId && $entity->has('usersIds')) {
			$usersIds = $entity->get('usersIds');
			if (!is_array($usersIds)) {
				$usersIds = array();
			}
			if (!in_array($assignedUserId, $usersIds)) {
				$usersIds[] = $assignedUserId;
				$entity->set('usersIds', $usersIds);
				$hash = $entity->get('usersNames');
				if ($hash instanceof \stdClass) {
					$hash->assignedUserId = $entity->get('assignedUserName');
					$entity->set('usersNames', $hash);
				}
			}
		}		
		return parent::storeEntity($entity);
	}
}

