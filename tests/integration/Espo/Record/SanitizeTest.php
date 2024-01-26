<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Modules\Crm\Entities\Account;
use Espo\Tools\FieldManager\FieldManager;
use tests\integration\Core\BaseTestCase;

class SanitizeTest extends BaseTestCase
{
    public function testSanitize(): void
    {
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
            'array' => [
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
        $this->assertEquals(['test', 'hello'], $account->get('array'));

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
            'array' => null,
        ], CreateParams::create());

        $this->assertEquals('Test', $account->get('description'));
        $this->assertEquals([], $account->get('array'));

        $numbers = $account->getPhoneNumberGroup()->getNumberList();
        $this->assertCount(2, $numbers);

        sort($numbers);

        $this->assertEquals('+380904443322', $numbers[0]);
        $this->assertEquals('+380904443333', $numbers[1]);
    }
}
