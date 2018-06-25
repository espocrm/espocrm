<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Zend\Mail\Storage;

class EmailAccount extends Record
{
    protected $internalAttributeList = ['password', 'smtpPassword'];

    protected $readOnlyAttributeList = ['fetchData'];

    const PORTION_LIMIT = 10;

    protected function init()
    {
        parent::init();
        $this->addDependency('crypt');
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
            $entity = $this->getEntityManager()->getEntity('EmailAccount', $params['id']);
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

    public function createEntity($data)
    {
        if (!$this->getUser()->isAdmin()) {
            $count = $this->getEntityManager()->getRepository('EmailAccount')->where(array(
                'assignedUserId' => $this->getUser()->id
            ))->count();
            if ($count >= $this->getConfig()->get('maxEmailAccountCount', \PHP_INT_MAX)) {
                throw new Forbidden();
            }
        }

        $entity = parent::createEntity($data);
        if ($entity) {
            if (!$this->getUser()->isAdmin()) {
                $entity->set('assignedUserId', $this->getUser()->id);
            }
            $this->getEntityManager()->saveEntity($entity);
        }
        return $entity;
    }

    public function storeSentMessage(Entity $emailAccount, $message)
    {
        $storage = $this->getStorage($emailAccount);

        $folder = $emailAccount->get('sentFolder');
        if (empty($folder)) {
            throw new Error("No sent folder for Email Account: " . $emailAccount->id . ".");
        }

        $storage->appendMessage($message->toString(), $folder);
    }

    protected function getStorage(Entity $emailAccount)
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

    public function fetchFromMailServer(Entity $emailAccount)
    {
        if ($emailAccount->get('status') != 'Active' || !$emailAccount->get('useImap')) {
            throw new Error("Email Account {$emailAccount->id} is not active.");
        }

        $importer = new \Espo\Core\Mail\Importer($this->getEntityManager(), $this->getConfig());

        $maxSize = $this->getConfig()->get('emailMessageMaxSize');

        $user = $this->getEntityManager()->getEntity('User', $emailAccount->get('assignedUserId'));
        if (!$user) {
            throw new Error();
        }

        $userId = $user->id;
        $teamId = $user->get('defaultTeamId');
        $teamIdList = [];
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
            $fetchData = new \StdClass();
        }
        if (!property_exists($fetchData, 'lastUID')) {
            $fetchData->lastUID = new \StdClass();;
        }
        if (!property_exists($fetchData, 'lastDate')) {
            $fetchData->lastDate = new \StdClass();;
        }

        $storage = $this->getStorage($emailAccount);

        $monitoredFolders = $emailAccount->get('monitoredFolders');
        if (empty($monitoredFolders)) {
            throw new Error();
        }

        $portionLimit = $this->getConfig()->get('personalEmailMaxPortionSize', self::PORTION_LIMIT);

        $parserName = 'MailMimeParser';
        if ($this->getConfig()->get('emailParser')) {
            $parserName = $this->getConfig()->get('emailParser');
        }

        $parserClassName = '\\Espo\\Core\\Mail\\Parsers\\' . $parserName;
        $parser = new $parserClassName($this->getEntityManager());

        $monitoredFoldersArr = explode(',', $monitoredFolders);
        foreach ($monitoredFoldersArr as $folder) {
            $folder = mb_convert_encoding(trim($folder), 'UTF7-IMAP', 'UTF-8');

            try {
                $storage->selectFolder($folder);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('EmailAccount '.$emailAccount->id.' (Select Folder) [' . $e->getCode() . '] ' .$e->getMessage());
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
                if ($k == count($ids) - 1) {
                    $lastUID = $storage->getUniqueId($id);
                }

                $fetchOnlyHeader = false;
                if ($maxSize) {
                    if ($storage->getSize($id) > $maxSize * 1024 * 1024) {
                        $fetchOnlyHeader = true;
                    }
                }

                $folderData = null;
                if ($emailAccount->get('emailFolderId')) {
                    $folderData = array();
                    $folderData[$userId] = $emailAccount->get('emailFolderId');
                }

                $message = null;
                $email = null;
                try {
                    $message = new \Espo\Core\Mail\MessageWrapper($storage, $id, $parser);

                    if ($message->isFetched() && $emailAccount->get('keepFetchedEmailsUnread')) {
                        $flags = $message->getFlags();
                    }

                    $email = $this->importMessage($parserName, $importer, $emailAccount, $message, $teamIdList, null, [$userId], $filterCollection, $fetchOnlyHeader, $folderData);

                    if ($emailAccount->get('keepFetchedEmailsUnread')) {
                        if (is_array($flags) && empty($flags[Storage::FLAG_SEEN])) {
                            unset($flags[Storage::FLAG_RECENT]);
                            $storage->setFlags($id, $flags);
                        }
                    }

                } catch (\Exception $e) {
                    $GLOBALS['log']->error('EmailAccount '.$emailAccount->id.' (Get Message w/ parser '.$parserName.'): [' . $e->getCode() . '] ' .$e->getMessage());
                }

                if (!empty($email)) {
                    $this->getEntityManager()->getRepository('EmailAccount')->relate($emailAccount, 'emails', $email);
                    if (!$email->isFetched()) {
                        $this->noteAboutEmail($email);
                    }
                }

                if ($k == count($ids) - 1) {
                    $lastUID = $storage->getUniqueId($id);

                    if ($email && $email->get('dateSent')) {
                        $dt = null;
                        try {
                            $dt = new \DateTime($email->get('dateSent'));
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

    protected function importMessage($parserName, $importer, $emailAccount, $message, $teamIdList, $userId = null, $userIdList = [], $filterCollection, $fetchOnlyHeader, $folderData = null)
    {
        $email = null;
        try {
            $email = $importer->importMessage($parserName, $message, $userId, $teamIdList, $userIdList, $filterCollection, $fetchOnlyHeader, $folderData);
        } catch (\Exception $e) {
            $GLOBALS['log']->error('EmailAccount '.$emailAccount->id.' (Import Message w/ '.$parserName.'): [' . $e->getCode() . '] ' .$e->getMessage());
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

    public function findAccountForUser(\Espo\Entities\User $user, $address)
    {
        $emailAccount = $this->getEntityManager()->getRepository('EmailAccount')->where([
            'emailAddress' => $address,
            'assignedUserId' => $user->id,
            'active' => true
        ])->findOne();

        return $emailAccount;
    }

    public function getSmtpParamsFromAccount(\Espo\Entities\EmailAccount $emailAccount)
    {
        $smtpParams = array();
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
}
