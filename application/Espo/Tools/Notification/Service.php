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

use Espo\Core\Field\LinkParent;
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
use Espo\Tools\Notification\HookProcessor\Params;

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
        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $notification
            ->setType(Notification::TYPE_MENTION_IN_POST)
            ->setData(['noteId' => $note->getId()])
            ->setUserId($userId)
            ->setRelated(LinkParent::createFromEntity($note));

        $this->entityManager->saveEntity($notification);
    }

    /**
     * @param string[] $userIdList
     * @param ?Params $params Parameters. As of v9.2.0.
     */
    public function notifyAboutNote(array $userIdList, Note $note, ?Params $params = null): void
    {
        $related = null;

        if ($note->getRelatedType() === Email::ENTITY_TYPE) {
            $related = $this->entityManager
                ->getRDBRepository(Email::ENTITY_TYPE)
                ->select([
                    Attribute::ID,
                    'sentById',
                    'createdById',
                ])
                ->where([Attribute::ID => $note->getRelatedId()])
                ->findOne();
        }

        $now = date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        $collection = $this->entityManager->getCollectionFactory()->create();

        $users = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select([
                Attribute::ID,
                User::ATTR_TYPE,
            ])
            ->where([
                User::ATTR_IS_ACTIVE => true,
                Attribute::ID => $userIdList,
            ])
            ->find();

        foreach ($users as $user) {
            if (!$this->checkUserNoteAccess($user, $note)) {
                continue;
            }

            if ($note->getCreatedById() === $user->getId()) {
                continue;
            }

            if (
                $related instanceof Email &&
                $related->getSentBy()?->getId() === $user->getId()
            ) {
                continue;
            }

            if ($related && $related->get('createdById') === $user->getId()) {
                continue;
            }

            $actionId = $params?->actionId;

            if (
                in_array($note->getType(), [Note::TYPE_ASSIGN, Note::TYPE_CREATE]) &&
                ($note->getData()->assignedUserId ?? null) === $user->getId()
            ) {
                // Do not group notifications about assignment.
                $actionId = null;
            }

            $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

            $notification
                ->set(Attribute::ID, $this->idGenerator->generate())
                ->set(Field::CREATED_AT, $now)
                ->setData(['noteId' => $note->getId()])
                ->setType(Notification::TYPE_NOTE)
                ->setUserId($user->getId())
                ->setRelated(LinkParent::createFromEntity($note))
                ->setRelatedParent(
                    $note->getParentType() && $note->getParentId() ?
                        LinkParent::create($note->getParentType(), $note->getParentId()) : null
                )
                ->setActionId($actionId);

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

        if ($note->getRelatedType() && !$this->aclManager->checkScope($user, $note->getRelatedType())) {
            return false;
        }

        if ($note->getParentType() && !$this->aclManager->checkScope($user, $note->getParentType())) {
            return false;
        }

        return true;
    }
}
