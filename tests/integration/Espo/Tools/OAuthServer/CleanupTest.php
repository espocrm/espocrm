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

use Espo\Core\Field\DateTime;
use Espo\Core\Utils\DateTime\Clock;
use Espo\Tools\OAuthServer\Cleanup\GeneralCleanup;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\AuthorizationCode;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use tests\integration\Core\BaseTestCase;

class CleanupTest extends BaseTestCase
{
    public function testCleanupAuthTokens(): void
    {
        $now = DateTime::fromString('2030-01-01 00:00');

        $this->reCreateApplicationWithNow($now);

        $em = $this->getEntityManager();

        $accessToken1 = $em->getRDBRepositoryByClass(AccessToken::class)->getNew();
        $accessToken1->setExpiresAt($now->addHours(1));
        $em->saveEntity($accessToken1);

        $accessToken2 = $em->getRDBRepositoryByClass(AccessToken::class)->getNew();
        $accessToken2->setExpiresAt($now->addHours(1));
        $em->saveEntity($accessToken2);

        $refreshToken = $em->getRDBRepositoryByClass(RefreshToken::class)->getNew();
        $refreshToken
            ->setAccessToken($accessToken2)
            ->setExpiresAt($now->addMonths(3));
        $em->saveEntity($refreshToken);

        //

        $cleanup = $this->getInjectableFactory()->create(GeneralCleanup::class);
        $cleanup->process();

        $this->assertNotNull(
            $em->getRDBRepositoryByClass(AccessToken::class)->getById($accessToken1->getId())
        );

        $this->assertNotNull(
            $em->getRDBRepositoryByClass(AccessToken::class)->getById($accessToken2->getId())
        );

        //

        $now = $now->addDays(15)->addHours(1);

        $this->reCreateApplicationWithNow($now);

        //

        $cleanup = $this->getInjectableFactory()->create(GeneralCleanup::class);
        $cleanup->process();

        $this->assertFalse(
            $em->getRDBRepositoryByClass(AccessToken::class)->getById($accessToken1->getId()) !== null
        );

        $this->assertTrue(
            $em->getRDBRepositoryByClass(AccessToken::class)->getById($accessToken2->getId()) !== null
        );

        //

        $refreshToken->setRevoked();
        $em->saveEntity($refreshToken);

        //

        $cleanup->process();

        $this->assertFalse(
            $em->getRDBRepositoryByClass(AccessToken::class)->getById($accessToken2->getId()) !== null
        );
    }

    public function testCleanupRefreshTokens(): void
    {
        $now = DateTime::fromString('2030-01-01 00:00');

        $this->reCreateApplicationWithNow($now);

        $em = $this->getEntityManager();

        $refreshToken = $em->getRDBRepositoryByClass(RefreshToken::class)->getNew();
        $refreshToken
            ->setExpiresAt($now->addMonths(1));
        $em->saveEntity($refreshToken);

        //

        $cleanup = $this->getInjectableFactory()->create(GeneralCleanup::class);
        $cleanup->process();

        $this->assertNotNull(
            $em->getRDBRepositoryByClass(RefreshToken::class)->getById($refreshToken->getId())
        );

        //

        $now = $now->addMonths(1)->addDays(30);

        $this->reCreateApplicationWithNow($now);

        //

        $cleanup = $this->getInjectableFactory()->create(GeneralCleanup::class);
        $cleanup->process();

        $this->assertFalse(
            $em->getRDBRepositoryByClass(RefreshToken::class)->getById($refreshToken->getId()) !== null
        );
    }

    public function testCleanupAuthorizationCode(): void
    {
        $now = DateTime::fromString('2030-01-01 00:00');

        $this->reCreateApplicationWithNow($now);

        $em = $this->getEntityManager();

        $code = $em->getRDBRepositoryByClass(AuthorizationCode::class)->getNew();
        $code
            ->setExpiresAt($now->addHours(1));
        $em->saveEntity($code);

        //

        $cleanup = $this->getInjectableFactory()->create(GeneralCleanup::class);
        $cleanup->process();

        $this->assertNotNull(
            $em->getRDBRepositoryByClass(AuthorizationCode::class)->getById($code->getId())
        );

        //

        $now = $now->addHours(1)->addDays(15);

        $this->reCreateApplicationWithNow($now);

        //

        $cleanup = $this->getInjectableFactory()->create(GeneralCleanup::class);
        $cleanup->process();

        $this->assertFalse(
            $em->getRDBRepositoryByClass(AuthorizationCode::class)->getById($code->getId()) !== null
        );
    }

    private function reCreateApplicationWithNow(DateTime $now): void
    {
        $clock = $this->createMock(Clock::class);
        $clock->expects(self::any())
            ->method('now')
            ->willReturn($now->toDateTime());

        $this->reCreateApplication(
            reuse: true,
            binding: $this->prepareBinding(function ($binder) use ($clock) {
                $binder->bindInstance(Clock::class, $clock);
            }),
        );
    }
}
