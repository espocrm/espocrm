<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Classes\Jobs;

use Espo\Entities\AuthToken;
use Espo\Core\Job\JobDataLess;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;

use DateTime;

class AuthTokenControl implements JobDataLess
{
    private Config $config;
    private EntityManager $entityManager;

    public function __construct(Config $config, EntityManager $entityManager)
    {
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    public function run(): void
    {
        $authTokenLifetime = $this->config->get('authTokenLifetime');
        $authTokenMaxIdleTime = $this->config->get('authTokenMaxIdleTime');

        if (!$authTokenLifetime && !$authTokenMaxIdleTime) {
            return;
        }

        $whereClause = [
            'isActive' => true,
        ];

        if ($authTokenLifetime) {
            $dt = new DateTime();

            $dt->modify('-' . $authTokenLifetime . ' hours');

            $authTokenLifetimeThreshold = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

            $whereClause['createdAt<'] = $authTokenLifetimeThreshold;
        }

        if ($authTokenMaxIdleTime) {
            $dt = new DateTime();

            $dt->modify('-' . $authTokenMaxIdleTime . ' hours');

            $authTokenMaxIdleTimeThreshold = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

            $whereClause['lastAccess<'] = $authTokenMaxIdleTimeThreshold;
        }

        $tokenList = $this->entityManager
            ->getRDBRepository(AuthToken::ENTITY_TYPE)
            ->where($whereClause)
            ->limit(0, 500)
            ->find();

        foreach ($tokenList as $token) {
            $token->set('isActive', false);

            $this->entityManager->saveEntity($token);
        }
    }
}
