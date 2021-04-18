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

namespace Espo\Core;

use Espo\Core\Exceptions\{
    Error,
};

use Espo\Entities\{
    User,
};

use Espo\Core\{
    ORM\EntityManagerProxy,
};

/**
 * Setting a current user for the application.
 */
class ApplicationUser
{
    private $container;

    private $entityManagerProxy;

    public function __construct(Container $container, EntityManagerProxy $entityManagerProxy)
    {
        $this->container = $container;
        $this->entityManagerProxy = $entityManagerProxy;
    }

    /**
     * Setup the system user as a current user. The system user is used when no user is logged in.
     */
    public function setupSystemUser(): void
    {
        $user = $this->entityManagerProxy->getEntity('User', 'system');

        if (!$user) {
            throw new Error("System user is not found.");
        }

        $user->set('ipAddress', $_SERVER['REMOTE_ADDR'] ?? null);
        $user->set('type', 'system');

        $this->container->set('user', $user);
    }

    /**
     * Set a current user.
     */
    public function setUser(User $user): void
    {
        $this->container->set('user', $user);
    }
}
