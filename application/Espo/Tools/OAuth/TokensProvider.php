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

use Espo\Core\Field\DateTime;
use Espo\Core\Utils\Crypt;
use Espo\Entities\OAuthAccount;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder;
use Espo\Tools\OAuth\Exceptions\NoToken;
use Espo\Tools\OAuth\Exceptions\ProviderNotAvailable;
use Espo\Tools\OAuth\Exceptions\AccountNotFound;
use Espo\Tools\OAuth\Exceptions\TokenObtainingFailure;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use LogicException;

class TokensProvider
{
    private const EXPIRATION_LEAD_TIME = 60;

    public function __construct(
        private EntityManager $entityManager,
        private GenericProviderFactory $genericProviderFactory,
        private TokenSetter $tokenSetter,
        private Crypt $crypt,
    ) {}

    /**
     * @throws AccountNotFound
     * @throws ProviderNotAvailable
     * @throws NoToken
     * @throws TokenObtainingFailure
     */
    public function get(string $id): Tokens
    {
        $account = $this->fetch($id);

        if (
            $account->getRefreshToken() &&
            $account->getExpiresAt() &&
            $account->getExpiresAt()->isGreaterThan(
                DateTime::createNow()->addSeconds(- self::EXPIRATION_LEAD_TIME)
            )
        ) {
            $this->refresh($account);
        }

        if (!$account->getAccessToken()) {
            throw new NoToken();
        }

        $accessToken = $this->crypt->decrypt($account->getAccessToken());

        $refreshToken = $account->getRefreshToken() ?
            $this->crypt->decrypt($account->getRefreshToken()) :
            null;

        return new Tokens(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresAt: $account->getExpiresAt(),
        );
    }

    /**
     * @throws ProviderNotAvailable
     * @throws AccountNotFound
     * @throws NoToken
     */
    private function fetch(string $id): OAuthAccount
    {
        // Ensuring the token is not being refreshed.
        $this->entityManager->getTransactionManager()->start();

        $account = $this->entityManager
            ->getRDBRepositoryByClass(OAuthAccount::class)
            ->clone(
                SelectBuilder::create()
                    ->from(OAuthAccount::ENTITY_TYPE)
                    ->forShare()
                    ->build()
            )
            ->where([Attribute::ID => $id])
            ->findOne();

        $this->entityManager->getTransactionManager()->commit();

        if (!$account) {
            throw new AccountNotFound();
        }

        if (!$account->getProvider()->isActive()) {
            throw new ProviderNotAvailable();
        }

        if (!$account->getAccessToken()) {
            throw new NoToken();
        }

        return $account;
    }

    /**
     * @throws TokenObtainingFailure
     * @noinspection PhpDocRedundantThrowsInspection
     */
    private function refresh(OAuthAccount $account): void
    {
        $this->entityManager
            ->getTransactionManager()
            ->run(function () use ($account) {
                $this->refreshInTransaction($account);
            });
    }

    /**
     * @throws TokenObtainingFailure
     */
    private function refreshInTransaction(OAuthAccount $account): void
    {
        $refreshToken = $account->getRefreshToken();

        if (!$refreshToken) {
            throw new LogicException();
        }

        $refreshToken = $this->crypt->decrypt($refreshToken);

        $this->entityManager
            ->getRDBRepositoryByClass(OAuthAccount::class)
            ->forUpdate()
            ->sth()
            ->where([Attribute::ID => $account->getId()])
            ->find();

        $genericProvider = $this->genericProviderFactory->create($account->getProvider());

        try {
            $tokens = $genericProvider->getAccessToken('refresh_token', ['refresh_token' => $refreshToken]);
        } catch (GuzzleException|IdentityProviderException $e) {
            throw new TokenObtainingFailure($e->getMessage(), $e->getCode(), $e);
        }

        $this->tokenSetter->set($account, $tokens);

        $this->entityManager->saveEntity($account);
    }
}
