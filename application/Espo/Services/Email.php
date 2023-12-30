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

namespace Espo\Services;

use Espo\Core\Utils\SystemUser;
use Espo\Tools\Email\SendService;
use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Entities\Email as EmailEntity;
use Espo\Tools\Email\InboxService;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Mail\Sender;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\Record\CreateParams;

use Espo\Tools\Email\Util;
use stdClass;

/**
 * @extends Record<EmailEntity>
 */
class Email extends Record
{

    protected $getEntityBeforeUpdate = true;

    /** @var string[] */
    protected $allowedForUpdateFieldList = [
        'parent',
        'teams',
        'assignedUser',
    ];

    protected $mandatorySelectAttributeList = [
        'name',
        'createdById',
        'dateSent',
        'fromString',
        'fromEmailAddressId',
        'fromEmailAddressName',
        'parentId',
        'parentType',
        'isHtml',
        'isReplied',
        'status',
        'accountId',
        'folderId',
        'messageId',
        'sentById',
        'replyToString',
        'hasAttachment',
        'groupFolderId',
    ];

    private ?SendService $sendService = null;

    /**
     * @deprecated Use `Espo\Tools\Email\SendService`.
     */
    public function getUserSmtpParams(string $userId): ?SmtpParams
    {
        return $this->getSendService()->getUserSmtpParams($userId);
    }

    /**
     * @deprecated Use `Espo\Tools\Email\SendService`.
     *
     * @throws BadRequest
     * @throws SendingError
     * @throws Error
     */
    public function sendEntity(EmailEntity $entity, ?User $user = null): void
    {
        $this->getSendService()->send($entity, $user);
    }

    private function getSendService(): SendService
    {
        if (!$this->sendService) {
            $this->sendService = $this->injectableFactory->create(SendService::class);
        }

        return $this->sendService;
    }

    /**
     * @throws BadRequest
     * @throws Error
     * @throws Forbidden
     * @throws Conflict
     * @throws BadRequest
     * @throws SendingError
     */
    public function create(stdClass $data, CreateParams $params): Entity
    {
        /** @var EmailEntity $entity */
        $entity = parent::create($data, $params);

        if ($entity->getStatus() === EmailEntity::STATUS_SENDING) {
            $this->getSendService()->send($entity, $this->user);
        }

        return $entity;
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        /** @var EmailEntity $entity */

        if ($entity->getStatus() === EmailEntity::STATUS_SENDING) {
            $messageId = Sender::generateMessageId($entity);

            $entity->set('messageId', '<' . $messageId . '>');
        }
    }

    /**
     * @throws BadRequest
     * @throws Error
     * @throws SendingError
     */
    protected function afterUpdateEntity(Entity $entity, $data)
    {
        /** @var EmailEntity $entity */

        if ($entity->getStatus() === EmailEntity::STATUS_SENDING) {
            $this->getSendService()->send($entity, $this->user);
        }

        $this->loadAdditionalFields($entity);

        if (!isset($data->from) && !isset($data->to) && !isset($data->cc)) {
            $entity->clear('nameHash');
            $entity->clear('idHash');
            $entity->clear('typeHash');
        }
    }

    public function getEntity(string $id): ?Entity
    {
        /** @var ?EmailEntity $entity */
        $entity = parent::getEntity($id);

        if ($entity && !$entity->isRead()) {
            $this->markAsRead($entity->getId());
        }

        return $entity;
    }

    private function markAsRead(string $id, ?string $userId = null): void
    {
        $service = $this->injectableFactory->create(InboxService::class);

        $service->markAsRead($id, $userId);
    }

    /**
     * @deprecated Use `Util`.
     */
    static public function parseFromName(?string $string): string
    {
        return Util::parseFromName($string);
    }

    /**
     * @deprecated Use `Util`.
     */
    static public function parseFromAddress(?string $string): string
    {
        return Util::parseFromAddress($string);
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        /** @var EmailEntity $entity */

        $skipFilter = false;

        if ($this->user->isAdmin()) {
            $skipFilter = true;
        }

        if ($this->isEmailManuallyArchived($entity)) {
            $skipFilter = true;
        }
        else if ($entity->isAttributeChanged('dateSent')) {
            $entity->set('dateSent', $entity->getFetched('dateSent'));
        }

        if ($entity->getStatus() === EmailEntity::STATUS_DRAFT) {
            $skipFilter = true;
        }

        if (
            $entity->getStatus() === EmailEntity::STATUS_SENDING &&
            $entity->getFetched('status') === EmailEntity::STATUS_DRAFT
        ) {
            $skipFilter = true;
        }

        if (
            $entity->isAttributeChanged('status') &&
            $entity->getFetched('status') === EmailEntity::STATUS_ARCHIVED
        ) {
            $entity->set('status', EmailEntity::STATUS_ARCHIVED);
        }

        if (!$skipFilter) {
            $this->clearEntityForUpdate($entity);
        }

        if ($entity->getStatus() == EmailEntity::STATUS_SENDING) {
            $messageId = Sender::generateMessageId($entity);

            $entity->set('messageId', '<' . $messageId . '>');
        }
    }

    private function isEmailManuallyArchived(EmailEntity $email): bool
    {
        if ($email->getStatus() !== EmailEntity::STATUS_ARCHIVED) {
            return false;
        }

        $userId = $email->getCreatedBy()?->getId();

        if (!$userId) {
            return false;
        }

        /** @var ?User $user */
        $user = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->getById($userId);

        if (!$user) {
            return true;
        }

        return $user->getUserName() !== SystemUser::NAME;
    }

    private function clearEntityForUpdate(EmailEntity $email): void
    {
        $fieldDefsList = $this->entityManager
            ->getDefs()
            ->getEntity(EmailEntity::ENTITY_TYPE)
            ->getFieldList();

        foreach ($fieldDefsList as $fieldDefs) {
            $field = $fieldDefs->getName();

            if ($fieldDefs->getParam('isCustom')) {
                continue;
            }

            if (in_array($field, $this->allowedForUpdateFieldList)) {
                continue;
            }

            $attributeList = $this->fieldUtil->getAttributeList(EmailEntity::ENTITY_TYPE, $field);

            foreach ($attributeList as $attribute) {
                $email->clear($attribute);
            }
        }
    }
}
