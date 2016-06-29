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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class EmailNotification extends \Espo\Core\Services\Base
{
    const HOURS_THERSHOLD = 5;

    protected function init()
    {
        $this->addDependencyList([
            'metadata',
            'mailSender',
            'language',
            'dateTime',
            'number',
            'fileManager'
        ]);
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

    protected function getDateTime()
    {
        return $this->getInjection('dateTime');
    }

    protected function getHtmlizer()
    {
        if (empty($this->htmlizer)) {
            $this->htmlizer = new \Espo\Core\Htmlizer\Htmlizer($this->getInjection('fileManager'), $this->getInjection('dateTime'), $this->getInjection('number'), null);
        }
        return $this->htmlizer;
    }

    public function notifyAboutAssignmentJob($data)
    {
        $userId = $data['userId'];
        $assignerUserId = $data['assignerUserId'];
        $entityId = $data['entityId'];
        $entityType = $data['entityType'];

        $user = $this->getEntityManager()->getEntity('User', $userId);

        if ($user->get('isPortalUser')) return;

        $preferences = $this->getEntityManager()->getEntity('Preferences', $userId);
        if (!$preferences) return;
        if (!$preferences->get('receiveAssignmentEmailNotifications')) return;

        $assignerUser = $this->getEntityManager()->getEntity('User', $assignerUserId);
        $entity = $this->getEntityManager()->getEntity($entityType, $entityId);

        if ($user && $entity && $assignerUser && $entity->get('assignedUserId') == $userId) {
            $emailAddress = $user->get('emailAddress');
            if (!empty($emailAddress)) {
                $email = $this->getEntityManager()->getEntity('Email');

                $subjectTpl = $this->getAssignmentTemplate($entity->getEntityType(), 'subject');
                $bodyTpl = $this->getAssignmentTemplate($entity->getEntityType(), 'body');
                $subjectTpl = str_replace(array("\n", "\r"), '', $subjectTpl);

                $recordUrl = rtrim($this->getConfig()->get('siteUrl'), '/') . '/#' . $entity->getEntityType() . '/view/' . $entity->id;

                $data = array(
                    'userName' => $user->get('name'),
                    'assignerUserName' => $assignerUser->get('name'),
                    'recordUrl' => $recordUrl,
                    'entityType' => $this->getLanguage()->translate($entity->getEntityType(), 'scopeNames')
                );
                $data['entityTypeLowerFirst'] = lcfirst($data['entityType']);

                $subject = $this->getHtmlizer()->render($entity, $subjectTpl, 'assignment-email-subject-' . $entity->getEntityType(), $data, true);
                $body = $this->getHtmlizer()->render($entity, $bodyTpl, 'assignment-email-body-' . $entity->getEntityType(), $data, true);

                $email->set(array(
                    'subject' => $subject,
                    'body' => $body,
                    'isHtml' => true,
                    'to' => $emailAddress,
                    'isSystem' => true
                ));
                try {
                    $this->getMailSender()->send($email);
                } catch (\Exception $e) {
                    $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
                }
            }
        }

        return true;
    }

    protected function getAssignmentTemplate($entityType, $name)
    {
        $fileName = $this->getAssignmentTemplateFileName($entityType, $name);
        return file_get_contents($fileName);
    }

    protected function getAssignmentTemplateFileName($entityType, $name)
    {
        $language = $this->getConfig()->get('language');
        $moduleName = $this->getMetadata()->getScopeModuleName($entityType);
        $type = 'assignment';

        $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        if ($moduleName) {
            $fileName = "application/Espo/Modules/{$moduleName}/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
            if (file_exists($fileName)) return $fileName;
        }

        $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        $language = 'en_US';

        $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        if ($moduleName) {
            $fileName = "application/Espo/Modules/{$moduleName}/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
            if (file_exists($fileName)) return $fileName;
        }

        $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$entityType}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$name}.tpl";
        return $fileName;
    }

    protected function getMentionTemplate($name)
    {
        $fileName = $this->getMentionTemplateFileName($name);
        return file_get_contents($fileName);
    }

    protected function getMentionTemplateFileName($name)
    {
        $language = $this->getConfig()->get('language');
        $type = 'mention';

        $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        $language = 'en_US';

        $fileName = "custom/Espo/Custom/Resources/templates/{$type}/{$language}/{$name}.tpl";
        if (file_exists($fileName)) return $fileName;

        $fileName = "application/Espo/Resources/templates/{$type}/{$language}/{$name}.tpl";
        return $fileName;
    }

    public function process()
    {
        $dateTime = new \DateTime();
        $dateTime->modify('-' . self::HOURS_THERSHOLD . ' hours');

        $mentionEmailNotifications = $this->getConfig()->get('mentionEmailNotifications');

        $typeList = [];
        if ($mentionEmailNotifications) {
            $typeList[] = 'MentionInPost';
        }

        if (!$mentionEmailNotifications) return;

        $where = array(
            'createdAt' > $dateTime,
            'read' => false,
            'emailIsProcessed' => false
        );

        $where['type'] = $typeList;

        $notificationList = $this->getEntityManager()->getRepository('Notification')->where($where)->order('createdAt')->find();

        foreach ($notificationList as $notification) {
            $notification->set('emailIsProcessed', true);

            $type = $notification->get('type');

            $methodName = 'processNotification' . ucfirst($type);
            if (method_exists($this, $methodName)) {
                $this->$methodName($notification);
            }

            $this->getEntityManager()->saveEntity($notification);
        }
    }

    public function processNotificationMentionInPost(Entity $notification)
    {
        $userId = $notification->get('userId');

        $user = $this->getEntityManager()->getEntity('User', $userId);

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) return;

        $preferences = $this->getEntityManager()->getEntity('Preferences', $userId);
        if (!$preferences) return;

        if (!$preferences->get('receiveMentionEmailNotifications')) return;

        if ($notification->get('relatedType') !== 'Note' || !$notification->get('relatedId')) return;
        $note = $this->getEntityManager()->getEntity('Note', $notification->get('relatedId'));
        if (!$note) return;

        $post = $note->get('post');
        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $data = array();

        if ($parentId && $parentType) {
            $parent = $this->getEntityManager()->getEntity($parentType, $parentId);
            if (!$parent) return;

            $data['url'] = rtrim($this->getConfig()->get('siteUrl'), '/') . '/#' . $parentType . '/' . $parentId;
            $data['parentName'] = $parent->get('name');
            $data['parentType'] = $parentType;
            $data['parentId'] = $parentId;
        } else {
            $data['url'] = rtrim($this->getConfig()->get('siteUrl'), '/') . '/#Notification';
        }

        $data['userName'] = $note->get('createdByName');

        $data['post'] = $note->get('post');

        $subjectTpl = $this->getMentionTemplate('subject');
        $bodyTpl = $this->getMentionTemplate('body');
        $subjectTpl = str_replace(array("\n", "\r"), '', $subjectTpl);

        $subject = $this->getHtmlizer()->render($note, $subjectTpl, 'mention-email-subject', $data, true);
        $body = $this->getHtmlizer()->render($note, $bodyTpl, 'mention-email-body', $data, true);

        $email = $this->getEntityManager()->getEntity('Email');

        $email->set(array(
            'subject' => $subject,
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true
        ));
        try {
            $this->getMailSender()->send($email);
        } catch (\Exception $e) {
            $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }
}