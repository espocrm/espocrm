<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\WebSocket\Submission as WebSocketSubmission;
use Espo\Entities\Email;
use Espo\Entities\EmailFolder;
use Espo\Entities\GroupEmailFolder;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
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
        private Config $config,
    ) {}

    /**
     * @param string[] $idList
     */
    public function moveToFolderIdList(array $idList, ?string $folderId, ?string $userId = null): void
    {
        foreach ($idList as $id) {
            try {
                $this->moveToFolder($id, $folderId, $userId);
            }
            catch (Exception) {}
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

        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getById($id);

        if (!$email) {
            throw new NotFound();
        }

        $user = $userId === $this->user->getId() ?
            $this->user :
            $this->entityManager
                ->getRDBRepositoryByClass(User::class)
                ->getById($userId);

        if (!$user) {
            throw new NotFound("User not found.");
        }

        $previousFolderLink = $email->getGroupFolder();

        if ($previousFolderLink) {
            $this->checkCurrentGroupFolder($previousFolderLink->getId(), $user);
        }

        if ($folderId && str_starts_with($folderId, 'group:')) {
            try {
                $this->moveToGroupFolder($email, substr($folderId, 6), $user);
            }
            catch (Exception $e) {
                $this->log->debug("Could not move email to group folder. " . $e->getMessage());

                throw $e;
            }

            return;
        }

        if ($folderId === Folder::ARCHIVE) {
            $this->moveToArchive($email, $user);

            return;
        }

        if ($previousFolderLink) {
            $email->setGroupFolderId(null);

            if (!$this->aclManager->checkEntityRead($user, $email)) {
                throw new Forbidden("No read access to email to unset group folder.");
            }

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
                'deleted' => false,
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
        $folder = $this->entityManager->getEntityById(GroupEmailFolder::ENTITY_TYPE, $folderId);

        if (!$folder) {
            throw new NotFound("Group folder not found.");
        }

        if (!$this->aclManager->checkEntityRead($user, $folder)) {
            throw new Forbidden("No access to folder.");
        }

        if (!$this->aclManager->checkEntityRead($user, $email)) {
            throw new Forbidden("No read access to email to unset group folder.");
        }

        if (!$this->aclManager->checkField($user, Email::ENTITY_TYPE, 'groupFolder', Table::ACTION_EDIT)) {
            throw new Forbidden("No access to `groupFolder` field.");
        }

        $email->setGroupFolderId($folderId);
        $this->entityManager->saveEntity($email);

        $this->retrieveFromArchive($email, $user);
    }

    /**
     * @param string[] $idList
     */
    public function moveToTrashIdList(array $idList, ?string $userId = null): bool
    {
        foreach ($idList as $id) {
            $this->moveToTrash($id, $userId);
        }

        return true;
    }

    /**
     * @param string[] $idList
     */
    public function retrieveFromTrashIdList(array $idList, ?string $userId = null): void
    {
        foreach ($idList as $id) {
            $this->retrieveFromTrash($id, $userId);
        }
    }

    public function moveToTrash(string $id, ?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set(['inTrash' => true])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        $this->markNotificationAsRead($id, $userId);
    }

    public function retrieveFromTrash(string $id, ?string $userId = null): void
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Email::RELATIONSHIP_EMAIL_USER)
            ->set(['inTrash' => false])
            ->where([
                'deleted' => false,
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
                'deleted' => false,
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
                'deleted' => false,
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
                'deleted' => false,
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
                'deleted' => false,
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
                'deleted' => false,
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
                'deleted' => false,
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
        if (!$this->config->get('useWebSocket')) {
            return;
        }

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
            ->leftJoin('teams')
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
            }
            catch (BadRequest|Forbidden $e) {
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
            throw new Forbidden("No 'read' access");
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
                'deleted' => false,
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
                'deleted' => false,
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

        if (!$this->aclManager->checkField($user, Email::ENTITY_TYPE, 'groupFolder', Table::ACTION_EDIT)) {
            throw new Forbidden("No access to `groupFolder` field.");
        }
    }
}
