<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
namespace Espo\Modules\Crm\Services;

use Espo\Core\Exceptions\Error;
use Espo\Core\Mail\Importer;
use Espo\Core\Mail\Sender;
use Espo\Core\Utils\Crypt;
use Espo\Entities\Email;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Business\CaseDistribution\LeastBusy;
use Espo\Modules\Crm\Business\CaseDistribution\RoundRobin;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Entity;
use Espo\Services\EmailTemplate;
use Espo\Services\Record;
use Espo\Services\Stream;
use Zend\Mail\Storage\Imap;

class InboundEmail extends
    Record
{

    const PORTION_LIMIT = 20;

    protected $internalFields = array('password');

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
        $storage = new Imap($imapParams);
        $folders = new \RecursiveIteratorIterator($storage->getFolders(), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($folders as $name => $folder) {
            $foldersArr[] = $folder->getGlobalName();
        }
        return $foldersArr;
    }

    public function fetchFromMailServer(Entity $inboundEmail)
    {
        if ($inboundEmail->get('status') != 'Active') {
            throw new Error();
        }
        $importer = new Importer($this->getEntityManager(), $this->getFileManager());
        $maxSize = $this->getConfig()->get('emailMessageMaxSize');
        $teamId = $inboundEmail->get('teamId');
        $userId = $this->getUser()->id;
        if ($inboundEmail->get('assignToUserId')) {
            $userId = $inboundEmail->get('assignToUserId');
        }
        $fetchData = json_decode($inboundEmail->get('fetchData'), true);
        if (empty($fetchData)) {
            $fetchData = array();
        }
        if (!array_key_exists('lastUID', $fetchData)) {
            $fetchData['lastUID'] = array();
        }
        if (!array_key_exists('lastUID', $fetchData)) {
            $fetchData['lastDate'] = array();
        }
        $imapParams = array(
            'host' => $inboundEmail->get('host'),
            'port' => $inboundEmail->get('port'),
            'user' => $inboundEmail->get('username'),
            'password' => $this->getCrypt()->decrypt($inboundEmail->get('password')),
        );
        if ($inboundEmail->get('ssl')) {
            $imapParams['ssl'] = 'SSL';
        }
        $storage = new \Espo\Core\Mail\Storage\Imap($imapParams);
        $monitoredFolders = $inboundEmail->get('monitoredFolders');
        if (empty($monitoredFolders)) {
            $monitoredFolders = 'INBOX';
        }
        $monitoredFoldersArr = explode(',', $monitoredFolders);
        foreach ($monitoredFoldersArr as $folder) {
            $folder = trim($folder);
            $storage->selectFolder($folder);
            $lastUID = 0;
            $lastDate = 0;
            if (!empty($fetchData['lastUID'][$folder])) {
                $lastUID = $fetchData['lastUID'][$folder];
            }
            if (!empty($fetchData['lastDate'][$folder])) {
                $lastDate = $fetchData['lastDate'][$folder];
            }
            $ids = $storage->getIdsFromUID($lastUID);
            if ((count($ids) == 1) && !empty($lastUID)) {
                if ($storage->getUniqueId($ids[0]) == $lastUID) {
                    continue;
                }
            }
            $k = 0;
            foreach ($ids as $i => $id) {
                if ($k == count($ids) - 1) {
                    $lastUID = $storage->getUniqueId($id);
                }
                if ($maxSize) {
                    if ($storage->getSize($id) > $maxSize * 1024 * 1024) {
                        continue;
                    }
                }
                $message = $storage->getMessage($id);
                $email = $importer->importMessage($message, $userId, array($teamId));
                if ($email) {
                    if ($inboundEmail->get('createCase')) {
                        $this->createCase($inboundEmail, $email);
                    } else {
                        if ($inboundEmail->get('reply')) {
                            $user = $this->getEntityManager()->getEntity('User', $userId);
                            $this->autoReply($inboundEmail, $email, $user);
                        }
                    }
                }
                if ($k == count($ids) - 1) {
                    if ($message) {
                        $dt = new \DateTime($message->date);
                        if ($dt) {
                            $dateSent = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                            $lastDate = $dateSent;
                        }
                    }
                }
                if ($k == self::PORTION_LIMIT - 1) {
                    $lastUID = $storage->getUniqueId($id);
                    break;
                }
                $k++;
            }
            $fetchData['lastUID'][$folder] = $lastUID;
            $fetchData['lastDate'][$folder] = $lastDate;
            $inboundEmail->set('fetchData', json_encode($fetchData));
            $this->getEntityManager()->saveEntity($inboundEmail);
        }
        return true;
    }

    protected function getFileManager()
    {
        return $this->injections['fileManager'];
    }

    /**
     * @param \Espo\Modules\Crm\Entities\InboundEmail $inboundEmail
     * @param Email                                   $email
     *

     * @throws Error
     */
    protected function createCase($inboundEmail, $email)
    {
        /**
         * @var CaseObj $case
         * @var Stream  $streamService
         */
        if (preg_match('/\[#([0-9]+)[^0-9]*\]/', $email->get('name'), $m)) {
            $caseNumber = $m[1];
            $case = $this->getEntityManager()->getRepository('Case')->where(array(
                'number' => $caseNumber
            ))->findOne();
            if ($case) {
                $email->set('parentType', 'Case');
                $email->set('parentId', $case->id);
                $this->getEntityManager()->saveEntity($email);
                $streamService = $this->getServiceFactory()->create('Stream');
                $streamService->noteEmailReceived($case, $email);
            }
        } else {
            $params = array(
                'caseDistribution' => $inboundEmail->get('caseDistribution'),
                'teamId' => $inboundEmail->get('teamId'),
                'userId' => $inboundEmail->get('assignToUserId'),
            );
            $case = $this->emailToCase($email, $params);
            $user = $this->getEntityManager()->getEntity('User', $case->get('assignedUserId'));
            if ($inboundEmail->get('reply')) {
                $this->autoReply($inboundEmail, $email, $case, $user);
            }
        }
    }

    protected function emailToCase(Email $email, array $params = array())
    {
        /**
         * @var Contact $contact
         * @var CaseObj $case
         */
        $case = $this->getEntityManager()->getEntity('Case');
        $case->populateDefaults();
        $case->set('name', $email->get('name'));
        $userId = $this->getUser()->id;
        if (!empty($params['userId'])) {
            $userId = $params['userId'];
        }
        $case->set('assignedUserId', $userId);
        $teamId = false;
        if (!empty($params['teamId'])) {
            $teamId = $params['teamId'];
        }
        if ($teamId) {
            $case->set('teamsIds', array($teamId));
        }
        $caseDistribution = 'Direct-Assignment';
        if (!empty($params['caseDistribution'])) {
            $caseDistribution = $params['caseDistribution'];
        }
        $case->set('status', 'Assigned');
        switch ($caseDistribution) {
            case 'Round-Robin':
                if ($teamId) {
                    $team = $this->getEntityManager()->getEntity('Team', $teamId);
                    if ($team) {
                        $this->assignRoundRobin($case, $team);
                    }
                }
                break;
            case 'Least-Busy':
                if ($teamId) {
                    $team = $this->getEntityManager()->getEntity('Team', $teamId);
                    if ($team) {
                        $this->assignLeastBusy($case, $team);
                    }
                }
                break;
        }
        $email->set('assignedUserId', $case->get('assignedUserId'));
        $contact = $this->getEntityManager()->getRepository('Contact')->where(array(
            'EmailAddress.id' => $email->get('fromEmailAddressId')
        ))->findOne();
        if ($contact) {
            $case->set('contactId', $contact->id);
            if ($contact->get('accountId')) {
                $case->set('accountId', $contact->get('accountId'));
            }
        }
        $this->getEntityManager()->saveEntity($case);
        $email->set('parentType', 'Case');
        $email->set('parentId', $case->id);
        $this->getEntityManager()->saveEntity($email);
        $case = $this->getEntityManager()->getEntity('Case', $case->id);
        return $case;
    }

    /**
     * @param CaseObj $case
     * @param Team    $team
     *
     */
    protected function assignRoundRobin($case, $team)
    {
        $roundRobin = new RoundRobin($this->getEntityManager());
        $user = $roundRobin->getUser($team);
        if ($user) {
            $case->set('assignedUserId', $user->id);
        }
    }

    /**
     * @param CaseObj $case
     * @param Team    $team
     *
     */
    protected function assignLeastBusy($case, $team)
    {
        $leastBusy = new LeastBusy($this->getEntityManager());
        $user = $leastBusy->getUser($team);
        if ($user) {
            $case->set('assignedUserId', $user->id);
        }
    }

    /**
     * @param \Espo\Modules\Crm\Entities\InboundEmail $inboundEmail
     * @param Email                                   $email
     * @param CaseObj                                 $case
     * @param User                                    $user
     *
     * @return bool

     */
    protected function autoReply($inboundEmail, $email, $case = null, $user = null)
    {
        /**
         * @var EmailTemplate $emailTemplateService
         */
        try{
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
                    $contact->set('name', $email->get('fromName'));
                }
                $entityHash['Person'] = $contact;
                $entityHash['Contact'] = $contact;
                if ($user) {
                    $entityHash['User'] = $user;
                }
                $emailTemplateService = $this->getServiceFactory()->create('EmailTemplate');
                $replyData = $emailTemplateService->parse($replyEmailTemplateId, array('entityHash' => $entityHash),
                    true);
                $subject = $replyData['subject'];
                if ($case) {
                    $subject = '[#' . $case->get('number') . '] ' . $subject;
                }
                $reply = $this->getEntityManager()->getEntity('Email');
                $reply->set('to', $email->get('from'));
                $reply->set('subject', $subject);
                $reply->set('body', $replyData['body']);
                $reply->set('isHtml', $replyData['isHtml']);
                $reply->set('attachmentsIds', $replyData['attachmentsIds']);
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
                foreach ($reply->get('attachments') as $attachment) {
                    $this->getEntityManager()->removeEntity($attachment);
                }
                $this->getEntityManager()->removeEntity($reply);
                return true;
            }
        } catch(\Exception $e){
        }
    }

    /**
     * @return Sender

     */
    protected function getMailSender()
    {
        return $this->injections['mailSender'];
    }

    protected function init()
    {
        $this->dependencies[] = 'fileManager';
        $this->dependencies[] = 'mailSender';
        $this->dependencies[] = 'crypt';
    }

    protected function handleInput(&$data)
    {
        parent::handleInput($data);
        if (array_key_exists('password', $data)) {
            $data['password'] = $this->getCrypt()->encrypt($data['password']);
        }
    }

    /**
     * @return Crypt

     */
    protected function getCrypt()
    {
        return $this->injections['crypt'];
    }
}

