<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Select\AccessControl;

use Espo\Core\InjectableFactory;
use Espo\Core\Acl;
use Espo\Core\Portal\Acl as PortalAcl;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Acl\UserAclManagerProvider;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingData;

use Espo\Entities\User;

class FilterResolverFactory
{
    private InjectableFactory $injectableFactory;

    private Metadata $metadata;

    private UserAclManagerProvider $userAclManagerProvider;

    public function __construct(
        InjectableFactory $injectableFactory,
        Metadata $metadata,
        UserAclManagerProvider $userAclManagerProvider
    ) {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
        $this->userAclManagerProvider = $userAclManagerProvider;
    }

    public function create(string $entityType, User $user): FilterResolver
    {
        $className = !$user->isPortal() ?
            $this->getClassName($entityType) :
            $this->getPortalClassName($entityType);

        $acl = $this->userAclManagerProvider
            ->get($user)
            ->createUserAcl($user);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $user)
            ->bindInstance(Acl::class, $acl);

        if ($user->isPortal()) {
            $binder->bindInstance(PortalAcl::class, $acl);
        }

        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType);

        $bindingContainer = new BindingContainer($bindingData);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    /**
     * @return class-string<FilterResolver>
     */
    private function getClassName(string $entityType): string
    {
        /** @var class-string<FilterResolver> */
        return $this->metadata->get([
            'selectDefs', $entityType, 'accessControlFilterResolverClassName'
        ]) ?? DefaultFilterResolver::class;
    }

    /**
     * @return class-string<FilterResolver>
     */
    private function getPortalClassName(string $entityType): string
    {
        /** @var class-string<FilterResolver> */
        return $this->metadata->get([
            'selectDefs', $entityType, 'portalAccessControlFilterResolverClassName'
        ]) ?? DefaultPortalFilterResolver::class;
    }
}
