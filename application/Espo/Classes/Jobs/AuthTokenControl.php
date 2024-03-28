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

namespace Espo\Classes\Jobs;

use Espo\Entities\AuthToken;
use Espo\Core\Job\JobDataLess;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use DateTime;

/**
 * @noinspection PhpUnused
 */
class AuthTokenControl implements JobDataLess
{
    private const LIMIT = 500;

    public function __construct(
        private Config $config,
        private EntityManager $entityManager
    ) {}

    public function run(): void
    {
        $authTokenLifetime = (int) ($this->config->get('authTokenLifetime', 0) * 60);
        $authTokenMaxIdleTime = (int) ($this->config->get('authTokenMaxIdleTime', 0) * 60);

        if (!$authTokenLifetime && !$authTokenMaxIdleTime) {
            return;
        }

        $whereClause = [
            'isActive' => true,
        ];

        if ($authTokenLifetime) {
            $dt = new DateTime();
            $dt->modify("-$authTokenLifetime minutes");

            $whereClause['createdAt<'] = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
        }

        if ($authTokenMaxIdleTime) {
            $dt = new DateTime();
            $dt->modify("-$authTokenMaxIdleTime minutes");

            $whereClause['lastAccess<'] = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
        }

        $tokenList = $this->entityManager
            ->getRDBRepository(AuthToken::ENTITY_TYPE)
            ->sth()
            ->where($whereClause)
            ->limit(0, self::LIMIT)
            ->find();

        foreach ($tokenList as $token) {
            $token->set('isActive', false);

            $this->entityManager->saveEntity($token);
        }
    }
}
