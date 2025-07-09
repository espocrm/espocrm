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

namespace tests\unit\Espo\Core;

use Espo\Core\Acl\AccessChecker\AccessCheckerFactory;
use Espo\Core\Acl\GlobalRestriction;
use Espo\Core\Acl\Map\MapFactory;
use Espo\Core\Acl\OwnershipChecker\OwnershipCheckerFactory;
use Espo\Core\Acl\OwnerUserFieldProvider;
use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table;
use Espo\Core\Acl\Table\TableFactory;
use Espo\Core\AclManager;
use Espo\Core\ORM\EntityManager;

use Espo\Entities\User;
use PHPUnit\Framework\TestCase;

class AclManagerTest extends TestCase
{
    /** @var AclManager */
    private $aclManager;
    /** @var TableFactory */
    private $tableFactory;
    /** @var User */
    private $user;

    private $table;

    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
        $this->table = $this->createMock(Table::class);

        $accessCheckerFactory = $this->createMock(AccessCheckerFactory::class);
        $ownershipCheckerFactory = $this->createMock(OwnershipCheckerFactory::class);
        $this->tableFactory = $this->createMock(TableFactory::class);
        $mapFactory = $this->createMock(MapFactory::class);
        $globalRestriction = $this->createMock(GlobalRestriction::class);

        $this->aclManager = new AclManager(
            $accessCheckerFactory,
            $ownershipCheckerFactory,
            $this->tableFactory,
            $mapFactory,
            $globalRestriction,
            $this->createMock(OwnerUserFieldProvider::class),
            $this->createMock(EntityManager::class)
        );
    }

    private function initTableFactory(User $user, Table $table): void
    {
        $this->tableFactory
            ->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturn($table);
    }

    public function testGetPermissionLevel1(): void
    {
        $this->initTableFactory($this->user, $this->table);

        $this->table
            ->expects($this->once())
            ->method('getPermissionLevel')
            ->with(Permission::ASSIGNMENT)
            ->willReturn(Table::LEVEL_YES);

        $this->aclManager->getPermissionLevel($this->user, Permission::ASSIGNMENT);
    }

    public function testGetPermissionLevel2(): void
    {
        $this->initTableFactory($this->user, $this->table);

        $this->table
            ->expects($this->once())
            ->method('getPermissionLevel')
            ->with(Permission::ASSIGNMENT)
            ->willReturn(Table::LEVEL_YES);

        $this->aclManager->getPermissionLevel($this->user, Permission::ASSIGNMENT);
    }
}
