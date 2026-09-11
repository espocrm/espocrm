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

namespace integration\Espo\Core\Acl;

use Espo\Core\Acl\Scope;
use Espo\Core\Acl\Table;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Tools\Stream\GlobalRecordService;
use RuntimeException;
use tests\integration\Core\BaseTestCase;

class TableTest extends BaseTestCase
{
    public function testScopesRegular(): void
    {
        $roleData = [
            Account::ENTITY_TYPE => [
                Table::ACTION_CREATE => Table::LEVEL_YES,
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_ALL,
                Table::ACTION_DELETE => Table::LEVEL_ALL,
                Table::ACTION_STREAM => Table::LEVEL_ALL,
            ],
            Contact::ENTITY_TYPE => false,
            Lead::ENTITY_TYPE => [
                Table::ACTION_CREATE => Table::LEVEL_YES,
                Table::ACTION_READ => Table::LEVEL_ALL,
                Table::ACTION_EDIT => Table::LEVEL_ALL,
                Table::ACTION_DELETE => Table::LEVEL_ALL,
                Table::ACTION_STREAM => Table::LEVEL_ALL,
            ],
            GlobalRecordService::SCOPE_NAME => true,
        ];

        $user = $this->createUser(
            userData: [
                User::FIELD_USER_NAME => 'test',
            ],
            role: [
                Role::FIELD_DATA => $roleData,
            ],
        );

        $factory = $this->getInjectableFactory()->create(Table\DefaultTableFactory::class);

        //

        $user = $this->reFetchUser($user);

        $table = $factory->create($user);

        $this->assertEquals(Table::LEVEL_YES, $table->getScopeData(Account::ENTITY_TYPE)->getCreate());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getRead());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getEdit());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getDelete());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getStream());

        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Lead::ENTITY_TYPE)->getRead());

        $this->assertFalse($table->getScopeData(Contact::ENTITY_TYPE)->isTrue());
        $this->assertTrue($table->getScopeData(GlobalRecordService::SCOPE_NAME)->isTrue());

        //

        $user->setScopes([
            Lead::ENTITY_TYPE,
            GlobalRecordService::SCOPE_NAME,
        ]);

        $table = $factory->create($user);

        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getCreate());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getRead());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getEdit());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getDelete());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getStream());

        $this->assertEquals(Table::LEVEL_YES, $table->getScopeData(Lead::ENTITY_TYPE)->getCreate());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Lead::ENTITY_TYPE)->getRead());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Lead::ENTITY_TYPE)->getEdit());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Lead::ENTITY_TYPE)->getDelete());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Lead::ENTITY_TYPE)->getStream());

        $this->assertTrue($table->getScopeData(GlobalRecordService::SCOPE_NAME)->isTrue());

        //

        $user = $this->reFetchUser($user);

        $user->setScopes([
            Scope::GLOBAL,
        ]);

        $table = $factory->create($user);

        $this->assertEquals(Table::LEVEL_YES, $table->getScopeData(Account::ENTITY_TYPE)->getCreate());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getRead());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getEdit());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getDelete());
        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getStream());

        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Lead::ENTITY_TYPE)->getRead());

        $this->assertFalse($table->getScopeData(Contact::ENTITY_TYPE)->isTrue());
        $this->assertTrue($table->getScopeData(GlobalRecordService::SCOPE_NAME)->isTrue());

        //

        $user = $this->reFetchUser($user);

        $user->setScopes([]);

        $table = $factory->create($user);

        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getCreate());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getRead());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getEdit());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getDelete());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Account::ENTITY_TYPE)->getStream());

        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Lead::ENTITY_TYPE)->getCreate());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Lead::ENTITY_TYPE)->getRead());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Lead::ENTITY_TYPE)->getEdit());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Lead::ENTITY_TYPE)->getDelete());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Lead::ENTITY_TYPE)->getStream());

        $this->assertFalse($table->getScopeData(Contact::ENTITY_TYPE)->isTrue());
        $this->assertFalse($table->getScopeData(GlobalRecordService::SCOPE_NAME)->isTrue());

        //

        $user = $this->reFetchUser($user);

        $user->setScopes([
            Scope::ADMIN,
        ]);

        $table = $factory->create($user);

        $this->assertFalse($table->getScopeData(Scope::ADMIN)->isTrue());

        $this->assertFalse($user->isEffectiveAdmin());
    }

    public function testScopesAdmin(): void
    {
        $user = $this->createUser(
            userData: [
                User::FIELD_USER_NAME => 'test',
                User::FIELD_TYPE => User::TYPE_ADMIN,
            ]
        );

        $factory = $this->getInjectableFactory()->create(Table\DefaultTableFactory::class);

        //

        $user = $this->reFetchUser($user);

        $table = $factory->create($user);

        $this->assertTrue($table->getScopeData(Scope::ADMIN)->isTrue());

        //

        $user = $this->reFetchUser($user);

        $user->setScopes([
            Scope::ADMIN,
        ]);

        $table = $factory->create($user);

        $this->assertTrue($table->getScopeData(Scope::ADMIN)->isTrue());

        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getRead());
        $this->assertTrue($table->getScopeData(GlobalRecordService::SCOPE_NAME)->isTrue());

        //

        $user = $this->reFetchUser($user);

        $user->setScopes([
            Scope::GLOBAL,
        ]);

        $table = $factory->create($user);

        $this->assertFalse($table->getScopeData(Scope::ADMIN)->isTrue());

        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getRead());
        $this->assertTrue($table->getScopeData(GlobalRecordService::SCOPE_NAME)->isTrue());

        //

        $user = $this->reFetchUser($user);

        $user->setScopes([
            Account::ENTITY_TYPE,
        ]);

        $table = $factory->create($user);

        $this->assertFalse($table->getScopeData(Scope::ADMIN)->isTrue());

        $this->assertEquals(Table::LEVEL_ALL, $table->getScopeData(Account::ENTITY_TYPE)->getRead());
        $this->assertEquals(Table::LEVEL_NO, $table->getScopeData(Lead::ENTITY_TYPE)->getRead());
    }

    private function reFetchUser(User $user): User
    {
        return $this->getEntityManager()
            ->getRDBRepositoryByClass(User::class)
            ->getById($user->getId()) ?? throw new RuntimeException();
    }
}
