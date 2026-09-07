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

namespace Espo\Tools\OAuthServer\Cleanup;

use Espo\Core\Cleanup\Cleanup;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\SelectBuilder;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\AuthorizationCode;
use Espo\Tools\OAuthServer\Entities\RefreshToken;

/**
 * @noinspection PhpUnused
 */
class GeneralCleanup implements Cleanup
{
    private const string PERIOD_AUTHORIZATION_CODE = '15 days';
    private const string PERIOD_ACCESS_TOKEN = '15 days';
    private const string PERIOD_REFRESH_TOKEN = '30 days';

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private DateTime $dateTime,
    ) {}

    public function process(): void
    {
        $this->processAuthorizationCode();
        $this->processAccessTokens();
        $this->processRefreshTokens();
    }

    private function getAuthorizationCodePeriod(): string
    {
        return $this->config->get('oAuthServer.cleanup.authorizationCodePeriod') ?? self::PERIOD_AUTHORIZATION_CODE;
    }

    private function getAccessTokenPeriod(): string
    {
        return $this->config->get('oAuthServer.cleanup.accessTokenPeriod') ?? self::PERIOD_ACCESS_TOKEN;
    }

    private function getRefreshTokenPeriod(): string
    {
        return $this->config->get('oAuthServer.cleanup.refreshTokenPeriod') ?? self::PERIOD_REFRESH_TOKEN;
    }

    private function processAuthorizationCode(): void
    {
        $time = $this->dateTime->getNow()
            ->modify('-' . $this->getAuthorizationCodePeriod());

        $entries = $this->entityManager
            ->getRDBRepositoryByClass(AuthorizationCode::class)
            ->sth()
            ->where([
                AuthorizationCode::FIELD_EXPIRES_AT . '<' => $time->toString(),
            ])
            ->find();

        foreach ($entries as $entry) {
            $this->entityManager->removeEntity($entry);
        }
    }

    private function processAccessTokens(): void
    {
        $time = $this->dateTime->getNow()
            ->modify('-' . $this->getAccessTokenPeriod());

        $entries = $this->entityManager
            ->getRDBRepositoryByClass(AccessToken::class)
            ->sth()
            ->where([
                AccessToken::FIELD_EXPIRES_AT . '<' => $time->toString(),
            ])
            ->where(
                Cond::not(
                    Cond::exists(
                        SelectBuilder::create()
                            ->from(RefreshToken::ENTITY_TYPE)
                            ->select(Attribute::ID, 'r')
                            ->where([
                                'r.' . RefreshToken::FIELD_STATUS => RefreshToken::STATUS_ACTIVE,
                            ])
                            ->where(
                                Cond::equal(
                                    Expr::column(lcfirst(AccessToken::ENTITY_TYPE) . '.' . Attribute::ID),
                                    Expr::column('r.' . Attribute::ID),
                                )
                            )
                            ->build()
                    )
                )
            )
            ->find();

        foreach ($entries as $entry) {
            $this->entityManager->removeEntity($entry);
        }
    }

    private function processRefreshTokens(): void
    {
        $time = $this->dateTime->getNow()
            ->modify('-' . $this->getRefreshTokenPeriod());

        $entries = $this->entityManager
            ->getRDBRepositoryByClass(RefreshToken::class)
            ->sth()
            ->where([
                'OR' => [
                    [
                        RefreshToken::FIELD_EXPIRES_AT . '<' => $time->toString(),
                    ],
                    [
                        RefreshToken::FIELD_STATUS => RefreshToken::STATUS_REVOKED,
                        Field::MODIFIED_AT . '<' => $time->toString(),
                    ],
                ],
            ])
            ->find();

        foreach ($entries as $entry) {
            $this->entityManager->removeEntity($entry);
        }
    }
}
