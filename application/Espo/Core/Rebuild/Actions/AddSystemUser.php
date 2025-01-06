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

namespace Espo\Core\Rebuild\Actions;

use Espo\Core\Rebuild\RebuildAction;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\SystemUser;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

class AddSystemUser implements RebuildAction
{
    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private SystemUser $systemUser
    ) {}

    public function process(): void
    {
        $repository = $this->entityManager->getRDBRepositoryByClass(User::class);

        $user = $repository
            ->where(['userName' => SystemUser::NAME])
            ->findOne();

        if ($user) {
            if ($user->getId() === $this->systemUser->getId()) {
                return;
            }

            $this->entityManager
                ->getQueryExecutor()
                ->execute(
                    $this->entityManager
                        ->getQueryBuilder()
                        ->delete()
                        ->from(User::ENTITY_TYPE)
                        ->where([Attribute::ID => $user->getId()])
                        ->build()
                );
        }

        /** @var array<string, mixed> $attributes */
        $attributes = $this->config->get('systemUserAttributes');

        $user = $repository->getNew();

        $user
            ->set(Attribute::ID, $this->systemUser->getId())
            ->setUserName(SystemUser::NAME)
            ->setType(User::TYPE_SYSTEM);

        $user->setMultiple($attributes);

        $repository->save($user);
    }
}
