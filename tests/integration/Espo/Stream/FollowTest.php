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

namespace integration\Espo\Stream;

use Espo\Core\Acl\Table;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Name\Field;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Tools\EntityManager\EntityManager;
use Espo\Tools\Stream\Service;
use tests\integration\Core\BaseTestCase;

class FollowTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFollow(): void
    {
        $roleData = [
            Account::ENTITY_TYPE => [
                'create' => Table::LEVEL_NO,
                'read' => Table::LEVEL_ALL,
                'stream' => Table::LEVEL_ALL,
            ],
            Contact::ENTITY_TYPE => [
                'create' => Table::LEVEL_NO,
                'read' => Table::LEVEL_ALL,
                'stream' => Table::LEVEL_ALL,
            ],
            Meeting::ENTITY_TYPE => [
                'create' => Table::LEVEL_NO,
                'read' => Table::LEVEL_ALL,
                'stream' => Table::LEVEL_ALL,
            ],
        ];

        $user1 = $this->createUser('test-1', [
            'data' => $roleData,
        ]);

        $user2 = $this->createUser('test-2', [
            'data' => $roleData,
        ]);

        $user3 = $this->createUser('test-3', [
            'data' => $roleData,
        ]);

        $tool = $this->getInjectableFactory()->create(EntityManager::class);

        /** @noinspection PhpArrayKeyDoesNotMatchArrayShapeInspection */
        $tool->update(Contact::ENTITY_TYPE, ['assignedUsers' => true]);

        $this->reCreateApplication();

        $streamService = $this->getInjectableFactory()->create(Service::class);

        $em = $this->getEntityManager();

        //

        $account = $em->getRepositoryByClass(Account::class)->getNew();
        $account->setAssignedUser($user1);
        $em->saveEntity($account);

        $this->assertTrue($streamService->checkIsFollowed($account, $user1->getId()));

        $account->setAssignedUser($user2);
        $em->saveEntity($account);

        $this->assertTrue($streamService->checkIsFollowed($account, $user2->getId()));

        //

        $contact = $em->getRepositoryByClass(Contact::class)->getNew();
        $contact->setLinkMultipleIdList(Field::ASSIGNED_USERS, [$user1->getId()]);
        $em->saveEntity($contact);

        $this->assertTrue($streamService->checkIsFollowed($contact, $user1->getId()));

        $contact->setLinkMultipleIdList(Field::ASSIGNED_USERS, [$user2->getId()]);
        $em->saveEntity($contact);

        $this->assertTrue($streamService->checkIsFollowed($contact, $user2->getId()));

        //

        $meeting = $em->getRepositoryByClass(Meeting::class)->getNew();
        $meeting->setAssignedUser($user1);
        $meeting->setUsers(LinkMultiple::create()->withAddedId($user2->getId()));

        $em->saveEntity($meeting);

        $this->assertTrue($streamService->checkIsFollowed($meeting, $user1->getId()));
        $this->assertTrue($streamService->checkIsFollowed($meeting, $user2->getId()));

        $meeting->setUsers(LinkMultiple::create()->withAddedId($user3->getId()));

        $em->saveEntity($meeting);

        $this->assertTrue($streamService->checkIsFollowed($meeting, $user3->getId()));
    }
}
