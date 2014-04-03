<?php

namespace Espo\Modules\Crm\Business\Event;

class Invitations
{
	protected $entityManager;
	
	protected $mailSender;
	
	protected $config;
	
	protected $dateTime;
	
	protected $language;	
	
	public function __construct($entityManager, $mailSender, $config, $dateTime, $language)
	{
		$this->entityManager = $entityManager;
		$this->mailSender = $mailSender;
		$this->config = $config;
		$this->dateTime = $dateTime;
		$this->language = $language;
	}
	
	protected function getEntityManager()
	{
		return $this->entityManager;
	}
	
	protected function parseInvitationTemplate($contents, $entity, $invitee = null, $uid = null)
	{
		$contents = str_replace('{name}', $entity->get('name'), $contents);
		$contents = str_replace('{eventType}', strtolower($this->language->translate($entity->getEntityName(), 'scopeNames')), $contents);
		$contents = str_replace('{dateStart}', $this->dateTime->convertSystemDateTimeToGlobal($entity->get('dateStart')), $contents);
		if ($invitee) {
			$contents = str_replace('{inviteeName}', $invitee->get('name'), $contents);
		}
		if ($uid) {
			$siteUrl = rtrim($this->config->get('siteUrl'), '/');
			$contents = str_replace('{acceptLink}', $siteUrl . '?entryPoint=eventConfirmation&action=accept&uid=' . $uid->get('name'), $contents);
			$contents = str_replace('{declineLink}', $siteUrl . '?entryPoint=eventConfirmation&action=decline&uid=' . $uid->get('name'), $contents);
		}
		return $contents;
	}
	
	public function sendInvitation(Entity $entity, Entity $invitee, $link)
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

		$emailSender = $this->mailSender;

		$emailSender->send($email);
	}
	
}

