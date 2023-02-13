<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Rebuild\Actions;

use Espo\Core\ApplicationUser;
use Espo\Core\Rebuild\RebuildAction;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

class AddSystemUser implements RebuildAction
{
    public function __construct(
        private EntityManager $entityManager,
        private Config $config
    ) {}

    public function process(): void
    {
        $repository = $this->entityManager->getRDBRepositoryByClass(User::class);

        $user = $repository
            ->where(['userName' => ApplicationUser::SYSTEM_USER_NAME])
            ->findOne();

        if ($user) {
            return;
        }

        // @todo If a user with the 'system' ID already exists, delete it from DB.

        /** @var array<string, mixed> $attributes */
        $attributes = $this->config->get('systemUserAttributes');

        $user = $repository->getNew();

        $user->set('id', ApplicationUser::SYSTEM_USER_ID);
        $user->set('userName', ApplicationUser::SYSTEM_USER_NAME);
        $user->set('type', User::TYPE_SYSTEM);
        $user->set($attributes);

        $repository->save($user);
    }
}
