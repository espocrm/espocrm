<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Entities\EmailFilter;
use Espo\Entities\InboundEmail;
use Espo\Entities\User as UserEntity;
use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Repositories\Database;
use Espo\Entities\Email as EmailEntity;
use Espo\ORM\EntityCollection;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Entities\EmailAddress;
use Espo\Core\Di;
use stdClass;

/**
 * @extends Database<EmailEntity>
 * @internal
 */
class Email extends Database implements

    Di\EmailFilterManagerAware
{
    use Di\EmailFilterManagerSetter;

    private const ADDRESS_FROM = 'from';
    private const ADDRESS_TO = 'to';
    private const ADDRESS_CC = 'cc';
    private const ADDRESS_BCC = 'bcc';
    private const ADDRESS_REPLY_TO = 'replyTo';

    private const ATTR_FROM_EMAIL_ADDRESS_ID = 'fromEmailAddressId';
    private const ATTR_FROM_EMAIL_ADDRESS_NAME = 'fromEmailAddressName';

    /**
     * @private string[]
     */
    private const ADDRESS_TYPE_LIST = [
        self::ADDRESS_FROM,
        self::ADDRESS_TO,
        self::ADDRESS_CC,
        self::ADDRESS_BCC,
        self::ADDRESS_REPLY_TO,
    ];

    private function prepareAddresses(
        EmailEntity $entity,
        string $type,
        bool $addAssignedUser = false,
        bool $skipUsers = false,
    ): void {

        if (!$entity->has($type)) {
            return;
        }

        $link = $type . 'EmailAddresses';

        $addressValue = $entity->get($type);

        if (!$addressValue) {
            $entity->setLinkMultipleIdList($link, []);

            return;
        }

        $previousIds = [];

        if (!$entity->isNew()) {
            $previousIds = $entity->getFetchedLinkMultipleIdList($link);
        }

        $addressList = $this->explodeAndPrepareAddressList($addressValue);

        $ids = $this->getEmailAddressRepository()->getIdListFormAddressList($addressList);

        $entity->setLinkMultipleIdList($link, $ids);

        if (
            $skipUsers ||
            array_diff($previousIds, $ids) === array_diff($ids, $previousIds)
        ) {
            return;
        }

        foreach ($ids as $id) {
            $this->addUserByEmailAddressId($entity, $id, $addAssignedUser);
        }
    }

    private function addUserByEmailAddressId(
        EmailEntity $entity,
        string $emailAddressId,
        bool $addAssignedUser = false
    ): void {

        /** @var UserEntity[] $users */
        $users = $this->getEmailAddressRepository()
            ->getEntityListByAddressId($emailAddressId, null, UserEntity::ENTITY_TYPE, true);

        foreach ($users as $user) {
            $entity->addUserId($user->getId());

            if ($addAssignedUser && !$user->isPortal()) {
                $entity->addAssignedUserId($user->getId());
            }
        }
    }

    /**
     * @internal
     */
    public function loadFromField(EmailEntity $entity): void
    {
        $fromEmailAddressName = $entity->get(self::ATTR_FROM_EMAIL_ADDRESS_NAME);

        if ($fromEmailAddressName && !$entity->isAttributeChanged(self::ATTR_FROM_EMAIL_ADDRESS_NAME)) {
            $entity->set(self::ADDRESS_FROM, $fromEmailAddressName);
            $entity->setFetched(self::ADDRESS_FROM, $fromEmailAddressName);

            return;
        }

        $fromEmailAddressId = $entity->get(self::ATTR_FROM_EMAIL_ADDRESS_ID);

        if ($fromEmailAddressId) {
            $emailAddress = $this->getEmailAddressRepository()->getById($fromEmailAddressId);

            if ($emailAddress) {
                $entity->setFromAddress($emailAddress->getAddress());
                $entity->setFetched(self::ADDRESS_FROM, $emailAddress->getAddress());

                return;
            }
        }

        if (!$entity->has(self::ATTR_FROM_EMAIL_ADDRESS_ID)) {
            return;
        }

        $entity->setFromAddress(null);
        $entity->setFetched(self::ADDRESS_FROM, null);
    }

    private function loadAddressMultiField(EmailEntity $entity, string $type): void
    {
        $entity->loadLinkMultipleField($type . 'EmailAddresses');

        /** @var ?stdClass $names */
        $names = $entity->get($type . 'EmailAddressesNames');

        if ($names === null) {
            return;
        }

        $addresses = [];

        foreach (get_object_vars($names) as $address) {
            $addresses[] = $address;
        }

        $entity->set($type, implode(';', $addresses));
    }

    /**
     * @internal
     */
    public function loadToField(EmailEntity $entity): void
    {
        $this->loadAddressMultiField($entity, self::ADDRESS_TO);
    }

    /**
     * @internal
     */
    public function loadCcField(EmailEntity $entity): void
    {
        $this->loadAddressMultiField($entity, self::ADDRESS_CC);
    }

    /**
     * @internal
     */
    public function loadBccField(EmailEntity $entity): void
    {
        $this->loadAddressMultiField($entity, self::ADDRESS_BCC);
    }

    /**
     * @internal
     */
    public function loadReplyToField(EmailEntity $entity): void
    {
        $this->loadAddressMultiField($entity, self::ADDRESS_REPLY_TO);
    }

    /**
     * @internal
     * @param string[] $fieldList
     */
    public function loadNameHash(EmailEntity $entity, array $fieldList = self::ADDRESS_TYPE_LIST): void
    {
        $addressList = [];

        if (in_array(self::ADDRESS_FROM, $fieldList) && $entity->get(self::ADDRESS_FROM)) {
            $addressList[] = $entity->get(self::ADDRESS_FROM);
        }

        if (in_array(self::ADDRESS_TO, $fieldList)) {
            $this->addAddresses($entity, self::ADDRESS_TO, $addressList);
        }

        if (in_array(self::ADDRESS_CC, $fieldList)) {
            $this->addAddresses($entity, self::ADDRESS_CC, $addressList);
        }

        if (in_array(self::ADDRESS_BCC, $fieldList)) {
            $this->addAddresses($entity, self::ADDRESS_BCC, $addressList);
        }

        if (in_array(self::ADDRESS_REPLY_TO, $fieldList)) {
            $this->addAddresses($entity, self::ADDRESS_REPLY_TO, $addressList);
        }

        $nameHash = (object) [];
        $typeHash = (object) [];
        $idHash = (object) [];

        foreach ($addressList as $address) {
            $related = $this->getEmailAddressRepository()->getEntityByAddress($address);

            if (!$related) {
                $related = $this->entityManager
                    ->getRDBRepositoryByClass(InboundEmail::class)
                    ->where(['emailAddress' => $address])
                    ->findOne();
            }

            if ($related) {
                $nameHash->$address = $related->get(Field::NAME);
                $typeHash->$address = $related->getEntityType();
                $idHash->$address = $related->getId();
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
            /** @var string[] $attachmentsIds */
            $attachmentsIds = $entity->get('attachmentsIds') ?? [];

            if ($attachmentsIds !== []) {
                $entity->set('hasAttachment', true);
            }
        }

        $this->processBeforeSaveAddresses($entity);

        if ($entity->getAssignedUser()) {
            $entity->addUserId($entity->getAssignedUser()->getId());
        }

        parent::beforeSave($entity, $options);

        if ($entity->getStatus() === EmailEntity::STATUS_SENDING && $entity->getCreatedBy()) {
            $entity->addUserId($entity->getCreatedBy()->getId());
            $entity->setUserColumnIsRead($entity->getCreatedBy()->getId(), true);
        }

        if ($entity->isNew() || $entity->isAttributeChanged('parentId')) {
            $this->fillAccount($entity);
        }

        if (
            !empty($options[EmailEntity::SAVE_OPTION_IS_BEING_IMPORTED]) ||
            !empty($options[EmailEntity::SAVE_OPTION_IS_JUST_SENT])
        ) {
            if (!$entity->has(self::ADDRESS_FROM)) {
                $this->loadFromField($entity);
            }

            if (!$entity->has(self::ADDRESS_TO)) {
                $this->loadToField($entity);
            }

            $this->applyUsersFilters($entity);
        }
    }

    /**
     * @internal
     */
    public function fillAccount(EmailEntity $entity): void
    {
        if (!$entity->isNew()) {
            $entity->setAccount(null);
        }

        $parent = $entity->getParent();

        if (!$parent) {
            return;
        }

        $accountId = null;

        if ($parent->getEntityType() == Account::ENTITY_TYPE) {
            $accountId = $parent->getId();
        }

        if (
            !$accountId &&
            $parent->get('accountId') &&
            $parent instanceof CoreEntity &&
            $parent->getRelationParam('account', RelationParam::ENTITY) === Account::ENTITY_TYPE
        ) {
            $accountId = $parent->get('accountId');
        }

        if ($accountId) {
            $account = $this->entityManager->getRDBRepositoryByClass(Account::class)->getById($accountId);

            if ($account) {
                $entity->setAccount($account);
            }
        }
    }

    /**
     * @internal
     */
    public function applyUsersFilters(EmailEntity $entity): void
    {
        foreach ($entity->getUsers()->getIdList() as $userId) {
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
                $entity->setUserColumnInTrash($userId, true);
            } else if ($filter->getAction() === EmailFilter::ACTION_MOVE_TO_FOLDER) {
                if ($filter->getEmailFolderId()) {
                    $entity->setUserColumnFolderId($userId, $filter->getEmailFolderId());
                }
            }

            if ($filter->markAsRead()) {
                $entity->setUserColumnIsRead($userId, true);
            }

            if ($filter->skipNotification()) {
                $entity->setUserSkipNotification($userId);
            }
        }
    }

    /**
     * @param EmailEntity $entity
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);

        if (
            !$entity->isNew() &&
            $entity->getParentType() &&
            $entity->getParentId() &&
            $entity->isAttributeChanged('parentId')
        ) {
            /** @var EntityCollection<EmailEntity> $replyList */
            $replyList = $this->getRelation($entity, 'replies')
                ->find();

            foreach ($replyList as $reply) {
                if ($reply->getId() === $entity->getId()) {
                    continue;
                }

                if ($reply->getParentId()) {
                    continue;
                }

                $reply->setMultiple([
                    'parentId' => $entity->getParentId(),
                    'parentType' => $entity->getParentType(),
                ]);

                $this->entityManager->saveEntity($reply);
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
            $replied = $entity->getReplied();

            if (
                $replied &&
                $replied->getId() !== $entity->getId() &&
                !$replied->isReplied()
            ) {
                $replied->setIsReplied();

                $this->entityManager->saveEntity($replied, [SaveOption::SILENT => true]);
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

    /**
     * @param string[] $addressList
     */
    private function addAddresses(EmailEntity $entity, string $type, array &$addressList): void
    {
        $value = $entity->get($type) ?? '';

        $splitList = explode(';', $value);

        foreach ($splitList as $address) {
            if (!in_array($address, $addressList)) {
                $addressList[] = $address;
            }
        }
    }

    private function processBeforeSaveAddresses(EmailEntity $entity): void
    {
        $hasOne =
            $entity->has(self::ADDRESS_FROM) ||
            $entity->has(self::ADDRESS_TO) ||
            $entity->has(self::ADDRESS_CC) ||
            $entity->has(self::ADDRESS_BCC) ||
            $entity->has(self::ADDRESS_REPLY_TO);

        if (!$hasOne) {
            return;
        }

        if (!$entity->has('usersIds')) {
            $entity->loadLinkMultipleField('users');
        }

        if ($entity->has(self::ADDRESS_FROM)) {
            $this->processBeforeSaveFrom($entity);
        }

        if ($entity->has(self::ADDRESS_TO)) {
            $this->prepareAddresses($entity, self::ADDRESS_TO, true);
        }

        if ($entity->has(self::ADDRESS_CC)) {
            $this->prepareAddresses($entity, self::ADDRESS_CC);
        }

        if ($entity->has(self::ADDRESS_BCC)) {
            $this->prepareAddresses($entity, self::ADDRESS_BCC);
        }

        if ($entity->has(self::ADDRESS_REPLY_TO)) {
            $this->prepareAddresses($entity, self::ADDRESS_REPLY_TO, false, true);
        }
    }

    private function processBeforeSaveFrom(EmailEntity $entity): void
    {
        $from = trim($entity->getFromAddress() ?? '');

        if (!$from) {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $entity->set(self::ATTR_FROM_EMAIL_ADDRESS_ID, null);

            return;
        }

        $ids = $this->getEmailAddressRepository()->getIdListFormAddressList([$from]);

        if ($ids === []) {
            return;
        }

        $entity->set(self::ATTR_FROM_EMAIL_ADDRESS_ID, $ids[0]);
        $entity->set(self::ATTR_FROM_EMAIL_ADDRESS_NAME, $from);

        $this->addUserByEmailAddressId($entity, $ids[0], true);

        if ($entity->getSentBy()) {
            return;
        }

        $user = $this->getEmailAddressRepository()->getEntityByAddressId($ids[0], UserEntity::ENTITY_TYPE, true);

        if (
            $user instanceof UserEntity &&
            $entity->getStatus() !== EmailEntity::STATUS_DRAFT
        ) {
            $entity->setSentBy($user);
        }
    }

    /**
     * @return string[]
     */
    private function explodeAndPrepareAddressList(string $addressValue): array
    {
        $addressList = array_map(fn ($item) => trim($item), explode(';', $addressValue));

        return array_filter($addressList, fn ($item) => filter_var($item, FILTER_VALIDATE_EMAIL) !== false);
    }
}
