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

use Espo\Core\Utils\SystemUser;
use Espo\Entities\User;
use Espo\Core\ORM\EntityManagerProxy;

use Espo\ORM\Name\Attribute;
use RuntimeException;

/**
 * Setting a current user for the application.
 */
class ApplicationUser
{
    /** @deprecated As of v7.4. Different IDs may be used. Use Espo\Core\Utils\SystemUser. */
    public const SYSTEM_USER_ID = 'system';

    public function __construct(
        private Container $container,
        private EntityManagerProxy $entityManagerProxy
    ) {}

    /**
     * Set up the system user as a current user. The system user is used when no user is logged in.
     */
    public function setupSystemUser(): void
    {
        $user = $this->entityManagerProxy
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select([
                Attribute::ID,
                'name',
                'userName',
                'type',
                'isActive',
                'firstName',
                'lastName',
                Attribute::DELETED,
            ])
            ->where(['userName' => SystemUser::NAME])
            ->findOne();

        if (!$user) {
            throw new RuntimeException("System user is not found.");
        }

        $user->set('ipAddress', $_SERVER['REMOTE_ADDR'] ?? null);
        $user->set('type', User::TYPE_SYSTEM);

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
