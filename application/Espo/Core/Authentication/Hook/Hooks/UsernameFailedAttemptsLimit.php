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

namespace Espo\Core\Authentication\Hook\Hooks;

use DateTime;
use Espo\Core\Api\Request;
use Espo\Core\Api\Util;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Authentication\ConfigDataProvider;
use Espo\Core\Authentication\HeaderKey;
use Espo\Core\Authentication\Hook\BeforeLogin;
use Espo\Core\Authentication\Util\DelayUtil;
use Espo\Entities\AuthLogRecord;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Exception;
use RuntimeException;

/**
 * @noinspection PhpUnused
 */
class UsernameFailedAttemptsLimit implements BeforeLogin
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private EntityManager $entityManager,
        private Util $util,
        private DelayUtil $delayUtil,
    ) {}

    public function process(AuthenticationData $data, Request $request): void
    {
        $isByTokenOnly = !$data->getMethod() && $request->getHeader(HeaderKey::AUTHORIZATION_BY_TOKEN) === 'true';

        if (
            $isByTokenOnly ||
            $this->configDataProvider->isAuthLogDisabled() ||
            !$this->configDataProvider->isUsernameFailedAttemptsLimitEnabled() ||
            $data->getUsername() === null
        ) {
            return;
        }

        $failedAttemptsPeriod = $this->configDataProvider->getFailedAttemptsPeriod();
        $delay = $this->configDataProvider->isUsernameFailedAttemptsDelay();

        $ipAddress = $this->util->obtainIpFromRequest($request);

        $repo = $this->entityManager->getRDBRepositoryByClass(AuthLogRecord::class);

        $where = [
            'username' => $data->getUsername(),
            'requestTime>' => $this->getTimeFrom($request, $failedAttemptsPeriod)->format('U'),
            'isDenied' => true,
        ];

        $wasFailed = (bool) $repo
            ->where($where)
            ->findOne();

        if (!$wasFailed) {
            return;
        }

        $failAttemptCount = $repo
            ->where($where)
            ->count();

        if ($failAttemptCount < $this->configDataProvider->getMaxUsernameFailedAttemptNumber()) {
            return;
        }

        if (
            // Prevent blocking for an IP address that has been logged in before.
            $ipAddress !== null &&
            $repo
                ->select([Attribute::ID])
                ->where([
                    'username' => $data->getUsername(),
                    'ipAddress' => $ipAddress,
                    'isDenied' => false,
                ])
                ->findOne()
        ) {
            return;
        }

        $this->delayUtil->delay($delay * 1000);
    }

    private function getTimeFrom(Request $request, string $failedAttemptsPeriod): DateTime
    {
        $requestTime = intval($request->getServerParam('REQUEST_TIME_FLOAT'));

        try {
            $requestTimeFrom = (new DateTime('@' . $requestTime))->modify('-' . $failedAttemptsPeriod);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return $requestTimeFrom;
    }
}
