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

use Espo\Core\{
    Exceptions\ConflictSilent,
    Record\CreateParams,
};

use Espo\Modules\Crm\Services\Account;
use Espo\Modules\Crm\Services\Lead;


class DuplicateFindTest extends \tests\integration\Core\BaseTestCase
{
    public function testAccount1()
    {
        /* @var $service Account */
        $service = $this->getContainer()
            ->get('recordServiceContainer')
            ->get('Account');

        $data1 = (object) [
            'name' => 'test1',
            'emailAddress' => 'test@test.com',
        ];

        $service->create($data1, CreateParams::create());

        $this->expectException(ConflictSilent::class);

        $data2 = (object) [
            'name' => 'test2',
            'emailAddress' => 'test@test.com',
        ];

        $service->create($data2, CreateParams::create());
    }

    public function testAccountSkip()
    {
        /* @var $service Account */
        $service = $this->getContainer()
            ->get('recordServiceContainer')
            ->get('Account');

        $data1 = (object) [
            'name' => 'test1',
            'emailAddress' => 'test@test.com',
        ];

        $service->create($data1, CreateParams::create());

        $data2 = (object) [
            'name' => 'test2',
            'emailAddress' => 'test@test.com',
        ];

        $service->create($data2, CreateParams::create()->withSkipDuplicateCheck());

        $this->assertTrue(true);
    }

    public function testLead1()
    {
        /* @var $service Lead */
        $service = $this->getContainer()
            ->get('recordServiceContainer')
            ->get('Lead');

        $data1 = (object) [
            'lastName' => 'test1',
            'emailAddress' => 'test@test.com',
        ];

        $service->create($data1, CreateParams::create());

        $this->expectException(ConflictSilent::class);

        $data2 = (object) [
            'lastName' => 'test2',
            'emailAddress' => 'test@test.com',
        ];

        $service->create($data2, CreateParams::create());
    }

    public function testLeadSkip()
    {
        /* @var $service Lead */
        $service = $this->getContainer()
            ->get('recordServiceContainer')
            ->get('Lead');

        $data1 = (object) [
            'lastName' => 'test1',
            'emailAddress' => 'test@test.com',
        ];

        $service->create($data1, CreateParams::create());

        $data2 = (object) [
            'lastName' => 'test2',
            'emailAddress' => 'test@test.com',
        ];

        $service->create($data2, CreateParams::create()->withSkipDuplicateCheck());

        $this->assertTrue(true);
    }
}
