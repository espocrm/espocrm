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

namespace Espo\Core\Notification;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Entities\Notification;
use Espo\Core\Notification\AssignmentNotificator\Params;

/**
 * @implements AssignmentNotificator<Entity>
 */
class DefaultAssignmentNotificator implements AssignmentNotificator
{
    public const OPTION_FORCE_ASSIGNED_USER = 'forceAssignedUser';

    private const FIELD_ASSIGNED_USERS = Field::ASSIGNED_USERS;
    private const ATTR_ASSIGNED_USER_ID = 'assignedUserId';

    public function __construct(
        protected User $user,
        protected EntityManager $entityManager,
        protected UserEnabledChecker $userChecker
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        if (
            $entity->hasLinkMultipleField(self::FIELD_ASSIGNED_USERS) &&
            !$params->getOption(self::OPTION_FORCE_ASSIGNED_USER)
        ) {
            $userIds = $entity->getLinkMultipleIdList(self::FIELD_ASSIGNED_USERS);
            /** @var string[] $fetchedIds */
            $fetchedIds = $entity->getFetched(self:: FIELD_ASSIGNED_USERS . 'Ids') ?? [];

            foreach ($userIds as $userId) {
                if (in_array($userId, $fetchedIds)) {
                    continue;
                }

                $this->processForUser($entity, $userId);
            }

            return;
        }

        if (!$entity->get(self::ATTR_ASSIGNED_USER_ID)) {
            return;
        }

        if (!$entity->isAttributeChanged(self::ATTR_ASSIGNED_USER_ID)) {
            return;
        }

        $assignedUserId = $entity->get(self::ATTR_ASSIGNED_USER_ID);

        $this->processForUser($entity, $assignedUserId);
    }

    protected function processForUser(Entity $entity, string $assignedUserId): void
    {
        if (!$this->userChecker->checkAssignment($entity->getEntityType(), $assignedUserId)) {
            return;
        }

        if ($entity->hasAttribute('createdById') && $entity->hasAttribute('modifiedById')) {
            $isSelfAssignment = $entity->isNew() ?
                $assignedUserId === $entity->get('createdById') :
                $assignedUserId === $entity->get('modifiedById');

            if ($isSelfAssignment) {
                return;
            }
        }

        $isSelfAssignment = $assignedUserId === $this->user->getId();

        if ($isSelfAssignment) {
            return;
        }

        $this->entityManager->createEntity(Notification::ENTITY_TYPE, [
            'type' => Notification::TYPE_ASSIGN,
            'userId' => $assignedUserId,
            'data' => [
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'entityName' => $entity->get(Field::NAME),
                'isNew' => $entity->isNew(),
                'userId' => $this->user->getId(),
                'userName' => $this->user->getName(),
            ],
            'relatedType' => $entity->getEntityType(),
            'relatedId' => $entity->getId(),
        ]);
    }
}
