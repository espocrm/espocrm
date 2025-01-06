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

namespace tests\integration\Espo\Role;

use Espo\Core\Acl\Table;
use Espo\Core\Portal\Acl\Table as TablePortal;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Entities\PortalRole;
use Espo\Entities\Role;
use Espo\Modules\Crm\Entities\Account;
use tests\integration\Core\BaseTestCase;

class ServiceTest extends BaseTestCase
{
    public function testCreateUpdate(): void
    {
        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Role::class);

        $data = (object) [
            Account::ENTITY_TYPE => (object) [
                Table::ACTION_CREATE => Table::LEVEL_YES,
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_OWN,
                Table::ACTION_DELETE => Table::LEVEL_NO,
                Table::ACTION_STREAM => Table::LEVEL_TEAM,
            ],
            'Activities' => true,
            'ExternalAccount' => false,
        ];

        $fieldData = (object) [
            Account::ENTITY_TYPE => (object) [
                'description' => (object) [
                    Table::ACTION_READ => Table::LEVEL_YES,
                    Table::ACTION_EDIT => Table::LEVEL_NO,
                ],
            ],
        ];

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->create((object) [
            'name' => 'Test',
            'data' => $data,
            'fieldData' => $fieldData,
        ], CreateParams::create());

        $this->assertEquals($data, $role->get('data'));
        $this->assertEquals($fieldData, $role->get('fieldData'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->update($role->getId(), (object) [
            'data' => (object) [],
            'fieldData' => (object) [],
        ], UpdateParams::create());

        $this->assertEquals((object) [], $role->get('data'));
        $this->assertEquals((object) [], $role->get('fieldData'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->update($role->getId(), (object) [
            'data' => $data,
            'fieldData' => $fieldData,
        ], UpdateParams::create());

        $this->assertEquals($data, $role->get('data'));
        $this->assertEquals($fieldData, $role->get('fieldData'));
    }

    public function testPortalCreateUpdate(): void
    {
        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(PortalRole::class);

        $data = (object) [
            Account::ENTITY_TYPE => (object) [
                Table::ACTION_CREATE => Table::LEVEL_YES,
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => TablePortal::LEVEL_CONTACT,
                Table::ACTION_DELETE => Table::LEVEL_NO,
                Table::ACTION_STREAM => TablePortal::LEVEL_ACCOUNT,
            ],
            'Activities' => true,
            'ExternalAccount' => false,
        ];

        $fieldData = (object) [
            Account::ENTITY_TYPE => (object) [
                'description' => (object) [
                    Table::ACTION_READ => Table::LEVEL_YES,
                    Table::ACTION_EDIT => Table::LEVEL_NO,
                ],
            ],
        ];

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->create((object) [
            'name' => 'Test',
            'data' => $data,
            'fieldData' => $fieldData,
        ], CreateParams::create());

        $this->assertEquals($data, $role->get('data'));
        $this->assertEquals($fieldData, $role->get('fieldData'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->update($role->getId(), (object) [
            'data' => (object) [],
            'fieldData' => (object) [],
        ], UpdateParams::create());

        $this->assertEquals((object) [], $role->get('data'));
        $this->assertEquals((object) [], $role->get('fieldData'));

        /** @noinspection PhpUnhandledExceptionInspection */
        $role = $service->update($role->getId(), (object) [
            'data' => $data,
            'fieldData' => $fieldData,
        ], UpdateParams::create());

        $this->assertEquals($data, $role->get('data'));
        $this->assertEquals($fieldData, $role->get('fieldData'));
    }
}
