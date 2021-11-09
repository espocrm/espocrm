<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\Notification;

use Espo\Core\AclManager as InternalAclManager;
use Espo\Core\Acl\Table;

use Espo\Services\Stream as StreamService;

use Espo\ORM\EntityManager;
use Espo\ORM\Collection;

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
    private $streamService;

    private $service;

    private $entityManager;

    private $user;

    private $internalAclManager;

    public function __construct(
        StreamService $streamService,
        Service $service,
        EntityManager $entityManager,
        User $user,
        InternalAclManager $internalAclManager
    ) {
        $this->streamService = $streamService;
        $this->service = $service;
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->internalAclManager = $internalAclManager;
    }

    public function afterSave(Note $note): void
    {
        if ($note->getParentType() && $note->getParentId()) {
            $this->afterSaveParent($note);

            return;
        }

        $this->afterSaveNoParent($note);
    }

    private function afterSaveParent(Note $note): void
    {
        $parentType = $note->getParentType();
        $parentId = $note->getParentId();
        $superParentType = $note->getSuperParentType();
        $superParentId = $note->getSuperParentId();

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
            $teamIdList = $note->getLinkMultipleIdList('teams');
            $userIdList = $note->getLinkMultipleIdList('users');
        }

        $notifyUserIdList = [];

        foreach ($userList as $user) {
            if ($skipAclCheck) {
                $notifyUserIdList[] = $user->getId();

                continue;
            }

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

        $this->processNotify($note, array_unique($notifyUserIdList));
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

            return;
        }
    }

    private function processNotify(Note $note, array $userIdList): void
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
                    ->select(['id'])
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

        $this->service->notifyAboutNote($filteredUserIdList, $note);
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
            $team = $this->entityManager->getEntity(Team::ENTITY_TYPE, $teamId);

            if (!$team) {
                continue;
            }

            $targetUserList = $this->entityManager
                ->getRDBRepository(Team::ENTITY_TYPE)
                ->getRelation($team, 'users')
                ->where([
                    'isActive' => true,
                ])
                ->select('id')
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
            $portal = $this->entityManager->getEntity(Portal::ENTITY_TYPE, $portalId);

            if (!$portal) {
                continue;
            }

            $targetUserList = $this->entityManager
                ->getRDBRepository(Portal::ENTITY_TYPE)
                ->getRelation($portal, 'users')
                ->where([
                    'isActive' => true,
                ])
                ->select(['id'])
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
            ->select('id')
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

            $userTeamIdList = $user->getLinkMultipleIdList('teams');

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
     * @phpstan-return Collection&iterable<User>
     * @return User[]
     */
    private function getSubscriberList(string $parentType, string $parentId, bool $isInternal = false): Collection
    {
        /** @var Collection&iterable<User> */
        return $this->streamService->getSubscriberList($parentType, $parentId, $isInternal);
    }
}
