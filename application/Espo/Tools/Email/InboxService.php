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

namespace Espo\Tools\Email;

use Espo\Core\AclManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Name\Field;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Core\Utils\Log;
use Espo\Core\WebSocket\Submission as WebSocketSubmission;
use Espo\Entities\Email;
use Espo\Entities\EmailFolder;
use Espo\Entities\GroupEmailFolder;
use Espo\Entities\Notification;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\SelectBuilder;
use Exception;
use RuntimeException;

class InboxService
{
    public function __construct(
        private User $user,
        private EntityManager $entityManager,
        private AclManager $aclManager,
        private Log $log,
        private SelectBuilderFactory $selectBuilderFactory,
        private WebSocketSubmission $webSocketSubmission,
    ) {}

    /**
     * @param string[] $idList
     */
    public function moveToFolderIdList(array $idList, ?string $folderId, ?string $userId = null): void
    {
        foreach ($idList as $id) {
            try {
                $this->moveToFolder($id, $folderId, $userId);
            } catch (Exception) {}
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function moveToFolder(string $id, ?string $folderId, ?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        if ($folderId === Folder::INBOX) {
            $folderId = null;
        }

        $email = $this->getEmail($id);
        $user = $this->getUser($userId);

        if ($email->getGroupFolder()) {
            $this->checkCurrentGroupFolder($email->getGroupFolder()->getId(), $user);
        }

        if ($folderId && str_starts_with($folderId, 'group:')) {
            try {
                $this->moveToGroupFolder($email, substr($folderId, 6), $user);
            } catch (Exception $e) {
                $this->log->debug("Could not move email to group folder. {message}", ['message' => $e->getMessage()]);

                throw $e;
            }

            return;
        }

        if ($folderId === Folder::ARCHIVE) {
            $this->moveToArchive($email, $user);

            return;
        }

        if ($email->getGroupFolder()) {
            if (!$this->aclManager->checkEntityEdit($user, $email)) {
                throw Forbidden::createWithBody(
                    "Cannot move out from group folder. No edit access to email.",
                    Body::create()->withMessageTranslation('groupMoveOutNoEditAccess', 'Email')
                );
            }

            $email
                ->setGroupFolder(null)
                ->setGroupStatusFolder(null);

            $this->entityManager->saveEntity($email);
        }

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set([
                'folderId' => $folderId,
                'inTrash' => false,
                'inArchive' => false,
            ])
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function moveToGroupFolder(Email $email, string $folderId, User $user): void
    {
        $folder = $this->getGroupFolder($folderId);

        if (!$this->aclManager->checkEntityRead($user, $folder)) {
            throw new Forbidden("Cannot move to group folder. No access to folder.");
        }

        if (!$this->aclManager->checkEntityEdit($user, $email)) {
            throw Forbidden::createWithBody(
                "Cannot move to group folder. No edit access to email.",
                Body::create()->withMessageTranslation('groupMoveToNoEditAccess', 'Email')
            );
        }

        $email
            ->setGroupFolder($folder)
            ->setGroupStatusFolder(null);

        $this->applyGroupFolder($email, $folder);

        $this->entityManager->saveEntity($email);

        $this->retrieveFromArchive($email, $user);
    }

    /**
     * @param string[] $idList
     */
    public function moveToTrashIdList(array $idList, ?string $userId = null): bool
    {
        foreach ($idList as $id) {
            try {
                $this->moveToTrash($id, $userId);
            } catch (Exception) {}
        }

        return true;
    }

    /**
     * @param string[] $idList
     */
    public function retrieveFromTrashIdList(array $idList, ?string $userId = null): void
    {
        foreach ($idList as $id) {
            try {
                $this->retrieveFromTrash($id, $userId);
            } catch (Exception) {}
        }
    }

    /**
     * @throws NotFound
     * @throws Forbidden
     */
    public function moveToTrash(string $id, ?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        $email = $this->getEmail($id);
        $user = $this->getUser($userId);

        if ($email->getGroupFolder()) {
            $folder = $this->getGroupFolder($email->getGroupFolder()->getId());

            if (!$this->aclManager->checkEntityRead($user, $folder)) {
                throw Forbidden::createWithBody(
                    "Cannot move email from group folder to trash. No access to group folder.",
                    Body::create()->withMessageTranslation('groupFolderNoAccess', 'Email')
                );
            }

            if (!$this->aclManager->checkEntityEdit($user, $email)) {
                throw Forbidden::createWithBody(
                    "Cannot move email from group folder to trash.",
                    Body::create()->withMessageTranslation('groupMoveToTrashNoEditAccess', 'Email')
                );
            }

            $email->setGroupStatusFolder(Email::GROUP_STATUS_FOLDER_TRASH);
            $this->entityManager->saveEntity($email);

            return;
        }

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set(['inTrash' => true])
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        $this->markNotificationAsRead($id, $userId);
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function retrieveFromTrash(string $id, ?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        $email = $this->getEmail($id);
        $user = $this->getUser($userId);

        if ($email->getGroupFolder()) {
            $folder = $this->getGroupFolder($email->getGroupFolder()->getId());

            if (!$this->aclManager->checkEntityEdit($user, $email)) {
                throw Forbidden::createWithBody(
                    "Cannot retrieve group folder email from trash. No edit to email.",
                    Body::create()->withMessageTranslation('notEditAccess', 'Email')
                );
            }

            if (!$this->aclManager->checkEntityRead($user, $folder)) {
                throw Forbidden::createWithBody(
                    "Cannot retrieve group folder email from trash. No access to group folder.",
                    Body::create()->withMessageTranslation('groupFolderNoAccess', 'Email')
                );
            }

            $email->setGroupStatusFolder(null);

            $this->entityManager->saveEntity($email);

            return;
        }

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set(['inTrash' => false])
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    /**
     * @param string[] $idList
     */
    public function markAsReadIdList(array $idList, ?string $userId = null): void
    {
        foreach ($idList as $id) {
            $this->markAsRead($id, $userId);
        }
    }

    /**
     * @param string[] $idList
     */
    public function markAsNotReadIdList(array $idList, ?string $userId = null): void
    {
        foreach ($idList as $id) {
            $this->markAsNotRead($id, $userId);
        }
    }

    public function markAsRead(string $id, ?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set(['isRead' => true])
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        $this->markNotificationAsRead($id, $userId);
    }

    public function markAsNotRead(string $id, ?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set(['isRead' => false])
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    /**
     * @param string[] $idList
     */
    public function markAsImportantIdList(array $idList, ?string $userId = null): void
    {
        foreach ($idList as $id) {
            $this->markAsImportant($id, $userId);
        }
    }

    /**
     * @param string[] $idList
     */
    public function markAsNotImportantIdList(array $idList, ?string $userId = null): void
    {
        foreach ($idList as $id) {
            $this->markAsNotImportant($id, $userId);
        }
    }

    public function markAsImportant(string $id, ?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set(['isImportant' => true])
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    public function markAsNotImportant(string $id, ?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set(['isImportant' => false])
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    public function markAllAsRead(?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set(['isRead' => true])
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'isRead' => false,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        $update = $this
            ->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Notification::ENTITY_TYPE)
            ->set(['read' => true])
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'relatedType' => Email::ENTITY_TYPE,
                'read' => false,
                'type' => Notification::TYPE_EMAIL_RECEIVED,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        $this->submitNotificationWebSocket($userId);
    }

    public function markNotificationAsRead(string $id, string $userId): void
    {
        $notification = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->where([
                'userId' => $userId,
                'relatedType' => Email::ENTITY_TYPE,
                'relatedId' => $id,
                'read' => false,
                'type' => Notification::TYPE_EMAIL_RECEIVED,
            ])
            ->findOne();

        if (!$notification) {
            return;
        }

        $notification->setRead();
        $this->entityManager->saveEntity($notification);

        $this->submitNotificationWebSocket($userId);
    }

    private function submitNotificationWebSocket(string $userId): void
    {
        $this->webSocketSubmission->submit('newNotification', $userId);
    }

    /**
     * @return array<string, int>
     */
    public function getFoldersNotReadCounts(): array
    {
        $data = [];

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from(Email::ENTITY_TYPE)
            ->withAccessControlFilter();

        $draftsSelectBuilder = clone $selectBuilder;

        $selectBuilder->withWhere(
            WhereItem::fromRaw([
                'type' => 'isTrue',
                'attribute' => 'isNotRead',
            ])
        );

        $folderIdList = [Folder::INBOX, Folder::DRAFTS];

        $emailFolderList = $this->entityManager
            ->getRDBRepository(EmailFolder::ENTITY_TYPE)
            ->where([
                'assignedUserId' => $this->user->getId(),
            ])
            ->find();

        foreach ($emailFolderList as $folder) {
            $folderIdList[] = $folder->getId();
        }

        $groupFolderList = $this->entityManager
            ->getRDBRepositoryByClass(GroupEmailFolder::class)
            ->distinct()
            ->leftJoin(Field::TEAMS)
            ->where(
                $this->user->isAdmin() ?
                    ['id!=' => null] :
                    ['teams.id' => $this->user->getTeamIdList()]
            )
            ->find();

        foreach ($groupFolderList as $folder) {
            $folderIdList[] = 'group:' . $folder->getId();
        }

        foreach ($folderIdList as $folderId) {
            $itemSelectBuilder = clone $selectBuilder;

            if ($folderId === Folder::DRAFTS) {
                $itemSelectBuilder = clone $draftsSelectBuilder;
            }

            $itemSelectBuilder->withWhere(
                WhereItem::fromRaw([
                    'type' => 'inFolder',
                    'attribute' => 'folderId',
                    'value' => $folderId,
                ])
            );

            try {
                $data[$folderId] = $this->entityManager
                    ->getRDBRepository(Email::ENTITY_TYPE)
                    ->clone($itemSelectBuilder->build())
                    ->count();
            } catch (BadRequest|Forbidden $e) {
                throw new RuntimeException($e->getMessage());
            }
        }

        return $data;
    }

    /**
     * @throws Forbidden
     */
    public function moveToArchive(Email $email, User $user): void
    {
        if (!$this->aclManager->checkEntityRead($user, $email)) {
            throw new Forbidden("No read access to email.");
        }

        if ($email->getGroupFolder()) {
            if (!$this->aclManager->checkEntityEdit($user, $email)) {
                throw Forbidden::createWithBody(
                    "Cannot move from group folder to Archive. No edit access to email.",
                    Body::create()->withMessageTranslation('groupMoveToArchiveNoEditAccess', 'Email')
                );
            }

            $email->setGroupStatusFolder(Email::GROUP_STATUS_FOLDER_ARCHIVE);
            $this->entityManager->saveEntity($email);

            return;
        }

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set([
                'folderId' => null,
                'inArchive' => true,
                'inTrash' => false,
            ])
            ->where([
                Attribute::DELETED => false,
                'userId' => $user->getId(),
                'emailId' => $email->getId(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    public function retrieveFromArchive(Email $email, User $user): void
    {
        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set([
                'folderId' => null,
                'inArchive' => false,
            ])
            ->where([
                Attribute::DELETED => false,
                'userId' => $user->getId(),
                'emailId' => $email->getId(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    /**
     * @throws Forbidden
     */
    private function checkCurrentGroupFolder(string $folderId, User $user): void
    {
        $folder = $this->entityManager->getEntityById(GroupEmailFolder::ENTITY_TYPE, $folderId);

        if ($folder && !$this->aclManager->checkEntityRead($user, $folder)) {
            throw new Forbidden("No access to current group folder.");
        }
    }

    /**
     * @throws NotFound
     */
    private function getUser(string $userId): User
    {
        $user = $userId === $this->user->getId() ?
            $this->user :
            $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

        if (!$user) {
            throw new NotFound("User not found.");
        }

        return $user;
    }

    /**
     * @throws NotFound
     */
    private function getEmail(string $id): Email
    {
        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getById($id);

        if (!$email) {
            throw new NotFound();
        }

        return $email;
    }

    /**
     * @throws NotFound
     */
    private function getGroupFolder(string $folderId): GroupEmailFolder
    {
        $folder = $this->entityManager->getRDBRepositoryByClass(GroupEmailFolder::class)->getById($folderId);

        if (!$folder) {
            throw new NotFound("Group folder not found.");
        }

        return $folder;
    }

    private function applyGroupFolder(Email $email, GroupEmailFolder $folder): void
    {
        if (!$folder->getTeams()->getCount()) {
            return;
        }

        foreach ($folder->getTeams()->getIdList() as $teamId) {
            $email->addTeamId($teamId);
        }

        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->select([Attribute::ID])
            ->where([
                'type' => [User::TYPE_REGULAR, User::TYPE_ADMIN],
                'isActive' => true,
            ])
            ->where(
                Condition::in(
                    Expression::column(Attribute::ID),
                    SelectBuilder::create()
                        ->from(Team::RELATIONSHIP_TEAM_USER)
                        ->select('userId')
                        ->where(['teamId' => $folder->getTeams()->getIdList()])
                        ->build()
                )
            )
            ->find();

        foreach ($users as $user) {
            $email->addUserId($user->getId());
        }
    }
}
