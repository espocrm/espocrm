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

namespace Espo\Core\Utils\OAuth;

use RuntimeException;

class OAuthRouteUtil
{
    private static function detectBasePath(): string
    {
        /** @var string $serverScriptName */
        $serverScriptName = $_SERVER['SCRIPT_NAME'];

        /** @var string $serverRequestUri */
        $serverRequestUri = $_SERVER['REQUEST_URI'];

        /** @var string $scriptName */
        $scriptName = parse_url($serverScriptName , PHP_URL_PATH);

        $scriptNameModified = str_replace('public/oauth/', 'oauth/', $scriptName);

        $scriptDir = dirname($scriptNameModified);

        $uri = parse_url('https://any.com' . $serverRequestUri, PHP_URL_PATH);

        if (!is_string($uri)) {
            throw new RuntimeException();
        }

        if (stripos($uri, $scriptName) === 0) {
            return $scriptName;
        }

        if ($scriptDir !== '/' && stripos($uri, $scriptDir) === 0) {
            return $scriptDir;
        }

        return '';
    }

    public static function detectPath(): string
    {
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

        if (!is_string($requestPath)) {
            throw new RuntimeException();
        }

        $basePath = self::detectBasePath();

        $path = substr($requestPath, strlen($basePath));

        return trim($path, '/');
    }

    public static function detectBasePast(): string
    {
        $hasTrailingSlash = self::isTrailingSlash();

        $levels = substr_count(self::detectPath(), '/') + 1;

        if ($hasTrailingSlash) {
            $levels ++;
        }

        return str_repeat('../', $levels);
    }

    private static function isTrailingSlash(): bool
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        $requestPath = parse_url($requestUri, PHP_URL_PATH);

        if (!is_string($requestPath)) {
            throw new RuntimeException();
        }

        return ($requestPath !== '/' && str_ends_with($requestPath, '/'));
    }

    public static function getRedirectUrlWithTrailingSlash(): ?string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return null;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = explode('?', $uri, 2)[0];

        if ($path === '' || $path === '/' || str_ends_with($path, '/')) {
            return null;
        }

        $output = $path . '/';

        $queryString = $_SERVER['QUERY_STRING'] ?? null;

        if ($queryString !== null && $queryString !== '') {
            $output .= '?' . $queryString;
        }

        return $output;
    }
}
