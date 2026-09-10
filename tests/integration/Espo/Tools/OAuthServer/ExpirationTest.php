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

namespace integration\Espo\Tools\OAuthServer;

use Espo\Core\Field\Date;
use Espo\Core\Field\DateTime;
use Espo\Core\Utils\DateTime\Clock;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\AuthorizationCode;
use Espo\Tools\OAuthServer\Entities\Client;
use Espo\Tools\OAuthServer\Entities\ClientSecret;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use Espo\Tools\OAuthServer\Jobs\OAuthServerControl;
use tests\integration\Core\BaseTestCase;

class ExpirationTest extends BaseTestCase
{
    public function testExpire(): void
    {
        $now = DateTime::fromString('2030-01-01 00:00');

        $clock = $this->createClock($now);

        $this->reCreateApplication(
            reuse: true,
            noUser: true,
            binding: $this->prepareBinding(function ($binder) use ($clock) {
                $binder->bindInstance(Clock::class, $clock);
            }),
        );

        $em = $this->getEntityManager();

        $client = $em->getRDBRepositoryByClass(Client::class)->getNew();
        $em->saveEntity($client);

        //

        $expirationDate = Date::fromDateTime($now->toDateTime())->addMonths(1);
        $expiresAt = DateTime::fromDateTime($now->toDateTime())->addMonths(1);

        $secret = $em->getRDBRepositoryByClass(ClientSecret::class)->getNew();
        $secret
            ->setClient($client)
            ->setExpirationDate($expirationDate);
        $em->saveEntity($secret);

        $code = $em->getRDBRepositoryByClass(AuthorizationCode::class)->getNew();
        $code
            ->setClient($client)
            ->setExpiresAt($expiresAt);
        $em->saveEntity($code);

        $accessToken = $em->getRDBRepositoryByClass(AccessToken::class)->getNew();
        $accessToken
            ->setClient($client)
            ->setExpiresAt($expiresAt);
        $em->saveEntity($accessToken);

        $refreshToken = $em->getRDBRepositoryByClass(RefreshToken::class)->getNew();
        $refreshToken
            ->setClient($client)
            ->setExpiresAt($expiresAt);
        $em->saveEntity($refreshToken);

        //

        $job = $this->getInjectableFactory()->create(OAuthServerControl::class);

        $job->run();

        $em->refreshEntity($secret);
        $em->refreshEntity($code);
        $em->refreshEntity($accessToken);
        $em->refreshEntity($refreshToken);

        $this->assertTrue($secret->isActive());
        $this->assertTrue($code->isActive());
        $this->assertTrue($accessToken->isActive());
        $this->assertTrue($refreshToken->isActive());

        //

        $now = $now->addMonths(1);

        $clock = $this->createClock($now);

        $this->reCreateApplication(
            reuse: true,
            noUser: true,
            binding: $this->prepareBinding(function ($binder) use ($clock) {
                $binder->bindInstance(Clock::class, $clock);
            }),
        );

        //

        $job = $this->getInjectableFactory()->create(OAuthServerControl::class);

        $job->run();

        $em->refreshEntity($secret);
        $em->refreshEntity($code);
        $em->refreshEntity($accessToken);
        $em->refreshEntity($refreshToken);

        $this->assertTrue($secret->isExpired());
        $this->assertFalse($secret->isActive());
        $this->assertFalse($secret->isRevoked());

        $this->assertTrue($code->isExpired());
        $this->assertTrue($accessToken->isExpired());
        $this->assertTrue($refreshToken->isExpired());
    }

    private function createClock(DateTime $now): Clock
    {
        $clock = $this->createMock(Clock::class);

        $clock->expects(self::any())
            ->method('now')
            ->willReturn($now->toDateTime());

        return $clock;
    }
}
