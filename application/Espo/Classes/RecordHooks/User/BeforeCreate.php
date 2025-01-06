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

namespace Espo\Classes\RecordHooks\User;

use Espo\Core\Authentication\Logins\Hmac;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Tools\User\UserUtil;

/**
 * @implements SaveHook<User>
 * @noinspection PhpUnused
 */
class BeforeCreate implements SaveHook
{
    public function __construct(
        private Config $config,
        private User $user,
        private UserUtil $util
    ) {}

    public function process(Entity $entity): void
    {
        $this->processLimitChecking($entity);
        $this->processUserExistsChecking($entity);
        $this->processApi($entity);
        $this->processTypeChecking($entity);
    }

    /**
     * @throws Conflict
     */
    private function processUserExistsChecking(User $entity): void
    {
        if ($this->util->checkExists($entity)) {
            throw new Conflict('userNameExists');
        }
    }

    /**
     * @throws Forbidden
     */
    private function processLimitChecking(User $entity): void
    {
        $userLimit = $this->config->get('userLimit');
        $portalUserLimit = $this->config->get('portalUserLimit');

        if (
            $userLimit &&
            !$this->user->isSuperAdmin() &&
            !$entity->isPortal() && !$entity->isApi()
        ) {
            $userCount = $this->util->getInternalCount();

            if ($userCount >= $userLimit) {
                throw new Forbidden("User limit $userLimit is reached.");
            }
        }

        if (
            $portalUserLimit &&
            !$this->user->isSuperAdmin() &&
            $entity->isPortal()
        ) {
            $portalUserCount = $this->util->getPortalCount();

            if ($portalUserCount >= $portalUserLimit) {
                throw new Forbidden("Portal user limit $portalUserLimit is reached.");
            }
        }
    }

    private function processApi(User $entity): void
    {
        if (!$entity->isApi()) {
            return;
        }

        $entity->set('apiKey', Util::generateApiKey());

        if ($entity->getAuthMethod() === Hmac::NAME) {
            $secretKey = Util::generateSecretKey();

            $entity->set('secretKey', $secretKey);
        }
    }

    /**
     * @throws Forbidden
     */
    private function processTypeChecking(User $entity): void
    {
        if (
            $entity->isSuperAdmin() ||
            !$entity->getType() ||
            in_array($entity->getType(), $this->util->getAllowedUserTypeList())
        ) {
            return;
        }

        throw new Forbidden("Not allowed 'type'.");
    }
}
