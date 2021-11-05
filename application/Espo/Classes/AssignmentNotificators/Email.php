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

namespace Espo\Classes\AssignmentNotificators;

use Espo\Services\Email as EmailService;
use Espo\Services\Stream as StreamService;

use Espo\Core\Notification\AssignmentNotificator;
use Espo\Core\Notification\AssignmentNotificator\Params;
use Espo\Core\Notification\UserEnabledChecker;
use Espo\Core\ServiceFactory;
use Espo\Core\AclManager;

use Espo\ORM\EntityManager;
use Espo\ORM\Entity;

use Espo\Entities\User;
use Espo\Entities\Notification;
use Espo\Entities\Email as EmailEntity;

use Espo\Repositories\Email as EmailRepository;
use Espo\Repositories\EmailAddress as EmailAddressRepository;

use DateTime;
use Exception;

class Email implements AssignmentNotificator
{
    private const DAYS_THRESHOLD = 2;

    private $streamService = null;

    private $user;

    private $entityManager;

    private $serviceFactory;

    private $aclManager;

    private $userChecker;

    public function __construct(
        User $user,
        EntityManager $entityManager,
        UserEnabledChecker $userChecker,
        ServiceFactory $serviceFactory,
        AclManager $aclManager
    ) {
        $this->user = $user;
        $this->entityManager = $entityManager;
        $this->userChecker = $userChecker;
        $this->serviceFactory = $serviceFactory;
        $this->aclManager = $aclManager;
    }

    public function process(Entity $entity, Params $params): void
    {
        /** @var EmailEntity $entity */

        if (!in_array($entity->get('status'), ['Archived', 'Sent', 'Being Imported'])) {
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

        $dateSent = $entity->get('dateSent');

        if (!$dateSent) {
            return;
        }

        try {
            $dt = new DateTime($dateSent);
        }
        catch (Exception $e) {
            return;
        }

        if ($dt->diff(new DateTime())->days > self::DAYS_THRESHOLD) {
            return;
        }

        $emailUserIdList = $entity->get('usersIds');

        if (is_null($emailUserIdList) || !is_array($emailUserIdList)) {
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
            'emailName' => $entity->get('name'),
        ];

        /** @var EmailRepository $emailRepository */
        $emailRepository = $this->entityManager->getRepository('Email');
        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository('EmailAddress');

        if (!$entity->has('from')) {
            $emailRepository->loadFromField($entity);
        }

        if (!$entity->has('to')) {
            $emailRepository->loadToField($entity);
        }

        $person = null;

        $from = $entity->get('from');

        if ($from) {
            $person = $emailAddressRepository->getEntityByAddress($from, null, ['User', 'Contact', 'Lead']);

            if ($person) {
                $data['personEntityType'] = $person->getEntityType();
                $data['personEntityName'] = $person->get('name');
                $data['personEntityId'] = $person->getId();
            }
        }

        $userIdFrom = null;

        if ($person && $person->getEntityType() === 'User') {
            $userIdFrom = $person->getId();
        }

        if (empty($data['personEntityId'])) {
            $data['fromString'] = EmailService::parseFromName($entity->get('fromString'));

            if (empty($data['fromString']) && $from) {
                $data['fromString'] = $from;
            }
        }

        $parent = null;

        if ($entity->get('parentId') && $entity->get('parentType')) {
            $parent = $this->entityManager->getEntity($entity->get('parentType'), $entity->get('parentId'));
        }

        $account = null;

        if ($entity->get('accountId')) {
            $account = $this->entityManager->getEntity('Account', $entity->get('accountId'));
        }

        foreach ($userIdList as $userId) {
            if (!$userId) {
                continue;
            }

            if ($userIdFrom === $userId) {
                continue;
            }

            if ($entity->getLinkMultipleColumn('users', 'inTrash', $userId)) {
                continue;
            }

            if (!$this->userChecker->checkAssignment('Email', $userId)) {
                continue;
            }

            if (
                $params->getOption('isBeingImported') ||
                $params->getOption('isJustSent')
            ) {
                $folderId = $entity->getLinkMultipleColumn('users', 'folderId', $userId);

                if ($folderId) {
                    if (
                        $this->entityManager
                            ->getRDBRepository('EmailFolder')
                            ->where([
                                'id' => $folderId,
                                'skipNotifications' => true,
                            ])
                            ->count()
                    ) {
                        continue;
                    }
                }
            }

            /** @var User|null $user */
            $user = $this->entityManager->getEntity('User', $userId);

            if (!$user) {
                continue;
            }

            if ($user->isPortal()) {
                continue;
            }

            if (!$this->aclManager->checkScope($user, 'Email')) {
                continue;
            }

            $isArchivedOrBeingImported =
                $entity->get('status') === 'Archived' ||
                $params->getOption('isBeingImported');

            if (
                $isArchivedOrBeingImported &&
                $parent &&
                $this->getStreamService()->checkIsFollowed($parent, $userId)
            ) {
                continue;
            }

            if (
                $isArchivedOrBeingImported &&
                $account &&
                $this->getStreamService()->checkIsFollowed($account, $userId)
            ) {
                continue;
            }

            $existing = $this->entityManager
                ->getRDBRepository(Notification::ENTITY_TYPE)
                ->where([
                    'type' => Notification::TYPE_EMAIL_RECEIVED,
                    'userId' => $userId,
                    'relatedId' => $entity->getId(),
                    'relatedType' => 'Email',
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
                'relatedType' => 'Email',
            ]);
        }
    }

    private function getStreamService(): StreamService
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->serviceFactory->create('Stream');
        }

        return $this->streamService;
    }
}
