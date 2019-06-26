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

namespace Espo\Repositories;

use Espo\ORM\Entity;

class Email extends \Espo\Core\ORM\Repositories\RDB
{
    protected function init()
    {
        parent::init();
        $this->addDependency('emailFilterManager');
    }

    protected function prepareAddressess(Entity $entity, $type, $addAssignedUser = false)
    {
        if (!$entity->has($type)) {
            return;
        }

        $eaRepository = $this->getEntityManager()->getRepository('EmailAddress');

        $addressValue = $entity->get($type);
        $idList = [];

        if (!empty($addressValue)) {
            $addressList = array_map(function ($item) {
                return trim($item);
            }, explode(';', $addressValue));

            $addressList = array_filter($addressList, function ($item) {
                return filter_var($item, FILTER_VALIDATE_EMAIL);
            });

            $idList = $eaRepository->getIdListFormAddressList($addressList);

            if ($type !== 'replyTo') {
                foreach ($idList as $id) {
                    $this->addUserByEmailAddressId($entity, $id, $addAssignedUser);
                }
            }
        }

        $entity->setLinkMultipleIdList($type . 'EmailAddresses', $idList);
    }

    protected function addUserByEmailAddressId(Entity $entity, $emailAddressId, $addAssignedUser = false)
    {
        $userList = $this->getEntityManager()->getRepository('EmailAddress')->getEntityListByAddressId($emailAddressId, null, 'User', true);

        foreach ($userList as $user) {
            $entity->addLinkMultipleId('users', $user->id);
            if ($addAssignedUser) {
                $entity->addLinkMultipleId('assignedUsers', $user->id);
            }
        }
    }

    public function loadFromField(Entity $entity)
    {
        if ($entity->get('fromEmailAddressName')) {
            $entity->set('from', $entity->get('fromEmailAddressName'));
        } else {
            $entity->set('from', null);
        }
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

    public function loadReplyToField(Entity $entity)
    {
        $entity->loadLinkMultipleField('replyToEmailAddresses');
        $names = $entity->get('replyToEmailAddressesNames');
        if (!empty($names)) {
            $arr = array();
            foreach ($names as $id => $address) {
                $arr[] = $address;
            }
            $entity->set('replyTo', implode(';', $arr));
        }
    }

    public function loadNameHash(Entity $entity, array $fieldList = ['from', 'to', 'cc', 'bcc', 'replyTo'])
    {
        $addressList = array();
        if (in_array('from', $fieldList) && $entity->get('from')) {
            $addressList[] = $entity->get('from');
        }

        if (in_array('to', $fieldList)) {
            $arr = explode(';', $entity->get('to'));
            foreach ($arr as $address) {
                if (!in_array($address, $addressList)) {
                    $addressList[] = $address;
                }
            }
        }

        if (in_array('cc', $fieldList)) {
            $arr = explode(';', $entity->get('cc'));
            foreach ($arr as $address) {
                if (!in_array($address, $addressList)) {
                    $addressList[] = $address;
                }
            }
        }
        if (in_array('bcc', $fieldList)) {
            $arr = explode(';', $entity->get('bcc'));
            foreach ($arr as $address) {
                if (!in_array($address, $addressList)) {
                    $addressList[] = $address;
                }
            }
        }
        if (in_array('replyTo', $fieldList)) {
            $arr = explode(';', $entity->get('replyTo'));
            foreach ($arr as $address) {
                if (!in_array($address, $addressList)) {
                    $addressList[] = $address;
                }
            }
        }

        $nameHash = (object) [];
        $typeHash = (object) [];
        $idHash = (object) [];
        foreach ($addressList as $address) {
            $p = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($address);
            if (!$p) {
                $p = $this->getEntityManager()->getRepository('InboundEmail')->where(array('emailAddress' => $address))->findOne();
            }
            if ($p) {
                $nameHash->$address = $p->get('name');
                $typeHash->$address = $p->getEntityType();
                $idHash->$address = $p->id;
            }
        }

        $addressNameMap = $entity->get('addressNameMap');
        if (is_object($addressNameMap)) {
            foreach (get_object_vars($addressNameMap) as $key => $value) {
                if (!isset($nameHash->$key)) {
                    $nameHash->$key = $value;
                }
            }
        }

        $entity->set('nameHash', $nameHash);
        $entity->set('typeHash', $typeHash);
        $entity->set('idHash', $idHash);
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew() && !$entity->get('messageId')) {
            $entity->setDummyMessageId();
        }

        if ($entity->has('attachmentsIds')) {
            $attachmentsIds = $entity->get('attachmentsIds');
            if (!empty($attachmentsIds)) {
                $entity->set('hasAttachment', true);
            }
        }

        if ($entity->has('from') || $entity->has('to') || $entity->has('cc') || $entity->has('bcc') || $entity->has('replyTo')) {
            if (!$entity->has('usersIds')) {
                $entity->loadLinkMultipleField('users');
            }

            if ($entity->has('from')) {
                $from = trim($entity->get('from'));
                if (!empty($from)) {
                    $ids = $this->getEntityManager()->getRepository('EmailAddress')->getIds([$from]);
                    if (!empty($ids)) {
                        $entity->set('fromEmailAddressId', $ids[0]);
                        $this->addUserByEmailAddressId($entity, $ids[0], true);

                        if (!$entity->get('sentById')) {
                            $user = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddressId($entity->get('fromEmailAddressId'), 'User', true);
                            if ($user) {
                                $entity->set('sentById', $user->id);
                            }
                        }
                    }
                } else {
                    $entity->set('fromEmailAddressId', null);
                }
            }

            if ($entity->has('to')) {
                $this->prepareAddressess($entity, 'to', true);
            }
            if ($entity->has('cc')) {
                $this->prepareAddressess($entity, 'cc');
            }
            if ($entity->has('bcc')) {
                $this->prepareAddressess($entity, 'bcc');
            }
            if ($entity->has('replyTo')) {
                $this->prepareAddressess($entity, 'replyTo');
            }

            $assignedUserId = $entity->get('assignedUserId');
            if ($assignedUserId) {
                $entity->addLinkMultipleId('users', $assignedUserId);
            }
        }


        parent::beforeSave($entity, $options);

        if ($entity->get('status') === 'Sending' && $entity->get('createdById')) {
            $entity->addLinkMultipleId('users', $entity->get('createdById'));
            $entity->setLinkMultipleColumn('users', 'isRead', $entity->get('createdById'), true);
        }

        if ($entity->isNew() || $entity->isAttributeChanged('parentId')) {
            $this->fillAccount($entity);
        }

        if (!empty($options['isBeingImported']) || !empty($options['isJustSent'])) {
            if (!$entity->has('from')) {
                $this->loadFromField($entity);
            }
            if (!$entity->has('to')) {
                $this->loadToField($entity);
            }

            $this->applyUsersFilters($entity);
        }
    }

    public function fillAccount(Entity $entity)
    {
        if (!$entity->isNew()) {
            $entity->set('accountId', null);
        }

        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');
        if ($parentId && $parentType) {
            $parent = $this->getEntityManager()->getEntity($parentType, $parentId);
            if ($parent) {
                $accountId = null;
                if ($parent->getEntityType() == 'Account') {
                    $accountId = $parent->id;
                }
                if (!$accountId && $parent->get('accountId') && $parent->getRelationParam('account', 'entity') == 'Account') {
                    $accountId = $parent->get('accountId');
                }
                if ($accountId) {
                    $account = $this->getEntityManager()->getEntity('Account', $accountId);
                    if ($account) {
                        $entity->set('accountId', $accountId);
                        $entity->set('accountName', $account->get('name'));
                    }
                }
            }
        }
    }

    public function applyUsersFilters(Entity $entity)
    {
        foreach ($entity->getLinkMultipleIdList('users') as $userId) {
            if ($entity->get('status') === 'Sent') {
                if ($entity->get('sentById') && $entity->get('sentById') === $userId) continue;
            }

            $filter = $this->getEmailFilterManager()->getMatchingFilter($entity, $userId);
            if ($filter) {
                $action = $filter->get('action');
                if ($action === 'Skip') {
                    $entity->setLinkMultipleColumn('users', 'inTrash', $userId, true);
                } else if ($action === 'Move to Folder') {
                    $folderId = $filter->get('emailFolderId');
                    if ($folderId) {
                        $entity->setLinkMultipleColumn('users', 'folderId', $userId, $folderId);
                    }
                }
            }
        }
    }

    protected function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);

        if (!$entity->isNew()) {
            if ($entity->get('parentType') && $entity->get('parentId') && $entity->isAttributeChanged('parentId')) {
                $replyList = $this->findRelated($entity, 'replies');
                foreach ($replyList as $reply) {
                    if ($reply->id === $entity->id) continue;
                    if (!$reply->get('parentId')) {
                        $reply->set([
                            'parentId' => $entity->get('parentId'),
                            'parentType' => $entity->get('parentType')
                        ]);
                        $this->getEntityManager()->saveEntity($reply);
                    }
                }
            }
        }

        if (
            ($entity->get('status') === 'Archived' || $entity->get('status') === 'Sent')
            &&
            ($entity->isAttributeChanged('status') || $entity->isNew())
        ) {
            if ($entity->get('repliedId')) {
                $replied = $this->getEntityManager()->getEntity('Email', $entity->get('repliedId'));
                if ($replied && $replied->id !== $entity->id && !$replied->get('isReplied')) {
                    $replied->set('isReplied', true);
                    $this->getEntityManager()->saveEntity($replied, ['silent' => true]);
                }
            }
        }

        if ($entity->get('isBeingImported')) {
            $entity->set('isBeingImported', false);
        }
    }

    protected function getEmailFilterManager()
    {
        return $this->getInjection('emailFilterManager');
    }
}
