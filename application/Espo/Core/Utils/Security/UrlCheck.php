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

class UrlCheck
{
    public function __construct(
        private HostCheck $hostCheck,
    ) {}

    public function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Checks whether it's a URL, and it does not follow to an internal host.
     *
     * @since 9.3.4
     */
    public function isUrlAndNotIternal(string $url): bool
    {
        if (!$this->isUrl($url)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host)) {
            return false;
        }

        return $this->hostCheck->isHostAndNotInternal($host);
    }

    /**
     * @return ?string[] Null if not a domain name or not a URL.
     * @internal
     * @since 9.3.4
     */
    public function getCurlResolve(string $url): ?array
    {
        if (!$this->isUrl($url)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($port === null && $scheme) {
            $port = match (strtolower($scheme)) {
                'http' => 80,
                'https'=> 443,
                'ftp' => 21,
                'ssh' => 22,
                'smtp' => 25,
                default  => null,
            };
        }

        if ($port === null) {
            return [];
        }

        if (!is_string($host)) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (!$this->hostCheck->isDomainHost($host)) {
            return null;
        }

        $ipAddresses = $this->hostCheck->getHostIpAddresses($host);

        $output = [];

        foreach ($ipAddresses as $ipAddress) {
            $ipPart = $ipAddress;

            if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ipPart = "[$ipPart]";
            }

            $output[] = "$host:$port:$ipPart";
        }

        return $output;
    }

    /**
     * @param string[] $resolve
     * @param string[] $allowed An allowed address list in the `{host}:{port}` format.
     * @internal
     */
    public function validateCurlResolveNotInternal(array $resolve, array $allowed = []): bool
    {
        if ($resolve === []) {
            return false;
        }

        $ipAddresses = [];

        foreach ($resolve as $item) {
            $arr = explode(':', $item, 3);

            if (count($arr) < 3) {
                return false;
            }

            $ipAddress = $arr[2];
            $port = $arr[1];
            $domain = $arr[0];

            if (in_array("$ipAddress:$port", $allowed) || in_array("$domain:$port", $allowed)) {
                return true;
            }

            if (str_starts_with($ipAddress, '[') && str_ends_with($ipAddress, ']')) {
                $ipAddress = substr($ipAddress, 1, -1);
            }

            $ipAddresses[] = $ipAddress;
        }

        foreach ($ipAddresses as $ipAddress) {
            if (!$this->hostCheck->ipAddressIsNotInternal($ipAddress)) {
                return false;
            }
        }

        return true;
    }
}
