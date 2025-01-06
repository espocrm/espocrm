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

namespace Espo\Core\Mail\Account\Util;

use Espo\Core\Field\DateTime;
use Espo\Core\Field\LinkParent;
use Espo\Core\Mail\Account\Account;
use Espo\Core\Utils\Language;
use Espo\Entities\InboundEmail;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

class NotificationHelper
{
    private const PERIOD = '1 day';
    private const WAIT_PERIOD = '10 minutes';

    public function __construct(
        private EntityManager $entityManager,
        private Language $language
    ) {}

    public function processImapError(Account $account): void
    {
        $userId = $account->getUser()?->getId();
        $id = $account->getId();
        $entityType = $account->getEntityType();

        if (!$id) {
            return;
        }

        if (
            $account->getConnectedAt() &&
            DateTime::createNow()
                ->modify('-' . self::WAIT_PERIOD)
                ->isLessThan($account->getConnectedAt())
        ) {
            return;
        }

        $userIds = [];

        if ($entityType === InboundEmail::ENTITY_TYPE) {
            $userIds = $this->getAdminUserIds();
        } else if ($userId) {
            $userIds[] = $userId;
        }

        foreach ($userIds as $userId) {
            $this->processImapErrorForUser($entityType, $id, $userId);
        }
    }

    private function exists(string $entityType, string $id, string $userId): bool
    {
        $one = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->where([
                'relatedId' => $id,
                'relatedType' => $entityType,
                'userId' => $userId,
                'createdAt>' => DateTime::createNow()->modify('-' . self::PERIOD)->toString(),
            ])
            ->findOne();

        return $one !== null;
    }

    private function getMessage(string $entityType, string $id): string
    {
        $message = $this->language->translateLabel('imapNotConnected', 'messages', $entityType);

        return str_replace('{id}', $id, $message);
    }

    private function processImapErrorForUser(string $entityType, string $id, string $userId): void
    {
        if ($this->exists($entityType, $id, $userId)) {
            return;
        }

        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $message = $this->getMessage($entityType, $id);

        $notification
            ->setType(Notification::TYPE_MESSAGE)
            ->setMessage($message)
            ->setUserId($userId)
            ->setRelated(LinkParent::create($entityType, $id));

        $this->entityManager->saveEntity($notification);
    }

    /**
     * @return string[]
     */
    private function getAdminUserIds(): array
    {
        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->select([Attribute::ID])
            ->where([
                'isActive' => true,
                'type' => User::TYPE_ADMIN,
            ])
            ->find();

        $ids = [];

        foreach ($users as $user) {
            $ids[] = $user->getId();
        }

        return $ids;
    }
}
