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

namespace Espo\Core\Mail;

use \Zend\Mime\Mime as Mime;

use \Espo\ORM\Entity;
use \Espo\ORM\Email;

class Importer
{
    private $entityManager;

    private $config;

    private $filtersMatcher;

    private $notificator = null;

    public function __construct($entityManager, $config, $notificator = null)
    {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->filtersMatcher = new FiltersMatcher();
        $this->notificator = $notificator;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getFiltersMatcher()
    {
        return $this->filtersMatcher;
    }

    protected function getNotificator()
    {
        return $this->notificator;
    }

    public function importMessage($parserType = 'ZendMail', $message, $assignedUserId = null, $teamsIdList = [], $userIdList = [], $filterList = [], $fetchOnlyHeader = false, $folderData = null)
    {
        $parser = $message->getParser();
        $parserClassName = '\\Espo\\Core\\Mail\\Parsers\\' . $parserType;

        if (!$parser || get_class($parser) !== $parserClassName) {
            $parser = new $parserClassName($this->getEntityManager());
        }

        $email = $this->getEntityManager()->getEntity('Email');

        $email->set('isBeingImported', true);

        $subject = '';
        if ($parser->checkMessageAttribute($message, 'subject')) {
            $subject = $parser->getMessageAttribute($message, 'subject');
        }
        if (!empty($subject) && is_string($subject)) {
            $subject = trim($subject);
        }
        if ($subject !== '0' && empty($subject)) {
            $subject = '(No Subject)';
        }

        $email->set('isHtml', false);
        $email->set('name', $subject);
        $email->set('status', 'Archived');
        $email->set('attachmentsIds', []);
        if ($assignedUserId) {
            $email->set('assignedUserId', $assignedUserId);
            $email->addLinkMultipleId('assignedUsers', $assignedUserId);
        }
        $email->set('teamsIds', $teamsIdList);

        if (!empty($userIdList)) {
            foreach ($userIdList as $uId) {
                $email->addLinkMultipleId('users', $uId);
            }
        }

        $fromAddressData = $parser->getAddressDataFromMessage($message, 'from');
        if ($fromAddressData) {
            $fromString = ($fromAddressData['name'] ? ($fromAddressData['name'] . ' ') : '') . '<' . $fromAddressData['address'] .'>';
            $email->set('fromString', $fromString);
        }

        $replyToData = $parser->getAddressDataFromMessage($message, 'reply-To');
        if ($replyToData) {
            $replyToString = ($replyToData['name'] ? ($replyToData['name'] . ' ') : '') . '<' . $replyToData['address'] .'>';
            $email->set('replyToString', $replyToString);
        }

        $fromArr = $parser->getAddressListFromMessage($message, 'from');
        $toArr = $parser->getAddressListFromMessage($message, 'to');
        $ccArr = $parser->getAddressListFromMessage($message, 'cc');
        $replyToArr = $parser->getAddressListFromMessage($message, 'reply-To');

        if (count($fromArr)) {
            $email->set('from', $fromArr[0]);
        }
        $email->set('to', implode(';', $toArr));
        $email->set('cc', implode(';', $ccArr));
        $email->set('replyTo', implode(';', $replyToArr));

        $addressNameMap = $parser->getAddressNameMap($message);
        $email->set('addressNameMap', $addressNameMap);

        if ($folderData) {
            foreach ($folderData as $uId => $folderId) {
                $email->setLinkMultipleColumn('users', 'folderId', $uId, $folderId);
            }
        }

        if ($this->getFiltersMatcher()->match($email, $filterList, true)) {
            return false;
        }

        if ($parser->checkMessageAttribute($message, 'message-Id') && $parser->getMessageAttribute($message, 'message-Id')) {
            $messageId = $parser->getMessageMessageId($message);

            $email->set('messageId', $messageId);
            if ($parser->checkMessageAttribute($message, 'delivered-To')) {
                $email->set('messageIdInternal', $messageId . '-' . $parser->getMessageAttribute($message, 'delivered-To'));
            }
            if (stripos($messageId, '@espo-system') !== false) {
                return;
            }
        }

        $duplicate = null;

        if ($duplicate = $this->findDuplicate($email)) {
            if ($duplicate->get('status') != 'Being Imported') {
                $duplicate = $this->getEntityManager()->getEntity('Email', $duplicate->id);
                $this->processDuplicate($duplicate, $assignedUserId, $userIdList, $folderData, $teamsIdList);
                return $duplicate;
            }
        }

        if ($parser->checkMessageAttribute($message, 'date')) {
            try {
                $dt = new \DateTime($parser->getMessageAttribute($message, 'date'));
                if ($dt) {
                    $dateSent = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    $email->set('dateSent', $dateSent);
                }
            } catch (\Exception $e) {}
        } else {
            $email->set('dateSent', date('Y-m-d H:i:s'));
        }
        if ($parser->checkMessageAttribute($message, 'delivery-Date')) {
            try {
                $dt = new \DateTime($parser->getMessageAttribute($message, 'delivery-Date'));
                if ($dt) {
                    $deliveryDate = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    $email->set('delivery-Date', $deliveryDate);
                }
            } catch (\Exception $e) {}
        }

        $inlineAttachmentList = [];

        if (!$fetchOnlyHeader) {
            $parser->fetchContentParts($email, $message, $inlineAttachmentList);

            if ($this->getFiltersMatcher()->match($email, $filterList)) {
                return false;
            }
        } else {
            $email->set('body', 'Not fetched. The email size exceeds the limit.');
            $email->set('isHtml', false);
        }

        $parentFound = false;

        $replied = null;

        if ($parser->checkMessageAttribute($message, 'in-Reply-To') && $parser->getMessageAttribute($message, 'in-Reply-To')) {
            $arr = explode(' ', $parser->getMessageAttribute($message, 'in-Reply-To'));
            $inReplyTo = $arr[0];
            $replied = $this->getEntityManager()->getRepository('Email')->where(array(
                'messageId' => $inReplyTo
            ))->findOne();
            if ($replied) {
                $email->set('repliedId', $replied->id);
                $repliedTeamIdList = $replied->getLinkMultipleIdList('teams');
                foreach ($repliedTeamIdList as $repliedTeamId) {
                    $email->addLinkMultipleId('teams', $repliedTeamId);
                }
            }
        }

        if ($parser->checkMessageAttribute($message, 'references') && $parser->getMessageAttribute($message, 'references')) {
            $arr = explode(' ', $parser->getMessageAttribute($message, 'references'));
            $reference = $arr[0];
            $reference = str_replace(array('/', '@'), " ", trim($reference, '<>'));
            $parentType = $parentId = null;
            $emailSent = PHP_INT_MAX;
            $number = null;
            $n = sscanf($reference, '%s %s %d %d espo', $parentType, $parentId, $emailSent, $number);
            if ($n != 4) {
                $n = sscanf($reference, '%s %s %d %d espo-system', $parentType, $parentId, $emailSent, $number);
            }
            if ($n == 4 && $emailSent < time()) {
                if (!empty($parentType) && !empty($parentId)) {
                    if ($parentType == 'Lead') {
                        $parent = $this->getEntityManager()->getEntity('Lead', $parentId);
                        if ($parent && $parent->get('status') == 'Converted') {
                            if ($parent->get('createdAccountId')) {
                                $account = $this->getEntityManager()->getEntity('Account', $parent->get('createdAccountId'));
                                if ($account) {
                                    $parentType = 'Account';
                                    $parentId = $account->id;
                                }
                            } else {
                                if ($this->getConfig()->get('b2cMode')) {
                                    if ($parent->get('createdContactId')) {
                                        $contact = $this->getEntityManager()->getEntity('Contact', $parent->get('createdContactId'));
                                        if ($contact) {
                                            $parentType = 'Contact';
                                            $parentId = $contact->id;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $email->set('parentType', $parentType);
                    $email->set('parentId', $parentId);
                    $parentFound = true;
                }
            }
        }

        if (!$parentFound) {
            if ($replied && $replied->get('parentId') && $replied->get('parentType')) {
                $parentFound = $this->getEntityManager()->getEntity($replied->get('parentType'), $replied->get('parentId'));
                if ($parentFound) {
                    $email->set('parentType', $replied->get('parentType'));
                    $email->set('parentId', $replied->get('parentId'));
                }
            }
        }
        if (!$parentFound) {
            $from = $email->get('from');
            if ($from) {
                $parentFound = $this->findParent($email, $from);
            }
        }
        if (!$parentFound) {
            if (!empty($replyToArr)) {
                $parentFound = $this->findParent($email, $replyToArr[0]);
            }
        }
        if (!$parentFound) {
            if (!empty($toArr)) {
                $parentFound = $this->findParent($email, $toArr[0]);
            }
        }

        if (!$duplicate) {
            $this->lockEmailTable();
            if ($duplicate = $this->findDuplicate($email)) {
                $this->unlockTables();
                if ($duplicate->get('status') != 'Being Imported') {
                    $duplicate = $this->getEntityManager()->getEntity('Email', $duplicate->id);
                    $this->processDuplicate($duplicate, $assignedUserId, $userIdList, $folderData, $teamsIdList);
                    return $duplicate;
                }
            }
        }

        if ($duplicate) {
            $duplicate->set([
                'from' => $email->get('from'),
                'to' => $email->get('to'),
                'cc' => $email->get('cc'),
                'bcc' => $email->get('bcc'),
                'replyTo' => $email->get('replyTo'),
                'name' => $email->get('name'),
                'dateSent' => $email->get('dateSent'),
                'body' => $email->get('body'),
                'bodyPlain' => $email->get('bodyPlain'),
                'parentType' => $email->get('parentType'),
                'parentId' => $email->get('parentId'),
                'isHtml' => $email->get('isHtml'),
                'messageId' => $email->get('messageId'),
                'fromString' => $email->get('fromString'),
                'replyToString' => $email->get('replyToString'),
            ]);
            $this->getEntityManager()->getRepository('Email')->fillAccount($duplicate);

            $this->processDuplicate($duplicate, $assignedUserId, $userIdList, $folderData, $teamsIdList);
            return $duplicate;
        }

        if (!$email->get('messageId')) {
            $email->setDummyMessageId();
        }
        $email->set('status', 'Being Imported');

        $this->getEntityManager()->saveEntity($email, [
            'skipAll' => true,
            'keepNew' => true
        ]);

        $this->unlockTables();

        if ($parentFound) {
            $parentType = $email->get('parentType');
            $parentId = $email->get('parentId');
            $emailKeepParentTeamsEntityList = $this->getConfig()->get('emailKeepParentTeamsEntityList', []);
            if ($parentId && in_array($parentType, $emailKeepParentTeamsEntityList)) {
                if ($this->getEntityManager()->hasRepository($parentType)) {
                    $parent = $this->getEntityManager()->getEntity($parentType, $parentId);
                    if ($parent) {
                        $parentTeamIdList = $parent->getLinkMultipleIdList('teams');
                        foreach ($parentTeamIdList as $parentTeamId) {
                            $email->addLinkMultipleId('teams', $parentTeamId);
                        }
                    }
                }
            }
        }

        $email->set('status', 'Archived');

        $this->getEntityManager()->saveEntity($email, [
            'isBeingImported' => true
        ]);

        foreach ($inlineAttachmentList as $attachment) {
            $attachment->set([
                'relatedId' => $email->id,
                'relatedType' => 'Email',
                'field' => 'body'
            ]);
            $this->getEntityManager()->saveEntity($attachment);
        }

        return $email;
    }

    protected function lockEmailTable()
    {
        $this->getEntityManager()->getPdo()->query('LOCK TABLES `email` WRITE');
    }

    protected function unlockTables()
    {
        $this->getEntityManager()->getPdo()->query('UNLOCK TABLES');
    }

    protected function findParent(Entity $email, $emailAddress)
    {
        $contact = $this->getEntityManager()->getRepository('Contact')->where(array(
            'emailAddress' => $emailAddress
        ))->findOne();
        if ($contact) {
            if (!$this->getConfig()->get('b2cMode')) {
                if ($contact->get('accountId')) {
                    $email->set('parentType', 'Account');
                    $email->set('parentId', $contact->get('accountId'));
                    return true;
                }
            }
            $email->set('parentType', 'Contact');
            $email->set('parentId', $contact->id);
            return true;
        } else {
            $account = $this->getEntityManager()->getRepository('Account')->where(array(
                'emailAddress' => $emailAddress
            ))->findOne();
            if ($account) {
                $email->set('parentType', 'Account');
                $email->set('parentId', $account->id);
                return true;
            } else {
                $lead = $this->getEntityManager()->getRepository('Lead')->where(array(
                    'emailAddress' => $emailAddress
                ))->findOne();
                if ($lead) {
                    $email->set('parentType', 'Lead');
                    $email->set('parentId', $lead->id);
                    return true;
                }
            }
        }
    }

    protected function findDuplicate(Entity $email)
    {
        if ($email->get('messageId')) {
            $duplicate = $this->getEntityManager()->getRepository('Email')->select(['id', 'status'])->where([
                'messageId' => $email->get('messageId')
            ])->findOne(['skipAdditionalSelectParams' => true]);
            if ($duplicate) {
                return $duplicate;
            }
        }
    }

    protected function processDuplicate(Entity $duplicate, $assignedUserId, $userIdList, $folderData, $teamsIdList)
    {
        if ($duplicate->get('status') == 'Archived') {
            $this->getEntityManager()->getRepository('Email')->loadFromField($duplicate);
            $this->getEntityManager()->getRepository('Email')->loadToField($duplicate);
        }

        $duplicate->loadLinkMultipleField('users');
        $fetchedUserIdList = $duplicate->getLinkMultipleIdList('users');
        $duplicate->setLinkMultipleIdList('users', []);

        $processNoteAcl = false;

        if ($assignedUserId) {
            if (!in_array($assignedUserId, $fetchedUserIdList)) {
                $processNoteAcl = true;
                $duplicate->addLinkMultipleId('users', $assignedUserId);
            }
            $duplicate->addLinkMultipleId('assignedUsers', $assignedUserId);
        }

        if (!empty($userIdList)) {
            foreach ($userIdList as $uId) {
                if (!in_array($uId, $fetchedUserIdList)) {
                    $processNoteAcl = true;
                    $duplicate->addLinkMultipleId('users', $uId);
                }
            }
        }

        if ($folderData) {
            foreach ($folderData as $uId => $folderId) {
                if (!in_array($uId, $fetchedUserIdList)) {
                    $duplicate->setLinkMultipleColumn('users', 'folderId', $uId, $folderId);
                } else {
                    $this->getEntityManager()->getRepository('Email')->updateRelation($duplicate, 'users', $uId, [
                        'folderId' => $folderId
                    ]);
                }
            }
        }

        $duplicate->set('isBeingImported', true);

        $this->getEntityManager()->getRepository('Email')->applyUsersFilters($duplicate);

        $this->getEntityManager()->getRepository('Email')->processLinkMultipleFieldSave($duplicate, 'users', [
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true
        ]);

        $this->getEntityManager()->getRepository('Email')->processLinkMultipleFieldSave($duplicate, 'assignedUsers', [
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true
        ]);

        if ($notificator = $this->getNotificator()) {
            $notificator->process($duplicate, [
                'isBeingImported' => true
            ]);
        }

        $fetchedTeamIdList = $duplicate->getLinkMultipleIdList('teams');

        if (!empty($teamsIdList)) {
            foreach ($teamsIdList as $teamId) {
                if (!in_array($teamId, $fetchedTeamIdList)) {
                    $processNoteAcl = true;
                    $this->getEntityManager()->getRepository('Email')->relate($duplicate, 'teams', $teamId);
                }
            }
        }

        if ($duplicate->get('parentType') && $processNoteAcl) {
            $dt = new \DateTime();
            $dt->modify('+5 seconds');
            $executeAt = $dt->format('Y-m-d H:i:s');

            $job = $this->getEntityManager()->getEntity('Job');
            $job->set([
                'serviceName' => 'Note',
                'methodName' => 'processNoteAclJob',
                'data' => [
                    'targetType' => 'Email',
                    'targetId' => $duplicate->id
                ],
                'executeAt' => $executeAt,
                'queue' => 'q1'
            ]);
            $this->getEntityManager()->saveEntity($job);
        }
    }
}
