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

namespace Espo\Tools\UserReaction;

use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\Entities\Note;
use Espo\Entities\Notification;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Tools\Stream\Service;

class NotificationService
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Service $streamService,
    ) {}

    public function notifyNote(Note $note, string $type): void
    {
        $recipientId = $note->getCreatedById();

        if (!$recipientId || $recipientId === $this->user->getId()) {
            return;
        }

        $parent = $note->getParent();

        if ($parent && !$this->streamService->checkIsFollowed($parent, $note->getCreatedById())) {
            return;
        }

        if (!$this->isEnabledForUser($recipientId)) {
            return;
        }

        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $data = [
            'type' => $type,
            'userId' => $this->user->getId(),
            'userName' => $this->user->getName(),
        ];

        $notification
            ->setType(Notification::TYPE_USER_REACTION)
            ->setUserId($recipientId)
            ->setRelated(LinkParent::createFromEntity($note));

        if ($parent instanceof Entity) {
            $notification->setRelatedParent($parent);
            $data['entityName'] = $parent->get(Field::NAME);
        }

        $notification->setData($data);

        $this->entityManager->saveEntity($notification);
    }

    private function isEnabledForUser(string $recipientId): bool
    {
        $recipientPreferences = $this->entityManager->getRepositoryByClass(Preferences::class)->getById($recipientId);

        return $recipientPreferences && $recipientPreferences->get('reactionNotifications');
    }

    public function removeNoteUnread(Note $note, User $user, ?string $type = null): void
    {
        $notifications = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->where([
                'read' => false,
                'createdById' => $user->getId(),
                'type' => Notification::TYPE_USER_REACTION,
                'relatedId' => $note->getId(),
                'relatedType' => $note->getEntityType(),
            ])
            ->find();

        foreach ($notifications as $notification) {
            if ($type && $notification->getData()?->type !== $type) {
                continue;
            }

            $this->entityManager->removeEntity($notification);
        }
    }
}
