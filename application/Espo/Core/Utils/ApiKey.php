<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\Utils;

use Espo\Core\Utils\Config\ConfigWriter;

class ApiKey
{
    public function __construct(
        private Config $config,
        private ConfigWriter $configWriter)
    {}

    public static function hash(string $secretKey, string $string = ''): string
    {
        return hash_hmac('sha256', $string, $secretKey);
    }

    /**
     * @deprecated
     * @internal
     */
    public static function hashLegacy(string $secretKey, string $string = ''): string
    {
        return hash_hmac('sha256', $string, $secretKey, true);
    }

    public function getSecretKeyForUserId(string $id): ?string
    {
        $apiSecretKeys = $this->config->get('apiSecretKeys');

        if (!$apiSecretKeys) {
            return null;
        }

        if (!is_object($apiSecretKeys)) {
            return null;
        }

        if (!isset($apiSecretKeys->$id)) {
            return null;
        }

        return $apiSecretKeys->$id;
    }

    public function storeSecretKeyForUserId(string $id, string $secretKey): void
    {
        $apiSecretKeys = $this->config->get('apiSecretKeys');

        if (!is_object($apiSecretKeys)) {
            $apiSecretKeys = (object) [];
        }

        $apiSecretKeys->$id = $secretKey;

        $this->configWriter->set('apiSecretKeys', $apiSecretKeys);
        $this->configWriter->save();
    }

    public function removeSecretKeyForUserId(string $id): void
    {
        $apiSecretKeys = $this->config->get('apiSecretKeys');

        if (!is_object($apiSecretKeys)) {
            $apiSecretKeys = (object) [];
        }

        unset($apiSecretKeys->$id);

        $this->configWriter->set('apiSecretKeys', $apiSecretKeys);
        $this->configWriter->save();
    }
}
