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

use Espo\Entities\Note;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\Entities\Email;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Util;
use Espo\Core\AclManager;
use Espo\Core\WebSocket\Submission;
use Espo\Core\Utils\DateTime as DateTimeUtil;

use Espo\ORM\EntityManager;

class Service
{
    private $entityManager;

    private $config;

    private $aclManager;

    private $webSocketSubmission;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        AclManager $aclManager,
        Submission $webSocketSubmission
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->aclManager = $aclManager;
        $this->webSocketSubmission = $webSocketSubmission;
    }

    public function notifyAboutMentionInPost(string $userId, Note $note): void
    {
        $this->entityManager->createEntity(Notification::ENTITY_TYPE, [
            'type' => Notification::TYPE_MENTION_IN_POST,
            'data' => [
                'noteId' => $note->getId(),
            ],
            'userId' => $userId,
            'relatedId' => $note->getId(),
            'relatedType' => Note::ENTITY_TYPE,
        ]);
    }

    public function notifyAboutNote(array $userIdList, Note $note): void
    {
        $related = null;

        if ($note->getRelatedType() === Email::ENTITY_TYPE) {
            $related = $this->entityManager
                ->getRDBRepository(Email::ENTITY_TYPE)
                ->select(['id', 'sentById', 'createdById'])
                ->where(['id' => $note->getRelatedId()])
                ->findOne();
        }

        $now = date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->create();

        $userList = $this->entityManager
            ->getRDBRepository('User')
            ->select(['id', 'type'])
            ->where([
                'isActive' => true,
                'id' => $userIdList,
            ])
            ->find();

        foreach ($userList as $user) {
            $userId = $user->getId();

            if (!$this->checkUserNoteAccess($user, $note)) {
                continue;
            }

            if ($note->get('createdById') === $user->getId()) {
                continue;
            }

            if (
                $related &&
                $related->getEntityType() === Email::ENTITY_TYPE &&
                $related->get('sentById') === $user->getId()
            ) {
                continue;
            }

            if ($related && $related->get('createdById') === $user->getId()) {
                continue;
            }

            $notification = $this->entityManager->getEntity(Notification::ENTITY_TYPE);

            $notification->set([
                'id' => Util::generateId(),
                'data' => [
                    'noteId' => $note->getId(),
                ],
                'type' => Notification::TYPE_NOTE,
                'userId' => $userId,
                'createdAt' => $now,
                'relatedId' => $note->getId(),
                'relatedType' => Note::ENTITY_TYPE,
                'relatedParentId' => $note->getParentId(),
                'relatedParentType' => $note->getParentType(),
            ]);

            $collection[] = $notification;
        }

        if (!count($collection)) {
            return;
        }

        $this->entityManager->getMapper()->massInsert($collection);

        if ($this->config->get('useWebSocket')) {
            foreach ($userIdList as $userId) {
                $this->webSocketSubmission->submit('newNotification', $userId);
            }
        }
    }

    private function checkUserNoteAccess(User $user, Note $note): bool
    {
        if ($user->isPortal()) {
            if ($note->getRelatedType()) {
                /** @todo Revise. */
                return $note->getRelatedType() === Email::ENTITY_TYPE && $note->getParentType() === 'Case';
            }

            return true;
        }

        if ($note->getRelatedType()) {
            if (!$this->aclManager->checkScope($user, $note->getRelatedType())) {
                return false;
            }
        }

        if ($note->getParentType()) {
            if (!$this->aclManager->checkScope($user, $note->getParentType())) {
                return false;
            }
        }

        return true;
    }
}
