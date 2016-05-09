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

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Zend\Mail\Storage;

class EmailAccount extends Record
{
    protected $internalAttributeList = ['password'];

    protected $readOnlyAttributeList= ['fetchData'];

    const PORTION_LIMIT = 10;

    protected function init()
    {
        $this->dependencies[] = 'fileManager';
        $this->dependencies[] = 'crypt';
    }

    protected function getFileManager()
    {
        return $this->injections['fileManager'];
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
        if ($emailAccount->get('status') != 'Active') {
            throw new Error();
        }

        $importer = new \Espo\Core\Mail\Importer($this->getEntityManager(), $this->getFileManager(), $this->getConfig());

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
                $dt = new \DateTime($emailAccount->get('fetchSince'));
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

                $message = null;
                $email = null;
                try {
                    $message = $storage->getMessage($id);
                    if ($message && $emailAccount->get('keepFetchedEmailsUnread')) {
                        $flags = $message->getFlags();
                    }
                    try {
                    	$email = $importer->importMessage($message, null, $teamIdList, [$userId], $filterCollection, $fetchOnlyHeader);
    	            } catch (\Exception $e) {
    	                $GLOBALS['log']->error('EmailAccount '.$emailAccount->id.' (Import Message): [' . $e->getCode() . '] ' .$e->getMessage());
    	            }

                    if ($emailAccount->get('keepFetchedEmailsUnread')) {
                        if (is_array($flags) && empty($flags[Storage::FLAG_SEEN])) {
                            $storage->setFlags($id, $flags);
                        }
                    }

                } catch (\Exception $e) {
                    $GLOBALS['log']->error('EmailAccount '.$emailAccount->id.' (Get Message): [' . $e->getCode() . '] ' .$e->getMessage());
                }

                if (!empty($email)) {
                    if (!$email->isFetched()) {
                        $this->noteAboutEmail($email);
                    }
                }

                if ($k == count($ids) - 1) {
                    $lastUID = $storage->getUniqueId($id);

                    if ($message && isset($message->date)) {
                        $dt = new \DateTime($message->date);
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

}

