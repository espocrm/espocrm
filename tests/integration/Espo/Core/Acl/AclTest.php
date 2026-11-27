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

namespace tests\integration\Espo\Core\Acl;

use Espo\Core\AclManager;
use Espo\Core\Acl;
use Espo\Core\Field\LinkMultiple;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Task;
use RuntimeException;
use tests\integration\Core\BaseTestCase;

class AclTest extends BaseTestCase
{
    public function testGetReadOwnerUserField()
    {
        $aclManager = $this->getContainer()->getByClass(AclManager::class);

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

        $this->authenticate('tester');

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

        $aclManager = $this->getContainer()->getByClass(AclManager::class);

        $this->assertFalse($aclManager->checkField($user, 'Call', 'direction'));
        $this->assertTrue($aclManager->checkField($user, 'Call', 'contacts'));
        $this->assertFalse($aclManager->checkField($user, 'Call', 'contacts', Acl\Table::ACTION_EDIT));
    }

    public function testDisabledField(): void
    {
        $metadata = $this->getMetadata();

        $metadata->set('entityDefs', 'Account', [
            'fields' => [
                'assignedUser' => [
                    'disabled' => true,
                ]
            ]
        ]);
        $metadata->save();

        $this->reCreateApplication(reuse: true);

        $acl = $this->getContainer()->getByClass(Acl::class);

        $this->assertFalse($acl->checkField(Account::ENTITY_TYPE, 'assignedUser'));
        $this->assertTrue($acl->checkField(Account::ENTITY_TYPE, 'name'));
    }

    public function testDisabledLink(): void
    {
        $metadata = $this->getMetadata();

        $metadata->set('entityDefs', 'Account', [
            'links' => [
                'opportunities' => [
                    'disabled' => true,
                ]
            ]
        ]);
        $metadata->save();

        $this->reCreateApplication(reuse: true);

        $acl = $this->getContainer()->getByClass(Acl::class);

        $this->assertFalse($acl->checkLink('Account', 'opportunities'));
    }

    public function testMultipleRoles(): void
    {
        $em = $this->getEntityManager();

        $role1 = $em->getRDBRepositoryByClass(Role::class)->getNew();
        $role1->setRawData([
            'Lead' => [
                Acl\Table::ACTION_CREATE => Acl\Table::LEVEL_YES,
            ],
            'Opportunity' => [
                Acl\Table::ACTION_EDIT => Acl\Table::LEVEL_NO,
            ],
            'Account' => [
                Acl\Table::ACTION_CREATE => Acl\Table::LEVEL_NO,
            ],
            'Meeting' => [
                Acl\Table::ACTION_CREATE => Acl\Table::LEVEL_YES,
                Acl\Table::ACTION_EDIT => Acl\Table::LEVEL_NO,
                Acl\Table::ACTION_STREAM => Acl\Table::LEVEL_NO,
            ],
            'Call' => [
                Acl\Table::ACTION_CREATE => Acl\Table::LEVEL_NO,
                Acl\Table::ACTION_EDIT => Acl\Table::LEVEL_ALL,
            ],
        ]);
        $em->saveEntity($role1);

        $role2 = $em->getRDBRepositoryByClass(Role::class)->getNew();
        $role2->setRawData([
            'Lead' => [
                Acl\Table::ACTION_EDIT => Acl\Table::LEVEL_NO,
            ],
            'Opportunity' => [
                Acl\Table::ACTION_CREATE => Acl\Table::LEVEL_YES,
            ],
            'Account' => [
                Acl\Table::ACTION_EDIT => Acl\Table::LEVEL_NO,
            ],
            'Meeting' => [
                Acl\Table::ACTION_CREATE => Acl\Table::LEVEL_NO,
                Acl\Table::ACTION_EDIT => Acl\Table::LEVEL_ALL,
                Acl\Table::ACTION_STREAM => Acl\Table::LEVEL_NO,
            ],
            'Call' => [
                Acl\Table::ACTION_CREATE => Acl\Table::LEVEL_YES,
                Acl\Table::ACTION_EDIT => Acl\Table::LEVEL_NO,
            ],
        ]);
        $em->saveEntity($role2);

        $user = $em->getRDBRepositoryByClass(User::class)->getNew();
        $user
            ->setUserName('test-1')
            ->setRoles(LinkMultiple::create()->withAddedIdList([$role1->getId(), $role2->getId()]));
        $em->saveEntity($user);

        $em->refreshEntity($user);

        $aclManager = $this->getContainer()->getByClass(AclManager::class);

        $this->assertTrue($aclManager->checkScope($user, 'Lead', Acl\Table::ACTION_CREATE));
        $this->assertTrue($aclManager->checkScope($user, 'Opportunity', Acl\Table::ACTION_CREATE));
        $this->assertFalse($aclManager->checkScope($user, 'Account', Acl\Table::ACTION_CREATE));
        $this->assertFalse($aclManager->checkScope($user, 'Opportunity', Acl\Table::ACTION_EDIT));
        $this->assertFalse($aclManager->checkScope($user, 'Account', Acl\Table::ACTION_EDIT));
        $this->assertTrue($aclManager->checkScope($user, 'Meeting', Acl\Table::ACTION_CREATE));
        $this->assertTrue($aclManager->checkScope($user, 'Meeting', Acl\Table::ACTION_EDIT));
        $this->assertFalse($aclManager->checkScope($user, 'Meeting', Acl\Table::ACTION_STREAM));
        $this->assertTrue($aclManager->checkScope($user, 'Call', Acl\Table::ACTION_CREATE));
        $this->assertTrue($aclManager->checkScope($user, 'Call', Acl\Table::ACTION_EDIT));
    }

    public function testCheckAdmin(): void
    {
        $user = $this->createUser(
            userData: [
                User::FIELD_USER_NAME => 'test',
            ],
        );

        $admin = $this->createUser(
            userData: [
                User::FIELD_USER_NAME => 'admin-test',
                User::FIELD_TYPE => User::TYPE_ADMIN,
            ],
        );

        $aclManager = $this->getInjectableFactory()->create(AclManager::class);

        //

        $user = $this->reFetchUser($user);
        $admin = $this->reFetchUser($admin);

        $this->assertFalse($aclManager->checkAdmin($user));
        $this->assertTrue($aclManager->checkAdmin($admin));

        $this->assertFalse($aclManager->createUserAcl($user)->checkAdmin());
        $this->assertTrue($aclManager->createUserAcl($admin)->checkAdmin());

        //

        $user = $this->reFetchUser($user);
        $admin = $this->reFetchUser($admin);

        $user->setScopes([Acl\Scope::ADMIN]);
        $admin->setScopes([Acl\Scope::ADMIN]);

        $this->assertFalse($aclManager->checkAdmin($user));
        $this->assertTrue($aclManager->checkAdmin($admin));

        //

        $user = $this->reFetchUser($user);
        $admin = $this->reFetchUser($admin);

        $user->setScopes([]);
        $admin->setScopes([]);

        $this->assertFalse($aclManager->checkAdmin($user));
        $this->assertFalse($aclManager->checkAdmin($admin));
    }

    public function testHash(): void
    {
        $roleData = [
            Role::FIELD_DATA => [
                Call::ENTITY_TYPE => [
                    Acl\Table::ACTION_CREATE => Acl\Table::LEVEL_NO,
                    Acl\Table::ACTION_READ => Acl\Table::LEVEL_OWN,
                    Acl\Table::ACTION_EDIT => Acl\Table::LEVEL_NO,
                    Acl\Table::ACTION_DELETE => Acl\Table::LEVEL_NO,
                ],
            ],
            Role::FIELD_FIELD_DATA => [
                Call::ENTITY_TYPE => [
                    'direction' => [
                        Acl\Table::ACTION_READ => Acl\Table::LEVEL_YES,
                        Acl\Table::ACTION_EDIT => Acl\Table::LEVEL_NO,
                    ],
                ],
            ],
        ];

        $otherRoleData = $roleData;
        $otherRoleData['fieldData'][Call::ENTITY_TYPE]['direction'][Acl\Table::ACTION_READ] = Acl\Table::LEVEL_NO;

        $user1 = $this->reFetchUser($this->createUser('test-1', $roleData));
        $user2 = $this->reFetchUser($this->createUser('test-2', $roleData));
        $user3 = $this->reFetchUser($this->createUser('test-3', $otherRoleData));

        $aclManager = $this->getInjectableFactory()->create(AclManager::class);

        $hash1 = $aclManager->getHash($user1);
        $hash2 = $aclManager->getHash($user2);
        $hash3 = $aclManager->getHash($user3);

        $this->assertNotEmpty($hash1);
        $this->assertEquals($hash1, $hash2);
        $this->assertNotEquals($hash1, $hash3);
        $this->assertEquals($hash1, $aclManager->createUserAcl($user1)->getHash());

        $this->assertObjectNotHasProperty('hash', $aclManager->getMapData($user1));

        // A new instance. Loads from the cache if caching is enabled.
        $aclManager = $this->getInjectableFactory()->create(AclManager::class);

        $this->assertEquals($hash1, $aclManager->getHash($this->reFetchUser($user1)));
    }

    private function reFetchUser(User $user): User
    {
        return $this->getEntityManager()
            ->getRDBRepositoryByClass(User::class)
            ->getById($user->getId()) ?? throw new RuntimeException();
    }
}
