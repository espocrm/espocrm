<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Authentication\Hook\Hooks;

use Espo\Core\Authentication\Hook\BeforeLogin;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Api\Request;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Authentication\ConfigDataProvider;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Log;

use Espo\Entities\AuthLogRecord;

use DateTime;

class FailedAttemptsLimit implements BeforeLogin
{
    private $configDataProvider;

    private $entityManager;

    private $log;

    public function __construct(ConfigDataProvider $configDataProvider, EntityManager $entityManager, Log $log)
    {
        $this->configDataProvider = $configDataProvider;
        $this->entityManager = $entityManager;
        $this->log = $log;
    }

    public function process(AuthenticationData $data, Request $request): void
    {
        $isByTokenOnly = !$data->getMethod() && $request->getHeader('Espo-Authorization-By-Token') === 'true';

        if ($isByTokenOnly) {
            return;
        }

        $failedAttemptsPeriod = $this->configDataProvider->getFailedAttemptsPeriod();
        $maxFailedAttempts = $this->configDataProvider->getMaxFailedAttemptNumber();

        $requestTime = intval($request->getServerParam('REQUEST_TIME_FLOAT'));

        $requestTimeFrom = (new DateTime('@' . $requestTime))->modify('-' . $failedAttemptsPeriod);

        $ip = $request->getServerParam('REMOTE_ADDR');

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
