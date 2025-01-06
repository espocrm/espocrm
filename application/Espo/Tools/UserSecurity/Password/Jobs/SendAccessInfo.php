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

namespace Espo\Tools\UserSecurity\Password\Jobs;

use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Entities\User;
use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\Exceptions\Error;
use Espo\ORM\EntityManager;
use Espo\Tools\UserSecurity\Password\Service as PasswordService;

class SendAccessInfo implements Job
{
    private EntityManager $entityManager;
    private PasswordService $passwordService;

    public function __construct(EntityManager $entityManager, PasswordService $passwordService)
    {
        $this->entityManager = $entityManager;
        $this->passwordService = $passwordService;
    }

    /**
     * @throws SendingError
     * @throws Error
     */
    public function run(Data $data): void
    {
        $userId = $data->getTargetId();

        if (!$userId) {
            throw new Error();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new Error("User '{$userId}' not found.");
        }

        $this->passwordService->sendAccessInfoForNewUser($user);
    }
}
