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

namespace tests\integration\Espo\Tools\Notification;

use Espo\Core\Field\LinkParent;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Entities\Notification;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Tools\Notification\GetParams;
use Espo\Tools\Notification\RecordService;
use tests\integration\Core\BaseTestCase;

class RecordServiceTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGet(): void
    {
        $this->createUser('tester');
        $this->authenticate('tester');

        $service = $this->getInjectableFactory()->create(RecordService::class);

        $user = $this->getContainer()->getByClass(User::class);

        $service->get($user, SearchParams::create());

        $this->assertTrue(true);
    }

    /**
     * A filter defined by a user disables grouping, so that individual notifications are
     * returned instead of group rows.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetFilteredIsNotGrouped(): void
    {
        $user = $this->initUser();

        $this->setGrouping($user, true);

        $account = $this->getEntityManager()->createEntity('Account', ['name' => 'Test']);

        $this->createNotification($user, Notification::TYPE_COLLABORATING, relatedParent: $account);
        $this->createNotification($user, Notification::TYPE_COLLABORATING, relatedParent: $account);

        $notFiltered = $this->getNotifications($user, SearchParams::create()->withMaxSize(10));

        $this->assertCount(1, $notFiltered, "Grouping is expected without a filter.");
        $this->assertSame(Notification::GROUP_TYPE_RECORD, $notFiltered[0]->getGroupType());

        $filtered = $this->getNotifications(
            $user,
            $this->searchParamsForType(Notification::TYPE_COLLABORATING),
            params: self::filteredParams()
        );

        $this->assertCount(2, $filtered, "Both notifications are expected with a filter.");

        foreach ($filtered as $notification) {
            $this->assertNull($notification->getGroupType());
        }
    }

    /**
     * Notifications fetched with a filter are not marked as read. Searching the history is a
     * lookup, not reading the feed.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetFilteredDoesNotMarkAsRead(): void
    {
        $user = $this->initUser();

        $notification = $this->createNotification($user, Notification::TYPE_MESSAGE);
        $other = $this->createNotification($user, Notification::TYPE_SYSTEM);

        $filtered = $this->getNotifications(
            $user,
            $this->searchParamsForType(Notification::TYPE_MESSAGE),
            params: self::filteredParams()
        );

        $this->assertCount(1, $filtered);
        $this->assertSame($notification->getId(), $filtered[0]->getId());

        $this->assertFalse($this->fetchNotification($notification->getId())->isRead());
        $this->assertFalse($this->fetchNotification($other->getId())->isRead());
    }

    /**
     * A control for `testGetFilteredDoesNotMarkAsRead`. Without a filter, notifications are
     * still marked as read.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetNotFilteredMarksAsRead(): void
    {
        $user = $this->initUser();

        $notification = $this->createNotification($user, Notification::TYPE_MESSAGE);

        $this->getNotifications($user, SearchParams::create()->withMaxSize(10));

        $this->assertTrue($this->fetchNotification($notification->getId())->isRead());
    }

    /**
     * An empty where clause is what the front-end sends when no filter is applied. It must not
     * be treated as a filter.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetWithEmptyWhereKeepsGrouping(): void
    {
        $user = $this->initUser();

        $this->setGrouping($user, true);

        $account = $this->getEntityManager()->createEntity('Account', ['name' => 'Test']);

        $this->createNotification($user, Notification::TYPE_COLLABORATING, relatedParent: $account);
        $this->createNotification($user, Notification::TYPE_COLLABORATING, relatedParent: $account);

        $searchParams = SearchParams::fromRaw(['where' => [], 'maxSize' => 10]);

        $this->assertNotNull($searchParams->getWhere(), "An empty where is not null.");
        $this->assertSame([], $searchParams->getWhere()->getItemList());

        // As the controller does for an empty where clause.
        $list = $this->getNotifications($user, $searchParams, params: new GetParams());

        $this->assertCount(1, $list);
        $this->assertSame(Notification::GROUP_TYPE_RECORD, $list[0]->getGroupType());
    }

    /**
     * Pagination by number composes with a filter.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetFilteredWithBeforeNumber(): void
    {
        $user = $this->initUser();

        $first = $this->createNotification($user, Notification::TYPE_MESSAGE);
        $second = $this->createNotification($user, Notification::TYPE_MESSAGE);
        $third = $this->createNotification($user, Notification::TYPE_MESSAGE);

        $this->createNotification($user, Notification::TYPE_SYSTEM);

        $searchParams = $this->searchParamsForType(Notification::TYPE_MESSAGE);

        $list = $this->getNotifications($user, $searchParams, params: self::filteredParams());

        $this->assertSame(
            [$third->getId(), $second->getId(), $first->getId()],
            $this->idsOf($list)
        );

        $thirdNumber = $this->fetchNotification($third->getId())->get(Notification::ATTR_NUMBER);

        $this->assertNotNull($thirdNumber);

        $list = $this->getNotifications(
            $user,
            $searchParams,
            beforeNumber: (string) $thirdNumber,
            params: self::filteredParams()
        );

        $this->assertSame([$second->getId(), $first->getId()], $this->idsOf($list));
    }

    /**
     * Notifications produced by one action share an `actionId` and are collapsed to the
     * oldest one. That must not happen when a filter is applied: the oldest one may not match
     * the filter, which would hide its matching siblings.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetFilteredWithSharedActionIdKeepsSiblings(): void
    {
        $user = $this->initUser();

        $actionId = '0123456789abcdef0123456789abcdef';

        // The oldest notification of the action group does not match the filter below.
        $this->createNotification($user, Notification::TYPE_ASSIGN, actionId: $actionId);
        $second = $this->createNotification($user, Notification::TYPE_MESSAGE, actionId: $actionId);
        $third = $this->createNotification($user, Notification::TYPE_MESSAGE, actionId: $actionId);

        $list = $this->getNotifications(
            $user,
            $this->searchParamsForType(Notification::TYPE_MESSAGE),
            params: self::filteredParams()
        );

        $this->assertSame([$third->getId(), $second->getId()], $this->idsOf($list));
    }

    /**
     * Grouping and marking as read are independent. A consumer that only displays
     * notifications, such as a dashlet, can keep grouping without consuming the unread state.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetGroupedWithoutMarkingAsRead(): void
    {
        $user = $this->initUser();

        $this->setGrouping($user, true);

        $notification = $this->createNotification($user, Notification::TYPE_MESSAGE);

        $list = $this->getNotifications(
            $user,
            SearchParams::create()->withMaxSize(10),
            params: new GetParams(groupingDisabled: false, markAsRead: false)
        );

        $this->assertCount(1, $list);
        $this->assertSame($notification->getId(), $list[0]->getId());

        $this->assertFalse($this->fetchNotification($notification->getId())->isRead());
    }

    /**
     * A control for `testGetFilteredWithSharedActionIdKeepsSiblings`. Without a filter, an
     * action group is still collapsed to its oldest notification.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetNotFilteredCollapsesActionGroup(): void
    {
        $this->assertTrue(
            (bool) $this->getConfig()->get('notificationGrouping'),
            "Action grouping is expected to be enabled."
        );

        $user = $this->initUser();

        $this->setGrouping($user, false);

        $actionId = '0123456789abcdef0123456789abcdef';

        $first = $this->createNotification($user, Notification::TYPE_ASSIGN, actionId: $actionId);
        $this->createNotification($user, Notification::TYPE_MESSAGE, actionId: $actionId);
        $this->createNotification($user, Notification::TYPE_MESSAGE, actionId: $actionId);

        $list = $this->getNotifications($user, SearchParams::create()->withMaxSize(10));

        $this->assertSame([$first->getId()], $this->idsOf($list));
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

        return $this->getContainer()->getByClass(User::class);
    }

    /**
     * @return Notification[]
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function getNotifications(
        User $user,
        SearchParams $searchParams,
        ?string $beforeNumber = null,
        GetParams $params = new GetParams(),
    ): array {

        $collection = $this->getInjectableFactory()
            ->create(RecordService::class)
            ->get($user, $searchParams, $beforeNumber, $params)
            ->getCollection();

        return iterator_to_array($collection);
    }

    /**
     * What the controller passes for a filter defined by a user.
     */
    private static function filteredParams(): GetParams
    {
        return new GetParams(groupingDisabled: true, markAsRead: false);
    }

    private function searchParamsForType(string $type): SearchParams
    {
        return SearchParams::create()
            ->withMaxSize(10)
            ->withWhere(
                WhereItem
                    ::createBuilder()
                    ->setAttribute(Notification::FIELD_TYPE)
                    ->setType(WhereItem\Type::EQUALS)
                    ->setValue($type)
                    ->build()
            );
    }

    private function createNotification(
        User $user,
        string $type,
        ?string $actionId = null,
        ?Entity $relatedParent = null,
    ): Notification {

        $notification = $this->getEntityManager()
            ->getRDBRepositoryByClass(Notification::class)
            ->getNew();

        $notification
            ->setType($type)
            ->setUserId($user->getId())
            ->setActionId($actionId)
            ->setRead(false);

        if ($relatedParent) {
            $notification->setRelatedParent(LinkParent::fromEntity($relatedParent));
        }

        $this->getEntityManager()->saveEntity($notification);

        return $notification;
    }

    private function setGrouping(User $user, bool $value): void
    {
        $preferences = $this->getEntityManager()
            ->getRepositoryByClass(Preferences::class)
            ->getById($user->getId());

        $this->assertNotNull($preferences);

        $preferences->set('notificationGrouping', $value);

        $this->getEntityManager()->saveEntity($preferences);
    }

    private function fetchNotification(string $id): Notification
    {
        $notification = $this->getEntityManager()
            ->getRDBRepositoryByClass(Notification::class)
            ->getById($id);

        $this->assertNotNull($notification);

        return $notification;
    }

    /**
     * @param Notification[] $list
     * @return string[]
     */
    private function idsOf(array $list): array
    {
        return array_map(fn (Notification $it) => $it->getId(), $list);
    }
}
