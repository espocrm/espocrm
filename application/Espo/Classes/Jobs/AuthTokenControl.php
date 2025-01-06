<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Entities\Portal;
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
        $lifetime = (int) ($this->config->get('authTokenLifetime', 0) * 60);
        $maxIdleTime = (int) ($this->config->get('authTokenMaxIdleTime', 0) * 60);

        $portalIds = [];

        /** @var iterable<Portal> $portals */
        $portals = $this->entityManager
            ->getRDBRepositoryByClass(Portal::class)
            ->find();

        foreach ($portals as $portal) {
            $portalIds[] = $portal->getId();
        }

        $this->process(null, $lifetime, $maxIdleTime, $portalIds);

        foreach ($portals as $portal) {
            $itemLifetime = $portal->get('authTokenLifetime') !== null ?
                (int) ($portal->get('authTokenLifetime') * 60) :
                $lifetime;

            $itemMaxIdleTime = $portal->get('authTokenMaxIdleTime') !== null ?
                (int) ($portal->get('authTokenMaxIdleTime') * 60) :
                $maxIdleTime;

            $this->process($portal->getId(), $itemLifetime, $itemMaxIdleTime);
        }
    }

    /**
     * @param string[] $ignorePortalIds
     */
    private function process(?string $portalId, int $lifetime, int $maxIdleTime, array $ignorePortalIds = []): void
    {
        if (!$lifetime && !$maxIdleTime) {
            return;
        }

        $whereClause = ['isActive' => true];

        if ($portalId) {
            $whereClause['portalId'] = $portalId;
        }

        if (!$portalId && $ignorePortalIds !== []) {
            $whereClause[] = [
                'OR' => [
                    ['portalId' => null],
                    ['portalId!=' => $ignorePortalIds],
                ]
            ];
        }

        if ($lifetime) {
            $dt = new DateTime();
            $dt->modify("-$lifetime minutes");

            $whereClause['createdAt<'] = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
        }

        if ($maxIdleTime) {
            $dt = new DateTime();
            $dt->modify("-$maxIdleTime minutes");

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
