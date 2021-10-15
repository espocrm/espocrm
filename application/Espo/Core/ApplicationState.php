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

use Espo\Core\Exceptions\Error;
use Espo\Entities\Portal as PortalEntity;
use Espo\Entities\User as UserEntity;

/**
 * Provides information about an application, current user, portal.
 */
class ApplicationState
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Whether an application is initialized as a portal.
     */
    public function isPortal(): bool
    {
        return $this->container->has('portal');
    }

    /**
     * Get a portal ID (if an application is portal).
     */
    public function getPortalId(): string
    {
        if (!$this->isPortal()) {
            throw new Error("Can't get portal ID for non-portal application.");
        }

        return $this->getPortal()->id;
    }

    /**
     * Get a portal entity (if an application is portal).
     */
    public function getPortal(): PortalEntity
    {
        if (!$this->isPortal()) {
            throw new Error("Can't get portal for non-portal application.");
        }

        return $this->container->get('portal');
    }

    /**
     * Whether any user is initialized. If not logged, it will also return TRUE, meaning the system used is used.
     */
    public function hasUser(): bool
    {
        return $this->container->has('user');
    }

    /**
     * Get a current logged user. If no auth is applied, then the system user will be returned.
     */
    public function getUser(): UserEntity
    {
        if (!$this->hasUser()) {
            throw new Error("User is not yet available.");
        }

        return $this->container->get('user');
    }

    /**
     * Get an ID of a current logged user. If no auth is applied, then the system user will be returned.
     */
    public function getUserId(): string
    {
        return $this->getUser()->id;
    }

    /**
     * Whether a user is logged.
     */
    public function isLogged(): bool
    {
        if (!$this->container->has('user')) {
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
