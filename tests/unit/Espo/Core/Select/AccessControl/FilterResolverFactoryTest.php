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

namespace tests\unit\Espo\Core\Select\AccessControl;

use Espo\Core\Utils\Acl\UserAclManagerProvider;

use Espo\Core\Acl;
use Espo\Core\AclManager;
use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingData;
use Espo\Core\InjectableFactory;
use Espo\Core\Portal\Acl as PortalAcl;
use Espo\Core\Portal\AclManager as PortalAclManager;
use Espo\Core\Select\AccessControl\DefaultFilterResolver;
use Espo\Core\Select\AccessControl\DefaultPortalFilterResolver;
use Espo\Core\Select\AccessControl\FilterResolverFactory;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use PHPUnit\Framework\TestCase;

class FilterResolverFactoryTest extends TestCase
{
    private $injectableFactory;
    private $metadata;
    private $user;
    private $aclManager;
    private $acl;
    private $factory;

    protected function setUp() : void
    {
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->user = $this->createMock(User::class);
        $this->aclManager = $this->createMock(AclManager::class);
        $this->acl = $this->createMock(Acl::class);

        $this->factory = new FilterResolverFactory(
            $this->injectableFactory,
            $this->metadata,
            $this->aclManager,
            $this->acl,
        );
    }

    public function testCreate1()
    {
        $this->prepareFactoryTest(null);
    }

    public function testCreate2()
    {
        $this->prepareFactoryTest('SomeClass');
    }

    public function testCreatePortal()
    {
        $this->user
            ->expects($this->any())
            ->method('isPortal')
            ->willReturn(true);

        $this->prepareFactoryTest(null);
    }

    protected function prepareFactoryTest(?string $className)
    {
        $entityType = 'Test';

        if (!$this->user->isPortal()) {
            $defaultClassName = DefaultFilterResolver::class;
        } else {
            $defaultClassName = DefaultPortalFilterResolver::class;
        }

        $this->metadata
            ->expects($this->once())
            ->method('get')
            ->with([
                'selectDefs', $entityType,
                !$this->user->isPortal() ?
                    'accessControlFilterResolverClassName':
                    'portalAccessControlFilterResolverClassName'
            ])
            ->willReturn($className);

        $className = $className ?? $defaultClassName;

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $this->user)
            ->bindInstance(AclManager::class, $this->aclManager)
            ->bindInstance(Acl::class, $this->acl);


        if ($this->user->isPortal()) {
            $binder->bindInstance(PortalAcl::class, $this->acl);
            $binder->bindInstance(PortalAclManager::class, $this->aclManager);
        }

        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType);

        $bindingContainer = new BindingContainer($bindingData);

        $object = $this->createMock($defaultClassName);

        $this->injectableFactory
            ->expects($this->once())
            ->method('createWithBinding')
            ->with($className, $bindingContainer)
            ->willReturn($object);

        $resultObject = $this->factory->create($entityType, $this->user);

        $this->assertEquals($object, $resultObject);
    }
}
