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

namespace tests\integration\Espo\Core\FieldProcessing;

use DateTimeImmutable;
use Espo\Core\Authentication\Util\DelayUtil;
use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingProcessor;
use Espo\Core\Field\DateTime;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\DateTime\Clock;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Reminder;
use tests\integration\Core\BaseTestCase;

class ReminderTest extends BaseTestCase
{
    public function testOne(): void
    {
        /* @var $entityManager EntityManager */
        $entityManager = $this->getContainer()->getByClass(EntityManager::class);

        $user = $this->getContainer()->getByClass(User::class);

        $meeting = $entityManager->createEntity('Meeting', [
            'dateStart' => DateTime::createNow()->modify('+1 day')->toString(),
            'usersIds' => [$user->getId()],
            'reminders' => [
                (object) [
                    'type' => 'Popup',
                    'seconds' => 0,
                ],
                 (object) [
                    'type' => 'Popup',
                    'seconds' => 60,
                ]
            ]
        ]);

        $reminderList = $entityManager
            ->getRDBRepository(Reminder::ENTITY_TYPE)
            ->where([
                'entityId' => $meeting->getId(),
                'entityType' => $meeting->getEntityType(),
            ])
            ->order('remindAt')
            ->find();

        $this->assertEquals(2, count($reminderList));
    }

    public function testFallsToPast(): void
    {
        $clock = $this->createMock(Clock::class);
        $clock->method('now')
            ->willReturn(new DateTimeImmutable('2030-01-01 00:00'));


        $app = $this->createApplication(
            binding: new class ($clock) implements BindingProcessor {

                public function __construct(private Clock $clock) {}

                public function process(Binder $binder): void
                {
                    $binder->bindInstance(Clock::class, $this->clock);
                }
            },
            // @todo Need to reset loaded hooks in the HookManager. Bind EventDispatcher to create repositories,
            //    so that it is available in the HookManager.
            //reuse: true,
        );
        $this->setApplication($app);

        $entityManager = $this->getEntityManager();

        $user = $this->getContainer()->getByClass(User::class);

        $meeting = $entityManager->createEntity(Meeting::ENTITY_TYPE, [
            'dateStart' => DateTime::fromDateTime($clock->now())->modify('+10 minutes')->toString(),
            'usersIds' => [$user->getId()],
            'reminders' => [
                (object) [
                    'type' => Reminder::TYPE_POPUP,
                    'seconds' => 60 * 60,
                ],
                (object) [
                    'type' => Reminder::TYPE_POPUP,
                    'seconds' => 120 * 60,
                ],
                (object) [
                    'type' => Reminder::TYPE_EMAIL,
                    'seconds' => 60 * 60,
                ],
                (object) [
                    'type' => Reminder::TYPE_EMAIL,
                    'seconds' => 120 * 60,
                ],
            ]
        ]);

        $reminderList = $entityManager
            ->getRDBRepositoryByClass(Reminder::class)
            ->where([
                'entityId' => $meeting->getId(),
                'entityType' => $meeting->getEntityType(),
            ])
            ->find();

        $this->assertCount(2, $reminderList);
    }
}
