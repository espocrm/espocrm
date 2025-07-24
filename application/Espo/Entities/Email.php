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

namespace Espo\Entities;

use Espo\Core\Field\LinkMultiple;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Util;
use Espo\Core\ORM\Entity;
use Espo\Core\Field\DateTime;
use Espo\Core\Field\LinkParent;
use Espo\Core\Field\Link;
use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\Entity as OrmEntity;
use Espo\ORM\EntityCollection;
use Espo\Repositories\Email as EmailRepository;
use Espo\Tools\Email\Util as EmailUtil;

use RuntimeException;
use stdClass;

class Email extends Entity
{
    public const ENTITY_TYPE = 'Email';

    public const STATUS_BEING_IMPORTED = 'Being Imported';
    public const STATUS_ARCHIVED = 'Archived';
    public const STATUS_SENT = 'Sent';
    public const STATUS_SENDING = 'Sending';
    public const STATUS_DRAFT = 'Draft';

    public const RELATIONSHIP_EMAIL_USER = 'EmailUser';
    public const ALIAS_INBOX = 'emailUserInbox';

    public const USERS_COLUMN_IS_READ = 'isRead';
    public const USERS_COLUMN_IN_TRASH = 'inTrash';
    public const USERS_COLUMN_IN_ARCHIVE = 'inArchive';
    public const USERS_COLUMN_FOLDER_ID = 'folderId';
    public const USERS_COLUMN_IS_IMPORTANT = 'isImportant';

    public const GROUP_STATUS_FOLDER_ARCHIVE = 'Archive';
    public const GROUP_STATUS_FOLDER_TRASH = 'Trash';

    public const SAVE_OPTION_IS_BEING_IMPORTED = 'isBeingImported';
    public const SAVE_OPTION_IS_JUST_SENT = 'isJustSent';

    private const ATTR_BODY_PLAIN = 'bodyPlain';

    public const LINK_REPLIES = 'replies';

    public function get(string $attribute): mixed
    {
        if ($attribute === 'subject') {
            return $this->get(Field::NAME);
        }

        if ($attribute === 'fromName') {
            return EmailUtil::parseFromName($this->get('fromString') ?? '') ?: null;
        }

        if ($attribute === 'fromAddress') {
            return EmailUtil::parseFromAddress($this->get('fromString') ?? '') ?: null;
        }

        if ($attribute === 'replyToName') {
            return $this->getReplyToNameInternal();
        }

        if ($attribute === 'replyToAddress') {
            return $this->getReplyToAddressInternal();
        }

        if ($attribute === self::ATTR_BODY_PLAIN) {
            return $this->getBodyPlain();
        }

        return parent::get($attribute);
    }

    public function has(string $attribute): bool
    {
        if ($attribute === 'subject') {
            return $this->has(Field::NAME);
        }

        if ($attribute === 'fromName' || $attribute === 'fromAddress') {
            return $this->has('fromString');
        }

        if ($attribute === 'replyToName' || $attribute === 'replyToAddress') {
            return $this->has('replyToString');
        }

        return parent::has($attribute);
    }

    /** @noinspection PhpUnused */
    protected function _setSubject(?string $value): void
    {
        $this->set(Field::NAME, $value);
    }

    private function getReplyToNameInternal(): ?string
    {
        if (!$this->has('replyToString')) {
            return null;
        }

        $string = $this->get('replyToString');

        if (!$string) {
            return null;
        }

        $string = trim(explode(';', $string)[0]);

        return EmailUtil::parseFromName($string);
    }

    private function getReplyToAddressInternal(): ?string
    {
        if (!$this->has('replyToString')) {
            return null;
        }

        $string = $this->get('replyToString');

        if (!$string) {
            return null;
        }

        $string = trim(explode(';', $string)[0]);

        return EmailUtil::parseFromAddress($string);
    }

    /** @noinspection PhpUnused */
    protected function _setIsRead(?bool $value): void
    {
        $this->setInContainer('isRead', $value !== false);

        if ($value === true || $value === false) {
            $this->setInContainer('isUsers', true);

            return;
        }

        $this->setInContainer('isUsers', false);
    }

    /**
     * @deprecated As of v7.4. As the system user ID may be not constant in the future.
     * @todo Remove in v10.0.
     */
    public function isManuallyArchived(): bool
    {
        if ($this->getStatus() !== Email::STATUS_ARCHIVED) {
            return false;
        }

        return true;
    }

    /**
     * @todo Revise.
     * @deprecated
     */
    public function addAttachment(Attachment $attachment): void
    {
        if (!$this->id) {
            return;
        }

        $attachment->set('parentId', $this->id);
        $attachment->set('parentType', Email::ENTITY_TYPE);

        if (!$this->entityManager) {
            throw new RuntimeException();
        }

        $this->entityManager->saveEntity($attachment);
    }

    public function hasBodyPlain(): bool
    {
        return $this->hasInContainer(self::ATTR_BODY_PLAIN) && $this->getFromContainer(self::ATTR_BODY_PLAIN);
    }

    /**
     * @since 9.0.0
     */
    public function getBodyPlainWithoutReplyPart(): ?string
    {
        $body = $this->getBodyPlain();

        if (!$body) {
            return null;
        }

        return EmailUtil::stripPlainTextQuotePart($body) ?: null;
    }

    public function getBodyPlain(): ?string
    {
        if ($this->getFromContainer(self::ATTR_BODY_PLAIN)) {
            return $this->getFromContainer(self::ATTR_BODY_PLAIN);
        }

        if (!$this->isHtml()) {
            return $this->getBody();
        }

        $body = $this->getBody() ?: '';

        return EmailUtil::stripHtml($body) ?: null;
    }

    public function getBodyPlainForSending(): string
    {
        return $this->getBodyPlain() ?? '';
    }

    public function getBodyForSending(): string
    {
        $body = $this->getBody() ?: '';

        if ($body && $this->isHtml()) {
            $attachmentList = $this->getInlineAttachmentList();

            foreach ($attachmentList as $attachment) {
                $id = $attachment->getId();
                $partId = $id . '@espo';

                $body = str_replace(
                    "\"?entryPoint=attachment&amp;id=$id\"",
                    "\"cid:$partId\"",
                    $body
                );
            }
        }

        return $body;
    }

    /**
     * @return Attachment[]
     */
    public function getInlineAttachmentList(): array
    {
        $idList = [];

        $body = $this->getBody();

        if (!$body) {
            return [];
        }

        $matches = [];

        if (!preg_match_all("/\?entryPoint=attachment&amp;id=([^&=\"']+)/", $body, $matches)) {
            return [];
        }

        if (empty($matches[1]) || !is_array($matches[1])) {
            return [];
        }

        $attachmentList = [];

        foreach ($matches[1] as $id) {
            if (in_array($id, $idList)) {
                continue;
            }

            $idList[] = $id;

            if (!$this->entityManager) {
                throw new RuntimeException();
            }

            $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

            if ($attachment) {
                $attachmentList[] = $attachment;
            }
        }

        return $attachmentList;
    }

    public function getDateSent(): ?DateTime
    {
        /** @var ?DateTime */
        return $this->getValueObject('dateSent');
    }

    public function getDeliveryDate(): ?DateTime
    {
        /** @var ?DateTime */
        return $this->getValueObject('deliveryDate');
    }

    public function getSubject(): ?string
    {
        return $this->get('subject');
    }

    /**
     * @param Email::STATUS_* $status
     * @noinspection PhpDocSignatureInspection
     */
    public function setStatus(string $status): self
    {
        $this->set('status', $status);

        return $this;
    }

    public function setSubject(?string $subject): self
    {
        $this->set('subject', $subject);

        return $this;
    }

    /**
     * @param string[] $idList
     */
    public function setAttachmentIdList(array $idList): self
    {
        $this->setLinkMultipleIdList('attachments', $idList);

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->get('body');
    }

    public function setBody(?string $body): self
    {
        $this->set('body', $body);

        return $this;
    }

    public function setBodyPlain(?string $bodyPlain): self
    {
        $this->set(self::ATTR_BODY_PLAIN, $bodyPlain);

        return $this;
    }

    public function isHtml(): ?bool
    {
        return $this->get('isHtml');
    }

    public function isRead(): ?bool
    {
        return $this->get('isRead');
    }

    public function setIsHtml(bool $isHtml = true): self
    {
        $this->set('isHtml', $isHtml);

        return $this;
    }

    public function setIsPlain(bool $isPlain = true): self
    {
        $this->set('isHtml', !$isPlain);

        return $this;
    }

    public function setFromAddress(?string $address): self
    {
        $this->set('from', $address);

        return $this;
    }

    /**
     * @param string[] $addressList
     */
    public function setToAddressList(array $addressList): self
    {
        $this->set('to', implode(';', $addressList));

        return $this;
    }

    /**
     * @param string[] $addressList
     */
    public function setCcAddressList(array $addressList): self
    {
        $this->set('cc', implode(';', $addressList));

        return $this;
    }

    /**
     * @param string[] $addressList
     * @noinspection PhpUnused
     */
    public function setBccAddressList(array $addressList): self
    {
        $this->set('bcc', implode(';', $addressList));

        return $this;
    }

    /**
     * @param string[] $addressList
     */
    public function setReplyToAddressList(array $addressList): self
    {
        $this->set('replyTo', implode(';', $addressList));

        return $this;
    }

    public function addToAddress(string $address): self
    {
        $list = $this->getToAddressList();

        $list[] = $address;

        $this->set('to', implode(';', $list));

        return $this;
    }

    public function addCcAddress(string $address): self
    {
        $list = $this->getCcAddressList();

        $list[] = $address;

        $this->set('cc', implode(';', $list));

        return $this;
    }

    public function addBccAddress(string $address): self
    {
        $list = $this->getBccAddressList();

        $list[] = $address;

        $this->set('bcc', implode(';', $list));

        return $this;
    }

    public function addReplyToAddress(string $address): self
    {
        $list = $this->getReplyToAddressList();

        $list[] = $address;

        $this->set('replyTo', implode(';', $list));

        return $this;
    }

    public function getFromString(): ?string
    {
        return $this->get('fromString');
    }

    public function getFromAddress(): ?string
    {
        if (!$this->hasInContainer('from') && !$this->isNew()) {
            $this->getEmailRepository()->loadFromField($this);
        }

        return $this->get('from');
    }

    /**
     * @return string[]
     */
    public function getToAddressList(): array
    {
        if (!$this->hasInContainer('to') && !$this->isNew()) {
            $this->getEmailRepository()->loadToField($this);
        }

        $value = $this->get('to');

        if (!$value) {
            return [];
        }

        return explode(';', $value);
    }

    /**
     * @return string[]
     */
    public function getCcAddressList(): array
    {
        if (!$this->hasInContainer('cc') && !$this->isNew()) {
            $this->getEmailRepository()->loadCcField($this);
        }

        $value = $this->get('cc');

        if (!$value) {
            return [];
        }

        return explode(';', $value);
    }

    /**
     * @return string[]
     */
    public function getBccAddressList(): array
    {
        if (!$this->hasInContainer('bcc') && !$this->isNew()) {
            $this->getEmailRepository()->loadBccField($this);
        }

        $value = $this->get('bcc');

        if (!$value) {
            return [];
        }

        return explode(';', $value);
    }

    /**
     * @return string[]
     */
    public function getReplyToAddressList(): array
    {
        if (!$this->hasInContainer('replyTo') && !$this->isNew()) {
            $this->getEmailRepository()->loadReplyToField($this);
        }

        $value = $this->get('replyTo');

        if (!$value) {
            return [];
        }

        return explode(';', $value);
    }

    public function setDummyMessageId(): self
    {
        $this->set('messageId', 'dummy:' . Util::generateId());

        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->get('messageId');
    }

    public function getParentType(): ?string
    {
        return $this->get('parentType');
    }

    public function getParentId(): ?string
    {
        return $this->get('parentId');
    }

    public function getParent(): ?OrmEntity
    {
        /** @var ?OrmEntity */
        return $this->relations->getOne(Field::PARENT);
    }

    public function setAccount(Link|Account|null $account): self
    {
        return $this->setRelatedLinkOrEntity('account', $account);
    }

    public function setParent(LinkParent|OrmEntity|null $parent): self
    {
        return $this->setRelatedLinkOrEntity(Field::PARENT, $parent);
    }

    public function getStatus(): ?string
    {
        return $this->get('status');
    }

    public function getAccount(): ?Account
    {
        /** @var ?Account */
        return $this->relations->getOne('account');
    }

    public function getTeams(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject(Field::TEAMS);
    }

    public function getUsers(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject('users');
    }

    public function getAssignedUsers(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject(Field::ASSIGNED_USERS);
    }

    public function getAssignedUser(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject(Field::ASSIGNED_USER);
    }

    public function getCreatedBy(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject(Field::CREATED_BY);
    }

    public function getSentBy(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('sentBy');
    }

    public function setSentBy(Link|User|null $sentBy): self
    {
        return $this->setRelatedLinkOrEntity('sentBy', $sentBy);
    }

    public function getGroupFolder(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('groupFolder');
    }

    public function getReplied(): ?Email
    {
        /** @var ?Email */
        return $this->relations->getOne('replied');
    }

    /**
     * @return string[]
     */
    public function getAttachmentIdList(): array
    {
        /** @var string[] */
        return $this->getLinkMultipleIdList('attachments');
    }

    private function getEmailRepository(): EmailRepository
    {
        if (!$this->entityManager) {
            throw new RuntimeException();
        }

        /** @var EmailRepository */
        return $this->entityManager->getRepository(Email::ENTITY_TYPE);
    }

    public function setReplied(?Email $replied): self
    {
        return $this->setRelatedLinkOrEntity('replied', $replied);
    }

    /**
     * @deprecated As of v9.0.0.
     * @todo Remove in v9.2.0.
     */
    public function setRepliedId(?string $repliedId): self
    {
        $this->set('repliedId', $repliedId);

        return $this;
    }

    public function setMessageId(?string $messageId): self
    {
        $this->set('messageId', $messageId);

        return $this;
    }

    public function setGroupFolder(Link|GroupEmailFolder|null $groupFolder): self
    {
        return $this->setRelatedLinkOrEntity('groupFolder', $groupFolder);
    }

    public function setGroupFolderId(?string $groupFolderId): self
    {
        $groupFolder = $groupFolderId ? Link::create($groupFolderId) : null;

        return $this->setGroupFolder($groupFolder);
    }

    public function getGroupStatusFolder(): ?string
    {
        return $this->get('groupStatusFolder');
    }

    public function setGroupStatusFolder(?string $groupStatusFolder): self
    {
        $this->set('groupStatusFolder', $groupStatusFolder);

        return $this;
    }

    public function setDateSent(?DateTime $dateSent): self
    {
        $this->setValueObject('dateSent', $dateSent);

        return $this;
    }

    public function setDeliveryDate(?DateTime $deliveryDate): self
    {
        $this->setValueObject('deliveryDate', $deliveryDate);

        return $this;
    }

    public function setAssignedUserId(?string $assignedUserId): self
    {
        $this->set('assignedUserId', $assignedUserId);

        return $this;
    }

    public function addAssignedUserId(string $assignedUserId): self
    {
        $this->addLinkMultipleId(Field::ASSIGNED_USERS, $assignedUserId);

        return $this;
    }

    public function addUserId(string $userId): self
    {
        $this->addLinkMultipleId('users', $userId);

        return $this;
    }

    public function getUserColumnIsRead(string $userId): ?bool
    {
        return $this->getLinkMultipleColumn('users', self::USERS_COLUMN_IS_READ, $userId);
    }

    public function getUserColumnInTrash(string $userId): ?bool
    {
        return $this->getLinkMultipleColumn('users', self::USERS_COLUMN_IN_TRASH, $userId);
    }

    public function getUserColumnFolderId(string $userId): ?string
    {
        return $this->getLinkMultipleColumn('users', self::USERS_COLUMN_FOLDER_ID, $userId);
    }

    public function setUserColumnFolderId(string $userId, ?string $folderId): self
    {
        $this->setLinkMultipleColumn('users', self::USERS_COLUMN_FOLDER_ID, $userId, $folderId);

        return $this;
    }

    public function setUserColumnIsRead(string $userId, bool $isRead): self
    {
        $this->setLinkMultipleColumn('users', self::USERS_COLUMN_IS_READ, $userId, $isRead);

        return $this;
    }

    public function setUserColumnInTrash(string $userId, bool $inTrash): self
    {
        $this->setLinkMultipleColumn('users', self::USERS_COLUMN_IN_TRASH, $userId, $inTrash);

        return $this;
    }

    public function getUserSkipNotification(string $userId): bool
    {
        /** @var stdClass $map */
        $map = $this->get('skipNotificationMap') ?? (object) [];

        return $map->$userId ?? false;
    }

    public function setUserSkipNotification(string $userId): self
    {
        /** @var stdClass $map */
        $map = $this->get('skipNotificationMap') ?? (object) [];
        $map->$userId = true;
        $this->set('skipNotificationMap', $map);

        return $this;
    }

    public function addTeamId(string $teamId): self
    {
        $this->addLinkMultipleId(Field::TEAMS, $teamId);

        return $this;
    }

    public function setTeams(LinkMultiple $teams): self
    {
        $this->setValueObject(Field::TEAMS, $teams);

        return $this;
    }

    /**
     * @return EntityCollection<Attachment>
     */
    public function getAttachments(): iterable
    {
        /** @var EntityCollection<Attachment> */
        return $this->relations->getMany('attachments');
    }

    public function getSendAt(): ?DateTime
    {
        /** @var ?DateTime */
        return $this->getValueObject('sendAt');
    }

    public function setSendAt(?DateTime $sendAt): self
    {
        $this->setValueObject('sendAt', $sendAt);

        return $this;
    }

    public function getIcsContents(): ?string
    {
        return $this->get('icsContents');
    }

    public function isReplied(): bool
    {
        return (bool) $this->get('isReplied');
    }

    public function setIsReplied(bool $isReplied = true): self
    {
        return $this->set('isReplied', $isReplied);
    }
}
