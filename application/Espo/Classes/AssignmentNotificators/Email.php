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

namespace Espo\Classes\AssignmentNotificators;

use Espo\Core\Field\DateTime;
use Espo\Core\Name\Field;
use Espo\Core\Notification\DefaultAssignmentNotificator;
use Espo\Entities\EmailAddress;
use Espo\Entities\EmailFolder;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Name\Attribute;
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

    public function __construct(
        private User $user,
        private EntityManager $entityManager,
        private UserEnabledChecker $userChecker,
        private AclManager $aclManager,
        private StreamService $streamService,
        private DefaultAssignmentNotificator $defaultAssignmentNotificator,
    ) {}

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

        if (
            $entity->getStatus() !== EmailEntity::STATUS_BEING_IMPORTED &&
            !$this->streamService->checkIsEnabled(EmailEntity::ENTITY_TYPE)
        ) {
            $this->defaultAssignmentNotificator->process(
                $entity,
                $params->withOption(DefaultAssignmentNotificator::OPTION_FORCE_ASSIGNED_USER, true)
            );
        }

        if ($params->getOption(EmailEntity::SAVE_OPTION_IS_JUST_SENT)) {
            $previousUserIdList = [];
        } else {
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
                $data['personEntityName'] = $person->get(Field::NAME);
                $data['personEntityId'] = $person->getId();
            }
        }

        $userIdFrom = null;

        if ($person && $person->getEntityType() === User::ENTITY_TYPE) {
            $userIdFrom = $person->getId();
        }

        if (empty($data['personEntityId'])) {
            $data['fromString'] = Util::parseFromName($entity->getFromString() ?? '');

            if (empty($data['fromString']) && $from) {
                $data['fromString'] = $from;
            }
        }

        $parent = $entity->getParent();
        $account = $entity->getAccount();

        foreach ($userIdList as $userId) {
            if ($userIdFrom === $userId) {
                continue;
            }

            if (
                $entity->getUserColumnInTrash($userId) ||
                $entity->getUserColumnIsRead($userId) ||
                $entity->getUserSkipNotification($userId)
            ) {
                continue;
            }

            if (!$this->userChecker->checkAssignment(EmailEntity::ENTITY_TYPE, $userId)) {
                continue;
            }

            if (
                $params->getOption(EmailEntity::SAVE_OPTION_IS_BEING_IMPORTED) ||
                $params->getOption(EmailEntity::SAVE_OPTION_IS_JUST_SENT)
            ) {
                $folderId = $entity->getUserColumnFolderId($userId);

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

            $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

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
                $params->getOption(EmailEntity::SAVE_OPTION_IS_BEING_IMPORTED);

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
                ->select([Attribute::ID])
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
