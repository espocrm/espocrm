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

namespace Espo\Classes\RecordHooks\Email;

use Espo\Core\Mail\EmailSender;
use Espo\Core\Name\Field;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\SystemUser;
use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements SaveHook<Email>
 */
class BeforeUpdate implements SaveHook
{
    /** @var string[] */
    private $allowedForUpdateFieldList = [
        Field::PARENT,
        Field::TEAMS,
        Field::ASSIGNED_USER,
    ];

    public function __construct(
        private User $user,
        private EntityManager $entityManager,
        private FieldUtil $fieldUtil
    ) {}

    public function process(Entity $entity): void
    {
        $skipFilter = false;

        if ($this->user->isAdmin()) {
            $skipFilter = true;
        }

        if ($this->isEmailManuallyArchived($entity)) {
            $skipFilter = true;
        } else if ($entity->isAttributeChanged('dateSent')) {
            $entity->set('dateSent', $entity->getFetched('dateSent'));
        }

        if ($entity->getStatus() === Email::STATUS_DRAFT) {
            $skipFilter = true;
        }

        if (
            $entity->getStatus() === Email::STATUS_SENDING &&
            $entity->getFetched('status') === Email::STATUS_DRAFT
        ) {
            $skipFilter = true;
        }

        if (
            $entity->isAttributeChanged('status') &&
            $entity->getFetched('status') === Email::STATUS_ARCHIVED
        ) {
            $entity->setStatus(Email::STATUS_ARCHIVED);
        }

        if (!$skipFilter) {
            $this->clearEntityForUpdate($entity);
        }

        if ($entity->getStatus() == Email::STATUS_SENDING) {
            $messageId = EmailSender::generateMessageId($entity);

            $entity->setMessageId('<' . $messageId . '>');
        }
    }

    private function isEmailManuallyArchived(Email $email): bool
    {
        if ($email->getStatus() !== Email::STATUS_ARCHIVED) {
            return false;
        }

        $userId = $email->getCreatedBy()?->getId();

        if (!$userId) {
            return false;
        }

        $user = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->getById($userId);

        if (!$user) {
            return true;
        }

        return $user->getUserName() !== SystemUser::NAME;
    }

    private function clearEntityForUpdate(Email $email): void
    {
        $fieldDefsList = $this->entityManager
            ->getDefs()
            ->getEntity(Email::ENTITY_TYPE)
            ->getFieldList();

        foreach ($fieldDefsList as $fieldDefs) {
            $field = $fieldDefs->getName();

            if ($fieldDefs->getParam('isCustom')) {
                continue;
            }

            if (in_array($field, $this->allowedForUpdateFieldList)) {
                continue;
            }

            $attributeList = $this->fieldUtil->getAttributeList(Email::ENTITY_TYPE, $field);

            foreach ($attributeList as $attribute) {
                $email->clear($attribute);
            }
        }
    }
}
