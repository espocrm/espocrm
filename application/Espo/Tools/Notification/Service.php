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

use Espo\Core\Name\Field;
use Espo\Core\Utils\Id\RecordIdGenerator;
use Espo\Entities\Note;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\Entities\Email;
use Espo\Core\AclManager;
use Espo\Core\WebSocket\Submission;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

class Service
{
    public function __construct(
        private EntityManager $entityManager,
        private AclManager $aclManager,
        private Submission $webSocketSubmission,
        private RecordIdGenerator $idGenerator,
    ) {}

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

    /**
     * @param string[] $userIdList
     */
    public function notifyAboutNote(array $userIdList, Note $note): void
    {
        $related = null;

        if ($note->getRelatedType() === Email::ENTITY_TYPE) {
            $related = $this->entityManager
                ->getRDBRepository(Email::ENTITY_TYPE)
                ->select([Attribute::ID, 'sentById', 'createdById'])
                ->where([Attribute::ID => $note->getRelatedId()])
                ->findOne();
        }

        $now = date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->create();

        $userList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select([Attribute::ID, 'type'])
            ->where([
                'isActive' => true,
                Attribute::ID => $userIdList,
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

            $notification = $this->entityManager->getNewEntity(Notification::ENTITY_TYPE);

            $notification->set([
                Attribute::ID => $this->idGenerator->generate(),
                'data' => [
                    'noteId' => $note->getId(),
                ],
                'type' => Notification::TYPE_NOTE,
                'userId' => $userId,
                Field::CREATED_AT => $now,
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

        foreach ($userIdList as $userId) {
            $this->webSocketSubmission->submit('newNotification', $userId);
        }
    }

    private function checkUserNoteAccess(User $user, Note $note): bool
    {
        if ($user->isPortal()) {
            if ($note->getRelatedType()) {
                /** @todo Revise. */
                return
                    $note->getRelatedType() === Email::ENTITY_TYPE &&
                    $note->getParentType() === CaseObj::ENTITY_TYPE;
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
