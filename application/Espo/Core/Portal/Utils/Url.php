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

namespace Espo\Core\Portal\Utils;

class Url
{
    public static function detectPortalIdForApi(): ?string
    {
        $portalId = filter_input(INPUT_GET, 'portalId');

        if ($portalId)  {
            return $portalId;
       }

        $url = $_SERVER['REQUEST_URI'] ?? null;
        $scriptName = $_SERVER['SCRIPT_NAME'];

        if (!$url) {
            return null;
        }

        $scriptNameModified = str_replace('public/api/', 'api/', $scriptName);

        return explode('/', $url)[count(explode('/', $scriptNameModified)) - 1] ?? null;
    }

    public static function getPortalIdFromEnv(): ?string
    {
        return $_SERVER['ESPO_PORTAL_ID'] ?? null;
    }

    public static function detectPortalId(): ?string
    {
        $portalId = self::getPortalIdFromEnv();

        if ($portalId) {
            return $portalId;
        }

        $url = $_SERVER['REQUEST_URI'] ?? null;
        $scriptName = $_SERVER['SCRIPT_NAME'];

        $scriptNameModified = str_replace('public/api/', 'api/', $scriptName);

        if ($url) {
            $portalId = explode('/', $url)[count(explode('/', $scriptNameModified)) - 1] ?? null;

            if (str_contains($url, '=')) {
                $portalId = null;
            }
        }

        if ($portalId) {
            return $portalId;
        }

        $url = $_SERVER['REDIRECT_URL'] ?? null;

        if (!$url) {
            return null;
        }

        $portalId = explode('/', $url)[count(explode('/', $scriptNameModified)) - 1] ?? null;

        if ($portalId === '') {
            $portalId = null;
        }

        return $portalId;
    }

    protected static function detectIsCustomUrl(): bool
    {
        return (bool) ($_SERVER['ESPO_PORTAL_IS_CUSTOM_URL'] ?? false);
    }

    public static function detectIsInPortalDir(): bool
    {
        $isCustomUrl = self::detectIsCustomUrl();

        if ($isCustomUrl) {
            return false;
        }

        $a = explode('?', $_SERVER['REQUEST_URI']);

        $url = rtrim($a[0], '/');

        return strpos($url, '/portal') !== false;
    }

    public static function detectIsInPortalWithId(): bool
    {
        if (!self::detectIsInPortalDir()) {
            return false;
        }

        $url = $_SERVER['REQUEST_URI'];

        $a = explode('?', $url);

        $url = rtrim($a[0], '/');

        $folders = explode('/', $url);

        if (count($folders) > 1 && $folders[count($folders) - 2] === 'portal') {
            return true;
        }

        return false;
    }
}
