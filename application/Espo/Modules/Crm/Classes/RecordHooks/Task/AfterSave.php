<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Modules\Crm\Classes\RecordHooks\Task;

use Espo\Core\Record\Hook\SaveHook;
use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements SaveHook<Task>
 */
class AfterSave implements SaveHook
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function process(Entity $entity): void
    {
        /** @var ?string $emailId */
        $emailId = $entity->get('emailId');

        if (!$emailId || !$entity->getAssignedUser()) {
            return;
        }

        if (!$entity->isNew() && !$entity->isAttributeChanged('assignedUserId')) {
            return;
        }

        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getById($emailId);

        if (!$email) {
            return;
        }

        $relation = $this->entityManager->getRelation($email, 'users');

        if ($relation->isRelatedById($entity->getAssignedUser()->getId())) {
            return;
        }

        $isRead = $entity->getAssignedUser()->getId() === $this->user->getId();

        $relation->relateById($entity->getAssignedUser()->getId(), [Email::USERS_COLUMN_IS_READ => $isRead]);
    }
}
