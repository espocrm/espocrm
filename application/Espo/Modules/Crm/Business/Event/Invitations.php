<?php

namespace Espo\Modules\Crm\Business\Event;

use \Espo\ORM\Entity;

class Invitations
{
    protected $entityManager;
    
    protected $smtpParams;

    protected $mailSender;
    
    protected $config;
    
    protected $dateTime;
    
    protected $language;
    
    protected $fileManager;
    
    protected $ics;
    
    public function __construct($entityManager, $smtpParams, $mailSender, $config, $dateTime, $language, $fileManager)
    {
        $this->entityManager = $entityManager;
        $this->smtpParams = $smtpParams;
        $this->mailSender = $mailSender;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->language = $language;
        $this->fileManager = $fileManager;
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

        $emailAddress = $invitee->get('emailAddress');
        if (empty($emailAddress)) {
            return;
        }

        $email = $this->getEntityManager()->getEntity('Email');
        $email->set('to', $emailAddress);
        
        $systemLanguage = $this->config->get('language');

        $subjectTplFileName = 'custom/Espo/Custom/Resources/templates/InvitationSubject.'.$systemLanguage.'.tpl';
        if (!file_exists($subjectTplFileName)) {
            $subjectTplFileName = 'application/Espo/Modules/Crm/Resources/templates/InvitationSubject.'.$systemLanguage.'.tpl';
        } 
        if (!file_exists($subjectTplFileName)) {
            $subjectTplFileName = 'custom/Espo/Custom/Resources/templates/InvitationSubject.en_US.tpl';
        }
        if (!file_exists($subjectTplFileName)) {
            $subjectTplFileName = 'application/Espo/Modules/Crm/Resources/templates/InvitationSubject.en_US.tpl';
        }
        $subjectTpl = file_get_contents($subjectTplFileName);

        $bodyTplFileName = 'custom/Espo/Custom/Resources/templates/InvitationBody.'.$systemLanguage.'.tpl';
        if (!file_exists($bodyTplFileName)) {
            $bodyTplFileName = 'application/Espo/Modules/Crm/Resources/templates/InvitationBody.'.$systemLanguage.'.tpl';
        }
        if (!file_exists($bodyTplFileName)) {
            $bodyTplFileName = 'custom/Espo/Custom/Resources/templates/InvitationBody.en_US.tpl';
        }
        if (!file_exists($bodyTplFileName)) {
            $bodyTplFileName = 'application/Espo/Modules/Crm/Resources/templates/InvitationBody.en_US.tpl';
        }
        $bodyTpl = file_get_contents($bodyTplFileName);

        $subject = $this->parseInvitationTemplate($subjectTpl, $entity, $invitee, $uid);
        $subject = str_replace(array("\n", "\r"), '', $subject);
        
        $body = $this->parseInvitationTemplate($bodyTpl, $entity, $invitee, $uid);

        $email->set('subject', $subject);
        $email->set('body', $body);
        $email->set('isHtml', true);        
        $this->getEntityManager()->saveEntity($email);
        
        $attachmentName = ucwords($this->language->translate($entity->getEntityName(), 'scopeNames')).'.ics';
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set(array(
            'name' => $attachmentName,
            'type' => 'text/calendar',
            'contents' => $this->getIscContents($entity),
        ));
        
        $email->addAttachment($attachment);        

        $emailSender = $this->mailSender;

        if ($this->smtpParams) {
            $emailSender->useSmtp($this->smtpParams);
        }
        $emailSender->send($email);        
        
        $this->getEntityManager()->removeEntity($email);
    }
    
    protected function getIscContents(Entity $entity)
    {
        $user = $entity->get('assignedUser');        
        
        $who = '';
        $email = '';
        if ($user) {
            $who = $user->get('name');
            $email = $user->get('emailAddress');
        }
        
        $ics = new Ics('//EspoCRM//EspoCRM Calendar//EN', array(
            'startDate' => strtotime($entity->get('dateStart')),
            'endDate' => strtotime($entity->get('dateEnd')),
            'uid' => $entity->id,
            'summary' => $entity->get('name'),
            'who' => $who,
            'email' => $email,
            'description' => $entity->get('description'),
        ));
        
        return $ics->get();
    }
    
}

