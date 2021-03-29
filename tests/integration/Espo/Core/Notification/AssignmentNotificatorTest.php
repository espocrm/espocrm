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

namespace tests\integration\Espo\Core\Notification;

use Espo\Core\{
    ORM\EntityManager,
    Container,
    Utils\Config\ConfigWriter,
};

use Espo\{
    Entities\User,
};

class AssignmentNotificatorTest extends \tests\integration\Core\BaseTestCase
{
    /**
     * @var Container
     */
    private $container;

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

    /**
     * @var User
     */
    private $user3;

    public function setUp() : void
    {
        parent::setUp();

        $this->initTestData();

        $application = $this->createApplication();

        $this->container = $application->getContainer();

        $this->entityManager = $this->container->get('entityManager');
    }

    private function initTestData() : void
    {
        /* @var $configWriter ConfigWriter */
        $configWriter = $this->getContainer()->get('injectableFactory')->create(ConfigWriter::class);

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
            'rolesIds' => [$role->id],
            'emailAddress' => 'test1@test.com',
        ]);

        $this->user2 = $em->createEntity('User', [
            'userName' => 'test-2',
            'lastName' => 'Test 2',
            'rolesIds' => [$role->id],
            'emailAddress' => 'test2@test.com',
        ]);

        $this->user3 = $em->createEntity('User', [
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
            ->getRepository('Notification')
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
            ->getRepository('Notification')
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
            ->getRepository('Notification')
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
            ->getRepository('Notification')
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
