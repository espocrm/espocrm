<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Laminas\Mail\Storage;

use Espo\ORM\Entity;

use Espo\Core\{
    Exceptions\Error,
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Mail\Importer,
    Mail\MessageWrapper,
    Mail\Mail\Storage\Imap,
    Mail\Parsers\MailMimeParser,
};

use Espo\Entities\{
    EmailAccount as EmailAccountEntity,
    User,
};

use Espo\Core\Di;

use RecursiveIteratorIterator;
use Exception;
use Throwable;
use DateTime;
use DateTimeZone;
use StdClass;

class EmailAccount extends Record implements

    Di\CryptAware,
    Di\NotificatorFactoryAware
{
    use Di\CryptSetter;
    use Di\NotificatorFactorySetter;

    protected $storageClassName = Imap::class;

    protected $parserClassName = MailMimeParser::class;

    const PORTION_LIMIT = 10;

    protected function filterInput($data)
    {
        parent::filterInput($data);

        if (property_exists($data, 'password')) {
            $data->password = $this->crypt->encrypt($data->password);
        }

        if (property_exists($data, 'smtpPassword')) {
            $data->smtpPassword = $this->crypt->encrypt($data->smtpPassword);
        }
    }

    public function processValidation(Entity $entity, $data)
    {
        parent::processValidation($entity, $data);

        if ($entity->get('useImap')) {
            if (!$entity->get('fetchSince')) {
                throw new BadRequest("EmailAccount validation: fetchSince is required.");
            }
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
                $params['password'] = $this->crypt->decrypt($entity->get('password'));
                $params['imapHandler'] = $entity->get('imapHandler');
            }
        }

        $storage = $this->createStorage($params);

        $foldersArr = [];

        $folders = new RecursiveIteratorIterator($storage->getFolders(), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($folders as $name => $folder) {
            $foldersArr[] = mb_convert_encoding($folder->getGlobalName(), 'UTF-8', 'UTF7-IMAP');
        }

        return $foldersArr;
    }

    public function testConnection(array $params)
    {
        if (!empty($params['id'])) {
            $account = $this->getEntityManager()->getEntity('EmailAccount', $params['id']);
            if ($account) {
                $params['imapHandler'] = $account->get('imapHandler');
            }
        }

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

        $handlerClassName = $params['imapHandler'] ?? null;

        if ($handlerClassName && !empty($params['id'])) {
            try {
                $handler = $this->injectableFactory->create($handlerClassName);
            } catch (Throwable $e) {
                $GLOBALS['log']->error(
                    "EmailAccount: Could not create Imap Handler. Error: " . $e->getMessage()
                );
            }

            if (method_exists($handler, 'prepareProtocol')) {
                // for backward compatibility
                $params['ssl'] = $params['security'];

                $imapParams = $handler->prepareProtocol($params['id'], $params);
            }
        }

        if ($emailAddress && $userId && !$handlerClassName) {
            $emailAddress = strtolower($emailAddress);

            $userData = $this->getEntityManager()->getRepository('UserData')->getByUserId($userId);

            if ($userData) {
                $imapHandlers = $userData->get('imapHandlers') ?? (object) [];
                if (is_object($imapHandlers)) {
                    if (isset($imapHandlers->$emailAddress)) {
                        $handlerClassName = $imapHandlers->$emailAddress;
                        try {
                            $handler = $this->injectableFactory->create($handlerClassName);
                        } catch (Throwable $e) {
                            $GLOBALS['log']->error(
                                "EmailAccount: Could not create Imap Handler for {$emailAddress}. Error: " . $e->getMessage()
                            );
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

            if (!empty($params['security'])) {
                $imapParams['ssl'] = $params['security'];
            }
        }

        $storage = new $this->storageClassName($imapParams);

        return $storage;
    }

    public function create(StdClass $data) : Entity
    {
        if (!$this->getUser()->isAdmin()) {
            $count = $this->getEntityManager()->getRepository('EmailAccount')->where([
                'assignedUserId' => $this->getUser()->id
            ])->count();

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
            'password' => $this->crypt->decrypt($emailAccount->get('password')),
            'emailAddress' => $emailAccount->get('emailAddress'),
            'userId' => $emailAccount->get('assignedUserId'),
        ];

        if ($emailAccount->get('security')) {
            $params['security'] = $emailAccount->get('security');
        }

        $params['imapHandler'] = $emailAccount->get('imapHandler');
        $params['id'] = $emailAccount->id;

        $storage = $this->createStorage($params);

        return $storage;
    }

    public function fetchFromMailServer(Entity $emailAccount)
    {
        if ($emailAccount->get('status') != 'Active' || !$emailAccount->get('useImap')) {
            throw new Error("Email Account {$emailAccount->id} is not active.");
        }

        $notificator = $this->notificatorFactory->create('Email');

        $importer = new Importer($this->getEntityManager(), $this->getConfig(), $notificator, $this->parserClassName);

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


        $monitoredFoldersArr = explode(',', $monitoredFolders);

        foreach ($monitoredFoldersArr as $folder) {
            $folder = mb_convert_encoding(trim($folder), 'UTF7-IMAP', 'UTF-8');

            $portionLimit = $this->getConfig()->get('personalEmailMaxPortionSize', self::PORTION_LIMIT);

            try {
                $storage->selectFolder($folder);
            } catch (Exception $e) {
                $GLOBALS['log']->error(
                    'EmailAccount '.$emailAccount->id.' (Select Folder) [' . $e->getCode() . '] ' .$e->getMessage()
                );
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
                    $dt = new DateTime($fetchSince);
                } catch (Exception $e) {}

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
                    $folderData = [];
                    $folderData[$userId] = $emailAccount->get('emailFolderId');
                }

                $message = null;
                $email = null;
                try {
                    $parser = new $this->parserClassName($this->getEntityManager());
                    $message = new MessageWrapper($storage, $id, $parser);

                    if ($message->isFetched() && $emailAccount->get('keepFetchedEmailsUnread')) {
                        $flags = $message->getFlags();
                    }

                    $email = $this->importMessage(
                        $importer,
                        $emailAccount,
                        $message,
                        $teamIdList,
                        null,
                        [$userId],
                        $filterCollection,
                        $fetchOnlyHeader,
                        $folderData
                    );

                    if ($emailAccount->get('keepFetchedEmailsUnread')) {
                        if (is_array($flags) && empty($flags[Storage::FLAG_SEEN])) {
                            unset($flags[Storage::FLAG_RECENT]);
                            $storage->setFlags($id, $flags);
                        }
                    }

                } catch (Throwable $e) {
                    $GLOBALS['log']->error(
                        'EmailAccount '.$emailAccount->id.
                        ' (Get Message): [' . $e->getCode() . '] ' .$e->getMessage()
                    );
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
                            $dt = new DateTime($email->get('dateSent'));
                        } catch (Exception $e) {}

                        if ($dt) {
                            $nowDt = new DateTime();
                            if ($dt->getTimestamp() >= $nowDt->getTimestamp()) {
                                $dt = $nowDt;
                            }
                            $dateSent = $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                            $lastDate = $dateSent;
                        }
                    }

                    break;
                }

                $k++;
            }

            if ($forceByDate) {
                $nowDt = new DateTime();
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

    protected function importMessage(
        $importer,
        $emailAccount,
        $message,
        $teamIdList,
        $userId,
        $userIdList,
        $filterCollection,
        $fetchOnlyHeader,
        $folderData = null
    ) {
        $email = null;

        try {
            $email = $importer->importMessage(
                $message, $userId, $teamIdList, $userIdList, $filterCollection, $fetchOnlyHeader, $folderData
            );
        } catch (Exception $e) {
            $GLOBALS['log']->error(
                'EmailAccount '.$emailAccount->id.' (Import Message): [' . $e->getCode() . '] ' .
                $e->getMessage()
            );

            if ($this->getEntityManager()->getLocker()->isLocked()) {
                $this->getEntityManager()->getLocker()->rollback();
            }
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

    public function findAccountForUser(User $user, $address)
    {
        $emailAccount = $this->getEntityManager()->getRepository('EmailAccount')->where([
            'emailAddress' => $address,
            'assignedUserId' => $user->id,
            'status' => 'Active'
        ])->findOne();

        return $emailAccount;
    }

    public function getSmtpParamsFromAccount(EmailAccountEntity $emailAccount) : ?array
    {
        $smtpParams = [];
        $smtpParams['server'] = $emailAccount->get('smtpHost');
        if ($smtpParams['server']) {
            $smtpParams['port'] = $emailAccount->get('smtpPort');
            $smtpParams['auth'] = $emailAccount->get('smtpAuth');
            $smtpParams['security'] = $emailAccount->get('smtpSecurity');
            if ($emailAccount->get('smtpAuth')) {
                $smtpParams['username'] = $emailAccount->get('smtpUsername');
                $smtpParams['password'] = $emailAccount->get('smtpPassword');
                $smtpParams['authMechanism'] = $emailAccount->get('smtpAuthMechanism');
            }
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->crypt->decrypt($smtpParams['password']);
            }

            $this->applySmtpHandler($emailAccount, $smtpParams);

            return $smtpParams;
        }
        return null;
    }

    public function applySmtpHandler(EmailAccountEntity $emailAccount, array &$params)
    {
        $handlerClassName = $emailAccount->get('smtpHandler');
        if (!$handlerClassName) return;

        try {
            $handler = $this->injectableFactory->create($handlerClassName);
        } catch (Throwable $e) {
            $GLOBALS['log']->error(
                "EmailAccount: Could not create Smtp Handler for account {$emailAccount->id}. Error: " . $e->getMessage()
            );
        }
        if (method_exists($handler, 'applyParams')) {
            $handler->applyParams($emailAccount->id, $params);
        }
    }
}
