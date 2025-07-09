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

namespace tests\unit\Espo\Core\Select\Where;

use Espo\Core\Utils\Acl\UserAclManagerProvider;

use Espo\Core\Acl;
use Espo\Core\AclManager;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\Where\Checker;
use Espo\Core\Select\Where\CheckerFactory;
use Espo\Entities\User;
use PHPUnit\Framework\TestCase;

class CheckerFactoryTest extends TestCase
{
    private $injectableFactory;
    private $user;
    private $acl;
    private $factory;

    protected function setUp(): void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $aclManager = $this->createMock(AclManager::class);
        $this->user = $this->createMock(User::class);
        $this->acl = $this->createMock(Acl::class);
        $userAclFilterResolver = $this->createMock(UserAclManagerProvider::class);

        $aclManager
            ->expects($this->any())
            ->method('createUserAcl')
            ->with($this->user)
            ->willReturn($this->acl);

        $userAclFilterResolver
            ->expects($this->any())
            ->method('get')
            ->with($this->user)
            ->willReturn($aclManager);

        $this->factory = new CheckerFactory(
            $this->injectableFactory,
            $userAclFilterResolver
        );
    }

    public function testCreate1()
    {
        $this->prepareFactoryTest();
    }

    protected function prepareFactoryTest()
    {
        $entityType = 'Test';

        $object = $this->createMock(Checker::class);

        $this->injectableFactory
            ->expects($this->once())
            ->method('createWith')
            ->with(
                Checker::class,
                [
                    'entityType' => $entityType,
                    'acl' => $this->acl,
                ]
            )
            ->willReturn($object);

        $resultObject = $this->factory->create(
            $entityType,
            $this->user
        );

        $this->assertEquals($object, $resultObject);
    }
}
