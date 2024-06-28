<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\ExternalAccount;

use Espo\Core\Exceptions\Error;
use Espo\Entities\Integration;
use Espo\Entities\ExternalAccount;
use Espo\ORM\EntityManager;
use Espo\Core\ExternalAccount\Clients\IClient;
use Espo\Core\ExternalAccount\OAuth2\Client as OAuth2Client;
use Espo\Core\InjectableFactory;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use RuntimeException;

class ClientManager
{
    /** @var array<string, array<string, mixed>> */
    protected $clientMap = [];

    public function __construct(
        protected EntityManager $entityManager,
        protected Metadata $metadata,
        protected Config $config,
        protected ?InjectableFactory $injectableFactory = null
    ) {}

    /**
     * @param array{
     *   accessToken: ?string,
     *   tokenType: ?string,
     *   expiresAt?: ?string,
     *   refreshToken?: ?string,
     * } $data
     * @throws Error
     */
    public function storeAccessToken(string $hash, array $data): void
    {
        if (
            empty($this->clientMap[$hash]) ||
            empty($this->clientMap[$hash]['externalAccountEntity'])
        ) {
            return;
        }

        /** @var ExternalAccount $account */
        $account = $this->clientMap[$hash]['externalAccountEntity'];

        $account->set('accessToken', $data['accessToken']);
        $account->set('tokenType', $data['tokenType']);
        $account->set('expiresAt', $data['expiresAt'] ?? null);

        if ($data['refreshToken'] ?? null) {
            $account->set('refreshToken', $data['refreshToken']);
        }

        // @todo Revise. Use refreshEntity?

        $account = $this->entityManager->getEntityById(ExternalAccount::ENTITY_TYPE, $account->getId());

        if (!$account) {
            throw new Error("External Account: Account removed.");
        }

        if (!$account->get('enabled')) {
            throw new Error("External Account: Account disabled.");
        }

        $account->set('accessToken', $data['accessToken']);
        $account->set('tokenType', $data['tokenType']);
        $account->set('expiresAt', $data['expiresAt'] ?? null);

        if ($data['refreshToken'] ?? null) {
            $account->set('refreshToken', $data['refreshToken'] ?? null);
        }

        $this->entityManager->saveEntity($account, [
            'isTokenRenewal' => true,
            SaveOption::SKIP_HOOKS => true,
        ]);
    }

    /**
     * @throws Error
     */
    public function create(string $integration, string $userId): ?object
    {
        $authMethod = $this->metadata->get("integrations.$integration.authMethod");

        if (ucfirst($authMethod) === 'OAuth2') {
            return $this->createOAuth2($integration, $userId);
        }

        $methodName = 'create' . ucfirst($authMethod);

        if (method_exists($this, $methodName)) {
            return $this->$methodName($integration, $userId);
        }

        if (!$this->injectableFactory) {
            throw new RuntimeException("No injectableFactory.");
        }

        /** @var ?Integration $integrationEntity */
        $integrationEntity = $this->entityManager->getEntityById(Integration::ENTITY_TYPE, $integration);

        /** @var ?ExternalAccount $externalAccountEntity */
        $externalAccountEntity = $this->entityManager
            ->getEntityById(ExternalAccount::ENTITY_TYPE, "{$integration}__$userId");

        if (!$externalAccountEntity) {
            throw new Error("External Account $integration not found for $userId.");
        }

        if (
            !$integrationEntity ||
            !$integrationEntity->get('enabled') ||
            !$externalAccountEntity->get('enabled')
        ) {
            return null;
        }

        /** @var class-string $className */
        $className = $this->metadata->get("integrations.$integration.clientClassName");

        $client = $this->injectableFactory->create($className);

        if (!method_exists($client, 'setup')) {
            throw new RuntimeException("$className does not have `setup` method.");
        }

        $client->setup(
            $userId,
            $integrationEntity,
            $externalAccountEntity,
            $this
        );

        $this->addToClientMap($client, $integrationEntity, $externalAccountEntity, $userId);

        return $client;
    }

    /**
     * @throws Error
     */
    protected function createOAuth2(string $integration, string $userId): ?object
    {
        /** @var Integration|null $integrationEntity */
        $integrationEntity = $this->entityManager->getEntity(Integration::ENTITY_TYPE, $integration);

        /** @var ExternalAccount|null $externalAccountEntity */
        $externalAccountEntity = $this->entityManager
            ->getEntity(ExternalAccount::ENTITY_TYPE, $integration . '__' . $userId);

        /** @var class-string $className */
        $className = $this->metadata->get("integrations.$integration.clientClassName");

        $redirectUri = $this->config->get('siteUrl') . '?entryPoint=oauthCallback';

        $redirectUriPath = $this->metadata->get(['integrations', $integration, 'params', 'redirectUriPath']);

        if ($redirectUriPath) {
            $redirectUri = rtrim($this->config->get('siteUrl'), '/') . '/' . $redirectUriPath;
        }

        if (!$externalAccountEntity) {
            throw new Error("External Account $integration not found for '$userId'.");
        }

        if (
            !$integrationEntity ||
            !$integrationEntity->get('enabled') ||
            !$externalAccountEntity->get('enabled')
        ) {
            return null;
        }

        $oauth2Client = new OAuth2Client();

        $params = [
            'endpoint' => $this->metadata->get("integrations.$integration.params.endpoint"),
            'tokenEndpoint' => $this->metadata->get("integrations.$integration.params.tokenEndpoint"),
            'clientId' => $integrationEntity->get('clientId'),
            'clientSecret' => $integrationEntity->get('clientSecret'),
            'redirectUri' => $redirectUri,
            'accessToken' => $externalAccountEntity->get('accessToken'),
            'refreshToken' => $externalAccountEntity->get('refreshToken'),
            'tokenType' => $externalAccountEntity->get('tokenType'),
            'expiresAt' => $externalAccountEntity->get('expiresAt'),
        ];

        foreach (get_object_vars($integrationEntity->getValueMap()) as $k => $v) {
            if (array_key_exists($k, $params)) {
                continue;
            }

            if ($integrationEntity->hasAttribute($k)) {
                continue;
            }

            $params[$k] = $v;
        }

        $client = new $className($oauth2Client, $params, $this);

        if ($this->injectableFactory) {
            $this->injectableFactory->createWith($className, [
                'client' => $oauth2Client,
                'params' => $params,
                'manager' => $this,
            ]);
        } else {
            // For backward compatibility.
            $client = new $className($oauth2Client, $params, $this);
        }

        $this->addToClientMap($client, $integrationEntity, $externalAccountEntity, $userId);

        return $client;
    }

    /**
     * @param object $client
     * @return void
     */
    protected function addToClientMap(
        $client,
        Integration $integrationEntity,
        ExternalAccount $externalAccountEntity,
        string $userId
    ) {
        $this->clientMap[spl_object_hash($client)] = [
            'client' => $client,
            'userId' => $userId,
            'integration' => $integrationEntity->getId(),
            'integrationEntity' => $integrationEntity,
            'externalAccountEntity' => $externalAccountEntity,
        ];
    }

    /**
     * @param object $client
     * @throws Error
     */
    protected function getClientRecord($client): Entity
    {
        $data = $this->clientMap[spl_object_hash($client)];

        if (!$data) {
            throw new Error("External Account: Client not found in hash.");
        }

        return $data['externalAccountEntity'];
    }

    /**
     * @param object $client
     * @throws Error
     */
    public function isClientLocked($client): bool
    {
        $externalAccountEntity = $this->getClientRecord($client);

        $id = $externalAccountEntity->getId();

        $account = $this->fetchAccountOnlyWithIsLocked($id);

        return $account->get('isLocked');
    }

    /**
     * @throws Error
     */
    public function lockClient(object $client): void
    {
        $externalAccountEntity = $this->getClientRecord($client);

        $id = $externalAccountEntity->getId();

        $account = $this->fetchAccountOnlyWithIsLocked($id);
        $account->set('isLocked', true);

        $this->entityManager->saveEntity($account, [
            SaveOption::SKIP_HOOKS => true,
            SaveOption::SILENT => true,
        ]);
    }

    /**
     * @throws Error
     */
    public function unlockClient(object $client): void
    {
        $externalAccountEntity = $this->getClientRecord($client);

        $id = $externalAccountEntity->getId();

        $account = $this->fetchAccountOnlyWithIsLocked($id);
        $account->set('isLocked', false);

        $this->entityManager->saveEntity($account, [
            SaveOption::SKIP_HOOKS => true,
            SaveOption::SILENT => true,
        ]);
    }

    /**
     * @param IClient $client
     * @throws Error
     */
    public function reFetchClient($client): void
    {
        $externalAccountEntity = $this->getClientRecord($client);

        $id = $externalAccountEntity->getId();

        $account = $this->entityManager->getEntityById(ExternalAccount::ENTITY_TYPE, $id);

        if (!$account) {
            throw new Error("External Account: Client $id not found in DB.");
        }

        $data = $account->getValueMap();

        $externalAccountEntity->set($data);

        $client->setParams(get_object_vars($data));
    }

    /**
     * @throws Error
     */
    private function fetchAccountOnlyWithIsLocked(string $id): ExternalAccount
    {
        $account = $this->entityManager
            ->getRDBRepository(ExternalAccount::ENTITY_TYPE)
            ->select(['id', 'isLocked'])
            ->where(['id' => $id])
            ->findOne();

        if (!$account) {
            throw new Error("External Account: Client '$id' not found in DB.");
        }

        return $account;
    }
}
