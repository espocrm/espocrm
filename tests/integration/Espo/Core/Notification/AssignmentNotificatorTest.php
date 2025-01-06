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

namespace tests\integration\Espo\Core\Notification;

use Espo\Core\Container;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Config\ConfigWriter;

use Espo\{
    Core\InjectableFactory,
    Entities\User};
use tests\integration\Core\BaseTestCase;

class AssignmentNotificatorTest extends BaseTestCase
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var User
     */
    private $user1;

    /**
     * @var User
     */
    private $user2;


    public function setUp(): void
    {
        parent::setUp();

        $this->initTestData();

        $application = $this->createApplication();

        $container = $application->getContainer();

        $this->entityManager = $container->getByClass(EntityManager::class);
    }

    private function initTestData() : void
    {
        $configWriter = $this->getContainer()->getByClass(InjectableFactory::class)->create(ConfigWriter::class);

        $this->getMetadata()->set('scopes', 'Meeting', [
            'stream' => false,
        ]);

        $this->getMetadata()->save();

        $configWriter->set('assignmentNotificationsEntityList', [
            'Email',
            'Meeting',
        ]);

        $configWriter->save();

        $em = $this->getContainer()->get('entityManager');

        $role = $em->createEntity('Role', [
            'name' => 'test',
            'data' => [
                'Meeting' => [
                    'read' => 'own',
                ],
                'Email' => [
                    'read' => 'own',
                ],
            ],
        ]);

        $this->user1 = $em->createEntity('User', [
            'userName' => 'test-1',
            'lastName' => 'Test 1',
            'rolesIds' => [$role->getId()],
            'emailAddress' => 'test1@test.com',
        ]);

        $this->user2 = $em->createEntity('User', [
            'userName' => 'test-2',
            'lastName' => 'Test 2',
            'rolesIds' => [$role->getId()],
            'emailAddress' => 'test2@test.com',
        ]);

        $em->createEntity('User', [
            'userName' => 'test-3',
            'lastName' => 'Test 3',
            'rolesIds' => [],
            'emailAddress' => 'test3@test.com',
        ]);

        $preferences2 = $em->getEntity('Preferences', $this->user2->getId());

        $preferences2->set([
            'assignmentNotificationsIgnoreEntityTypeList' => ['Meeting'],
        ]);

        $em->saveEntity($preferences2);
    }

    public function testAssignmentSelf() : void
    {
        $meeting = $this->entityManager->createEntity('Meeting', [
            'name' => 'test',
            'assignedUserId' => $this->user1->getId(),
            'createdById' => $this->user1->getId(),
        ]);

        $notification = $this->entityManager
            ->getRDBRepository('Notification')
            ->where([
                'userId' => '1',
                'type' => 'Assign',
                'relatedType' => 'Meeting',
                'relatedId' => $meeting->getId(),
            ])
            ->findOne();

        $this->assertNull($notification, 'notification');
    }

    public function testAssignmentEnabled() : void
    {
        $meeting = $this->entityManager->createEntity('Meeting', [
            'name' => 'test',
            'assignedUserId' => $this->user1->getId(),
        ]);

        $notification = $this->entityManager
            ->getRDBRepository('Notification')
            ->where([
                'userId' => $this->user1->getId(),
                'type' => 'Assign',
                'relatedType' => 'Meeting',
                'relatedId' => $meeting->getId(),
            ])
            ->findOne();

        $this->assertNotNull($notification);
    }

    public function testAssignmentDisabled() : void
    {
        $call = $this->entityManager->createEntity('Call', [
            'name' => 'test',
            'assignedUserId' => $this->user1->getId(),
        ]);

        $notification = $this->entityManager
            ->getRDBRepository('Notification')
            ->where([
                'userId' => $this->user1->getId(),
                'type' => 'Assign',
                'relatedType' => 'Call',
                'relatedId' => $call->getId(),
            ])
            ->findOne();

        $this->assertNull($notification);
    }

    public function testAssignmentIgnored() : void
    {
        $meeting = $this->entityManager->createEntity('Meeting', [
            'name' => 'test',
            'assignedUserId' => $this->user2->getId(),
        ]);

        $notification = $this->entityManager
            ->getRDBRepository('Notification')
            ->where([
                'userId' => $this->user1->getId(),
                'type' => 'Assign',
                'relatedType' => 'Meeting',
                'relatedId' => $meeting->getId(),
            ])
            ->findOne();

        $this->assertNull($notification, 'notification');
    }

    public function testAssignmentEmailYes() : void
    {
        $email = $this->entityManager->createEntity('Email', [
            'name' => 'test',
            'status' => 'Archived',
            'from' => $this->user2->get('emailAddress'),
            'to' => $this->user1->get('emailAddress'),
            'dateSent' => date('Y-m-d H:i:s'),
        ]);

        $notification = $this->entityManager
            ->getRepository('Notification')
            ->where([
                'userId' => $this->user1->getId(),
                'type' => 'EmailReceived',
                'relatedType' => 'Email',
                'relatedId' => $email->getId(),
            ])
            ->findOne();

        $this->assertNotNull($notification);
    }
}
