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
use Espo\Core\Record\UpdateParams;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Account;
use tests\integration\Core\BaseTestCase;

class ReadOnlyTest extends BaseTestCase
{
    public function testReadOnly(): void
    {
        $metadata = $this->getContainer()->getByClass(Metadata::class);
        $metadata->set('entityDefs', Account::ENTITY_TYPE, [
            'fields' => [
                'name' => [
                    'readOnlyAfterCreate' => true,
                ],
                'type' => [
                    'readOnly' => true,
                ],
            ]
        ]);
        $metadata->save();

        $this->reCreateApplication();

        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Account::class);

        $account = $service->create((object) [
            'name' => 'Test',
            'type' => 'Customer',
        ], CreateParams::create());

        $this->assertEquals('Test', $account->get('name'));
        $this->assertNull($account->get('type'));

        $account = $service->update($account->getId(), (object) [
            'name' => 'Test 1',
            'billingAddressCity' => 'Hello',
        ], UpdateParams::create());

        $this->assertEquals('Test', $account->get('name'));
        $this->assertEquals('Hello', $account->get('billingAddressCity'));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testReadOnlyDynamicLogic(): void
    {
        $metadata = $this->getMetadata();

        $metadata->set('logicDefs', Account::ENTITY_TYPE, [
            'fields' => [
                'description' => [
                    'readOnlySaved' => [
                        'conditionGroup' => [
                            [
                                'type' => 'equals',
                                'attribute' => 'type',
                                'value' => 'Customer',
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $metadata->save();

        $this->reCreateApplication();

        $service = $this->getContainer()->getByClass(ServiceContainer::class)->getByClass(Account::class);

        $account = $service->create((object) [
            'name' => 'Test',
            'description' => '1',
        ], CreateParams::create());

        $service->update($account->getId(), (object) [
            'type' => 'Customer',
        ], UpdateParams::create());

        $account = $service->update($account->getId(), (object) [
            'description' => '2',
        ], UpdateParams::create());

        $this->assertEquals('1', $account->getValueMap()->description);
    }
}
