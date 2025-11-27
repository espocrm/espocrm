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

namespace Espo\Tools\Notification;

use Espo\Core\Acl\AssignmentChecker\Helper;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\Notification\AssignmentNotificator\Params;
use Espo\Core\ORM\Entity;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

class CollaboratorsNotificator
{
    public function __construct(
        private Helper $helper,
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        if (!$this->toProcess($entity)) {
            return;
        }

        $userIds = $entity->getLinkMultipleIdList(Field::COLLABORATORS);
        $previousUserIds = $entity->getFetchedLinkMultipleIdList(Field::COLLABORATORS);

        $addedUserIds = array_diff($userIds, $previousUserIds);

        foreach ($addedUserIds as $userId) {
            $this->processForUser($entity, $userId, $params);
        }
    }

    private function processForUser(Entity $entity, string $userId, Params $params): void
    {
        if (!$this->toProcessUser($entity, $userId)) {
            return;
        }

        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $notification
            ->setType(Notification::TYPE_COLLABORATING)
            ->setUserId($userId)
            ->setData([
                'relatedName' => $entity->get(Field::NAME),
                'createdByName' => $this->user->getName(),
            ])
            ->setRelated(LinkParent::createFromEntity($entity))
            ->setActionId($params->getActionId());

        $this->entityManager->saveEntity($notification);
    }

    private function toProcessUser(Entity $entity, string $userId): bool
    {
        if ($userId === $this->user->getId()) {
            return false;
        }

        if ($this->helper->hasAssignedUsersField($entity->getEntityType())) {
            return !in_array($userId, $entity->getLinkMultipleIdList(Field::ASSIGNED_USERS));
        }

        if ($this->helper->hasAssignedUserField($entity->getEntityType())) {
            return $userId !== $entity->get(Field::ASSIGNED_USER . 'Id');
        }

        return true;
    }

    private function toProcess(Entity $entity): bool
    {
        if (!$this->helper->hasCollaboratorsField($entity->getEntityType())) {
            return false;
        }

        $idsAttr = Field::COLLABORATORS . 'Ids';

        if (!$entity->isAttributeChanged($idsAttr)) {
            return false;
        }

        return true;
    }

}
