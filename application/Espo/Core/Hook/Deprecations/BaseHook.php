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

namespace Espo\Core\Hook\Deprecations;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

/**
 * For backward compatibility.
 *
 * @deprecated Not to be used.
 * @todo Remove in v10.0.
 */
class BaseHook
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Log $log,
        private Config $config,
    ) {
        $className = get_class($this);

        $message = "Important: Your hook $className extends from Espo\\Core\\Hooks\\Base. " .
            "This class is going to be removed. You need to fix your hooks.";

        $this->log->warning($message);
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    protected function getUser(): User
    {
        return $this->user;
    }

    protected function getConfig(): Config
    {
        return $this->config;
    }
}
