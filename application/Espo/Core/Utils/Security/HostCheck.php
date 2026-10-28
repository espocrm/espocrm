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
use const DNS_AAAA;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_FLAG_GLOBAL_RANGE;
use const FILTER_FLAG_HOSTNAME;
use const FILTER_VALIDATE_DOMAIN;
use const FILTER_VALIDATE_IP;

class HostCheck
{
    /** @var string[] */
    private const array BLOCKED_IPV4_RANGES = [
        '0.0.0.0/8', // "This network"
        '10.0.0.0/8', // Private
        '100.64.0.0/10', // Shared address space, CGNAT (RFC 6598)
        '127.0.0.0/8', // Loopback
        '169.254.0.0/16', // Link-local, cloud metadata
        '172.16.0.0/12', // Private
        '192.0.0.0/24', // IETF protocol assignments
        '192.0.2.0/24', // Documentation (TEST-NET-1)
        '192.88.99.0/24', // 6to4 relay anycast (deprecated)
        '192.168.0.0/16', // Private
        '198.18.0.0/15', // Benchmarking
        '198.51.100.0/24', // Documentation (TEST-NET-2)
        '203.0.113.0/24', // Documentation (TEST-NET-3)
        '224.0.0.0/4', // Multicast
        '240.0.0.0/4', // Reserved, limited broadcast
    ];

    /** @var string[] */
    private const array ALLOWED_IPV6_RANGES = [
        '2000::/3', // Global unicast
    ];

    /** @var string[] */
    private const array BLOCKED_IPV6_RANGES = [
        '2001::/23', // IETF protocol assignments
        '2001:db8::/32', // Documentation
        '2002::/16', // 6to4 (only reached if unwrapping failed)
        '3fff::/20', // Documentation (RFC 9637)
    ];

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

        if (!$this->isDomainHost($host)) {
            return false;
        }

        $ipAddresses = $this->getHostIpAddresses($host);

        if ($ipAddresses === []) {
            return false;
        }

        foreach ($ipAddresses as $idAddress) {
            if (!$this->ipAddressIsNotInternal($idAddress)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @internal
     * @since 9.3.4
     */
    public function isDomainHost(string $host): bool
    {
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

        if (filter_var($host, FILTER_VALIDATE_DOMAIN)) {
            return true;
        }

        return false;
    }

    /**
     * @return string[]
     * @internal
     * @since 9.3.4
     */
    public function getHostIpAddresses(string $host): array
    {
        $records = dns_get_record($host, DNS_A + DNS_AAAA);

        if (!$records) {
            return [];
        }

        $output = [];

        foreach ($records as $record) {
            $type = $record['type'] ?? null;

            if ($type === 'AAAA') {
                /** @var ?string $idAddress */
                $idAddress = $record['ipv6'] ?? null;
            } else {
                /** @var ?string $idAddress */
                $idAddress = $record['ip'] ?? null;
            }

            if (!$idAddress) {
                continue;
            }

            $output[] = $idAddress;
        }

        return $output;
    }

    /**
     * @internal
     */
    public function ipAddressIsNotInternal(string $ipAddress): bool
    {
        if (str_starts_with($ipAddress, '::ffff:')) {
            $ipAddress = substr($ipAddress, 7);
        }

        $packed = $this->toPackedUnwrapped($ipAddress);

        if ($packed !== null) {
            if (strlen($packed) === 4) {
                if (self::matchesAnyRange($packed, self::BLOCKED_IPV4_RANGES)) {
                    return false;
                }
            } else {
                if (!self::matchesAnyRange($packed, self::ALLOWED_IPV6_RANGES)) {
                    return false;
                }

                if (self::matchesAnyRange($packed, self::BLOCKED_IPV6_RANGES)) {
                    return false;
                }
            }

            $ipAddress = inet_ntop($packed);

            if ($ipAddress === false) {
                return false;
            }
        }

        return (bool) filter_var(
            $ipAddress,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE
        );
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
                $num = (int) $part;
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

    /**
     * Returns the binary address (4 or 16 bytes). IPv6 addresses that embed an IPv4
     * address the packet will actually be delivered to are unwrapped to that IPv4 address.
     */
    private function toPackedUnwrapped(string $ipAddress): ?string
    {
        if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return null;
        }

        $packed = inet_pton($ipAddress);

        if ($packed === false) {
            return null;
        }

        if (strlen($packed) === 4) {
            return $packed;
        }

        if (strlen($packed) !== 16) {
            return null;
        }

        // IPv4-mapped ::ffff:0:0/96, in any notation.
        if (str_starts_with($packed, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff")) {
            return substr($packed, 12, 4);
        }

        // NAT64 64:ff9b::/96
        if (str_starts_with($packed, "\x00\x64\xff\x9b\x00\x00\x00\x00\x00\x00\x00\x00")) {
            return substr($packed, 12, 4);
        }

        // 6to4 2002::/16
        if (str_starts_with($packed, "\x20\x02")) {
            return substr($packed, 2, 4);
        }

        // Teredo 2001:0000::/32
        if (str_starts_with($packed, "\x20\x01\x00\x00")) {
            return substr($packed, 12, 4) ^ "\xff\xff\xff\xff";
        }

        return $packed;
    }

    /**
     * @param string[] $ranges CIDR ranges of the same family as the address.
     */
    private static function matchesAnyRange(string $packed, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if (self::isInRange($packed, $range)) {
                return true;
            }
        }

        return false;
    }

    private static function isInRange(string $packed, string $cidr): bool
    {
        [$network, $bits] = explode('/', $cidr, 2);

        $networkPacked = inet_pton($network);

        if ($networkPacked === false || strlen($networkPacked) !== strlen($packed)) {
            return false;
        }

        $bits = (int) $bits;
        $fullBytes = intdiv($bits, 8);

        if (substr($packed, 0, $fullBytes) !== substr($networkPacked, 0, $fullBytes)) {
            return false;
        }

        $remainingBits = $bits % 8;

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (8 - $remainingBits)) & 0xff;

        return (ord($packed[$fullBytes]) & $mask) === (ord($networkPacked[$fullBytes]) & $mask);
    }
}
