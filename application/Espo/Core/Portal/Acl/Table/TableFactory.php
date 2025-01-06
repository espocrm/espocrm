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

namespace Espo\Core\Portal\Acl\Table;

use Espo\Entities\Portal;
use Espo\Entities\User;

use Espo\Core\Acl\Table\CacheKeyProvider;
use Espo\Core\Acl\Table\RoleListProvider;
use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingData;
use Espo\Core\InjectableFactory;
use Espo\Core\Portal\Acl\Table;
use Espo\Core\Portal\Acl\Table\CacheKeyProvider as PortalCacheKeyProvider;
use Espo\Core\Portal\Acl\Table\RoleListProvider as PortalRoleListProvider;

class TableFactory
{
    public function __construct(private InjectableFactory $injectableFactory)
    {}

    /**
     * Create a table.
     */
    public function create(User $user, Portal $portal): Table
    {
        $bindingContainer = $this->createBindingContainer($user, $portal);

        return $this->injectableFactory->createWithBinding(Table::class, $bindingContainer);
    }

    private function createBindingContainer(User $user, Portal $portal): BindingContainer
    {
        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $user)
            ->bindInstance(Portal::class, $portal)
            ->bindImplementation(RoleListProvider::class, PortalRoleListProvider::class)
            ->bindImplementation(CacheKeyProvider::class, PortalCacheKeyProvider::class);

        return new BindingContainer($bindingData);
    }
}
