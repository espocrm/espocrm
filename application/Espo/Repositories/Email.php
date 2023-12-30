<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Repositories;

use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Entities\EmailFilter;
use Espo\Entities\InboundEmail;
use Espo\Entities\User as UserEntity;
use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\Repositories\Database;

use Espo\Core\Di;

use Espo\Entities\Email as EmailEntity;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Entities\EmailAddress;
use stdClass;

/**
 * @extends Database<EmailEntity>
 */
class Email extends Database implements

    Di\EmailFilterManagerAware
{
    use Di\EmailFilterManagerSetter;

    protected function prepareAddresses(EmailEntity $entity, string $type, bool $addAssignedUser = false): void
    {
        if (!$entity->has($type)) {
            return;
        }

        $eaRepository = $this->getEmailAddressRepository();

        $addressValue = $entity->get($type);
        $idList = [];

        if (!empty($addressValue)) {
            $addressList = array_map(
                function ($item) {
                    return trim($item);
                },
                explode(';', $addressValue)
            );

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

    protected function addUserByEmailAddressId(
        EmailEntity $entity,
        string $emailAddressId,
        bool $addAssignedUser = false
    ): void {

        $userList = $this->getEmailAddressRepository()
            ->getEntityListByAddressId($emailAddressId, null, UserEntity::ENTITY_TYPE, true);

        foreach ($userList as $user) {
            $entity->addLinkMultipleId('users', $user->getId());

            if ($addAssignedUser) {
                $entity->addLinkMultipleId('assignedUsers', $user->getId());
            }
        }
    }

    public function loadFromField(EmailEntity $entity): void
    {
        if ($entity->get('fromEmailAddressName')) {
            $entity->set('from', $entity->get('fromEmailAddressName'));

            return;
        }

        $fromEmailAddressId = $entity->get('fromEmailAddressId');

        if ($fromEmailAddressId) {
            $ea = $this->getEmailAddressRepository()->getById($fromEmailAddressId);

            if ($ea) {
                $entity->set('from', $ea->get('name'));

                return;
            }
        }

        if (!$entity->has('fromEmailAddressId')) {
            return;
        }

        $entity->set('from', null);
    }

    public function loadToField(EmailEntity $entity): void
    {
        $entity->loadLinkMultipleField('toEmailAddresses');
        /** @var ?stdClass $names */
        $names = $entity->get('toEmailAddressesNames');

        if ($names === null) {
            return;
        }

        $arr = [];

        foreach (get_object_vars($names) as $address) {
            $arr[] = $address;
        }

        $entity->set('to', implode(';', $arr));
    }

    public function loadCcField(EmailEntity $entity): void
    {
        $entity->loadLinkMultipleField('ccEmailAddresses');
        /** @var ?stdClass $names */
        $names = $entity->get('ccEmailAddressesNames');

        if ($names === null) {
            return;
        }

        $arr = [];

        foreach (get_object_vars($names) as $address) {
            $arr[] = $address;
        }

        $entity->set('cc', implode(';', $arr));
    }

    public function loadBccField(EmailEntity $entity): void
    {
        $entity->loadLinkMultipleField('bccEmailAddresses');
        /** @var ?stdClass $names */
        $names = $entity->get('bccEmailAddressesNames');

        if ($names === null) {
            return;
        }

        $arr = [];

        foreach (get_object_vars($names) as $address) {
            $arr[] = $address;
        }

        $entity->set('bcc', implode(';', $arr));
    }

    public function loadReplyToField(EmailEntity $entity): void
    {
        $entity->loadLinkMultipleField('replyToEmailAddresses');

        $names = $entity->get('replyToEmailAddressesNames');

        if (!empty($names)) {
            $arr = [];

            foreach ($names as $address) {
                $arr[] = $address;
            }

            $entity->set('replyTo', implode(';', $arr));
        }
    }

    /**
     * @param string[] $fieldList
     */
    public function loadNameHash(EmailEntity $entity, array $fieldList = ['from', 'to', 'cc', 'bcc', 'replyTo']): void
    {
        $addressList = [];

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
            $p = $this->getEmailAddressRepository()->getEntityByAddress($address);

            if (!$p) {
                $p = $this->entityManager
                    ->getRDBRepository(InboundEmail::ENTITY_TYPE)
                    ->where(['emailAddress' => $address])
                    ->findOne();
            }

            if ($p) {
                $nameHash->$address = $p->get('name');
                $typeHash->$address = $p->getEntityType();

                $idHash->$address = $p->getId();
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

    /**
     * @param EmailEntity $entity
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew() && !$entity->getMessageId()) {
            $entity->setDummyMessageId();
        }

        if ($entity->has('attachmentsIds')) {
            $attachmentsIds = $entity->get('attachmentsIds');
            if (!empty($attachmentsIds)) {
                $entity->set('hasAttachment', true);
            }
        }

        if (
            $entity->has('from') ||
            $entity->has('to') ||
            $entity->has('cc') ||
            $entity->has('bcc') ||
            $entity->has('replyTo')
        ) {
            if (!$entity->has('usersIds')) {
                $entity->loadLinkMultipleField('users');
            }

            if ($entity->has('from')) {
                $from = trim($entity->get('from'));

                if (!empty($from)) {
                    $ids = $this->getEmailAddressRepository()->getIdListFormAddressList([$from]);

                    if (!empty($ids)) {
                        $entity->set('fromEmailAddressId', $ids[0]);
                        $entity->set('fromEmailAddressName', $from);

                        $this->addUserByEmailAddressId($entity, $ids[0], true);

                        if (!$entity->get('sentById')) {
                            $user = $this->getEmailAddressRepository()
                                ->getEntityByAddressId(
                                    $entity->get('fromEmailAddressId'),
                                    UserEntity::ENTITY_TYPE,
                                    true
                                );

                            if ($user) {
                                $entity->set('sentById', $user->getId());
                            }
                        }
                    }
                } else {
                    $entity->set('fromEmailAddressId', null);
                }
            }

            if ($entity->has('to')) {
                $this->prepareAddresses($entity, 'to', true);
            }

            if ($entity->has('cc')) {
                $this->prepareAddresses($entity, 'cc');
            }

            if ($entity->has('bcc')) {
                $this->prepareAddresses($entity, 'bcc');
            }

            if ($entity->has('replyTo')) {
                $this->prepareAddresses($entity, 'replyTo');
            }

            $assignedUserId = $entity->get('assignedUserId');
            if ($assignedUserId) {
                $entity->addLinkMultipleId('users', $assignedUserId);
            }
        }

        parent::beforeSave($entity, $options);

        if ($entity->getStatus() === EmailEntity::STATUS_SENDING && $entity->get('createdById')) {
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

    public function fillAccount(EmailEntity $entity): void
    {
        if (!$entity->isNew()) {
            $entity->set('accountId', null);
        }

        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');

        if ($parentId && $parentType) {
            $parent = $this->entityManager->getEntity($parentType, $parentId);

            if ($parent) {
                $accountId = null;

                if ($parent->getEntityType() == Account::ENTITY_TYPE) {
                    $accountId = $parent->getId();
                }

                if (
                    !$accountId &&
                    $parent->get('accountId') &&
                    $parent instanceof CoreEntity &&
                    $parent->getRelationParam('account', 'entity') === Account::ENTITY_TYPE
                ) {
                    $accountId = $parent->get('accountId');
                }

                if ($accountId) {
                    $account = $this->entityManager->getEntityById(Account::ENTITY_TYPE, $accountId);

                    if ($account) {
                        $entity->set('accountId', $accountId);
                        $entity->set('accountName', $account->get('name'));
                    }
                }
            }
        }
    }

    public function applyUsersFilters(EmailEntity $entity): void
    {
        $userIdList = $entity->getLinkMultipleIdList('users');

        foreach ($userIdList as $userId) {
            if (
                $entity->getStatus() === EmailEntity::STATUS_SENT &&
                $entity->getSentBy()?->getId() === $userId
            ) {
                continue;
            }

            $filter = $this->emailFilterManager->getMatchingFilter($entity, $userId);

            if (!$filter) {
                continue;
            }

            if ($filter->getAction() === EmailFilter::ACTION_SKIP) {
                $entity->setLinkMultipleColumn('users', EmailEntity::USERS_COLUMN_IN_TRASH, $userId, true);
            }
            else if ($filter->getAction() === EmailFilter::ACTION_MOVE_TO_FOLDER) {
                $folderId = $filter->getEmailFolderId();

                if ($folderId) {
                    $entity
                        ->setLinkMultipleColumn('users', EmailEntity::USERS_COLUMN_FOLDER_ID, $userId, $folderId);
                }
            }

            if ($filter->markAsRead()) {
                $entity->setLinkMultipleColumn('users', EmailEntity::USERS_COLUMN_IS_READ, $userId, true);
            }
        }
    }

    /**
     * @param EmailEntity $entity
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);

        if (!$entity->isNew()) {
            if (
                $entity->getParentType() &&
                $entity->getParentId() &&
                $entity->isAttributeChanged('parentId')
            ) {
                /** @var Collection<EmailEntity> $replyList */
                $replyList = $this
                    ->getRelation($entity, 'replies')
                    ->find();

                foreach ($replyList as $reply) {
                    if ($reply->getId() === $entity->getId()) {
                        continue;
                    }

                    if (!$reply->getParentId()) {
                        $reply->set([
                            'parentId' => $entity->getParentId(),
                            'parentType' => $entity->getParentType(),
                        ]);

                        $this->entityManager->saveEntity($reply);
                    }
                }
            }
        }

        if (
            (
                $entity->getStatus() === EmailEntity::STATUS_ARCHIVED ||
                $entity->getStatus() === EmailEntity::STATUS_SENT
            ) &&
            (
                $entity->isAttributeChanged('status') ||
                $entity->isNew()
            )
        ) {
            $repliedId = $entity->get('repliedId');

            if ($repliedId) {
                $replied = $this->entityManager->getEntityById(EmailEntity::ENTITY_TYPE, $repliedId);

                if (
                    $replied &&
                    $replied->getId() !== $entity->getId() &&
                    !$replied->get('isReplied')
                ) {
                    $replied->set('isReplied', true);

                    $this->entityManager->saveEntity($replied, [SaveOption::SILENT => true]);
                }
            }
        }

        if ($entity->get('isBeingImported')) {
            $entity->set('isBeingImported', false);
        }
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }
}
