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

namespace Espo\Core;

use Espo\Entities\Portal as PortalEntity;
use Espo\Entities\User as UserEntity;

use LogicException;

/**
 * Provides information about an application, current user, portal.
 */
class ApplicationState
{
    private const KEY_USER = 'user';
    private const KEY_PORTAL = 'portal';

    public function __construct(private Container $container)
    {}

    /**
     * Whether an application is initialized as a portal.
     */
    public function isPortal(): bool
    {
        return $this->container->has(self::KEY_PORTAL);
    }

    /**
     * Get a portal ID (if an application is portal).
     */
    public function getPortalId(): string
    {
        if (!$this->isPortal()) {
            throw new LogicException("Can't get portal ID for non-portal application.");
        }

        return $this->getPortal()->getId();
    }

    /**
     * Get a portal entity (if an application is portal).
     */
    public function getPortal(): PortalEntity
    {
        if (!$this->isPortal()) {
            throw new LogicException("Can't get portal for non-portal application.");
        }

        /** @var PortalEntity */
        return $this->container->get(self::KEY_PORTAL);
    }

    /**
     * Whether any user is initialized. If not logged, it will also return TRUE, meaning the system used is used.
     */
    public function hasUser(): bool
    {
        return $this->container->has(self::KEY_USER);
    }

    /**
     * Get a current logged user. If no auth is applied, then the system user will be returned.
     */
    public function getUser(): UserEntity
    {
        if (!$this->hasUser()) {
            throw new LogicException("User is not yet available.");
        }

        /** @var UserEntity */
        return $this->container->get(self::KEY_USER);
    }

    /**
     * Get an ID of a current logged user. If no auth is applied, then the system user will be returned.
     */
    public function getUserId(): string
    {
        return $this->getUser()->getId();
    }

    /**
     * Whether a user is logged.
     */
    public function isLogged(): bool
    {
        if (!$this->container->has(self::KEY_USER)) {
            return false;
        }

        if ($this->getUser()->isSystem()) {
            return false;
        }

        return true;
    }

    /**
     * Whether logged as an admin.
     */
    public function isAdmin(): bool
    {
        if (!$this->isLogged()) {
            return false;
        }

        return $this->getUser()->isAdmin();
    }


    /**
     * Whether logged as an API user.
     */
    public function isApi(): bool
    {
        if (!$this->isLogged()) {
            return false;
        }

        return $this->getUser()->isApi();
    }
}
