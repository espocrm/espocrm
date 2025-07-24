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

namespace Espo\Tools\Notification;

use Espo\Core\AclManager as InternalAclManager;
use Espo\Core\Acl\Table;

use Espo\Core\Name\Field;
use Espo\ORM\Name\Attribute;
use Espo\Tools\Notification\HookProcessor\Params;
use Espo\Tools\Stream\Service as StreamService;

use Espo\ORM\EntityManager;
use Espo\ORM\SthCollection;
use Espo\ORM\EntityCollection;

use Espo\Entities\User;
use Espo\Entities\Team;
use Espo\Entities\Portal;
use Espo\Entities\Notification;
use Espo\Entities\Note;

/**
 * Handles notifications after note saving.
 */
class NoteHookProcessor
{
    public function __construct(
        private StreamService $streamService,
        private Service $service,
        private EntityManager $entityManager,
        private User $user,
        private InternalAclManager $internalAclManager
    ) {}

    public function afterSave(Note $note, Params $params): void
    {
        if ($note->getParentType() && $note->getParentId()) {
            $this->afterSaveParent($note, $params);

            return;
        }

        $this->afterSaveNoParent($note);
    }

    private function afterSaveParent(Note $note, Params $params): void
    {
        $parentType = $note->getParentType();
        $parentId = $note->getParentId();
        $superParentType = $note->getSuperParentType();
        $superParentId = $note->getSuperParentId();

        if (!$parentType || !$parentId) {
            return;
        }

        $userList = $this->getSubscriberList($parentType, $parentId, $note->isInternal());

        $userIdMetList = [];

        foreach ($userList as $user) {
            $userIdMetList[] = $user->getId();
        }

        if ($superParentType && $superParentId) {
            $additionalUserList = $this->getSubscriberList(
                $superParentType,
                $superParentId,
                $note->isInternal()
            );

            foreach ($additionalUserList as $user) {
                if (
                    $user->isPortal() ||
                    in_array($user->getId(), $userIdMetList)
                ) {
                    continue;
                }

                $userIdMetList[] = $user->getId();
                $userList[] = $user;
            }
        }

        $targetType = $note->getRelatedType() ? $note->getRelatedType() : $parentType;

        // This is correct.
        $skipAclCheck = !$note->isAclProcessed();

        $teamIdList = null;
        $userIdList = null;

        if (!$skipAclCheck) {
            $teamIdList = $note->getLinkMultipleIdList(Field::TEAMS);
            $userIdList = $note->getLinkMultipleIdList('users');
        }

        $notifyUserIdList = [];

        foreach ($userList as $user) {
            if ($skipAclCheck) {
                $notifyUserIdList[] = $user->getId();

                continue;
            }

            /** @var string[] $userIdList */
            /** @var string[] $teamIdList */

            if ($user->isAdmin()) {
                $notifyUserIdList[] = $user->getId();

                continue;
            }

            if ($user->isPortal() && $note->getRelatedType()) {
                continue;
            }

            if ($user->isPortal()) {
                $notifyUserIdList[] = $user->getId();

                continue;
            }

            $level = $this->internalAclManager->getLevel($user, $targetType, Table::ACTION_READ);

            if (!$this->checkUserAccess($user, $level, $teamIdList, $userIdList)) {
                continue;
            }

            $notifyUserIdList[] = $user->getId();
        }

        $this->processNotify($note, array_unique($notifyUserIdList), $params);
    }

    private function afterSaveNoParent(Note $note): void
    {
        $targetType = $note->getTargetType();

        if ($targetType === Note::TARGET_USERS) {
            $this->afterSaveTargetUsers($note);

            return;
        }

        if ($targetType === Note::TARGET_TEAMS) {
            $this->afterSaveTargetTeams($note);

            return;
        }

        if ($targetType === Note::TARGET_PORTALS) {
            $this->afterSaveTargetPortals($note);

            return;
        }

        if ($targetType === Note::TARGET_ALL) {
            $this->afterSaveTargetAll($note);
        }
    }

    /**
     * @param string[] $userIdList
     */
    private function processNotify(Note $note, array $userIdList, ?Params $params = null): void
    {
        $filteredUserIdList = array_filter(
            $userIdList,
            function (string $userId) use ($note) {
                if ($note->isUserIdNotified($userId)) {
                    return false;
                }

                if ($note->isNew()) {
                    return true;
                }

                $existing = $this->entityManager
                    ->getRDBRepository(Notification::ENTITY_TYPE)
                    ->select([Attribute::ID])
                    ->where([
                        'type' => Notification::TYPE_NOTE,
                        'relatedType' => Note::ENTITY_TYPE,
                        'relatedId' => $note->getId(),
                        'userId' => $userId,
                    ])
                    ->findOne();

                if ($existing) {
                    return false;
                }

                return true;
            }
        );

        if (!count($filteredUserIdList)) {
            return;
        }

        $this->service->notifyAboutNote($filteredUserIdList, $note, $params);
    }

    private function afterSaveTargetUsers(Note $note): void
    {
        $targetUserIdList = $note->get('usersIds') ?? [];

        if (!count($targetUserIdList)) {
            return;
        }

        $notifyUserIdList = [];

        foreach ($targetUserIdList as $userId) {
            if ($userId === $this->user->getId()) {
                continue;
            }

            $notifyUserIdList[] = $userId;
        }

        $this->processNotify($note, array_unique($notifyUserIdList));
    }

    private function afterSaveTargetTeams(Note $note): void
    {
        $targetTeamIdList = $note->get('teamsIds') ?? [];

        if (!count($targetTeamIdList)) {
            return;
        }

        $notifyUserIdList = [];

        foreach ($targetTeamIdList as $teamId) {
            $team = $this->entityManager->getEntityById(Team::ENTITY_TYPE, $teamId);

            if (!$team) {
                continue;
            }

            $targetUserList = $this->entityManager
                ->getRDBRepository(Team::ENTITY_TYPE)
                ->getRelation($team, 'users')
                ->where([
                    'isActive' => true,
                ])
                ->select(Attribute::ID)
                ->find();

            foreach ($targetUserList as $user) {
                if ($user->getId() === $this->user->getId()) {
                    continue;
                }

                $notifyUserIdList[] = $user->getId();
            }
        }

        $this->processNotify($note, array_unique($notifyUserIdList));
    }

    private function afterSaveTargetPortals(Note $note): void
    {
        $targetPortalIdList = $note->get('portalsIds') ?? [];

        if (!count($targetPortalIdList)) {
            return;
        }

        $notifyUserIdList = [];

        foreach ($targetPortalIdList as $portalId) {
            $portal = $this->entityManager->getEntityById(Portal::ENTITY_TYPE, $portalId);

            if (!$portal) {
                continue;
            }

            $targetUserList = $this->entityManager
                ->getRDBRepository(Portal::ENTITY_TYPE)
                ->getRelation($portal, 'users')
                ->where([
                    'isActive' => true,
                ])
                ->select([Attribute::ID])
                ->find();

            foreach ($targetUserList as $user) {
                if ($user->getId() === $this->user->getId()) {
                    continue;
                }

                $notifyUserIdList[] = $user->getId();
            }
        }

        $this->processNotify($note, array_unique($notifyUserIdList));
    }

    private function afterSaveTargetAll(Note $note): void
    {
        $targetUserList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->where([
                'isActive' => true,
                'type' => ['regular', 'admin'],
            ])
            ->select(Attribute::ID)
            ->find();

        $notifyUserIdList = [];

        foreach ($targetUserList as $user) {
            if ($user->getId() === $this->user->getId()) {
                continue;
            }

            $notifyUserIdList[] = $user->getId();
        }

        $this->processNotify($note, $notifyUserIdList);
    }

    /**
     * @param string[] $teamIdList
     * @param string[] $userIdList
     * @return bool
     */
    private function checkUserAccess(
        User $user,
        string $level,
        array $teamIdList,
        array $userIdList
    ): bool {

        if ($level === Table::LEVEL_ALL) {
            return true;
        }

        if ($level === Table::LEVEL_TEAM) {
            if (in_array($user->getId(), $userIdList)) {
                return true;
            }

            if (!count($teamIdList)) {
                return false;
            }

            $userTeamIdList = $user->getLinkMultipleIdList(Field::TEAMS);

            foreach ($teamIdList as $teamId) {
                if (in_array($teamId, $userTeamIdList)) {
                    return true;
                }
            }

            return false;
        }

        if ($level === Table::LEVEL_OWN) {
            return in_array($user->getId(), $userIdList);
        }

        return false;
    }

    /**
     * @return EntityCollection<User>
     */
    private function getSubscriberList(string $parentType, string $parentId, bool $isInternal = false): EntityCollection
    {
        $collection = $this->streamService->getSubscriberList($parentType, $parentId, $isInternal);

        if ($collection instanceof EntityCollection) {
            return $collection;
        }

        if ($collection instanceof SthCollection) {
            /** @var EntityCollection<User> */
            return $this->entityManager
                ->getCollectionFactory()
                ->createFromSthCollection($collection);
        }

        /** @var EntityCollection<User> $newCollection */
        $newCollection = $this->entityManager
            ->getCollectionFactory()
            ->create(User::ENTITY_TYPE);

        foreach ($collection as $entity) {
            $newCollection[] = $entity;
        }

        return $newCollection;
    }
}
