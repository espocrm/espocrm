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

namespace Espo\Tools\OAuthServer\Record;

use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use Espo\Tools\OAuthServer\Record\RevokeService\Params;
use RuntimeException;

/**
 * @internal
 */
class RevokeService
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    /**
     * @internal
     */
    public function revoke(Params $params): void
    {
        $where = [];

        if ($params->userName !== null) {
            if ($params->adminOnly) {
                throw new RuntimeException("Parameter admin-only cannot be used with username.");
            }

            $user = $this->entityManager
                ->getRDBRepositoryByClass(User::class)
                ->where([
                    User::FIELD_USER_NAME => $params->userName
                ])
                ->findOne();

            if (!$user) {
                throw new RuntimeException("User not found.");
            }

            $where[RefreshToken::FIELD_USER . 'Id'] = $user->getId();
        }

        if ($params->adminOnly) {
            $where[RefreshToken::FIELD_USER . '.' . User::FIELD_TYPE] = [
                User::TYPE_ADMIN,
                User::TYPE_SUPER_ADMIN,
            ];
        }

        $this->revokeRefreshTokens($where, $params->adminOnly);
        $this->revokeAccessTokens($where, $params->adminOnly);
    }

    /**
     * @param array<string, mixed> $where
     */
    private function revokeRefreshTokens(array $where, bool $adminOnly): void
    {
        $repository = $this->entityManager->getRDBRepositoryByClass(RefreshToken::class);

        if ($adminOnly) {
            $repository->join(RefreshToken::FIELD_USER);
        }

        $tokens = $repository
            ->where([
                RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE,
            ])
            ->where($where)
            ->sth()
            ->find();

        foreach ($tokens as $token) {
            $token->setRevoked();

            $this->entityManager->saveEntity($token);
        }
    }

    /**
     * @param array<string, mixed> $where
     */
    private function revokeAccessTokens(array $where, bool $adminOnly): void
    {
        $repository = $this->entityManager->getRDBRepositoryByClass(AccessToken::class);

        if ($adminOnly) {
            $repository->join(AccessToken::FIELD_USER);
        }

        $tokens = $repository
            ->where([
                AccessToken::FIELD_STATUS => AccessToken::STATUS_ACTIVE,
            ])
            ->where($where)
            ->sth()
            ->find();

        foreach ($tokens as $token) {
            $token->setRevoked();

            $this->entityManager->saveEntity($token);
        }
    }
}
