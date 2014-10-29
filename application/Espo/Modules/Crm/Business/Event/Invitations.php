<?php

namespace Espo\Modules\Crm\Business\Event;

use Espo\Core\Mail\Sender;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\Language;
use Espo\Entities\Email;
use Espo\Entities\UniqueId;
use Espo\Entities\User;
use Espo\ORM\Entity;

class Invitations
{

    protected $entityManager;

    /**
     * @var Sender
     * @since 1.0
     */
    protected $mailSender;

    /**
     * @var Config
     * @since 1.0
     */
    protected $config;

    /**
     * @var DateTime
     * @since 1.0
     */
    protected $dateTime;

    /**
     * @var Language
     * @since 1.0
     */
    protected $language;

    protected $fileManager;

    protected $ics;

    public function __construct($entityManager, $mailSender, $config, $dateTime, $language, $fileManager)
    {
        $this->entityManager = $entityManager;
        $this->mailSender = $mailSender;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->language = $language;
        $this->fileManager = $fileManager;
    }

    public function sendInvitation(Entity $entity, Entity $invitee, $link)
    {
        /**
         * @var Email $email
         */
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
        $subjectTplFileName = 'custom/Espo/Custom/Resources/templates/InvitationSubject.' . $systemLanguage . '.tpl';
        if (!file_exists($subjectTplFileName)) {
            $subjectTplFileName = 'application/Espo/Modules/Crm/Resources/templates/InvitationSubject.' . $systemLanguage . '.tpl';
        }
        if (!file_exists($subjectTplFileName)) {
            $subjectTplFileName = 'custom/Espo/Custom/Resources/templates/InvitationSubject.en_US.tpl';
        }
        if (!file_exists($subjectTplFileName)) {
            $subjectTplFileName = 'application/Espo/Modules/Crm/Resources/templates/InvitationSubject.en_US.tpl';
        }
        $subjectTpl = file_get_contents($subjectTplFileName);
        $bodyTplFileName = 'custom/Espo/Custom/Resources/templates/InvitationBody.' . $systemLanguage . '.tpl';
        if (!file_exists($bodyTplFileName)) {
            $bodyTplFileName = 'application/Espo/Modules/Crm/Resources/templates/InvitationBody.' . $systemLanguage . '.tpl';
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
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set(array(
            'name' => 'event.ics',
            'type' => 'text/calendar',
            'contents' => $this->getIscContents($entity),
        ));
        $email->addAttachment($attachment);
        $emailSender = $this->mailSender;
        $emailSender->send($email);
        $this->getEntityManager()->removeEntity($email);
    }

    /**
     * @return EntityManager
     * @since 1.0
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param        $contents
     * @param Entity $entity
     * @param Entity   $invitee
     * @param UniqueId   $uid
     *
     * @return mixed
     * @since 1.0
     */
    protected function parseInvitationTemplate($contents, $entity, $invitee = null, $uid = null)
    {
        $contents = str_replace('{name}', $entity->get('name'), $contents);
        $contents = str_replace('{eventType}',
            strtolower($this->language->translate($entity->getEntityName(), 'scopeNames')), $contents);
        $contents = str_replace('{dateStart}',
            $this->dateTime->convertSystemDateTimeToGlobal($entity->get('dateStart')), $contents);
        if ($invitee) {
            $contents = str_replace('{inviteeName}', $invitee->get('name'), $contents);
        }
        if ($uid) {
            $siteUrl = rtrim($this->config->get('siteUrl'), '/');
            $contents = str_replace('{acceptLink}',
                $siteUrl . '?entryPoint=eventConfirmation&action=accept&uid=' . $uid->get('name'), $contents);
            $contents = str_replace('{declineLink}',
                $siteUrl . '?entryPoint=eventConfirmation&action=decline&uid=' . $uid->get('name'), $contents);
        }
        return $contents;
    }

    protected function getIscContents(Entity $entity)
    {
        /**
         * @var User $user
         */
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

