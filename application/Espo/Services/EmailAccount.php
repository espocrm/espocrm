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
namespace Espo\Services;

use Espo\Core\Exceptions\Error;
use Espo\Core\Mail\Importer;
use Espo\Core\Utils\Crypt;
use Espo\ORM\Entity;
use Zend\Mail\Storage\Imap;

class EmailAccount extends
    Record
{

    const PORTION_LIMIT = 10;

    protected $internalFields = array('password');

    protected $readOnlyFields = array('assignedUserId', 'fetchData');

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
        $storage = new Imap($imapParams);
        $folders = new \RecursiveIteratorIterator($storage->getFolders(), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($folders as $name => $folder) {
            $foldersArr[] = $folder->getGlobalName();
        }
        return $foldersArr;
    }

    public function createEntity($data)
    {
        $entity = parent::createEntity($data);
        if ($entity) {
            $entity->set('assignedUserId', $this->getUser()->id);
            $this->getEntityManager()->saveEntity($entity);
        }
        return $entity;
    }

    public function fetchFromMailServer(Entity $emailAccount)
    {
        if ($emailAccount->get('status') != 'Active') {
            throw new Error();
        }
        $importer = new Importer($this->getEntityManager(), $this->getFileManager());
        $maxSize = $this->getConfig()->get('emailMessageMaxSize');
        $user = $this->getEntityManager()->getEntity('User', $emailAccount->get('assignedUserId'));
        if (!$user) {
            throw new Error();
        }
        $userId = $user->id;
        $teamId = $user->get('defaultTeam');
        $fetchData = json_decode($emailAccount->get('fetchData'), true);
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
            'host' => $emailAccount->get('host'),
            'port' => $emailAccount->get('port'),
            'user' => $emailAccount->get('username'),
            'password' => $this->getCrypt()->decrypt($emailAccount->get('password')),
        );
        if ($emailAccount->get('ssl')) {
            $imapParams['ssl'] = 'SSL';
        }
        $storage = new \Espo\Core\Mail\Storage\Imap($imapParams);
        $monitoredFolders = $emailAccount->get('monitoredFolders');
        if (empty($monitoredFolders)) {
            throw new Error();
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
                if ($maxSize) {
                    if ($storage->getSize($id) > $maxSize * 1024 * 1024) {
                        continue;
                    }
                }
                $message = $storage->getMessage($id);
                $email = $importer->importMessage($message, $userId, array($teamId));
                if ($k == count($ids) - 1) {
                    $lastUID = $storage->getUniqueId($id);
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
            $emailAccount->set('fetchData', json_encode($fetchData));
            $this->getEntityManager()->saveEntity($emailAccount);
        }
        return true;
    }

    /**
     * @return \Espo\Core\Utils\File\Manager

     */
    protected function getFileManager()
    {
        return $this->injections['fileManager'];
    }

    protected function init()
    {
        $this->dependencies[] = 'fileManager';
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

