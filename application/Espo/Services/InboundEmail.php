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

namespace Espo\Services;

use \Espo\ORM\Entity;
use \Espo\Entities\Team;
use \Zend\Mail\Storage;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class InboundEmail extends \Espo\Services\Record
{
    private $campaignService = null;

    const PORTION_LIMIT = 20;

    protected function init()
    {
        parent::init();

        $this->addDependency('mailSender');
        $this->addDependency('crypt');
        $this->addDependency('notificatorFactory');
    }

    protected function getMailSender()
    {
        return $this->getInjection('mailSender');
    }

    protected function getCrypt()
    {
        return $this->getInjection('crypt');
    }

    protected function handleInput($data)
    {
        parent::handleInput($data);
        if (property_exists($data, 'password')) {
            $data->password = $this->getCrypt()->encrypt($data->password);
        }
        if (property_exists($data, 'smtpPassword')) {
            $data->smtpPassword = $this->getCrypt()->encrypt($data->smtpPassword);
        }
    }

    public function getFolders($params)
    {
        $password = $params['password'];

        if (!empty($params['id'])) {
            $entity = $this->getEntityManager()->getEntity('InboundEmail', $params['id']);
            if ($entity) {
                $password = $this->getCrypt()->decrypt($entity->get('password'));
            }
        }

        $imapParams = array(
            'host' => $params['host'],
            'port' => $params['port'],
            'user' => $params['username'],
            'password' => $password,
        );

        if (!empty($params['ssl'])) {
            $imapParams['ssl'] = 'SSL';
        }

        $foldersArr = array();

        $storage = new \Espo\Core\Mail\Mail\Storage\Imap($imapParams);

        $folders = new \RecursiveIteratorIterator($storage->getFolders(), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($folders as $name => $folder) {
            $foldersArr[] = mb_convert_encoding($folder->getGlobalName(), 'UTF-8', 'UTF7-IMAP');
        }
        return $foldersArr;
    }

    public function testConnection(array $params)
    {
        $imapParams = array(
            'host' => $params['host'],
            'port' => $params['port'],
            'user' => $params['username'],
            'password' => $params['password']
        );

        if (!empty($params['ssl'])) {
            $imapParams['ssl'] = 'SSL';
        }

        $storage = new \Espo\Core\Mail\Mail\Storage\Imap($imapParams);

        if ($storage->getFolders()) {
            return true;
        }
        throw new Error();
    }

    public function fetchFromMailServer(Entity $emailAccount)
    {
        if ($emailAccount->get('status') != 'Active' || !$emailAccount->get('useImap')) {
            throw new Error("Group Email Account {$emailAccount->id} is not active.");
        }

        $notificator = $this->getInjection('notificatorFactory')->create('Email');

        $importer = new \Espo\Core\Mail\Importer($this->getEntityManager(), $this->getConfig(), $notificator);

        $maxSize = $this->getConfig()->get('emailMessageMaxSize');

        $teamId = $emailAccount->get('teamId');
        $userId = null;
        if ($emailAccount->get('assignToUserId')) {
            $userId = $emailAccount->get('assignToUserId');
        }
        $userIdList = [];

        $teamIdList = $emailAccount->getLinkMultipleIdList('teams');

        if (!empty($teamIdList)) {
            if ($emailAccount->get('addAllTeamUsers')) {
                $userList = $this->getEntityManager()->getRepository('User')->find(array(
                    'select' => ['id'],
                    'whereClause' => array(
                        'isActive' => true,
                        'teamsMiddle.teamId' => $teamIdList
                    ),
                    'distinct' => true,
                    'joins' => ['teams']
                ));
                foreach ($userList as $user) {
                    $userIdList[] = $user->id;
                }
            }
        }

        if (!empty($teamId)) {
            $teamIdList[] = $teamId;
        }

        $filterCollection = $this->getEntityManager()->getRepository('EmailFilter')->where([
            'action' => 'Skip',
            'OR' => [
                [
                    'parentType' => $emailAccount->getEntityType(),
                    'parentId' => $emailAccount->id
                ],
                [
                    'parentId' => null
                ]
            ]
        ])->find();

        $fetchData = $emailAccount->get('fetchData');
        if (empty($fetchData)) {
            $fetchData = (object) [];
        }
        $fetchData = clone $fetchData;
        if (!property_exists($fetchData, 'lastUID')) {
            $fetchData->lastUID = (object) [];
        }
        if (!property_exists($fetchData, 'lastDate')) {
            $fetchData->lastDate = (object) [];
        }
        if (!property_exists($fetchData, 'byDate')) {
            $fetchData->byDate = (object) [];
        }
        $fetchData->lastUID = clone $fetchData->lastUID;
        $fetchData->lastDate = clone $fetchData->lastDate;
        $fetchData->byDate = clone $fetchData->byDate;

        $imapParams = [
            'host' => $emailAccount->get('host'),
            'port' => $emailAccount->get('port'),
            'user' => $emailAccount->get('username'),
            'password' => $this->getCrypt()->decrypt($emailAccount->get('password')),
        ];

        if ($emailAccount->get('ssl')) {
            $imapParams['ssl'] = 'SSL';
        }

        $storage = new \Espo\Core\Mail\Mail\Storage\Imap($imapParams);

        $monitoredFolders = $emailAccount->get('monitoredFolders');
        if (empty($monitoredFolders)) {
            $monitoredFolders = 'INBOX';
        }

        $parserName = 'MailMimeParser';
        if ($this->getConfig()->get('emailParser')) {
            $parserName = $this->getConfig()->get('emailParser');
        }

        $parserClassName = '\\Espo\\Core\\Mail\\Parsers\\' . $parserName;

        $monitoredFoldersArr = explode(',', $monitoredFolders);

        foreach ($monitoredFoldersArr as $folder) {
            $folder = mb_convert_encoding(trim($folder), 'UTF7-IMAP', 'UTF-8');

            $portionLimit = $this->getConfig()->get('inboundEmailMaxPortionSize', self::PORTION_LIMIT);

            try {
                $storage->selectFolder($folder);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('InboundEmail '.$emailAccount->id.' (Select Folder) [' . $e->getCode() . '] ' .$e->getMessage());
                continue;
            }

            $lastUID = 0;
            $lastDate = 0;
            if (!empty($fetchData->lastUID->$folder)) {
                $lastUID = $fetchData->lastUID->$folder;
            }
            if (!empty($fetchData->lastDate->$folder)) {
                $lastDate = $fetchData->lastDate->$folder;
            }
            $forceByDate = !empty($fetchData->byDate->$folder);

            if ($forceByDate) {
                $portionLimit = 0;
            }

            $previousLastUID = $lastUID;
            $previousLastDate = $lastDate;

            if (!empty($lastUID) && !$forceByDate) {
                $idList = $storage->getIdsFromUID($lastUID);
            } else {
                $fetchSince = $emailAccount->get('fetchSince');
                if ($lastDate) {
                    $fetchSince = $lastDate;
                }

                $dt = null;
                try {
                    $dt = new \DateTime($fetchSince);
                } catch (\Exception $e) {}

                if ($dt) {
                    $idList = $storage->getIdsFromDate($dt->format('d-M-Y'));
                } else {
                    return false;
                }
            }

            if ((count($idList) == 1) && !empty($lastUID)) {
                if ($storage->getUniqueId($idList[0]) == $lastUID) {
                    continue;
                }
            }

            $k = 0;
            foreach ($idList as $i => $id) {
                if ($k == count($idList) - 1) {
                    $lastUID = $storage->getUniqueId($id);
                }

                if ($forceByDate && $previousLastUID) {
                    $uid = $storage->getUniqueId($id);
                    if ($uid <= $previousLastUID) {
                        $k++;
                        continue;
                    }
                }

                $fetchOnlyHeader = false;
                if ($maxSize) {
                    if ($storage->getSize($id) > $maxSize * 1024 * 1024) {
                        $fetchOnlyHeader = true;
                    }
                }

                $message = null;
                $email = null;
                try {
                    $toSkip = false;
                    $parser = new $parserClassName($this->getEntityManager());
                    $message = new \Espo\Core\Mail\MessageWrapper($storage, $id, $parser);

                    if ($message && $message->checkAttribute('from')) {
                        $fromString = $message->getAttribute('from');

                        if (preg_match('/MAILER-DAEMON|POSTMASTER/i', $fromString)) {
                            $toSkip = true;
                            try {
                                $this->processBouncedMessage($message);
                            } catch (\Exception $e) {
                                $GLOBALS['log']->error('InboundEmail '.$emailAccount->id.' (Process Bounced Message: [' . $e->getCode() . '] ' .$e->getMessage());
                            }
                        }
                    }

                    if (!$toSkip) {
                        if ($message->isFetched() && $emailAccount->get('keepFetchedEmailsUnread')) {
                            $flags = $message->getFlags();
                        }

                        $email = $this->importMessage($parserName, $importer, $emailAccount, $message, $teamIdList, $userId, $userIdList, $filterCollection, $fetchOnlyHeader, null);

                        if ($emailAccount->get('keepFetchedEmailsUnread')) {
                            if (is_array($flags) && empty($flags[Storage::FLAG_SEEN])) {
                                unset($flags[Storage::FLAG_RECENT]);
                                $storage->setFlags($id, $flags);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $GLOBALS['log']->error('InboundEmail '.$emailAccount->id.' (Get Message w/ parser '.$parserName.'): [' . $e->getCode() . '] ' .$e->getMessage());
                }

                try {
                    if (!empty($email)) {
                        if (!$emailAccount->get('createCase')) {
                            if (!$email->isFetched()) {
                                $this->noteAboutEmail($email);
                            }
                        }

                        $this->getEntityManager()->getRepository('InboundEmail')->relate($emailAccount, 'emails', $email);

                        if ($emailAccount->get('createCase')) {
                            if ($email->isFetched()) {
                                $email = $this->getEntityManager()->getEntity('Email', $email->id);
                            } else {
                                $email->updateFetchedValues();
                            }
                            if ($email) {
                                $this->createCase($emailAccount, $email);
                            }
                        } else {
                            if ($emailAccount->get('reply')) {
                                $user = $this->getEntityManager()->getEntity('User', $userId);
                                $this->autoReply($emailAccount, $email, $user);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $GLOBALS['log']->error('InboundEmail '.$emailAccount->id.' (Post Import Logic): [' . $e->getCode() . '] ' .$e->getMessage());
                }

                if ($k === count($idList) - 1 || $k === $portionLimit - 1) {
                    $lastUID = $storage->getUniqueId($id);

                    if ($email && $email->get('dateSent')) {
                        $dt = null;
                        try {
                            $dt = new \DateTime($email->get('dateSent'));
                        } catch (\Exception $e) {}

                        if ($dt) {
                            $nowDt = new \DateTime();
                            if ($dt->getTimestamp() >= $nowDt->getTimestamp()) {
                                $dt = $nowDt;
                            }
                            $dateSent = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                            $lastDate = $dateSent;
                        }
                    }

                    break;
                }

                $k++;
            }

            if ($forceByDate) {
                $nowDt = new \DateTime();
                $lastDate = $nowDt->format('Y-m-d H:i:s');
            }

            $fetchData->lastDate->$folder = $lastDate;
            $fetchData->lastUID->$folder = $lastUID;

            if ($forceByDate) {
                if ($previousLastUID) {
                    $idList = $storage->getIdsFromUID($previousLastUID);
                    if (count($idList)) {
                        $uid1 = $storage->getUniqueId($idList[0]);
                        if ($uid1 && $uid1 > $previousLastUID) {
                            unset($fetchData->byDate->$folder);
                        }
                    }
                }
            } else {
                if ($previousLastUID && count($idList) && $previousLastUID >= $lastUID) {
                     $fetchData->byDate->$folder = true;
                }
            }

            $emailAccount->set('fetchData', $fetchData);

            $this->getEntityManager()->saveEntity($emailAccount, ['silent' => true]);
        }

        $storage->close();

        return true;
    }

    protected function importMessage($parserName, $importer, $emailAccount, $message, $teamIdList, $userId = null, $userIdList = [], $filterCollection, $fetchOnlyHeader, $folderData = null)
    {
        $email = null;
        try {
            $email = $importer->importMessage($parserName, $message, $userId, $teamIdList, $userIdList, $filterCollection, $fetchOnlyHeader, $folderData);
        } catch (\Exception $e) {
            $GLOBALS['log']->error('InboundEmail '.$emailAccount->id.' (Import Message w/ '.$parserName.'): [' . $e->getCode() . '] ' .$e->getMessage());
            $this->getEntityManager()->getPdo()->query('UNLOCK TABLES');
        }
        return $email;
    }

    protected function noteAboutEmail($email)
    {
        if ($email->get('parentType') && $email->get('parentId')) {
            $parent = $this->getEntityManager()->getEntity($email->get('parentType'), $email->get('parentId'));
            if ($parent) {
                $this->getServiceFactory()->create('Stream')->noteEmailReceived($parent, $email);
                return;
            }
        }
    }

    protected function processCaseToEmailFields($case, $email)
    {
        $userIdList = [];

        if ($case->hasLinkMultipleField('assignedUsers')) {
            $userIdList = $case->getLinkMultipleIdList('assignedUsers');
        } else {
            $assignedUserId = $case->get('assignedUserId');
            if ($assignedUserId) {
                $userIdList[] = $assignedUserId;
            }
        }

        foreach ($userIdList as $userId) {
            $email->addLinkMultipleId('users', $userId);
        }

        $teamIdList = $case->getLinkMultipleIdList('teams');

        foreach ($teamIdList as $teamId) {
            $email->addLinkMultipleId('teams', $teamId);
        }

        $this->getEntityManager()->saveEntity($email, [
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true
        ]);
    }

    protected function createCase($inboundEmail, $email)
    {
        if ($email->get('parentType') == 'Case' && $email->get('parentId')) {
            $case = $this->getEntityManager()->getEntity('Case', $email->get('parentId'));
            if ($case) {
                $this->processCaseToEmailFields($case, $email);
                if (!$email->isFetched()) {
                    $this->getServiceFactory()->create('Stream')->noteEmailReceived($case, $email);
                }
            }
            return;
        }

        if (preg_match('/\[#([0-9]+)[^0-9]*\]/', $email->get('name'), $m)) {
            $caseNumber = $m[1];
            $case = $this->getEntityManager()->getRepository('Case')->where([
                'number' => $caseNumber
            ])->findOne();
            if ($case) {
                $email->set('parentType', 'Case');
                $email->set('parentId', $case->id);
                $this->processCaseToEmailFields($case, $email);
                if (!$email->isFetched()) {
                    $this->getServiceFactory()->create('Stream')->noteEmailReceived($case, $email);
                }
            }
        } else {
            $params = [
                'caseDistribution' => $inboundEmail->get('caseDistribution'),
                'teamId' => $inboundEmail->get('teamId'),
                'userId' => $inboundEmail->get('assignToUserId'),
                'targetUserPosition' => $inboundEmail->get('targetUserPosition'),
                'inboundEmailId' => $inboundEmail->id
            ];
            $case = $this->emailToCase($email, $params);
            $user = $this->getEntityManager()->getEntity('User', $case->get('assignedUserId'));
            $this->getServiceFactory()->create('Stream')->noteEmailReceived($case, $email, true);
            if ($inboundEmail->get('reply')) {
                $this->autoReply($inboundEmail, $email, $case, $user);
            }
        }
    }

    protected function assignRoundRobin(Entity $case, Team $team, $targetUserPosition)
    {
        $className = '\\Espo\\Custom\\Business\\Distribution\\CaseObj\\RoundRobin';
        if (!class_exists($className)) {
            $className = '\\Espo\\Modules\\Crm\\Business\\Distribution\\CaseObj\\RoundRobin';
        }

        $distribution = new $className($this->getEntityManager());

        $user = $distribution->getUser($team, $targetUserPosition);

        if ($user) {
            $case->set('assignedUserId', $user->id);
            $case->set('status', 'Assigned');
        }
    }

    protected function assignLeastBusy(Entity $case, Team $team, $targetUserPosition)
    {
        $className = '\\Espo\\Custom\\Business\\Distribution\\CaseObj\\LeastBusy';
        if (!class_exists($className)) {
            $className = '\\Espo\\Modules\\Crm\\Business\\Distribution\\CaseObj\\LeastBusy';
        }

        $distribution = new $className($this->getEntityManager());

        $user = $distribution->getUser($team, $targetUserPosition);

        if ($user) {
            $case->set('assignedUserId', $user->id);
            $case->set('status', 'Assigned');
        }
    }

    protected function emailToCase(\Espo\Entities\Email $email, array $params = [])
    {
        $case = $this->getEntityManager()->getEntity('Case');
        $case->populateDefaults();
        $case->set('name', $email->get('name'));

        $bodyPlain = $email->getBodyPlain();

        if (trim(preg_replace('/\s+/', '', $bodyPlain)) === '') {
            $bodyPlain = '';
        }

        if ($bodyPlain) {
            $case->set('description', $bodyPlain);
        }

        $attachmentIdList = $email->getLinkMultipleIdList('attachments');
        $copiedAttachmentIdList = [];

        foreach ($attachmentIdList as $attachmentId) {
            $attachment = $this->getEntityManager()->getRepository('Attachment')->get($attachmentId);
            if (!$attachment) continue;
            $copiedAttachment = $this->getEntityManager()->getRepository('Attachment')->getCopiedAttachment($attachment);
            $copiedAttachmentIdList[] = $copiedAttachment->id;
        }

        if (count($copiedAttachmentIdList)) {
            $case->setLinkMultipleIdList('attachments', $copiedAttachmentIdList);
        }

        $userId = null;
        if (!empty($params['userId'])) {
            $userId = $params['userId'];
        }

        if (!empty($params['inboundEmailId'])) {
            $case->set('inboundEmailId', $params['inboundEmailId']);
        }

        $teamId = false;
        if (!empty($params['teamId'])) {
            $teamId = $params['teamId'];
        }
        if ($teamId) {
            $case->set('teamsIds', [$teamId]);
        }

        $caseDistribution = '';
        if (!empty($params['caseDistribution'])) {
            $caseDistribution = $params['caseDistribution'];
        }

        $targetUserPosition = null;
        if (!empty($params['targetUserPosition'])) {
            $targetUserPosition = $params['targetUserPosition'];
        }


        switch ($caseDistribution) {
            case 'Direct-Assignment':
                if ($userId) {
                    $case->set('assignedUserId', $userId);
                    $case->set('status', 'Assigned');
                }
                break;
            case 'Round-Robin':
                if ($teamId) {
                    $team = $this->getEntityManager()->getEntity('Team', $teamId);
                    if ($team) {
                        $this->assignRoundRobin($case, $team, $targetUserPosition);
                    }
                }
                break;
            case 'Least-Busy':
                if ($teamId) {
                    $team = $this->getEntityManager()->getEntity('Team', $teamId);
                    if ($team) {
                        $this->assignLeastBusy($case, $team, $targetUserPosition);
                    }
                }
                break;
        }

        if ($case->get('assignedUserId')) {
            $email->set('assignedUserId', $case->get('assignedUserId'));
        }

        if ($email->get('accountId')) {
            $case->set('accountId', $email->get('accountId'));
        }

        $contact = $this->getEntityManager()->getRepository('Contact')->join([['emailAddresses', 'emailAddressesMultiple']])->where(array(
            'emailAddressesMultiple.id' => $email->get('fromEmailAddressId')
        ))->findOne();
        if ($contact) {
            $case->set('contactId', $contact->id);
        } else {
            if (!$case->get('accountId')) {
                $lead = $this->getEntityManager()->getRepository('Lead')->join([['emailAddresses', 'emailAddressesMultiple']])->where(array(
                    'emailAddressesMultiple.id' => $email->get('fromEmailAddressId')
                ))->findOne();
                if ($lead) {
                    $case->set('leadId', $lead->id);
                }
            }
        }

        $this->getEntityManager()->saveEntity($case);

        $email->set('parentType', 'Case');
        $email->set('parentId', $case->id);

        $this->getEntityManager()->saveEntity($email, [
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true
        ]);

        $case = $this->getEntityManager()->getEntity('Case', $case->id);

        return $case;
    }

    protected function autoReply($inboundEmail, $email, $case = null, $user = null)
    {
        if (!$email->get('from')) {
            return false;
        }

        $d = new \DateTime();
        $d->modify('-3 hours');
        $threshold = $d->format('Y-m-d H:i:s');

        $emailAddress = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($email->get('from'));

        $sent = $this->getEntityManager()->getRepository('Email')->where([
            'toEmailAddresses.id' => $emailAddress->id,
            'dateSent>' => $threshold,
            'status' => 'Sent'
        ])->join('toEmailAddresses')->findOne();

        if ($sent) {
            return false;
        }

        try {
            $replyEmailTemplateId = $inboundEmail->get('replyEmailTemplateId');
            if ($replyEmailTemplateId) {
                $entityHash = array();
                if ($case) {
                    $entityHash['Case'] = $case;
                    if ($case->get('contactId')) {
                        $contact = $this->getEntityManager()->getEntity('Contact', $case->get('contactId'));
                    }
                }
                if (empty($contact)) {
                    $contact = $this->getEntityManager()->getEntity('Contact');
                    $fromName = \Espo\Services\Email::parseFromName($email->get('fromString'));
                    if (!empty($fromName)) {
                        $contact->set('name', $fromName);
                    }
                }

                $entityHash['Person'] = $contact;
                $entityHash['Contact'] = $contact;

                if ($user) {
                    $entityHash['User'] = $user;
                }

                $emailTemplateService = $this->getServiceFactory()->create('EmailTemplate');

                $replyData = $emailTemplateService->parse($replyEmailTemplateId, array('entityHash' => $entityHash), true);

                $subject = $replyData['subject'];
                if ($case) {
                    $subject = '[#' . $case->get('number'). '] ' . $subject;
                }

                $reply = $this->getEntityManager()->getEntity('Email');
                $reply->set('to', $email->get('from'));
                $reply->set('subject', $subject);
                $reply->set('body', $replyData['body']);
                $reply->set('isHtml', $replyData['isHtml']);
                $reply->set('attachmentsIds', $replyData['attachmentsIds']);

                if ($email->has('teamsIds')) {
                    $reply->set('teamsIds', $email->get('teamsIds'));
                }

                if ($email->get('parentId') && $email->get('parentType')) {
                    $reply->set('parentId', $email->get('parentId'));
                    $reply->set('parentType', $email->get('parentType'));
                }

                $this->getEntityManager()->saveEntity($reply);

                $sender = $this->getMailSender()->useGlobal();

                if ($inboundEmail->get('useSmtp')) {
                    $smtpParams = $this->getSmtpParamsFromInboundEmail($inboundEmail);
                    if ($smtpParams) {
                        $sender->useSmtp($smtpParams);
                    }
                }
                $senderParams = [];
                if ($inboundEmail->get('fromName')) {
                    $senderParams['fromName'] = $inboundEmail->get('fromName');
                }
                if ($inboundEmail->get('replyFromAddress')) {
                    $senderParams['fromAddress'] = $inboundEmail->get('replyFromAddress');
                }
                if ($inboundEmail->get('replyFromName')) {
                    $senderParams['fromName'] = $inboundEmail->get('replyFromName');
                }
                if ($inboundEmail->get('replyToAddress')) {
                    $senderParams['replyToAddress'] = $inboundEmail->get('replyToAddress');
                }
                $sender->send($reply, $senderParams);

                $this->getEntityManager()->saveEntity($reply);

                return true;
            }

        } catch (\Exception $e) {}
    }

    protected function getSmtpParamsFromInboundEmail(\Espo\Entities\InboundEmail $emailAccount)
    {
        $smtpParams = [];
        $smtpParams['server'] = $emailAccount->get('smtpHost');
        if ($smtpParams['server']) {
            $smtpParams['port'] = $emailAccount->get('smtpPort');
            $smtpParams['auth'] = $emailAccount->get('smtpAuth');
            $smtpParams['security'] = $emailAccount->get('smtpSecurity');
            $smtpParams['username'] = $emailAccount->get('smtpUsername');
            $smtpParams['password'] = $emailAccount->get('smtpPassword');
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }
            return $smtpParams;
        }
        return;
    }

    protected function processBouncedMessage($message)
    {
        $content = $message->getRawContent();

        $isHard = false;
        if (preg_match('/permanent[ ]*[error|failure]/', $content)) {
            $isHard = true;
        }
        if (preg_match('/X-Queue-Item-Id: [a-z0-9\-]*/', $content, $m)) {
            $arr = preg_split('/X-Queue-Item-Id: /', $m[0], -1, \PREG_SPLIT_NO_EMPTY);

            $queueItemId = $arr[0];
            if (!$queueItemId) return;

            $queueItem = $this->getEntityManager()->getEntity('EmailQueueItem', $queueItemId);
            if (!$queueItem) return;
            $massEmailId = $queueItem->get('massEmailId');
            $massEmail = $this->getEntityManager()->getEntity('MassEmail', $massEmailId);

            $campaignId = null;
            if ($massEmail) {
                $campaignId = $massEmail->get('campaignId');
            }

            $targetType = $queueItem->get('targetType');
            $targetId = $queueItem->get('targetId');
            $target = $this->getEntityManager()->getEntity($targetType, $targetId);

            $emailAddress = $queueItem->get('emailAddress');

            if ($isHard && $emailAddress) {
                $emailAddressEntity = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($emailAddress);
                $emailAddressEntity->set('invalid', true);
                $this->getEntityManager()->saveEntity($emailAddressEntity);
            }

            if ($campaignId && $target && $target->id) {
                $this->getCampaignService()->logBounced($campaignId, $queueItemId, $target, $emailAddress, $isHard, null, $queueItem->get('isTest'));
            }
        }
    }

    protected function getCampaignService()
    {
        if (!$this->campaignService) {
            $this->campaignService = $this->getServiceFactory()->create('Campaign');
        }
        return $this->campaignService;
    }

    public function findSharedAccountForUser(\Espo\Entities\User $user, $emailAddress)
    {
        $groupEmailAccountPermission = $this->getAclManager()->get($user, 'groupEmailAccountPermission');
        $teamIdList = $user->getLinkMultipleIdList('teams');

        $inboundEmail = null;

        $groupEmailAccountPermission = $this->getAcl()->get('groupEmailAccountPermission');
        if ($groupEmailAccountPermission && $groupEmailAccountPermission !== 'no') {
            if ($groupEmailAccountPermission === 'team') {
                if (!count($teamIdList)) return;
                $selectParams = [
                    'whereClause' => [
                        'status' => 'Active',
                        'useSmtp' => true,
                        'smtpIsShared' => true,
                        'teamsMiddle.teamId' => $teamIdList,
                        'emailAddress' => $emailAddress
                    ],
                    'joins' => ['teams'],
                    'distinct' => true
                ];
            } else if ($groupEmailAccountPermission === 'all') {
                $selectParams = [
                    'whereClause' => [
                        'status' => 'Active',
                        'useSmtp' => true,
                        'smtpIsShared' => true,
                        'emailAddress' => $emailAddress
                    ]
                ];
            }
            $inboundEmail = $this->getEntityManager()->getRepository('InboundEmail')->findOne($selectParams);

        }
        return $inboundEmail;
    }

    protected function getStorage(\Espo\Entities\InboundEmail $emailAccount)
    {
        $imapParams = array(
            'host' => $emailAccount->get('host'),
            'port' => $emailAccount->get('port'),
            'user' => $emailAccount->get('username'),
            'password' => $this->getCrypt()->decrypt($emailAccount->get('password')),
        );

        if ($emailAccount->get('ssl')) {
            $imapParams['ssl'] = 'SSL';
        }

        $storage = new \Espo\Core\Mail\Mail\Storage\Imap($imapParams);

        return $storage;
    }

    public function storeSentMessage(\Espo\Entities\InboundEmail $emailAccount, $message)
    {
        $storage = $this->getStorage($emailAccount);

        $folder = $emailAccount->get('sentFolder');
        if (empty($folder)) {
            throw new Error("No sent folder for Email Account: " . $emailAccount->id . ".");
        }
        $storage->appendMessage($message->toString(), $folder);
    }

    public function getSmtpParamsFromAccount(\Espo\Entities\InboundEmail $emailAccount)
    {
        $smtpParams = array();
        $smtpParams['server'] = $emailAccount->get('smtpHost');
        if ($smtpParams['server']) {
            $smtpParams['port'] = $emailAccount->get('smtpPort');
            $smtpParams['auth'] = $emailAccount->get('smtpAuth');
            $smtpParams['security'] = $emailAccount->get('smtpSecurity');
            $smtpParams['username'] = $emailAccount->get('smtpUsername');
            $smtpParams['password'] = $emailAccount->get('smtpPassword');
            if ($emailAccount->get('fromName')) {
                $smtpParams['fromName'] = $emailAccount->get('fromName');
            }
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }
            return $smtpParams;
        }
        return;
    }
}
