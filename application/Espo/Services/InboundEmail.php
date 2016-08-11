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

use \Espo\ORM\Entity;
use \Espo\Entities\Team;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class InboundEmail extends \Espo\Services\Record
{
    protected $internalAttributeList = ['password'];

    protected $readOnlyAttributeList= ['fetchData'];

    private $campaignService = null;

    const PORTION_LIMIT = 20;

    public function createEntity($data)
    {
        $entity = parent::createEntity($data);
        return $entity;
    }

    public function getEntity($id = null)
    {
        $entity = parent::getEntity($id);
        return $entity;
    }

    public function updateEntity($id, $data)
    {
        $entity = parent::updateEntity($id, $data);
        return $entity;
    }

    public function findEntities($params)
    {
        $result = parent::findEntities($params);

        return $result;
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('mailSender');
        $this->addDependency('crypt');
    }

    protected function getMailSender()
    {
        return $this->injections['mailSender'];
    }

    protected function getCrypt()
    {
        return $this->injections['crypt'];
    }

    protected function handleInput(&$data)
    {
        parent::handleInput($data);
        if (array_key_exists('password', $data)) {
            $data['password'] = $this->getCrypt()->encrypt($data['password']);
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
        if ($emailAccount->get('status') != 'Active') {
            throw new Error();
        }

        $importer = new \Espo\Core\Mail\Importer($this->getEntityManager(), $this->getConfig());

        $maxSize = $this->getConfig()->get('emailMessageMaxSize');

        $teamId = $emailAccount->get('teamId');
        $userId = null;
        if ($emailAccount->get('assignToUserId')) {
            $userId = $emailAccount->get('assignToUserId');
        }
        $teamIdList = [];
        $userIdList = [];
        if (!empty($teamId)) {
            $teamIdList[] = $teamId;
            if ($emailAccount->get('addAllTeamUsers')) {
                $team = $this->getEntityManager()->getEntity('Team', $teamId);
                if ($team) {
                    $userList = $this->getEntityManager()->getRepository('Team')->findRelated($team, 'users', array(
                        'whereClause' => array(
                            'isActive' => true
                        )
                    ));
                    foreach ($userList as $user) {
                        $userIdList[] = $user->id;
                    }
                }
            }
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
            $fetchData = new \StdClass();
        }
        if (!property_exists($fetchData, 'lastUID')) {
            $fetchData->lastUID = new \StdClass();;
        }
        if (!property_exists($fetchData, 'lastDate')) {
            $fetchData->lastDate = new \StdClass();;
        }

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

        $monitoredFolders = $emailAccount->get('monitoredFolders');
        if (empty($monitoredFolders)) {
            $monitoredFolders = 'INBOX';
        }

        $portionLimit = $this->getConfig()->get('inboundEmailMaxPortionSize', self::PORTION_LIMIT);

        $monitoredFoldersArr = explode(',', $monitoredFolders);
        foreach ($monitoredFoldersArr as $folder) {
            $folder = mb_convert_encoding(trim($folder), 'UTF7-IMAP', 'UTF-8');

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

            if (!empty($lastUID)) {
                $ids = $storage->getIdsFromUID($lastUID);
            } else {
                $dt = null;
                try {
                    $dt = new \DateTime($emailAccount->get('fetchSince'));
                } catch (\Exception $e) {}

                if ($dt) {
                    $ids = $storage->getIdsFromDate($dt->format('d-M-Y'));
                } else {
                    return false;
                }
            }

            if ((count($ids) == 1) && !empty($lastUID)) {
                if ($storage->getUniqueId($ids[0]) == $lastUID) {
                    continue;
                }
            }

            $k = 0;
            foreach ($ids as $i => $id) {
                $toSkip = false;

                if ($k == count($ids) - 1) {
                    $lastUID = $storage->getUniqueId($id);
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
                    $message = $storage->getMessage($id);
                    if ($message && isset($message->from)) {
                        $fromString = $message->from;
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
                        try {
                            $email = $importer->importMessage($message, $userId, $teamIdList, $userIdList, $filterCollection, $fetchOnlyHeader);
                        } catch (\Exception $e) {
                            $GLOBALS['log']->error('InboundEmail '.$emailAccount->id.' (Import Message): [' . $e->getCode() . '] ' .$e->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    $GLOBALS['log']->error('InboundEmail '.$emailAccount->id.' (Get Message): [' . $e->getCode() . '] ' .$e->getMessage());
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
                            $this->createCase($emailAccount, $email);
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

                if ($k == count($ids) - 1) {
                    if ($message && isset($message->date)) {
                        $dt = null;
                        try {
                            $dt = new \DateTime($message->date);
                        } catch (\Exception $e) {}

                        if ($dt) {
                            $dateSent = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                            $lastDate = $dateSent;
                        }
                    }
                }

                if ($k == $portionLimit - 1) {
                    $lastUID = $storage->getUniqueId($id);
                    break;
                }
                $k++;
            }

            $fetchData->lastUID->$folder = $lastUID;
            $fetchData->lastDate->$folder = $lastDate;
            $emailAccount->set('fetchData', $fetchData);

            $this->getEntityManager()->saveEntity($emailAccount, array('silent' => true));
        }

        $storage->close();

        return true;
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

    protected function createCase($inboundEmail, $email)
    {
        if ($email->get('parentType') == 'Case' && $email->get('parentId')) {
            $case = $this->getEntityManager()->getEntity('Case', $email->get('parentId'));
            if ($case) {
                if (!$email->isFetched()) {
                    $this->getServiceFactory()->create('Stream')->noteEmailReceived($case, $email);
                }
            }
            return;
        }

        if (preg_match('/\[#([0-9]+)[^0-9]*\]/', $email->get('name'), $m)) {
            $caseNumber = $m[1];
            $case = $this->getEntityManager()->getRepository('Case')->where(array(
                'number' => $caseNumber
            ))->findOne();
            if ($case) {
                $email->set('parentType', 'Case');
                $email->set('parentId', $case->id);
                $this->getEntityManager()->saveEntity($email);
                if (!$email->isFetched()) {
                    $this->getServiceFactory()->create('Stream')->noteEmailReceived($case, $email);
                }
            }
        } else {
            $params = array(
                'caseDistribution' => $inboundEmail->get('caseDistribution'),
                'teamId' => $inboundEmail->get('teamId'),
                'userId' => $inboundEmail->get('assignToUserId'),
                'targetUserPosition' => $inboundEmail->get('targetUserPosition'),
                'inboundEmailId' => $inboundEmail->id
            );
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

    protected function emailToCase(\Espo\Entities\Email $email, array $params = array())
    {
        $case = $this->getEntityManager()->getEntity('Case');
        $case->populateDefaults();
        $case->set('name', $email->get('name'));

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
            $case->set('teamsIds', array($teamId));
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

        $contact = $this->getEntityManager()->getRepository('Contact')->where(array(
            'emailAddresses.id' => $email->get('fromEmailAddressId')
        ))->findOne();
        if ($contact) {
            $case->set('contactId', $contact->id);
        } else {
            if (!$case->get('accountId')) {
                $lead = $this->getEntityManager()->getRepository('Lead')->where(array(
                    'emailAddresses.id' => $email->get('fromEmailAddressId')
                ))->findOne();
                if ($lead) {
                    $case->set('leadId', $lead->id);
                }
            }
        }

        $this->getEntityManager()->saveEntity($case);

        $email->set('parentType', 'Case');
        $email->set('parentId', $case->id);
        $this->getEntityManager()->saveEntity($email);

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

        $sent = $this->getEntityManager()->getRepository('Email')->where(array(
            'toEmailAddresses.id' => $emailAddress->id,
            'dateSent>' => $threshold,
            'status' => 'Sent'
        ))->join('toEmailAddresses')->findOne();

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
                $senderParams = array();
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

    protected function processBouncedMessage(\Zend\Mail\Storage\Message $message)
    {
        $content = $message->getContent();

        $isHard = false;
        if (preg_match('/permanent[ ]*[error|failure]/', $content)) {
            $isHard = true;
        }
        if (preg_match('/X-QueueItemId: [a-z0-9\-]*/', $content, $m)) {
            $arr = preg_split('/X-QueueItemId: /', $m[0], -1, \PREG_SPLIT_NO_EMPTY);

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

}

