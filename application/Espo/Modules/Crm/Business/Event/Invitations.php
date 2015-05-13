<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

        $contents = str_replace('{eventType}', strtolower($this->language->translate($entity->getEntityName(), 'scopeNames')), $contents);

        foreach ($entity->getFields() as $field => $d) {
            if (empty($d['type'])) continue;
            $key = '{'.$field.'}';
            switch ($d['type']) {
                case 'datetime':
                    $contents = str_replace($key, $this->dateTime->convertSystemDateTimeToGlobal($entity->get($field)), $contents);
                    break;
                case 'date':
                    $contents = str_replace($key, $this->dateTime->convertSystemDateToGlobal($entity->get($field)), $contents);
                    break;
                case 'jsonArray':
                    break;
                case 'jsonObject':
                    break;
                default:
                    $contents = str_replace($key, $entity->get($field), $contents);
            }
        }

        if ($invitee) {
            $contents = str_replace('{inviteeName}', $invitee->get('name'), $contents);
        }

        $siteUrl = rtrim($this->config->get('siteUrl'), '/');

        $url = $siteUrl . '/#' . $entity->getEntityName() . '/view/' . $entity->id;
        $contents = str_replace('{url}', $url, $contents);

        if ($invitee && $invitee->getEntityName() != 'User') {
            $contents = preg_replace('/\{#userOnly\}(.*?)\{\/userOnly\}/s', '', $contents);
        }

        $contents = str_replace('{#userOnly}', '', $contents);
        $contents = str_replace('{/userOnly}', '', $contents);

        if ($uid) {
            $contents = str_replace('{acceptLink}', $siteUrl . '?entryPoint=eventConfirmation&action=accept&uid=' . $uid->get('name'), $contents);
            $contents = str_replace('{declineLink}', $siteUrl . '?entryPoint=eventConfirmation&action=decline&uid=' . $uid->get('name'), $contents);
            $contents = str_replace('{tentativeLink}', $siteUrl . '?entryPoint=eventConfirmation&action=tentativeLink&uid=' . $uid->get('name'), $contents);
        }
        return $contents;
    }

    protected function getTemplate($name)
    {
        $systemLanguage = $this->config->get('language');

        $fileName = 'custom/Espo/Custom/Resources/templates/'.$name.'.'.$systemLanguage.'.tpl';
        if (!file_exists($fileName)) {
            $fileName = 'application/Espo/Modules/Crm/Resources/templates/'.$name.'.'.$systemLanguage.'.tpl';
        }
        if (!file_exists($fileName)) {
            $fileName = 'custom/Espo/Custom/Resources/templates/'.$name.'.en_US.tpl';
        }
        if (!file_exists($fileName)) {
            $fileName = 'application/Espo/Modules/Crm/Resources/templates/'.$name.'.en_US.tpl';
        }

        return file_get_contents($fileName);
    }

    public function sendInvitation(Entity $entity, Entity $invitee, $link)
    {
        $uid = $this->getEntityManager()->getEntity('UniqueId');
        $uid->set('data', array(
            'eventType' => $entity->getEntityName(),
            'eventId' => $entity->id,
            'inviteeId' => $invitee->id,
            'inviteeType' => $invitee->getEntityName(),
            'link' => $link
        ));
        $this->getEntityManager()->saveEntity($uid);

        $emailAddress = $invitee->get('emailAddress');
        if (empty($emailAddress)) {
            return;
        }

        $email = $this->getEntityManager()->getEntity('Email');
        $email->set('to', $emailAddress);

        $subjectTpl = $this->getTemplate('InvitationSubject');
        $bodyTpl = $this->getTemplate('InvitationBody');

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

