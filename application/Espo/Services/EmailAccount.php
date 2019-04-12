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

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Zend\Mail\Storage;

class EmailAccount extends Record
{
    const PORTION_LIMIT = 10;

    protected function init()
    {
        parent::init();
        $this->addDependency('crypt');
        $this->addDependency('notificatorFactory');
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
        $userId = $params['userId'] ?? null;
        if ($userId) {
            if (!$this->getUser()->isAdmin() && $userId !== $this->getUser()->id) {
                throw new Forbidden();
            }
        }

        $password = $params['password'];

        if (!empty($params['id'])) {
            $entity = $this->getEntityManager()->getEntity('EmailAccount', $params['id']);
            if ($entity) {
                $params['password'] = $this->getCrypt()->decrypt($entity->get('password'));
            }
        }

        $storage = $this->createStorage($params);

        $foldersArr = [];

        $folders = new \RecursiveIteratorIterator($storage->getFolders(), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($folders as $name => $folder) {
            $foldersArr[] = mb_convert_encoding($folder->getGlobalName(), 'UTF-8', 'UTF7-IMAP');
        }
        return $foldersArr;
    }

    public function testConnection(array $params)
    {
        $storage = $this->createStorage($params);

        $userId = $params['userId'] ?? null;
        if ($userId) {
            if (!$this->getUser()->isAdmin() && $userId !== $this->getUser()->id) {
                throw new Forbidden();
            }
        }

        if ($storage->getFolders()) {
            return true;
        }
        throw new Error();
    }

    protected function createStorage(array $params)
    {

        $emailAddress = $params['emailAddress'] ?? null;
        $userId = $params['userId'] ?? null;

        $handler = null;

        $imapParams = null;

        if ($emailAddress && $userId) {
            $emailAddress = strtolower($emailAddress);
            $userData = $this->getEntityManager()->getRepository('UserData')->getByUserId($userId);
            if ($userData) {
                $imapHandlers = $userData->get('imapHandlers') ?? (object) [];
                if (is_object($imapHandlers)) {
                    if (isset($imapHandlers->$emailAddress)) {
                        $handlerClassName = $imapHandlers->$emailAddress;
                        try {
                            $handler = $this->getInjection('injectableFactory')->createByClassName($handlerClassName);
                        } catch (\Throwable $e) {
                            $GLOBALS['log']->error("EmailAccount: Could not create Imap Handler for {$emailAddress}. Error: " . $e->getMessage());
                        }
                        if (method_exists($handler, 'prepareProtocol')) {
                            $imapParams = $handler->prepareProtocol($userId, $emailAddress, $params);
                        }
                    }
                }
            }
        }

        if (!$imapParams) {
            $imapParams = [
                'host' => $params['host'],
                'port' => $params['port'],
                'user' => $params['username'],
                'password' => $params['password'],
            ];
            if (!empty($params['ssl'])) {
                $imapParams['ssl'] = 'SSL';
            }
        }

        $storage = new \Espo\Core\Mail\Mail\Storage\Imap($imapParams);

        return $storage;
    }

    public function create($data)
    {
        if (!$this->getUser()->isAdmin()) {
            $count = $this->getEntityManager()->getRepository('EmailAccount')->where(array(
                'assignedUserId' => $this->getUser()->id
            ))->count();
            if ($count >= $this->getConfig()->get('maxEmailAccountCount', \PHP_INT_MAX)) {
                throw new Forbidden();
            }
        }

        $entity = parent::create($data);
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
        $params = [
            'host' => $emailAccount->get('host'),
            'port' => $emailAccount->get('port'),
            'username' => $emailAccount->get('username'),
            'password' => $this->getCrypt()->decrypt($emailAccount->get('password')),
            'emailAddress' => $emailAccount->get('emailAddress'),
            'userId' => $emailAccount->get('assignedUserId'),
        ];

        if ($emailAccount->get('ssl')) {
            $params['ssl'] = true;
        }

        $storage = $this->createStorage($params);

        return $storage;
    }

    public function fetchFromMailServer(Entity $emailAccount)
    {
        if ($emailAccount->get('status') != 'Active' || !$emailAccount->get('useImap')) {
            throw new Error("Email Account {$emailAccount->id} is not active.");
        }

        $notificator = $this->getInjection('notificatorFactory')->create('Email');

        $importer = new \Espo\Core\Mail\Importer($this->getEntityManager(), $this->getConfig(), $notificator);

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

        $storage = $this->getStorage($emailAccount);

        $monitoredFolders = $emailAccount->get('monitoredFolders');
        if (empty($monitoredFolders)) {
            throw new Error();
        }

        $parserName = 'MailMimeParser';
        if ($this->getConfig()->get('emailParser')) {
            $parserName = $this->getConfig()->get('emailParser');
        }

        $parserClassName = '\\Espo\\Core\\Mail\\Parsers\\' . $parserName;

        $monitoredFoldersArr = explode(',', $monitoredFolders);

        foreach ($monitoredFoldersArr as $folder) {
            $folder = mb_convert_encoding(trim($folder), 'UTF7-IMAP', 'UTF-8');

            $portionLimit = $this->getConfig()->get('personalEmailMaxPortionSize', self::PORTION_LIMIT);

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

                $folderData = null;
                if ($emailAccount->get('emailFolderId')) {
                    $folderData = array();
                    $folderData[$userId] = $emailAccount->get('emailFolderId');
                }

                $message = null;
                $email = null;
                try {
                    $parser = new $parserClassName($this->getEntityManager());
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
            'status' => 'Active'
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
            if ($emailAccount->get('smtpAuth')) {
                $smtpParams['username'] = $emailAccount->get('smtpUsername');
                $smtpParams['password'] = $emailAccount->get('smtpPassword');
            }
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }
            return $smtpParams;
        }
        return;
    }
}
