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

namespace Espo\Core\HttpClient;

use Espo\Core\HttpClient\Exceptions\ConnectException;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp;
use Psr\Http\Message\StreamInterface;

class Util
{
    /**
     * @param resource|string|int|float|bool|StreamInterface $resource
     * @since 10.0.0
     */
    public static function streamFor($resource): StreamInterface
    {
        return Utils::streamFor($resource);
    }

    /**
     * @internal
     * @param string[] $addressList
     */
    public static function matchUrlToAddressList(string $url, array $addressList): bool
    {
        if (!$addressList) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!is_string($host)) {
            return false;
        }

        if (!is_int($port)) {
            if ($scheme === 'https') {
                $port = 443;
            } else if ($scheme === 'http') {
                $port = 80;
            }
        }

        if (!is_int($port)) {
            return false;
        }

        $address = $host . ':' . $port;

        return in_array($address, $addressList);
    }

    /**
     * @internal
     * @throws ConnectException
     */
    public static function handleConnectException(GuzzleHttp\Exception\ConnectException $exception): never
    {
        $context = $exception->getHandlerContext();

        $reason = null;

        if (($context['errno'] ?? 0) === CURLE_OPERATION_TIMEDOUT) {
            $reason = ConnectErrorReason::Timeout;
        }

        throw ConnectException::create(previous: $exception, reason: $reason);
    }
}
