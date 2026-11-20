<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace tests\integration\Espo\Controllers;

use Espo\Controllers\Notification as NotificationController;
use Espo\Core\Field\LinkParent;
use Espo\Entities\Notification;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\ORM\Entity;
use stdClass;
use tests\integration\Core\BaseTestCase;

class NotificationTest extends BaseTestCase
{
    /**
     * The front-end sends an empty where clause when no filter is applied. It must not be
     * treated as a user-defined filter, otherwise grouping and marking as read would stop
     * working on the default list view.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetActionListWithEmptyWhereKeepsGrouping(): void
    {
        $user = $this->initUser();

        $account = $this->getEntityManager()->createEntity('Account', ['name' => 'Test']);

        $first = $this->createNotification($user, Notification::TYPE_COLLABORATING, $account);
        $this->createNotification($user, Notification::TYPE_COLLABORATING, $account);

        $list = $this->getActionList(['where' => [], 'maxSize' => 10]);

        $this->assertCount(1, $list);
        $this->assertSame(Notification::GROUP_TYPE_RECORD, $list[0]->groupType);

        // A group row is not marked as read, its notifications keep their state.
        $this->assertFalse($this->fetchNotification($first->getId())->isRead());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetActionListWithFilterIsNotGroupedAndNotMarkedAsRead(): void
    {
        $user = $this->initUser();

        $account = $this->getEntityManager()->createEntity('Account', ['name' => 'Test']);

        $first = $this->createNotification($user, Notification::TYPE_COLLABORATING, $account);
        $second = $this->createNotification($user, Notification::TYPE_COLLABORATING, $account);

        $list = $this->getActionList([
            'maxSize' => 10,
            'where' => [
                [
                    'type' => 'equals',
                    'attribute' => Notification::FIELD_TYPE,
                    'value' => Notification::TYPE_COLLABORATING,
                ],
            ],
        ]);

        $this->assertCount(2, $list);

        foreach ($list as $item) {
            $this->assertNull($item->groupType ?? null);
        }

        $this->assertFalse($this->fetchNotification($first->getId())->isRead());
        $this->assertFalse($this->fetchNotification($second->getId())->isRead());
    }

    /**
     * A control. Without any search parameters, notifications are marked as read.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetActionListMarksAsRead(): void
    {
        $user = $this->initUser();

        $notification = $this->createNotification($user, Notification::TYPE_MESSAGE);

        $list = $this->getActionList(['maxSize' => 10]);

        $this->assertCount(1, $list);
        $this->assertTrue($this->fetchNotification($notification->getId())->isRead());
    }

    /**
     * The create endpoint accepts a type an extension defines, as it did when `type` was a
     * varchar. Turning the field into an enum would otherwise reject it.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testCreateAcceptsUnknownType(): void
    {
        $user = $this->initUser();

        $result = $this->create([
            'type' => 'MyExtensionType',
            'userId' => $user->getId(),
        ]);

        $this->assertSame('MyExtensionType', $result->type);
    }

    /**
     * The enum keeps the string trimming that the varchar type provided.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testCreateTrimsType(): void
    {
        $user = $this->initUser();

        $result = $this->create([
            'type' => ' MyExtensionType ',
            'userId' => $user->getId(),
        ]);

        $this->assertSame('MyExtensionType', $result->type);

        $result = $this->create([
            'type' => '   ',
            'userId' => $user->getId(),
        ]);

        $this->assertNull($result->type ?? null);
    }

    /**
     * @param array<string, mixed> $data
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function create(array $data): stdClass
    {
        $request = $this->createRequest(
            'POST',
            headers: ['Content-Type' => 'application/json'],
            body: json_encode($data, JSON_THROW_ON_ERROR)
        );

        return $this->getInjectableFactory()
            ->create(NotificationController::class)
            ->postActionCreate($request, $this->createResponse());
    }

    /**
     * @param array<string, mixed> $searchParams
     * @return stdClass[]
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function getActionList(array $searchParams): array
    {
        $request = $this->createRequest('GET', ['searchParams' => json_encode($searchParams)]);

        $result = $this->getInjectableFactory()
            ->create(NotificationController::class)
            ->getActionList($request, $this->createResponse());

        return $result->list;
    }

    /**
     * An admin, so that access control does not interfere with grouping, which excludes
     * related parent types the user has no access to.
     */
    private function initUser(): User
    {
        $this->createUser([
            'userName' => 'tester',
            'lastName' => 'Tester',
            'type' => 'admin',
        ]);

        $this->authenticate('tester');

        $preferences = $this->getEntityManager()
            ->getRepositoryByClass(Preferences::class)
            ->getById($this->getContainer()->getByClass(User::class)->getId());

        $this->assertNotNull($preferences);

        $preferences->set('notificationGrouping', true);

        $this->getEntityManager()->saveEntity($preferences);

        return $this->getContainer()->getByClass(User::class);
    }

    private function createNotification(
        User $user,
        string $type,
        ?Entity $relatedParent = null,
    ): Notification {

        $notification = $this->getEntityManager()
            ->getRDBRepositoryByClass(Notification::class)
            ->getNew();

        $notification
            ->setType($type)
            ->setUserId($user->getId())
            ->setRead(false);

        if ($relatedParent) {
            $notification->setRelatedParent(LinkParent::fromEntity($relatedParent));
        }

        $this->getEntityManager()->saveEntity($notification);

        return $notification;
    }

    private function fetchNotification(string $id): Notification
    {
        $notification = $this->getEntityManager()
            ->getRDBRepositoryByClass(Notification::class)
            ->getById($id);

        $this->assertNotNull($notification);

        return $notification;
    }
}
