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

namespace Espo\Services;

use Espo\ORM\Entity;
use Espo\Core\ExternalAccount\Clients\OAuth2Abstract;
use Espo\Core\ExternalAccount\ClientManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\ReadParams;
use Espo\Core\Di;
use Espo\Entities\ExternalAccount as ExternalAccountEntity;
use Espo\Entities\Integration as IntegrationEntity;
use Exception;

/**
 * @extends Record<ExternalAccountEntity>
 */
class ExternalAccount extends Record implements Di\HookManagerAware
{
    use Di\HookManagerSetter;

    /**
     * @throws NotFound
     * @throws Error
     */
    private function getClient(string $integration, string $id): ?object
    {
        /** @var IntegrationEntity|null $integrationEntity */
        $integrationEntity = $this->entityManager->getEntityById(IntegrationEntity::ENTITY_TYPE, $integration);

        if (!$integrationEntity) {
            throw new NotFound();
        }

        if (!$integrationEntity->get('enabled')) {
            throw new Error("$integration is disabled.");
        }

        return $this->injectableFactory
            ->create(ClientManager::class)
            ->create($integration, $id);
    }

    private function getExternalAccountEntity(string $integration, string $userId): ?ExternalAccountEntity
    {
        $id = $integration . '__' . $userId;

        /** @var ?ExternalAccountEntity */
        return $this->entityManager->getEntityById(ExternalAccountEntity::ENTITY_TYPE, $id);
    }

    /**
     * @return bool
     * @todo In v9.1. Move to Tools. Fix all usages.
     */
    public function ping(string $integration, string $userId)
    {
        try {
            $client = $this->getClient($integration, $userId);

            if ($client && method_exists($client, 'ping')) {
                /** @var bool */
                return $client->ping();
            }
        } catch (Exception) {}

        return false;
    }

    /**
     * @return bool
     * @throws NotFound
     * @throws Error
     * @throws Exception
     * @todo In v9.1. Return void. Move to Tools. Fix all usages.
     */
    public function authorizationCode(string $integration, string $userId, string $code)
    {
        $entity = $this->getExternalAccountEntity($integration, $userId);

        if (!$entity) {
            throw new NotFound();
        }

        $entity->set('enabled', true);

        $this->entityManager->saveEntity($entity);

        $client = $this->getClient($integration, $userId);

        if (!$client instanceof OAuth2Abstract) {
            throw new Error("Could not load client for $integration.");
        }


        $result = $client->getAccessTokenFromAuthorizationCode($code);

        if (empty($result) || empty($result['accessToken'])) {
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

        return true;
    }

    public function read(string $id, ReadParams $params): Entity
    {
        [, $userId] = explode('__', $id);

        if ($this->user->getId() !== $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $entity = $this->entityManager->getEntityById(ExternalAccountEntity::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFoundSilent();
        }

        [$integration,] = explode('__', $entity->getId());

        $secretAttributeList =
            $this->metadata->get(['integrations', $integration, 'externalAccountSecretAttributeList']) ?? [];

        foreach ($secretAttributeList as $a) {
            $entity->clear($a);
        }

        return $entity;
    }
}
