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

namespace Espo\Tools\OAuthServer\ConnectedApp;

use Espo\Core\Exceptions\NotFound;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Tools\OAuthServer\Entities\Client;
use Espo\Tools\OAuthServer\Repository\AccessTokenRepository;
use Espo\Tools\OAuthServer\Repository\ClientRepository;
use Espo\Tools\OAuthServer\Repository\RefreshTokenRepository;
use RuntimeException;

class ConnectedAppService
{
    private const int LIMIT = 50;

    public function __construct(
        private ClientRepository $clientRepository,
        private RefreshTokenRepository $refreshTokenRepository,
        private AccessTokenRepository $accessTokenRepository,
        private EntityManager $entityManager,
    ) {}

    /**
     * @return AppData[]
     */
    public function getList(User $user): array
    {
        $clients = $this->clientRepository->findConnectedForUser($user->getId(), self::LIMIT);

        return array_map(function (Client $it) {
            $id = $it->getIdentifier() ?? throw new RuntimeException();

            return new AppData(
                id: $id,
                name: $it->getName() ?? $id,
            );
        }, iterator_to_array($clients));
    }

    /**
     * @throws NotFound
     */
    public function disconnect(User $user, string $clientId): void
    {
        $client = $this->clientRepository->getByIdentifier($clientId);

        if (!$client) {
            throw new NotFound("Client not found.");
        }

        $refreshTokens = $this->refreshTokenRepository->findActiveForClientIdAndUser($client->getId(), $user->getId());

        foreach ($refreshTokens as $refreshToken) {
            $refreshToken->setRevoked();

            $this->entityManager->saveEntity($refreshToken);
        }

        $accessTokens = $this->accessTokenRepository->findActiveForClientIdAndUser($client->getId(), $user->getId());

        foreach ($accessTokens as $accessToken) {
            $accessToken->setRevoked();

            $this->entityManager->saveEntity($accessToken);
        }
    }
}
