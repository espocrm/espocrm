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

namespace tests\integration\Espo\Record;

use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Crm\Entities\Task;
use Espo\Tools\FieldManager\FieldManager;
use tests\integration\Core\BaseTestCase;

class SanitizeTest extends BaseTestCase
{
    public function testSanitize(): void
    {
        // phone

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getInjectableFactory()
            ->create(FieldManager::class)
            ->create(Account::ENTITY_TYPE, 'array', ['type' => 'array']);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getDataManager()->rebuild();

        $this->reCreateApplication();

        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Account::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @var Account $account */
        $account = $service->create((object) [
            'name' => '  Test 1 ',
            'sicCode' => ' ',
            'phoneNumber' => '+380 9044 433 11',
            'description' => '',
            'cArray' => [
                ' test ',
                'hello',
            ],
        ], CreateParams::create());

        $numbers = $account->getPhoneNumberGroup()->getNumberList();
        $this->assertCount(1, $numbers);
        $this->assertEquals('+380904443311', $numbers[0]);

        $this->assertEquals('Test 1', $account->getName());
        $this->assertEquals(null, $account->get('sicCode'));
        $this->assertEquals(null, $account->get('description'));
        $this->assertEquals(['test', 'hello'], $account->get('cArray'));

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @var Account $account */
        $account = $service->create((object) [
            'name' => 'Test 2',
            'phoneNumberData' => [
                (object) [
                    'phoneNumber' => '+38 09 044 433 22',
                ],
                (object) [
                    'phoneNumber' => '+38 09 044 433 33',
                ],
            ],
            'description' => 'Test',
            'cArray' => null,
        ], CreateParams::create());

        $this->assertEquals('Test', $account->get('description'));
        $this->assertEquals([], $account->get('cArray'));

        $numbers = $account->getPhoneNumberGroup()->getNumberList();
        $this->assertCount(2, $numbers);

        sort($numbers);

        $this->assertEquals('+380904443322', $numbers[0]);
        $this->assertEquals('+380904443333', $numbers[1]);

        $configWriter = $this->getInjectableFactory()->create(ConfigWriter::class);
        $configWriter->set('phoneNumberExtensions', true);
        $configWriter->save();

        /** @var Account $account */
        /** @noinspection PhpUnhandledExceptionInspection */
        $account = $service->create((object) [
            'name' => 'Test 3',
            'phoneNumberData' => [
                (object) [
                    'phoneNumber' => '+38 09 044 433 22 ext. 0001',
                ],
                (object) [
                    'phoneNumber' => '+38 09 044 433 33 x. 1001',
                ],
                (object) [
                    'phoneNumber' => '+380904443344x.1000',
                ],
                (object) [
                    'phoneNumber' => '+380904443355#1000',
                ],
                (object) [
                    'phoneNumber' => '+380904443366 # 1000',
                ],
            ],
        ], CreateParams::create());

        $numbers = $account->getPhoneNumberGroup()->getNumberList();
        $this->assertCount(5, $numbers);

        sort($numbers);

        $this->assertEquals('+380904443322 ext. 0001', $numbers[0]);
        $this->assertEquals('+380904443333 ext. 1001', $numbers[1]);
        $this->assertEquals('+380904443344 ext. 1000', $numbers[2]);
        $this->assertEquals('+380904443355 ext. 1000', $numbers[3]);
        $this->assertEquals('+380904443366 ext. 1000', $numbers[4]);

        // datetime

        /** @noinspection PhpUnhandledExceptionInspection */
        $meeting = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Meeting::class)
            ->create((object) [
                'name' => 'Test',
                'dateStart' => '2030-12-10 10:11:12',
                'dateEnd' => '2030-12-10T10:11:12-01:00',
                'assignedUserId' => $this->getContainer()->getByClass(User::class)->getId(),
            ], CreateParams::create());

        $this->assertEquals('2030-12-10 10:11:12', $meeting->get('dateStart'));
        $this->assertEquals('2030-12-10 11:11:12', $meeting->get('dateEnd'));

        // datetimeOptional

        /** @noinspection PhpUnhandledExceptionInspection */
        $task = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Task::class)
            ->create((object) [
                'name' => 'Test',
                'dateStartDate' => '2030-12-10T10:11:12-01:00',
                'dateEnd' => '2030-12-10T10:11:12-01:00',
                'assignedUserId' => $this->getContainer()->getByClass(User::class)->getId(),
            ], CreateParams::create());

        $this->assertEquals('2030-12-10', $task->get('dateStartDate'));
        $this->assertEquals('2030-12-10 11:11:12', $task->get('dateEnd'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $task = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Task::class)
            ->create((object) [
                'name' => 'Test',
                'dateStartDate' => '2030-12-10',
                'assignedUserId' => $this->getContainer()->getByClass(User::class)->getId(),
            ], CreateParams::create());

        $this->assertEquals('2030-12-10', $task->get('dateStartDate'));

        // date

        /** @noinspection PhpUnhandledExceptionInspection */
        $meeting = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Opportunity::class)
            ->create((object) [
                'name' => 'Test',
                'closeDate' => '2030-12-10T10:11:12-01:00',
                'assignedUserId' => $this->getContainer()->getByClass(User::class)->getId(),
                'probability' => 10,
                'amount' => 1.0,
            ], CreateParams::create());

        $this->assertEquals('2030-12-10', $meeting->get('closeDate'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $meeting = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Opportunity::class)
            ->create((object) [
                'name' => 'Test',
                'closeDate' => '2030-12-10',
                'assignedUserId' => $this->getContainer()->getByClass(User::class)->getId(),
                'probability' => 10,
                'amount' => 1.0,
            ], CreateParams::create());

        $this->assertEquals('2030-12-10', $meeting->get('closeDate'));
    }
}
