<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\Crm\Business\Reminder;

use \Espo\ORM\Entity;

use Espo\Core\Utils\Util;

class EmailReminder
{
    protected $entityManager;

    protected $mailSender;

    protected $config;

    protected $dateTime;

    protected $templateFileManager;

    protected $language;

    public function __construct($entityManager, $templateFileManager, $mailSender, $config, $fileManager, $dateTime, $number, $language)
    {
        $this->entityManager = $entityManager;
        $this->mailSender = $mailSender;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->language = $language;
        $this->number = $number;
        $this->fileManager = $fileManager;
        $this->templateFileManager = $templateFileManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getTemplateFileManager()
    {
        return $this->templateFileManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getLanguage()
    {
        return $this->language;
    }

    protected function parseInvitationTemplate($contents, $entity, $user = null)
    {
        $contents = str_replace('{eventType}', mb_strtolower($this->language->translate($entity->getEntityName(), 'scopeNames')), $contents);

        $preferences = $this->getEntityManager()->getEntity('Preferences', $user->id);
        $timezone = $preferences->get('timeZone');

        foreach ($entity->getFields() as $field => $d) {
            if (empty($d['type'])) continue;
            $key = '{'.$field.'}';
            switch ($d['type']) {
                case 'datetime':
                    $contents = str_replace($key, $this->dateTime->convertSystemDateTime($entity->get($field), $timezone), $contents);
                    break;
                case 'date':
                    $contents = str_replace($key, $this->dateTime->convertSystemDate($entity->get($field)), $contents);
                    break;
                default:
                    $contents = str_replace($key, $entity->get($field), $contents);
            }
        }

        if ($user) {
            $contents = str_replace('{userName}', $user->get('name'), $contents);
        }

        $siteUrl = rtrim($this->config->get('siteUrl'), '/');

        $url = $siteUrl . '/#' . $entity->getEntityName() . '/view/' . $entity->id;
        $contents = str_replace('{url}', $url, $contents);

        return $contents;
    }

    public function send(Entity $reminder)
    {
        $user = $this->getEntityManager()->getEntity('User', $reminder->get('userId'));
        $entity = $this->getEntityManager()->getEntity($reminder->get('entityType'), $reminder->get('entityId'));

        if (!$user || !$entity) return;
        $emailAddress = $user->get('emailAddress');
        if (!$emailAddress) return;

        if ($entity->hasLinkMultipleField('users')) {
            $entity->loadLinkMultipleField('users', ['status' => 'acceptanceStatus']);
            $status = $entity->getLinkMultipleColumn('users', 'status', $user->id);
            if ($status === 'Declined') return;
        }

        $email = $this->getEntityManager()->getEntity('Email');
        $email->set('to', $emailAddress);

        $subjectTpl = $this->getTemplateFileManager()->getTemplate('reminder', 'subject', $entity->getEntityType(), 'Crm');
        $bodyTpl = $this->getTemplateFileManager()->getTemplate('reminder', 'body', $entity->getEntityType(), 'Crm');

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $data = [];

        $siteUrl = rtrim($this->getConfig()->get('siteUrl'), '/');
        $recordUrl = $siteUrl . '/#' . $entity->getEntityType() . '/view/' . $entity->id;
        $data['recordUrl'] = $recordUrl;

        $data['entityType'] = $this->getLanguage()->translate($entity->getEntityType(), 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

        if ($user) {
            $data['userName'] = $user->get('name');
        }

        $preferences = $this->getEntityManager()->getEntity('Preferences', $user->id);
        $timezone = $preferences->get('timeZone');
        $dateTime = clone($this->dateTime);
        if ($timezone) {
            $dateTime->setTimezone($timezone);
        }

        $htmlizer = new \Espo\Core\Htmlizer\Htmlizer($this->fileManager, $dateTime, $this->number, null);

        $subject = $htmlizer->render($entity, $subjectTpl, 'reminder-email-subject-' . $entity->getEntityType(), $data, true);
        $body = $htmlizer->render($entity, $bodyTpl, 'reminder-email-body-' . $entity->getEntityType(), $data, false);

        $email->set('subject', $subject);
        $email->set('body', $body);
        $email->set('isHtml', true);

        $emailSender = $this->mailSender;

        $emailSender->send($email);
    }
}
