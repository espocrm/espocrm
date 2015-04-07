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
 ************************************************************************/

namespace Espo\Services;

use \Espo\ORM\Entity;
use \Espo\Entities;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class Email extends Record
{
    protected function init()
    {
        $this->dependencies[] = 'mailSender';
        $this->dependencies[] = 'preferences';
        $this->dependencies[] = 'fileManager';
        $this->dependencies[] = 'crypt';
        $this->dependencies[] = 'serviceFactory';
    }

    private $streamService = null;

    protected $getEntityBeforeUpdate = true;

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    protected function getMailSender()
    {
        return $this->injections['mailSender'];
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
                $this->getStreamService()->noteEmailSent($parent, $entity);
            }
        }

        $emailSender->send($entity, $params);

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

    protected function afterUpdate(Entity $entity)
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
                $userIdList = $entity->get('usersIds');
                if (!is_array($userIdList) || !in_array($this->getUser()->id, $userIdList)) {
                    throw new Forbidden();
                }
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

    protected function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);

        $this->loadFromField($entity);
        $this->loadToField($entity);
        $this->loadCcField($entity);
        $this->loadBccField($entity);

        $this->loadNameHash($entity);

        if ($entity->id) {
            $this->loadAttachmentsTypes($entity);
        }
    }

    public function markAsReadByIds(array $ids)
    {
        foreach ($ids as $id) {
            $this->markAsRead($id);
        }
        return true;
    }

    public function markAllAsRead()
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_read = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($this->getUser()->id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function markAsRead($id)
    {

        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_read = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($this->getUser()->id) . " AND
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

        $status = $entity->get('status');
        if (in_array($status, ['Archived', 'Received'])) {
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
        } else if (in_array($status, ['Sent', 'Draft', 'Sending'])) {
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
        }

        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            SELECT is_read AS 'isRead' FROM email_user
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($this->getUser()->id) . " AND
                email_id = " . $pdo->quote($entity->id) . "
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        if ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $isRead = !empty($row['isRead']) ? true : false;
        } else {
            $isRead = true;
        }
        $entity->set('isRead', $isRead);
    }

    protected function loadAttachmentsTypes(Entity $entity)
    {
        $types = new \stdClass();

        $attachmentsIds = $entity->get('attachmentsIds');
        if (!empty($attachmentsIds)) {
            foreach ($attachmentsIds as $id) {
                $attachment = $this->getEntityManager()->getEntity('Attachment', $id);
                if ($attachment) {
                    $types->$id = $attachment->get('type');
                }
            }
        }

        $entity->set('attachmentsTypes', $types);
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

        if (!empty($id)) {
            $email = $this->getEntityManager()->getEntity('Email', $id);
            if ($email && $this->getAcl()->check($email, 'read')) {
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

                        if (!empty($parentType) && !empty($parentId)) {
                            $attachment->set('parentType', $parentType);
                            $attachment->set('parentId', $parentId);
                        }

                        $this->getEntityManager()->saveEntity($attachment);

                        $contents = $this->getFileManager()->getContents('data/upload/' . $source->id);
                        if (!empty($contents)) {
                            $this->getFileManager()->putContents('data/upload/' . $attachment->id, $contents);
                            $ids[] = $attachment->id;
                            $names->{$attachment->id} = $attachment->get('name');
                        }
                    }
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
}

