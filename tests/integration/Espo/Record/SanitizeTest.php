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
use tests\integration\Core\BaseTestCase;

class SanitizeTest extends BaseTestCase
{
    public function testSanitize(): void
    {
        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Account::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @var Account $account */
        $account = $service->create((object) [
            'name' => 'Test 1',
            'phoneNumber' => '+380 9044 433 11',
        ], CreateParams::create());

        $numbers = $account->getPhoneNumberGroup()->getNumberList();
        $this->assertCount(1, $numbers);
        $this->assertEquals('+380904443311', $numbers[0]);

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
        ], CreateParams::create());

        $numbers = $account->getPhoneNumberGroup()->getNumberList();
        $this->assertCount(2, $numbers);

        sort($numbers);

        $this->assertEquals('+380904443322', $numbers[0]);
        $this->assertEquals('+380904443333', $numbers[1]);
    }
}
