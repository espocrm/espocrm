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

namespace Espo\Tools\UserSecurity;

use Espo\Core\Authentication\Logins\Hmac;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Util;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

class ApiService
{
    private ServiceContainer $serviceContainer;
    private User $user;
    private EntityManager $entityManager;

    public function __construct(
        ServiceContainer $serviceContainer,
        User $user,
        EntityManager $entityManager
    ) {
        $this->serviceContainer = $serviceContainer;
        $this->user = $user;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function generateNewApiKey(string $id): User
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $service = $this->serviceContainer->get(User::ENTITY_TYPE);

        /** @var ?User $entity */
        $entity = $service->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$entity->isApi()) {
            throw new Forbidden();
        }

        $apiKey = Util::generateApiKey();

        $entity->set('apiKey', $apiKey);

        if ($entity->getAuthMethod() === Hmac::NAME) {
            $secretKey = Util::generateSecretKey();

            $entity->set('secretKey', $secretKey);
        }

        $this->entityManager->saveEntity($entity);

        $service->prepareEntityForOutput($entity);

        return $entity;
    }
}
