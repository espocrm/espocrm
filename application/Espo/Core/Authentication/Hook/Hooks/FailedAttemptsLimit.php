<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Api\Util;
use Espo\Core\Authentication\Hook\BeforeLogin;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Authentication\ConfigDataProvider;
use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;
use Espo\Entities\AuthLogRecord;

use DateTime;

class FailedAttemptsLimit implements BeforeLogin
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private EntityManager $entityManager,
        private Log $log,
        private Util $util
    ) {}

    /**
     * @throws Forbidden
     */
    public function process(AuthenticationData $data, Request $request): void
    {
        $isByTokenOnly = !$data->getMethod() && $request->getHeader('Espo-Authorization-By-Token') === 'true';

        if ($isByTokenOnly) {
            return;
        }

        if ($this->configDataProvider->isAuthLogDisabled()) {
            return;
        }

        $failedAttemptsPeriod = $this->configDataProvider->getFailedAttemptsPeriod();
        $maxFailedAttempts = $this->configDataProvider->getMaxFailedAttemptNumber();

        $requestTime = intval($request->getServerParam('REQUEST_TIME_FLOAT'));

        $requestTimeFrom = (new DateTime('@' . $requestTime))->modify('-' . $failedAttemptsPeriod);

        $ip = $this->util->obtainIpFromRequest($request);

        $where = [
            'requestTime>' => $requestTimeFrom->format('U'),
            'ipAddress' => $ip,
            'isDenied' => true,
        ];

        $wasFailed = (bool) $this->entityManager
            ->getRDBRepository(AuthLogRecord::ENTITY_TYPE)
            ->select(['id'])
            ->where($where)
            ->findOne();

        if (!$wasFailed) {
            return;
        }

        $failAttemptCount = $this->entityManager
            ->getRDBRepository(AuthLogRecord::ENTITY_TYPE)
            ->where($where)
            ->count();

        if ($failAttemptCount <= $maxFailedAttempts) {
            return;
        }

        $this->log->warning("AUTH: Max failed login attempts exceeded for IP '{$ip}'.");

        throw new Forbidden("Max failed login attempts exceeded.");
    }
}
