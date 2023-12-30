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

namespace Espo\Classes\AssignmentNotificators;

use Espo\Core\Field\DateTime;
use Espo\Entities\EmailAddress;
use Espo\Entities\EmailFolder;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Tools\Stream\Service as StreamService;
use Espo\Core\Notification\AssignmentNotificator;
use Espo\Core\Notification\AssignmentNotificator\Params;
use Espo\Core\Notification\UserEnabledChecker;
use Espo\Core\AclManager;
use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Entities\Notification;
use Espo\Entities\Email as EmailEntity;
use Espo\Repositories\Email as EmailRepository;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Tools\Email\Util;

/**
 * @implements AssignmentNotificator<EmailEntity>
 */
class Email implements AssignmentNotificator
{
    private const DAYS_THRESHOLD = 2;

    private User $user;
    private EntityManager $entityManager;
    private UserEnabledChecker $userChecker;
    private AclManager $aclManager;
    private StreamService $streamService;

    public function __construct(
        User $user,
        EntityManager $entityManager,
        UserEnabledChecker $userChecker,
        AclManager $aclManager,
        StreamService $streamService
    ) {
        $this->user = $user;
        $this->entityManager = $entityManager;
        $this->userChecker = $userChecker;
        $this->aclManager = $aclManager;
        $this->streamService = $streamService;
    }

    /**
     * @param EmailEntity $entity
     */
    public function process(Entity $entity, Params $params): void
    {
        if (
            !in_array(
                $entity->getStatus(),
                [
                    EmailEntity::STATUS_ARCHIVED,
                    EmailEntity::STATUS_SENT,
                    EmailEntity::STATUS_BEING_IMPORTED,
                ]
            )
        ) {
            return;
        }

        if ($params->getOption('isJustSent')) {
            $previousUserIdList = [];
        }
        else {
            $previousUserIdList = $entity->getFetched('usersIds');

            if (!is_array($previousUserIdList)) {
                $previousUserIdList = [];
            }
        }

        $dateSent = $entity->getDateSent();

        if (!$dateSent) {
            return;
        }

        if ($dateSent->diff(DateTime::createNow())->days > self::DAYS_THRESHOLD) {
            return;
        }

        $emailUserIdList = $entity->get('usersIds');

        if (!is_array($emailUserIdList)) {
            return;
        }

        $userIdList = [];

        foreach ($emailUserIdList as $userId) {
            if (
                !in_array($userId, $userIdList) &&
                !in_array($userId, $previousUserIdList) &&
                $userId !== $this->user->getId()
            ) {
                $userIdList[] = $userId;
            }
        }

        $data = [
            'emailId' => $entity->getId(),
            'emailName' => $entity->getSubject(),
        ];

        /** @var EmailRepository $emailRepository */
        $emailRepository = $this->entityManager->getRepository(EmailEntity::ENTITY_TYPE);
        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        if (!$entity->has('from')) {
            $emailRepository->loadFromField($entity);
        }

        if (!$entity->has('to')) {
            $emailRepository->loadToField($entity);
        }

        $person = null;

        $from = $entity->get('from');

        if ($from) {
            $person = $emailAddressRepository->getEntityByAddress($from, null, [
                User::ENTITY_TYPE,
                Contact::ENTITY_TYPE,
                Lead::ENTITY_TYPE,
            ]);

            if ($person) {
                $data['personEntityType'] = $person->getEntityType();
                $data['personEntityName'] = $person->get('name');
                $data['personEntityId'] = $person->getId();
            }
        }

        $userIdFrom = null;

        if ($person && $person->getEntityType() === User::ENTITY_TYPE) {
            $userIdFrom = $person->getId();
        }

        if (empty($data['personEntityId'])) {
            $data['fromString'] = Util::parseFromName($entity->getFromString());

            if (empty($data['fromString']) && $from) {
                $data['fromString'] = $from;
            }
        }

        $parent = null;

        $parentId = $entity->getParentId();
        $parentType = $entity->getParentType();

        if ($parentType && $parentId) {
            $parent = $this->entityManager->getEntityById($parentType, $parentId);
        }

        $account = null;

        $accountLink = $entity->getAccount();

        if ($accountLink) {
            $account = $this->entityManager->getEntityById(Account::ENTITY_TYPE, $accountLink->getId());
        }

        foreach ($userIdList as $userId) {
            if (!$userId) {
                continue;
            }

            if ($userIdFrom === $userId) {
                continue;
            }

            if ($entity->getLinkMultipleColumn('users', EmailEntity::USERS_COLUMN_IN_TRASH, $userId)) {
                continue;
            }

            if ($entity->getLinkMultipleColumn('users', EmailEntity::USERS_COLUMN_IS_READ, $userId)) {
                continue;
            }

            if (!$this->userChecker->checkAssignment(EmailEntity::ENTITY_TYPE, $userId)) {
                continue;
            }

            if (
                $params->getOption('isBeingImported') ||
                $params->getOption('isJustSent')
            ) {
                $folderId = $entity->getLinkMultipleColumn('users', EmailEntity::USERS_COLUMN_FOLDER_ID, $userId);

                if (
                    $folderId &&
                    $this->entityManager
                        ->getRDBRepositoryByClass(EmailFolder::class)
                        ->where([
                            'id' => $folderId,
                            'skipNotifications' => true,
                        ])
                        ->count()
                ) {
                    continue;
                }
            }

            /** @var ?User $user */
            $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

            if (!$user) {
                continue;
            }

            if ($user->isPortal()) {
                continue;
            }

            if (!$this->aclManager->checkScope($user, EmailEntity::ENTITY_TYPE)) {
                continue;
            }

            $isArchivedOrBeingImported =
                $entity->getStatus() === EmailEntity::STATUS_ARCHIVED ||
                $params->getOption('isBeingImported');

            if (
                $isArchivedOrBeingImported &&
                $parent &&
                $this->streamService->checkIsFollowed($parent, $userId)
            ) {
                continue;
            }

            if (
                $isArchivedOrBeingImported &&
                $account &&
                $this->streamService->checkIsFollowed($account, $userId)
            ) {
                continue;
            }

            $existing = $this->entityManager
                ->getRDBRepository(Notification::ENTITY_TYPE)
                ->where([
                    'type' => Notification::TYPE_EMAIL_RECEIVED,
                    'userId' => $userId,
                    'relatedId' => $entity->getId(),
                    'relatedType' => EmailEntity::ENTITY_TYPE,
                ])
                ->select(['id'])
                ->findOne();

            if ($existing) {
                continue;
            }

            $this->entityManager->createEntity(Notification::ENTITY_TYPE, [
                'type' => Notification::TYPE_EMAIL_RECEIVED,
                'userId' => $userId,
                'data' => $data,
                'relatedId' => $entity->getId(),
                'relatedType' => EmailEntity::ENTITY_TYPE,
            ]);
        }
    }
}
