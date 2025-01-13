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

namespace Espo\Core\ExternalAccount;

use Espo\Core\Exceptions\Error;
use Espo\Core\Field\DateTime;
use Espo\Core\Utils\Language;
use Espo\Entities\Integration;
use Espo\Entities\ExternalAccount;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Core\ExternalAccount\Clients\IClient;
use Espo\Core\ExternalAccount\OAuth2\Client as OAuth2Client;
use Espo\Core\InjectableFactory;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Name\Attribute;
use RuntimeException;

class ClientManager
{
    private const REFRESH_TOKEN_ATTEMPTS_LIMIT = 20;
    private const REFRESH_TOKEN_ATTEMPTS_PERIOD = '1 day';

    /** @var array<string, (array<string, mixed> & array{externalAccountEntity: ExternalAccount})> */
    protected $clientMap = [];

    public function __construct(
        protected EntityManager $entityManager,
        protected Metadata $metadata,
        protected Config $config,
        protected ?InjectableFactory $injectableFactory = null,
        private ?Language $language = null
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
    public function storeAccessToken(object $client, array $data): void
    {
        try {
            $account = $this->getClientRecord($client);
        } catch (Error) {
            // @todo Revise.
            return;
        }

        $account->setAccessToken($data['accessToken']);
        $account->setTokenType($data['tokenType']);
        $account->setExpiresAt($data['expiresAt'] ?? null);
        $account->setRefreshTokenAttempts(null);

        if ($data['refreshToken'] ?? null) {
            $account->setRefreshToken($data['refreshToken']);
        }

        /** @var ?ExternalAccount $account */
        $account = $this->entityManager->getEntityById(ExternalAccount::ENTITY_TYPE, $account->getId());

        if (!$account) {
            throw new Error("External Account: Account removed.");
        }

        if (!$account->isEnabled()) {
            throw new Error("External Account: Account disabled.");
        }

        $account->setAccessToken($data['accessToken']);
        $account->setTokenType($data['tokenType']);
        $account->setExpiresAt($data['expiresAt'] ?? null);
        $account->setRefreshTokenAttempts(null);

        if ($data['refreshToken'] ?? null) {
            $account->setRefreshToken($data['refreshToken'] ?? null);
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

        /** @var ?ExternalAccount $account */
        $account = $this->entityManager->getEntityById(ExternalAccount::ENTITY_TYPE, "{$integration}__$userId");

        if (!$account) {
            throw new Error("External Account $integration not found for $userId.");
        }

        if (!$integrationEntity || !$integrationEntity->isEnabled() || !$account->isEnabled()) {
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
            $account,
            $this
        );

        $this->addToClientMap($client, $integrationEntity, $account, $userId);

        return $client;
    }

    /**
     * @throws Error
     */
    protected function createOAuth2(string $integration, string $userId): ?object
    {
        /** @var ?Integration $integrationEntity */
        $integrationEntity = $this->entityManager->getEntityById(Integration::ENTITY_TYPE, $integration);

        /** @var ?ExternalAccount $account */
        $account = $this->entityManager->getEntityById(ExternalAccount::ENTITY_TYPE, "{$integration}__$userId");

        /** @var class-string $className */
        $className = $this->metadata->get("integrations.$integration.clientClassName");
        $redirectUri = $this->config->get('siteUrl') . '?entryPoint=oauthCallback';
        $redirectUriPath = $this->metadata->get(['integrations', $integration, 'params', 'redirectUriPath']);

        if ($redirectUriPath) {
            $redirectUri = rtrim($this->config->get('siteUrl'), '/') . '/' . $redirectUriPath;
        }

        if (!$account) {
            throw new Error("External Account $integration not found for '$userId'.");
        }

        if (
            !$integrationEntity ||
            !$integrationEntity->isEnabled() ||
            !$account->isEnabled()
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
            'accessToken' => $account->getAccessToken(),
            'refreshToken' => $account->getRefreshToken(),
            'tokenType' => $account->getTokenType(),
            'expiresAt' => $account->getExpiresAt() ? $account->getExpiresAt()->toString() : null,
        ];

        $authType = $this->metadata->get("integrations.$integration.authType");
        $tokenType = $this->metadata->get("integrations.$integration.tokenType");

        if ($authType === 'Uri') {
            $oauth2Client->setAuthType(OAuth2Client::AUTH_TYPE_URI);
        } else if ($authType === 'Basic') {
            $oauth2Client->setAuthType(OAuth2Client::AUTH_TYPE_AUTHORIZATION_BASIC);
        } else if ($authType === 'Form') {
            $oauth2Client->setAuthType(OAuth2Client::AUTH_TYPE_FORM);
        }

        if ($tokenType === 'Bearer') {
            $oauth2Client->setTokenType(OAuth2Client::TOKEN_TYPE_BEARER);
        } else if ($authType === 'Uri') {
            $oauth2Client->setTokenType(OAuth2Client::TOKEN_TYPE_URI);
        } else if ($authType === 'OAuth') {
            $oauth2Client->setTokenType(OAuth2Client::TOKEN_TYPE_OAUTH);
        }

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

        $this->addToClientMap($client, $integrationEntity, $account, $userId);

        return $client;
    }

    /**
     * @param object $client
     * @return void
     */
    protected function addToClientMap(
        $client,
        Integration $integration,
        ExternalAccount $account,
        string $userId
    ) {
        $this->clientMap[spl_object_hash($client)] = [
            'client' => $client,
            'userId' => $userId,
            'integration' => $integration->getId(),
            'integrationEntity' => $integration,
            'externalAccountEntity' => $account,
        ];
    }

    /**
     * @param object $client
     * @throws Error
     */
    protected function getClientRecord($client): ExternalAccount
    {
        $data = $this->clientMap[spl_object_hash($client)] ?? null;

        if (!$data) {
            throw new Error("External Account: Client not found in hash.");
        }

        if (!isset($data['externalAccountEntity'])) {
            throw new Error("External Account: Account not found in hash.");
        }

        return $data['externalAccountEntity'];
    }

    /**
     * @param object $client
     * @throws Error
     */
    public function isClientLocked($client): bool
    {
        $accountSet = $this->getClientRecord($client);

        $account = $this->fetchAccountOnlyWithIsLocked($accountSet->getId());

        return $account->isLocked();
    }

    /**
     * @throws Error
     */
    public function lockClient(object $client): void
    {
        $accountSet = $this->getClientRecord($client);

        $account = $this->fetchAccountOnlyWithIsLocked($accountSet->getId());
        $account->setIsLocked(true);

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
        $accountSet = $this->getClientRecord($client);

        $accountSet = $this->fetchAccountOnlyWithIsLocked($accountSet->getId());
        $accountSet->setIsLocked(false);

        $this->entityManager->saveEntity($accountSet, [
            SaveOption::SKIP_HOOKS => true,
            SaveOption::SILENT => true,
        ]);
    }

    /**
     * @throws Error
     */
    public function controlRefreshTokenAttempts(object $client): void
    {
        $accountSet = $this->getClientRecord($client);

        $account = $this->entityManager
            ->getRDBRepositoryByClass(ExternalAccount::class)
            ->getById($accountSet->getId());

        if (!$account) {
            return;
        }

        $attempts = $account->getRefreshTokenAttempts();

        $account->setRefreshTokenAttempts($attempts + 1);

        if (
            $attempts >= self::REFRESH_TOKEN_ATTEMPTS_LIMIT &&
            $account->getExpiresAt() &&
            $account->getExpiresAt()
                ->modify('+' . self::REFRESH_TOKEN_ATTEMPTS_PERIOD)
                ->isLessThan(DateTime::createNow())
        ) {
            $account->setIsEnabled(false);
            $account->unsetData();
        }

        $this->entityManager->saveEntity($account, [
            SaveOption::SKIP_HOOKS => true,
            SaveOption::SILENT => true,
        ]);

        if (!$account->isEnabled()) {
            $this->createDisableNotification($account);
        }
    }

    /**
     * @param IClient $client
     * @throws Error
     */
    public function reFetchClient($client): void
    {
        $accountSet = $this->getClientRecord($client);

        $id = $accountSet->getId();

        $account = $this->entityManager->getEntityById(ExternalAccount::ENTITY_TYPE, $id);

        if (!$account) {
            throw new Error("External Account: Client $id not found in DB.");
        }

        $data = $account->getValueMap();

        $accountSet->set($data);

        $client->setParams(get_object_vars($data));
    }

    /**
     * @throws Error
     */
    private function fetchAccountOnlyWithIsLocked(string $id): ExternalAccount
    {
        $account = $this->entityManager
            ->getRDBRepository(ExternalAccount::ENTITY_TYPE)
            ->select([Attribute::ID, 'isLocked'])
            ->where([Attribute::ID => $id])
            ->findOne();

        if (!$account) {
            throw new Error("External Account: Client '$id' not found in DB.");
        }

        return $account;
    }

    private function createDisableNotification(ExternalAccount $account): void
    {
        if (!str_contains($account->getId(), '__'))    {
            return;
        }

        [$integration, $userId] = explode('__', $account->getId());

        if (!$this->entityManager->getEntityById(User::ENTITY_TYPE, $userId)) {
            return;
        }

        if (!$this->language) {
            return;
        }

        $message = $this->language->translateLabel('externalAccountNoConnectDisabled', 'messages', 'ExternalAccount');
        $message = str_replace('{integration}', $integration, $message);

        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $notification
            ->setType(Notification::TYPE_MESSAGE)
            ->setMessage($message)
            ->setUserId($userId);

        $this->entityManager->saveEntity($notification);
    }
}
