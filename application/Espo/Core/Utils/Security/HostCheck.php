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

namespace Espo\Core\Utils\Security;

use const DNS_A;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_FLAG_HOSTNAME;
use const FILTER_VALIDATE_DOMAIN;
use const FILTER_VALIDATE_IP;

class HostCheck
{
    /**
     * Validates the string is a host and it's not internal.
     * If not a host, returns false.
     *
     * @since 9.3.4
     */
    public function isHostAndNotInternal(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->ipAddressIsNotInternal($host);
        }

        $normalized = $this->normalizeIpAddress($host);

        if ($normalized !== false && filter_var($normalized, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return false;
        }

        if (!$this->hasNoNumericItem($host)) {
            return false;
        }

        $records = dns_get_record($host, DNS_A);

        if (!$records) {
            return true;
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

    /**
     * @deprecated Since 9.3.4. Use `isHostAndNotInternal`.
     * @todo Remove in 9.4.0.
     */
    public function isNotInternalHost(string $host): bool
    {
        return $this->isHostAndNotInternal($host);
    }

    private function normalizeIpAddress(string $ip): string|false
    {
        if (!str_contains($ip, '.')) {
            return self::normalizePart($ip);
        }

        $parts = explode('.', $ip);

        if (count($parts) !== 4) {
            return false;
        }

        $result = [];

        foreach ($parts as $part) {
            if (preg_match('/^0x[0-9a-f]+$/i', $part)) {
                $num = hexdec($part);
            } else if (preg_match('/^0[0-7]+$/', $part) && $part !== '0') {
                $num = octdec($part);
            } else if (ctype_digit($part)) {
                $num = (int)$part;
            } else {
                return false;
            }

            if ($num < 0 || $num > 255) {
                return false;
            }

            $result[] = $num;
        }

        return implode('.', $result);
    }

    private static function normalizePart(string $ip): string|false
    {
        if (preg_match('/^0x[0-9a-f]+$/i', $ip)) {
            $num = hexdec($ip);
        } elseif (preg_match('/^0[0-7]+$/', $ip) && $ip !== '0') {
            $num = octdec($ip);
        } elseif (ctype_digit($ip)) {
            $num = (int) $ip;
        } else {
            return false;
        }

        if ($num < 0 || $num > 0xFFFFFFFF) {
            return false;
        }

        $num = (int) $num;

        return long2ip($num);
    }


    private function hasNoNumericItem(string $host): bool
    {
        $hasNoNumeric = false;

        foreach (explode('.', $host) as $it) {
            if (!is_numeric($it) && !self::isHex($it)) {
                $hasNoNumeric = true;

                break;
            }
        }

        return $hasNoNumeric;
    }

    private function isHex(string $value): bool
    {
        return preg_match('/^0x[0-9a-fA-F]+$/', $value) === 1;
    }
}
