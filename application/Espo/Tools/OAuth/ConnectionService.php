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

namespace Espo\Tools\OAuth;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Field\DateTime;
use Espo\Entities\OAuthAccount;
use Espo\Entities\OAuthProvider;
use Espo\ORM\EntityManager;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;

class ConnectionService
{
    public function __construct(
        private EntityManager $entityManager,
        private ConfigDataProvider $configDataProvider,
    ) {}

    /**
     * @throws Forbidden
     * @throws Error
     */
    public function connect(OAuthAccount $account, string $code): void
    {
        $provider = $account->getProvider();

        if (!$provider->isActive()) {
            throw new Forbidden("Provider is not active.");
        }

        $genericProvider = new GenericProvider([
            'clientId' => $provider->getClientId(),
            'clientSecret' => $provider->getClientSecret(),
            'redirectUri'  => $this->configDataProvider->getRedirectUri(),
            'urlAccessToken' => $provider->getTokenEndpoint(),
        ]);

        try {
            $tokens = $genericProvider->getAccessToken('authorization_code', ['code' => $code]);
        } catch (GuzzleException $e) {
            throw new Error("Token request error.", 500, $e);
        } catch (IdentityProviderException $e) {
            throw new Error("Token request response error.", 500, $e);
        }

        $expires = $tokens->getExpires() !== null ?
            DateTime::fromTimestamp($tokens->getExpires()) :
            null;

        $account->setAccessToken($tokens->getToken());
        $account->setRefreshToken($tokens->getToken());
        $account->setExpiresAt($expires);

        $this->entityManager->saveEntity($account);
    }

    public function disconnect(OAuthAccount $account): void
    {
        $account->setAccessToken(null);
        $account->setRefreshToken(null);
        $account->setExpiresAt(null);

        $this->entityManager->saveEntity($account);
    }

    /**
     * @throws NotFound
     */
    private function fetch(string $id): OAuthProvider
    {
        $provider = $this->entityManager->getRDBRepositoryByClass(OAuthProvider::class)->getById($id);

        if (!$provider) {
            throw new NotFound("Provider not found.");
        }

        return $provider;
    }
}
