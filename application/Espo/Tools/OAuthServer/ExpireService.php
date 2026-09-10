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

namespace Espo\Tools\OAuthServer;

use Espo\Core\Utils\DateTime;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\UpdateBuilder;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\AuthorizationCode;
use Espo\Tools\OAuthServer\Entities\ClientSecret;
use Espo\Tools\OAuthServer\Entities\RefreshToken;

/**
 * Sets status Expired. Note that even Active status with the expiration date in past
 * renders the token/secret inactive. Expired status serves an information purpose.
 */
class ExpireService
{
    public function __construct(
        private DateTime $dateTime,
        private EntityManager $entityManager,
    ) {}

    public function expireClientSecrets(): void
    {
        $secrets = $this->entityManager
            ->getRDBRepositoryByClass(ClientSecret::class)
            ->where([
                ClientSecret::FIELD_STATUS => ClientSecret::STATUS_ACTIVE,
                ClientSecret::FIELD_EXPIRATION_DATE . '<=' => $this->dateTime->getToday()->toString(),
            ])
            ->find();

        foreach ($secrets as $secret) {
            $secret->setExpired();

            $this->entityManager->saveEntity($secret);
        }
    }

    public function expireAccessTokens(): void
    {
        $query = UpdateBuilder::create()
            ->in(AccessToken::ENTITY_TYPE)
            ->set([
                AccessToken::FIELD_STATUS => AccessToken::STATUS_EXPIRED,
            ])
            ->where([
                AccessToken::FIELD_STATUS => AccessToken::STATUS_ACTIVE,
                AccessToken::FIELD_EXPIRES_AT . '<=' => $this->dateTime->getNow()->toString(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    public function expireRefreshTokens(): void
    {
        $query = UpdateBuilder::create()
            ->in(RefreshToken::ENTITY_TYPE)
            ->set([
                RefreshToken::FIELD_STATUS => RefreshToken::STATUS_EXPIRED,
            ])
            ->where([
                RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE,
                RefreshToken::FIELD_EXPIRES_AT . '<=' => $this->dateTime->getNow()->toString(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    public function expireAuthorizationCodes(): void
    {
        $query = UpdateBuilder::create()
            ->in(AuthorizationCode::ENTITY_TYPE)
            ->set([
                AuthorizationCode::FIELD_STATUS => AuthorizationCode::STATUS_EXPIRED,
            ])
            ->where([
                AuthorizationCode::FIELD_STATUS => AuthorizationCode::STATUS_ACTIVE,
                AuthorizationCode::FIELD_EXPIRES_AT . '<=' => $this->dateTime->getNow()->toString(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }
}
