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

namespace Espo\Tools\OAuthServer\League;

use Espo\Core\Utils\Config;
use Random\RandomException;
use RuntimeException;

class CryptKeyProvider
{
    private const string PARAM_CRYPT_KEY = 'oAuthServerCryptKey';

    public function __construct(
        private Config $config,
        private Config\ConfigWriter $configWriter,
    ) {}

    public function getCryptKey(): string
    {
        if (!$this->config->has(self::PARAM_CRYPT_KEY)) {
            $this->configWriter->set(self::PARAM_CRYPT_KEY, $this->generateKey());
            $this->configWriter->save();
        }

        return $this->config->get(self::PARAM_CRYPT_KEY) ?? throw new RuntimeException("No crypt key.");
    }

    private function generateKey(): string
    {
        try {
            $key = bin2hex(random_bytes(32));
        } catch (RandomException $e) {
            throw new RuntimeException(previous: $e);
        }

        return $key;
    }
}
