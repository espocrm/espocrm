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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class EmailNotification extends \Espo\Core\Services\Base
{
    protected function init()
    {
        $this->dependencies[] = 'metadata';
        $this->dependencies[] = 'mailSender';
        $this->dependencies[] = 'language';
    }

    protected function getMailSender()
    {
        return $this->getInjection('mailSender');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getLanguage()
    {
        return $this->getInjection('language');
    }

    protected function replaceMessageVariables($text, $entity, $user, $assignerUser)
    {
        $recordUrl = $this->getConfig()->get('siteUrl') . '#' . $entity->getEntityName() . '/view/' . $entity->id;

        $text = str_replace('{userName}', $user->get('name'), $text);
        $text = str_replace('{assignerUserName}', $assignerUser->get('name'), $text);
        $text = str_replace('{recordUrl}', $recordUrl, $text);
        $text = str_replace('{entityType}', $this->getLanguage()->translate($entity->getEntityName(), 'scopeNames'), $text);

        $fields = $entity->getFields();
        foreach ($fields as $field => $d) {
            $text = str_replace('{Entity.' . $field . '}', $entity->get($field), $text);
        }

        return $text;
    }

    public function notifyAboutAssignmentJob($data)
    {
        $userId = $data['userId'];
        $assignerUserId = $data['assignerUserId'];
        $entityId = $data['entityId'];
        $entityType = $data['entityType'];

        $user = $this->getEntityManager()->getEntity('User', $userId);

        $prefs = $this->getEntityManager()->getEntity('Preferences', $userId);

        if (!$prefs) {
            return true;
        }

        if (!$prefs->get('receiveAssignmentEmailNotifications')) {
            return true;
        }

        $assignerUser = $this->getEntityManager()->getEntity('User', $assignerUserId);
        $entity = $this->getEntityManager()->getEntity($entityType, $entityId);

        if ($user && $entity && $assignerUser && $entity->get('assignedUserId') == $userId) {
            $emailAddress = $user->get('emailAddress');
            if (!empty($emailAddress)) {
                $email = $this->getEntityManager()->getEntity('Email');

                $subject = $this->getLanguage()->translate('assignmentEmailNotificationSubject', 'messages', $entity->getEntityName());
                $body = $this->getLanguage()->translate('assignmentEmailNotificationBody', 'messages', $entity->getEntityName());

                $subject = $this->replaceMessageVariables($subject, $entity, $user, $assignerUser);
                $body = $this->replaceMessageVariables($body, $entity, $user, $assignerUser);

                $email->set(array(
                    'subject' => $subject,
                    'body' => $body,
                    'isHtml' => false,
                    'to' => $emailAddress,
                    'isSystem' => true
                ));

                $this->getMailSender()->send($email);
            }
        }

        return true;
    }
}

