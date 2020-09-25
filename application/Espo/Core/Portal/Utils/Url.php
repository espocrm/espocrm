<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Portal\Utils;

class Url
{
    public static function detectPortalId() : ?string
    {
        $url = $_SERVER['REQUEST_URI'];

        $portalId = explode('/', $url)[count(explode('/', $_SERVER['SCRIPT_NAME'])) - 1] ?? null;

        if (strpos($url, '=') !== false) {
            $portalId = null;
        }

        if (!isset($portalId)) {
            $url = $_SERVER['REDIRECT_URL'];

            $portalId = explode('/', $url)[count(explode('/', $_SERVER['SCRIPT_NAME'])) - 1] ?? null;
        }

        return $portalId;
    }

    public static function detectUrl() : string
    {
        $url = $_SERVER['REQUEST_URI'];

        $portalId = explode('/', $url)[count(explode('/', $_SERVER['SCRIPT_NAME'])) - 1] ?? null;

        if (strpos($url, '=') !== false) {
            $portalId = null;
        }

        if (!isset($portalId)) {
            return $_SERVER['REDIRECT_URL'];
        }

        return $url;
    }

    public static function normalizeUrl(string $url) : string
    {
        $urlParts = explode('?', $url);

        if (substr($urlParts[0], -1) === '/') {
            return $url;
        }

        $url = $urlParts[0] . '/';

        if (count($urlParts) > 1) {
            $url .= '?' . $urlParts[1];
        }

        return $url;
    }

    public function detectIsInDir() : bool
    {
        $url = $_SERVER['REQUEST_URI'];

        $a = explode('?', $url);

        $url = rtrim($a[0], '/');

        return strpos($url, '/') !== false;
    }
}
