<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\ExternalAccount;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\ExternalAccount\ClientManager;
use Espo\Core\ExternalAccount\Clients\OAuth2Abstract;
use Espo\Core\HookManager;
use Espo\Entities\ExternalAccount;
use Espo\Entities\Integration;
use Espo\ORM\EntityManager;
use Exception;
use RuntimeException;

/**
 * @since 10.0.0
 */
class OAuthService
{
    public function __construct(
        private EntityManager $entityManager,
        private HookManager $hookManager,
        private ClientManager $clientManager,
    ) {}

    /**
     * @throws Error
     * @throws NotFound
     */
    public function authorizationCode(string $integration, string $userId, string $code): void
    {
        $entity = $this->getExternalAccountEntity($integration, $userId);

        if (!$entity) {
            throw new NotFound();
        }

        $entity->setIsEnabled(true);

        $this->entityManager->saveEntity($entity);

        $client = $this->getClient($integration, $userId);

        if (!$client instanceof OAuth2Abstract) {
            throw new RuntimeException("Could not load client for $integration.");
        }

        $result = $client->getAccessTokenFromAuthorizationCode($code);

        if (!$result || empty($result['accessToken'])) {
            throw new Error("Could not get access token for $integration.");
        }

        $entity->clear('accessToken');
        $entity->clear('refreshToken');
        $entity->clear('tokenType');
        $entity->clear('expiresAt');

        foreach ($result as $name => $value) {
            $entity->set($name, $value);
        }

        $this->entityManager->saveEntity($entity);

        $this->hookManager->process('ExternalAccount', 'afterConnect', $entity, [
            'integration' => $integration,
            'userId' => $userId,
            'code' => $code,
        ]);
    }

    public function ping(string $integration, string $userId): bool
    {
        try {
            $client = $this->getClient($integration, $userId);

            if (!$client) {
                return false;
            }

            if (!$client instanceof OAuth2Abstract) {
                throw new Exception("Could not load client for $integration.");
            }

            return $client->ping();
        } catch (Exception) {}

        return false;
    }

    /**
     * @throws NotFound
     * @throws Error
     */
    private function getClient(string $integration, string $id): ?object
    {
        $entity = $this->entityManager->getRDBRepositoryByClass(Integration::class)->getById($integration);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$entity->isEnabled()) {
            throw new Error("$integration is disabled.");
        }

        return $this->clientManager->create($integration, $id);
    }

    private function getExternalAccountEntity(string $integration, string $userId): ?ExternalAccount
    {
        $id = $integration . '__' . $userId;

        return $this->entityManager->getRDBRepositoryByClass(ExternalAccount::class)->getById($id);
    }
}
