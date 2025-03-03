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

namespace Espo\Core\WebSocket;

use Espo\Core\Utils\Config;

/**
 * @since 9.1.0
 */
class ConfigDataProvider
{
    public function __construct(
        private Config $config,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->config->get('useWebSocket');
    }

    public function isDebugMode(): bool
    {
        return (bool) $this->config->get('webSocketDebugMode');
    }

    public function useSecureServer(): bool
    {
        return (bool) $this->config->get('webSocketUseSecureServer');
    }

    public function getPort(): ?string
    {
        $port = $this->config->get('webSocketPort');

        if (!$port) {
            return null;
        }

        return (string) $port;
    }

    public function getPhpExecutablePath(): ?string
    {
        return $this->config->get('phpExecutablePath');
    }

    public function getSslCertificateFile(): ?string
    {
        return $this->config->get('webSocketSslCertificateFile');
    }

    public function allowSelfSignedSsl(): bool
    {
        return (bool) $this->config->get('webSocketSslAllowSelfSigned');
    }

    public function getSslCertificatePassphrase(): ?string
    {
        return $this->config->get('webSocketSslCertificatePassphrase');
    }

    public function getSslCertificateLocalPrivateKey(): ?string
    {
        return $this->config->get('webSocketSslCertificateLocalPrivateKey');
    }

    public function getMessager(): ?string
    {
        return $this->config->get('webSocketMessager');
    }
}
