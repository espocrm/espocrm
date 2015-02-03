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

use \Espo\Core\Exceptions\Error;

class Email extends Record
{
    protected function init()
    {
        $this->dependencies[] = 'mailSender';
        $this->dependencies[] = 'preferences';
        $this->dependencies[] = 'fileManager';
        $this->dependencies[] = 'crypt';
    }

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

    public function createEntity($data)
    {
        $entity = parent::createEntity($data);

        if ($entity && $entity->get('status') == 'Sending') {
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

            $emailSender->send($entity);

            $this->getEntityManager()->saveEntity($entity);
        }

        return $entity;
    }

    public function getEntity($id = null)
    {
        $entity = parent::getEntity($id);
        if (!empty($entity) && !empty($id)) {

            if ($entity->get('fromEmailAddressName')) {
                $entity->set('from', $entity->get('fromEmailAddressName'));
            }

            $entity->loadLinkMultipleField('toEmailAddresses');
            $entity->loadLinkMultipleField('ccEmailAddresses');
            $entity->loadLinkMultipleField('bccEmailAddresses');

            $names = $entity->get('toEmailAddressesNames');
            if (!empty($names)) {
                $arr = array();
                foreach ($names as $id => $address) {
                    $arr[] = $address;
                }
                $entity->set('to', implode(';', $arr));
            }

            $names = $entity->get('ccEmailAddressesNames');
            if (!empty($names)) {
                $arr = array();
                foreach ($names as $id => $address) {
                    $arr[] = $address;
                }
                $entity->set('cc', implode(';', $arr));
            }

            $names = $entity->get('bccEmailAddressesNames');
            if (!empty($names)) {
                $arr = array();
                foreach ($names as $id => $address) {
                    $arr[] = $address;
                }
                $entity->set('bcc', implode(';', $arr));
            }

            $this->loadNameHash($entity);

            $this->loadAttachmentsTypes($entity);

        }
        return $entity;
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

    public function loadNameHash(Entity $entity)
    {
        $addressList = array();
        if ($entity->get('from')) {
            $addressList[] = $entity->get('from');
        }

        $arr = explode(';', $entity->get('to'));
        foreach ($arr as $address) {
            if (!in_array($address, $addressList)) {
                $addressList[] = $address;
            }
        }

        $arr = explode(';', $entity->get('cc'));
        foreach ($arr as $address) {
            if (!in_array($address, $addressList)) {
                $addressList[] = $address;
            }
        }

        $nameHash = array();
        $typeHash = array();
        $idHash = array();
        foreach ($addressList as $address) {
            $p = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($address);
            if ($p) {
                $nameHash[$address] = $p->get('name');
                $typeHash[$address] = $p->getEntityName();
                $idHash[$address] = $p->id;
            }
        }

        $entity->set('nameHash', $nameHash);
        $entity->set('typeHash', $typeHash);
        $entity->set('idHash', $idHash);
    }

    public function findEntities($params)
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

        $collection = $this->getRepository()->find($selectParams);

        foreach ($collection as $e) {
            $this->loadParentNameFields($e);
        }

        return array(
            'total' => $this->getRepository()->count($selectParams),
            'collection' => $collection,
        );
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

