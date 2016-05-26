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
use \Espo\Entities;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\BadRequest;


class Email extends Record
{
    protected function init()
    {
        $this->dependencies[] = 'container';
        $this->dependencies[] = 'preferences';
        $this->dependencies[] = 'fileManager';
        $this->dependencies[] = 'crypt';
        $this->dependencies[] = 'serviceFactory';
    }

    private $streamService = null;

    protected $getEntityBeforeUpdate = true;

    protected $allowedForUpdateAttributeList = [
        'parentType', 'parentId', 'parentName', 'teamsIds', 'teamsNames', 'assignedUserId', 'assignedUserName'
    ];

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    protected function getMailSender()
    {
        return $this->getInjection('container')->get('mailSender');
    }

    protected function getPreferences()
    {
        return $this->injections['preferences'];
    }

    protected function getCrypt()
    {
        return $this->injections['crypt'];
    }

    protected function getServiceFactory()
    {
        return $this->injections['serviceFactory'];
    }

    protected function send(Entities\Email $entity)
    {
        $emailSender = $this->getMailSender();

        if (strtolower($this->getUser()->get('emailAddress')) == strtolower($entity->get('from'))) {
            $smtpParams = $this->getPreferences()->getSmtpParams();
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }

            if ($smtpParams) {
                $smtpParams['fromName'] = $this->getUser()->get('name');
                $emailSender->useSmtp($smtpParams);
            }
        } else {
            if (!$this->getConfig()->get('outboundEmailIsShared')) {
                throw new Error('Can not use system smtp. outboundEmailIsShared is false.');
            }
            $emailSender->setParams(array(
                'fromName' => $this->getUser()->get('name')
            ));
        }

        $params = array();

        if ($entity->get('parentType') && $entity->get('parentId')) {
            $parent = $this->getEntityManager()->getEntity($entity->get('parentType'), $entity->get('parentId'));
            if ($parent) {
                if ($entity->get('parentType') == 'Case') {
                    if ($parent->get('inboundEmailId')) {
                        $inboundEmail = $this->getEntityManager()->getEntity('InboundEmail', $parent->get('inboundEmailId'));
                        if ($inboundEmail && $inboundEmail->get('replyToAddress')) {
                            $params['replyToAddress'] = $inboundEmail->get('replyToAddress');
                        }
                    }
                }
            }
        }

        $message = null;

        try {
            $emailSender->send($entity, $params, $message);
        } catch (\Exception $e) {
            $entity->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($entity, array(
                'silent' => true
            ));
            throw new Error($e->getMessage(), $e->getCode());
        }

        if ($entity->get('from') && $message) {
            $emailAccount = $this->getEntityManager()->getRepository('EmailAccount')->where(array(
                'storeSentEmails' => true,
                'emailAddress' => $entity->get('from'),
                'assignedUserId' => $this->getUser()->id
            ))->findOne();
            if ($emailAccount) {
                try {
                    $emailAccountService = $this->getServiceFactory()->create('EmailAccount');
                    $emailAccountService->storeSentMessage($emailAccount, $message);
                } catch (\Exception $e) {
                    $GLOBALS['log']->error("Could not store sent email (Email Account {$emailAccount->id}): " . $e->getMessage());
                }
            }
        }

        if ($parent) {
            $this->getStreamService()->noteEmailSent($parent, $entity);
        }

        $entity->set('isJustSent', true);

        $this->getEntityManager()->saveEntity($entity);
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getServiceFactory()->create('Stream');
        }
        return $this->streamService;
    }

    public function createEntity($data)
    {
        $entity = parent::createEntity($data);

        if ($entity && $entity->get('status') == 'Sending') {
            $this->send($entity);
        }

        return $entity;
    }

    protected function afterUpdate(Entity $entity, array $data = array())
    {
        if ($entity && $entity->get('status') == 'Sending') {
            $this->send($entity);
        }

        $this->loadAdditionalFields($entity);
    }

    public function loadFromField(Entity $entity)
    {
        $this->getEntityManager()->getRepository('Email')->loadFromField($entity);
    }

    public function loadToField(Entity $entity)
    {
        $entity->loadLinkMultipleField('toEmailAddresses');
        $names = $entity->get('toEmailAddressesNames');
        if (!empty($names)) {
            $arr = array();
            foreach ($names as $id => $address) {
                $arr[] = $address;
            }
            $entity->set('to', implode(';', $arr));
        }
    }

    public function loadCcField(Entity $entity)
    {
        $entity->loadLinkMultipleField('ccEmailAddresses');
        $names = $entity->get('ccEmailAddressesNames');
        if (!empty($names)) {
            $arr = array();
            foreach ($names as $id => $address) {
                $arr[] = $address;
            }
            $entity->set('cc', implode(';', $arr));
        }
    }

    public function loadBccField(Entity $entity)
    {
        $entity->loadLinkMultipleField('bccEmailAddresses');
        $names = $entity->get('bccEmailAddressesNames');
        if (!empty($names)) {
            $arr = array();
            foreach ($names as $id => $address) {
                $arr[] = $address;
            }
            $entity->set('bcc', implode(';', $arr));
        }
    }

    public function getEntity($id = null)
    {
        $entity = $this->getRepository()->get($id);
        if (!empty($entity) && !empty($id)) {
            $this->loadAdditionalFields($entity);

            if (!$this->getAcl()->check($entity, 'read')) {
                throw new Forbidden();
            }
        }
        if (!empty($entity)) {
            $this->prepareEntityForOutput($entity);
        }

        if (!empty($entity) && !empty($id)) {
            $this->markAsRead($entity->id);
        }
        return $entity;
    }

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);

        $this->loadFromField($entity);
        $this->loadToField($entity);
        $this->loadCcField($entity);
        $this->loadBccField($entity);

        $this->loadNameHash($entity);

        $this->loadUserColumnFields($entity);
    }

    public function markAsReadByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->markAsRead($id, $userId);
        }
        return true;
    }

    public function markAsNotReadByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->markAsNotRead($id, $userId);
        }
        return true;
    }

    public function markAsImportantByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->markAsImportant($id, $userId);
        }
        return true;
    }

    public function markAsNotImportantByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->markAsNotImportant($id, $userId);
        }
        return true;
    }

    public function moveToTrashByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->moveToTrash($id, $userId);
        }
        return true;
    }

    public function retrieveFromTrashByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->retrieveFromTrash($id, $userId);
        }
        return true;
    }

    public function markAllAsRead($userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_read = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function markAsRead($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_read = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function markAsNotRead($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_read = 0
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function markAsImportant($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_important = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function markAsNotImportant($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_important = 0
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function moveToTrash($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET in_trash = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function retrieveFromTrash($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET in_trash = 0
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    static public function parseFromName($string)
    {
        $fromName = '';
        if ($string) {
            if (stripos($string, '<') !== false) {
                $fromName = trim(preg_replace('/(<.*>)/', '', $string), '" ');
            }
        }
        return $fromName;
    }

    public function loadAdditionalFieldsForList(Entity $entity)
    {
        parent::loadAdditionalFieldsForList($entity);

        $userEmailAdddressIdList = [];
        foreach ($this->getUser()->get('emailAddresses') as $ea) {
            $userEmailAdddressIdList[] = $ea->id;
        }

        $status = $entity->get('status');
        if (in_array($entity->get('fromEmailAddressId'), $userEmailAdddressIdList)) {
            $entity->loadLinkMultipleField('toEmailAddresses');
            $idList = $entity->get('toEmailAddressesIds');
            $names = $entity->get('toEmailAddressesNames');

            if (!empty($idList)) {
                $arr = [];
                foreach ($idList as $emailAddressId) {
                    $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddressId($emailAddressId);
                    if ($person) {
                        $arr[] = $person->get('name');
                    } else {
                        $arr[] = $names->$emailAddressId;
                    }
                }
                $entity->set('personStringData', 'To: ' . implode(', ', $arr));
            }
        } else {
            $fromEmailAddressId = $entity->get('fromEmailAddressId');
            if (!empty($fromEmailAddressId)) {
                $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddressId($fromEmailAddressId);
                if ($person) {
                    $entity->set('personStringData', $person->get('name'));
                } else {
                    $fromName = self::parseFromName($entity->get('fromString'));
                    if (!empty($fromName)) {
                        $entity->set('personStringData', $fromName);
                    } else {
                        $entity->set('personStringData', $entity->get('fromEmailAddressName'));
                    }
                }
            }
        }
    }

    public function loadUserColumnFields(Entity $entity)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            SELECT is_read AS 'isRead', is_important AS 'isImportant', in_trash AS 'inTrash' FROM email_user
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($this->getUser()->id) . " AND
                email_id = " . $pdo->quote($entity->id) . "
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        if ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $isRead = !empty($row['isRead']) ? true : false;
            $isImportant = !empty($row['isImportant']) ? true : false;
            $inTrash = !empty($row['inTrash']) ? true : false;

            $entity->set('isRead', $isRead);
            $entity->set('isImportant', $isImportant);
            $entity->set('inTrash', $inTrash);
        } else {
            $entity->set('isRead', null);
            $entity->clear('isImportant');
            $entity->clear('inTrash');
        }
    }

    public function loadNameHash(Entity $entity, array $fieldList = ['from', 'to', 'cc'])
    {
        $this->getEntityManager()->getRepository('Email')->loadNameHash($entity, $fieldList);
    }

    protected function getSelectParams($params)
    {
        $searchByEmailAddress = false;
        if (!empty($params['where']) && is_array($params['where'])) {
            foreach ($params['where'] as $i => $p) {
                if (!empty($p['field']) && $p['field'] == 'emailAddress') {
                    $searchByEmailAddress = true;
                    $emailAddress = $p['value'];
                    unset($params['where'][$i]);
                }

            }
        }

        $selectManager = $this->getSelectManager($this->entityName);

        $selectParams = $selectManager->getSelectParams($params, true);

        if ($searchByEmailAddress) {
            $selectManager->whereEmailAddress($emailAddress, $selectParams);
        }

        return $selectParams;
    }

    public function copyAttachments($emailId, $parentType, $parentId)
    {
        return $this->getCopiedAttachments($emailId, $parentType, $parentId);
    }

    public function getCopiedAttachments($id, $parentType = null, $parentId = null)
    {
        $ids = array();
        $names = new \stdClass();

        if (empty($id)) {
            throw new BadRequest();
        }
        $email = $this->getEntityManager()->getEntity('Email', $id);
        if (!$email) {
            throw new NotFound();
        }
        if (!$this->getAcl()->checkEntity($email, 'read')) {
            throw new Forbidden();
        }
        $email->loadLinkMultipleField('attachments');
        $attachmentsIds = $email->get('attachmentsIds');

        foreach ($attachmentsIds as $attachmentId) {
            $source = $this->getEntityManager()->getEntity('Attachment', $attachmentId);
            if ($source) {
                $attachment = $this->getEntityManager()->getEntity('Attachment');
                $attachment->set('role', 'Attachment');
                $attachment->set('type', $source->get('type'));
                $attachment->set('size', $source->get('size'));
                $attachment->set('global', $source->get('global'));
                $attachment->set('name', $source->get('name'));
                $attachment->set('sourceId', $source->getSourceId());

                if (!empty($parentType) && !empty($parentId)) {
                    $attachment->set('parentType', $parentType);
                    $attachment->set('parentId', $parentId);
                }

                if ($this->getFileManager()->isFile('data/upload/' . $source->getSourceId())) {
                    $this->getEntityManager()->saveEntity($attachment);

                    $this->getFileManager()->putContents('data/upload/' . $attachment->id, $contents);
                    $ids[] = $attachment->id;
                    $names->{$attachment->id} = $attachment->get('name');
                }
            }
        }

        return array(
            'ids' => $ids,
            'names' => $names
        );
    }

    public function sendTestEmail($data)
    {
        $email = $this->getEntityManager()->getEntity('Email');

        $email->set(array(
            'subject' => 'EspoCRM: Test Email',
            'isHtml' => false,
            'to' => $data['emailAddress']
        ));

        $emailSender = $this->getMailSender();
        $emailSender->useSmtp($data)->send($email);

        return true;
    }

    protected function beforeUpdate(Entity $entity, array $data = array())
    {
        if ($this->getUser()->isAdmin()) return;

        if ($entity->isManuallyArchived()) return;

        $attributList = $entity;

        foreach ($entity->getAttributeList() as $attribute) {
            if (in_array($attribute, $this->allowedForUpdateAttributeList)) return;
            $entity->clear($attribute);
        }
    }
}

