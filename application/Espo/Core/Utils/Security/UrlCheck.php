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

namespace Espo\Core\Utils\Security;

use const DNS_A;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;

class UrlCheck
{
    public function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Checks whether a URL does not follow to an internal host.
     */
    public function isNotInternalUrl(string $url): bool
    {
        if (!$this->isUrl($url)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host)) {
            return false;
        }

        $records = dns_get_record($host, DNS_A);

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->ipAddressIsNotInternal($host);
        }

        if (!$records) {
            return false;
        }

        foreach ($records as $record) {
            /** @var ?string $idAddress */
            $idAddress = $record['ip'] ?? null;

            if (!$idAddress) {
                return false;
            }

            if (!$this->ipAddressIsNotInternal($idAddress)) {
                return false;
            }
        }

        return true;
    }

    private function ipAddressIsNotInternal(string $ipAddress): bool
    {
        return (bool) filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
