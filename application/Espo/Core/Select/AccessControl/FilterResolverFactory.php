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

namespace Espo\Core\Select\AccessControl;

use Espo\Core\AclManager;
use Espo\Core\InjectableFactory;
use Espo\Core\Acl;
use Espo\Core\Portal\Acl as PortalAcl;
use Espo\Core\Portal\AclManager as PortalAclManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingData;
use Espo\Entities\User;

class FilterResolverFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        private AclManager $aclManager,
        private Acl $acl,
    ) {}

    public function create(string $entityType, User $user): FilterResolver
    {
        $className = !$user->isPortal() ?
            $this->getClassName($entityType) :
            $this->getPortalClassName($entityType);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $user)
            ->bindInstance(AclManager::class, $this->aclManager)
            ->bindInstance(Acl::class, $this->acl);

        if ($user->isPortal()) {
            $binder->bindInstance(PortalAcl::class, $this->acl);
            $binder->bindInstance(PortalAclManager::class, $this->aclManager);
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
        return $this->metadata->get(['selectDefs', $entityType, 'accessControlFilterResolverClassName']) ??
            DefaultFilterResolver::class;
    }

    /**
     * @return class-string<FilterResolver>
     */
    private function getPortalClassName(string $entityType): string
    {
        /** @var class-string<FilterResolver> */
        return $this->metadata->get(['selectDefs', $entityType, 'portalAccessControlFilterResolverClassName']) ??
            DefaultPortalFilterResolver::class;
    }
}
