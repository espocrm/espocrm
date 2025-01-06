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

namespace Espo\Modules\Crm\Classes\AssignmentNotificators;

use Espo\Core\Field\LinkParent;
use Espo\Core\Notification\AssignmentNotificator;
use Espo\Core\Notification\AssignmentNotificator\Params;
use Espo\Core\Notification\DefaultAssignmentNotificator;
use Espo\Core\Notification\UserEnabledChecker;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Meeting as MeetingEntity;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements AssignmentNotificator<MeetingEntity|Call>
 */
class Meeting implements AssignmentNotificator
{
    private const ATTR_USERS_IDS = 'usersIds';
    private const NOTIFICATION_TYPE_EVENT_ATTENDEE = 'EventAttendee';

    public function __construct(
        private DefaultAssignmentNotificator $defaultAssignmentNotificator,
        private UserEnabledChecker $userEnabledChecker,
        private EntityManager $entityManager,
        private User $user,
        private Metadata $metadata
    ) {}

    /**
     * @param MeetingEntity|Call $entity
     */
    public function process(Entity $entity, Params $params): void
    {
        // Default assignment notifications not needed if stream is enabled.
        if (!$this->hasStream($entity->getEntityType())) {
            $this->defaultAssignmentNotificator->process($entity, $params);
        }

        if ($entity->getStatus() !== MeetingEntity::STATUS_PLANNED) {
            return;
        }

        if (!$entity->isAttributeChanged(self::ATTR_USERS_IDS)) {
            return;
        }

        /** @var string[] $prevIds */
        $prevIds = $entity->getFetched(self::ATTR_USERS_IDS) ?? [];
        $ids = $entity->getUsers()->getIdList();

        $newIds = array_filter($ids, fn ($id) => !in_array($id, $prevIds));

        $assignedUser = $entity->getAssignedUser();

        if ($assignedUser) {
            $newIds = array_filter($newIds, fn($id) => $id !== $assignedUser->getId());
        }

        $newIds = array_values($newIds);

        foreach ($newIds as $id) {
            $this->processForUser($entity, $id);
        }
    }

    /**
     * @param MeetingEntity|Call $entity
     */
    private function processForUser(Entity $entity, string $userId): void
    {
        if (!$this->userEnabledChecker->checkAssignment($entity->getEntityType(), $userId)) {
            return;
        }

        $createdBy = $entity->getCreatedBy();
        $modifiedBy = $entity->getModifiedBy();

        $isSelfAssignment = $entity->isNew() ?
            $createdBy && $userId ===  $createdBy->getId() :
            $modifiedBy && $userId === $modifiedBy->getId();

        if ($isSelfAssignment) {
            return;
        }

        /** @var Notification $notification */
        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $notification
            ->setType(self::NOTIFICATION_TYPE_EVENT_ATTENDEE)
            ->setUserId($userId)
            ->setRelated(
                LinkParent::create($entity->getEntityType(), $entity->getId())
            )
            ->setData((object) [
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'entityName' => $entity->getName(),
                'isNew' => $entity->isNew(),
                'userId' => $this->user->getId(),
                'userName' => $this->user->getName(),
            ]);

        $this->entityManager->saveEntity($notification);
    }

    private function hasStream(string $entityType): bool
    {
        return (bool) $this->metadata->get(['scopes', $entityType, 'stream']);
    }
}
