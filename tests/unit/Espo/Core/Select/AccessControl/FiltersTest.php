<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\unit\Espo\Core\Select\AccessControl;

use Espo\{
    ORM\Query\SelectBuilder as QueryBuilder,
    Core\Select\Helpers\FieldHelper,
    Core\Select\AccessControl\Filters\No,
    Core\Select\AccessControl\Filters\OnlyOwn,
    Core\Select\AccessControl\Filters\OnlyTeam,
    Core\Select\AccessControl\Filters\PortalOnlyAccount,
    Core\Select\AccessControl\Filters\PortalOnlyContact,
    Core\Select\AccessControl\Filters\PortalOnlyOwn,
    Core\Select\AccessControl\Filter as AccessControlFilter,
    Entities\User,
};

class FiltersTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->user = $this->createMock(User::class);

        $this->user->id = 'user-id';

        $this->user
            ->method('getId')
            ->willReturn('user-id');

        $this->entityType = 'Test';

        $this->user
            ->expects($this->any())
            ->method('getTeamIdList')
            ->willReturn(['team-id']);
    }

    public function testNo()
    {
        $filter = $this->createFilter(No::class);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'id' => null,
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testOnlyOwn1()
    {
        $filter = $this->createFilter(OnlyOwn::class);

        $this->initHelperMethods([
            ['hasAssignedUsersField', true],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct');

        $this->queryBuilder
            ->expects($this->once())
            ->method('leftJoin')
            ->with('assignedUsers', 'assignedUsersAccess');

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'assignedUsersAccess.id' => $this->user->id,
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testOnlyOwn2()
    {
        $filter = $this->createFilter(OnlyOwn::class);

        $this->initHelperMethods([
            ['hasAssignedUsersField', false],
            ['hasAssignedUserField', true],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'assignedUserId' => $this->user->id,
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testOnlyOwn3()
    {
        $filter = $this->createFilter(OnlyOwn::class);

        $this->initHelperMethods([
            ['hasAssignedUsersField', false],
            ['hasAssignedUserField', false],
            ['hasCreatedByField', true],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'createdById' => $this->user->id,
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testOnlyTeam1()
    {
        $filter = $this->createFilter(OnlyTeam::class);

        $this->initHelperMethods([
            ['hasTeamsField', true],
            ['hasAssignedUsersField', true],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct');

        $this->queryBuilder
            ->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                ['teams', 'teamsAccess'],
                ['assignedUsers', 'assignedUsersAccess'],
            )
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'OR' => [
                    'teamsAccess.id' => ['team-id'],
                    'assignedUsersAccess.id' => $this->user->id,
                ],
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testOnlyTeam2()
    {
        $filter = $this->createFilter(OnlyTeam::class);

        $this->initHelperMethods([
            ['hasTeamsField', false],
        ]);

        $this->queryBuilder
            ->expects($this->never())
            ->method('distinct');

        $filter->apply($this->queryBuilder);
    }

    public function testOnlyTeam3()
    {
        $filter = $this->createFilter(OnlyTeam::class);

        $this->initHelperMethods([
            ['hasTeamsField', true],
            ['hasAssignedUsersField', false],
            ['hasAssignedUserField', true],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct');

        $this->queryBuilder
            ->expects($this->exactly(1))
            ->method('leftJoin')
            ->withConsecutive(
                ['teams', 'teamsAccess'],
            );

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'OR' => [
                    'teamsAccess.id' => ['team-id'],
                    'assignedUserId' => $this->user->id,
                ],
            ])
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    public function testOnlyTeam4()
    {
        $filter = $this->createFilter(OnlyTeam::class);

        $this->initHelperMethods([
            ['hasTeamsField', true],
            ['hasAssignedUsersField', false],
            ['hasAssignedUserField', false],
            ['hasCreatedByField', true],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct');

        $this->queryBuilder
            ->expects($this->exactly(1))
            ->method('leftJoin')
            ->withConsecutive(
                ['teams', 'teamsAccess'],
            )
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'OR' => [
                    'teamsAccess.id' => ['team-id'],
                    'createdById' => $this->user->id,
                ],
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testOnlyTeam5()
    {
        $filter = $this->createFilter(OnlyTeam::class);

        $this->initHelperMethods([
            ['hasTeamsField', true],
            ['hasAssignedUsersField', false],
            ['hasAssignedUserField', false],
            ['hasCreatedByField', false],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct');

        $this->queryBuilder
            ->expects($this->exactly(1))
            ->method('leftJoin')
            ->withConsecutive(
                ['teams', 'teamsAccess'],
            )
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'OR' => [
                    'teamsAccess.id' => ['team-id'],
                ],
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyAccount1()
    {
        $filter = $this->createFilter(PortalOnlyAccount::class);

        $this->user
            ->expects($this->any())
            ->method('getLinkMultipleIdList')
            ->with('accounts')
            ->willReturn(['account-id']);

        $this->user
            ->expects($this->any())
            ->method('get')
            ->with('contactId')
            ->willReturn('contact-id');

        $this->initHelperMethods([
            ['hasAccountField', true],
            ['hasAccountsRelation', true],
            ['hasParentField', true],
            ['hasContactField', true],
            ['hasContactsRelation', true],
            ['hasCreatedByField', true],
        ]);

        $this->queryBuilder
            ->expects($this->exactly(2))
            ->method('distinct')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->exactly(2))
            ->method('leftJoin')
            ->withConsecutive(
                ['accounts', 'accountsAccess'],
                ['contacts', 'contactsAccess'],
            )
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'OR' => [
                    'accountId' => ['account-id'],
                    'accountsAccess.id' => ['account-id'],
                    [
                        'parentType' => 'Account',
                        'parentId' => ['account-id'],
                    ],
                    [
                        'parentType' => 'Contact',
                        'parentId' => 'contact-id',
                    ],
                    'contactId' => 'contact-id',
                    'contactsAccess.id' => 'contact-id',
                    'createdById' => $this->user->id,
                ],
            ])
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyAccount2()
    {
        $filter = $this->createFilter(PortalOnlyAccount::class);

        $this->user
            ->expects($this->any())
            ->method('getLinkMultipleIdList')
            ->with('accounts')
            ->willReturn([]);

        $this->user
            ->expects($this->any())
            ->method('get')
            ->with('contactId')
            ->willReturn(null);

        $this->initHelperMethods([
            ['hasCreatedByField', false],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'id' => null,
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyContact1()
    {
        $filter = $this->createFilter(PortalOnlyContact::class);

        $this->user
            ->expects($this->any())
            ->method('get')
            ->with('contactId')
            ->willReturn('contact-id');

        $this->initHelperMethods([
            ['hasContactField', true],
            ['hasContactsRelation', true],
            ['hasParentField', true],
            ['hasCreatedByField', true],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('leftJoin')
            ->with('contacts', 'contactsAccess')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'OR' => [
                    'contactId' => 'contact-id',
                    'contactsAccess.id' => 'contact-id',
                    [
                        'parentType' => 'Contact',
                        'parentId' => 'contact-id',
                    ],
                    'createdById' => $this->user->id,
                ],
            ])
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyContact2()
    {
        $filter = $this->createFilter(PortalOnlyContact::class);

        $this->user
            ->expects($this->any())
            ->method('get')
            ->with('contactId')
            ->willReturn(null);

        $this->initHelperMethods([
            ['hasCreatedByField', false],
        ]);

        $this->queryBuilder
            ->expects($this->never())
            ->method('distinct');

        $this->queryBuilder
            ->expects($this->never())
            ->method('leftJoin');

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'id' => null,
            ])
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyOwn1()
    {
        $filter = $this->createFilter(PortalOnlyOwn::class);

        $this->initHelperMethods([
            ['hasCreatedByField', true],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'createdById' => $this->user->id,
            ])
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyOwn2()
    {
        $filter = $this->createFilter(PortalOnlyOwn::class);

        $this->initHelperMethods([
            ['hasCreatedByField', false],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'id' => null,
            ])
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    protected function initHelperMethods(array $map)
    {
        foreach ($map as $i => $item) {
            $this->fieldHelper
                ->expects($this->once())
                ->method($item[0])
                ->willReturn($item[1]);
        }
    }

    protected function createFilter(string $className) : AccessControlFilter
    {
        return new $className(
            $this->user,
            $this->fieldHelper
        );
    }

}
