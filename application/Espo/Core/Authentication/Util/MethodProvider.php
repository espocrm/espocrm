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

namespace Espo\Core\Authentication\Util;

use Espo\Core\ApplicationState;
use Espo\Core\Authentication\ConfigDataProvider;
use Espo\Core\Authentication\Logins\Espo;
use Espo\Core\ORM\EntityManagerProxy;
use Espo\Core\Utils\Metadata;
use Espo\Entities\AuthenticationProvider;
use Espo\Entities\Portal;
use RuntimeException;

/**
 * An authentication method provider.
 */
class MethodProvider
{
    public function __construct(
        private EntityManagerProxy $entityManager,
        private ApplicationState $applicationState,
        private ConfigDataProvider $configDataProvider,
        private Metadata $metadata
    ) {}

    /**
     * Get an authentication method.
     */
    public function get(): string
    {
        if ($this->applicationState->isPortal()) {
            $method = $this->getForPortal($this->applicationState->getPortal());

            if ($method) {
                return $method;
            }

            return $this->getDefaultForPortal();
        }

        return $this->configDataProvider->getDefaultAuthenticationMethod();
    }

    /**
     * Get an authentication method for portals. The method that is applied via the authentication provider link.
     * If no provider, then returns null.
     */
    public function getForPortal(Portal $portal): ?string
    {
        $providerId = $portal->getAuthenticationProvider()?->getId();

        if (!$providerId) {
            return null;
        }

        /** @var ?AuthenticationProvider $provider */
        $provider = $this->entityManager->getEntityById(AuthenticationProvider::ENTITY_TYPE, $providerId);

        if (!$provider) {
            throw new RuntimeException("No authentication provider for portal.");
        }

        $method = $provider->getMethod();

        if (!$method) {
            throw new RuntimeException("No method in authentication provider.");
        }

        return $method;
    }

    /**
     * Get a default authentication method for portals. Should be used if a portal does not have
     * an authentication provider.
     */
    private function getDefaultForPortal(): string
    {
        $method = $this->configDataProvider->getDefaultAuthenticationMethod();

        $allow = $this->metadata->get(['authenticationMethods', $method, 'portalDefault']);

        if (!$allow) {
            return Espo::NAME;
        }

        return $method;
    }
}
