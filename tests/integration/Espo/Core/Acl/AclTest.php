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

namespace tests\integration\Espo\Core\Acl;

use Espo\Core\AclManager;
use Espo\Core\Acl;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Task;

class AclTest extends \tests\integration\Core\BaseTestCase
{
    public function testGetReadOwnerUserField()
    {
        /* @var $aclManager AclManager */
        $aclManager = $this->getContainer()->get('aclManager');

        $this->assertEquals(
            'assignedUser',
            $aclManager->getReadOwnerUserField('Account')
        );

        $this->assertEquals(
            'createdBy',
            $aclManager->getReadOwnerUserField('Import')
        );

        $this->assertEquals(
            'users',
            $aclManager->getReadOwnerUserField('Email')
        );

        $this->assertEquals(
            'users',
            $aclManager->getReadOwnerUserField('Meeting')
        );
    }

    public function testTryCheck(): void
    {
        /* @var $Acl Acl */
        $acl = $this->getContainer()->get('acl');

        $this->assertFalse($acl->tryCheck('NonExistingEntityType'));
    }

    public function testCheckScopeAccess(): void
    {
        $this->createUser('tester', [
            'data' => [
                Task::ENTITY_TYPE => false,
                Call::ENTITY_TYPE => [
                    'create' => Acl\Table::LEVEL_NO,
                    'read' => Acl\Table::LEVEL_NO,
                    'edit' => Acl\Table::LEVEL_NO,
                    'delete' => Acl\Table::LEVEL_NO,
                ],
                Meeting::ENTITY_TYPE => [
                    'create' => Acl\Table::LEVEL_YES,
                    'read' => Acl\Table::LEVEL_YES,
                    'edit' => Acl\Table::LEVEL_NO,
                    'delete' => Acl\Table::LEVEL_NO,
                ],
            ],
        ]);

        $this->auth('tester');
        $this->reCreateApplication();

        $acl = $this->getContainer()->getByClass(Acl::class);

        $this->assertFalse($acl->checkScope(Task::ENTITY_TYPE));
        $this->assertTrue($acl->checkScope(Call::ENTITY_TYPE));
        $this->assertTrue($acl->checkScope(Meeting::ENTITY_TYPE));

        $this->assertFalse($acl->check(Task::ENTITY_TYPE, Acl\Table::ACTION_READ));
        $this->assertFalse($acl->check(Call::ENTITY_TYPE, Acl\Table::ACTION_READ));
        $this->assertTrue($acl->check(Meeting::ENTITY_TYPE, Acl\Table::ACTION_READ));
    }

    public function testCheckField(): void
    {
        $user = $this->createUser('tester', [
            'fieldData' => [
                'Call' => [
                    'direction' => [
                        'read' => Acl\Table::LEVEL_NO,
                        'edit' => Acl\Table::LEVEL_NO,
                    ],
                    'contacts' => [
                        'read' => Acl\Table::LEVEL_YES,
                        'edit' => Acl\Table::LEVEL_NO,
                    ],
                ],
            ],
        ]);

        /** @var User $user */
        $user = $this->getEntityManager()->getEntityById(User::ENTITY_TYPE, $user->getId());

        /* @var $aclManager AclManager */
        $aclManager = $this->getContainer()->get('aclManager');

        $this->assertFalse($aclManager->checkField($user, 'Call', 'direction'));
        $this->assertTrue($aclManager->checkField($user, 'Call', 'contacts'));
        $this->assertFalse($aclManager->checkField($user, 'Call', 'contacts', Acl\Table::ACTION_EDIT));
    }
}
