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

namespace Espo\Tools\User;

use Espo\Entities\User;
use Espo\Entities\User as UserEntity;
use Espo\ORM\EntityManager;

/**
 * @internal
 */
class UserUtil
{
    /** @var string[] */
    private $allowedUserTypeList = [
        UserEntity::TYPE_REGULAR,
        UserEntity::TYPE_ADMIN,
        UserEntity::TYPE_PORTAL,
        UserEntity::TYPE_API,
    ];

    public function __construct(
        private EntityManager $entityManager
    ) {}

    public function getInternalCount(): int
    {
        return $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->where([
                'isActive' => true,
                'type' => [
                    User::TYPE_ADMIN,
                    User::TYPE_REGULAR,
                ],
            ])
            ->count();
    }

    public function getPortalCount(): int
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where([
                'isActive' => true,
                'type' => User::TYPE_PORTAL,
            ])
            ->count();
    }

    public function checkExists(User $user): bool
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where(['userName' => $user->getUserName()])
            ->findOne() !== null;
    }

    /**
     * @return string[]
     */
    public function getAllowedUserTypeList(): array
    {
        return $this->allowedUserTypeList;
    }
}
