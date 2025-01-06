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

namespace Espo\Core\Utils;

use Symfony\Component\Process\PhpExecutableFinder;

class System
{
    /**
     * Get a web server name.
     *
     * @return string E.g. `microsoft-iis`, `nginx`, `apache`.
     */
    public function getServerType(): string
    {
        $serverSoft = $_SERVER['SERVER_SOFTWARE'];

        preg_match('/^(.*?)\//i', $serverSoft, $match);

        if (empty($match[1])) {
            preg_match('/^(.*)\/?/i', $serverSoft, $match);
        }

        return strtolower(
            trim($match[1]) /** @phpstan-ignore-line */
        );
    }

    /**
     * Get an OS. Details at http://en.wikipedia.org/wiki/Uname.
     *
     * @return ?string E.g. `windows`, `mac`, `linux`.
     */
    public function getOS(): ?string
    {
        $osList = [
            'windows' => [
                'win',
                'UWIN',
            ],
            'mac' => [
                'mac',
                'darwin',
            ],
            'linux' => [
                'linux',
                'cygwin',
                'GNU',
                'FreeBSD',
                'OpenBSD',
                'NetBSD',
            ],
        ];

        $sysOS = strtolower(PHP_OS);

        foreach ($osList as $osName => $osSystem) {
            if (preg_match('/^('.implode('|', $osSystem).')/i', $sysOS)) {
                return $osName;
            }
        }

        return null;
    }

    /**
     * Get a root directory of EspoCRM.
     */
    public function getRootDir(): string
    {
        $bPath = realpath('bootstrap.php') ?: '';

        return dirname($bPath);
    }

    /**
     * Get a PHP binary.
     */
    public function getPhpBinary(): ?string
    {
        $path = (new PhpExecutableFinder)->find();

        if ($path === false) {
            return null;
        }

        return $path;
    }

    /**
     * Get PHP version (only digits and dots).
     */
    public static function getPhpVersion(): string
    {
        $version = phpversion();

        $matches = null;

        if (preg_match('/^[0-9\.]+[0-9]/', $version, $matches)) {
            return $matches[0];
        }

        return $version;
    }

    /**
     * @return string|false
     */
    public function getPhpParam(string $name)
    {
        return ini_get($name);
    }

    /**
     * Whether a PHP extension is loaded.
     */
    public function hasPhpExtension(string $name): bool
    {
        return extension_loaded($name);
    }

    /**
     * @deprecated Use `hasPhpExtension`.
     */
    public function hasPhpLib(string $name): bool
    {
        return extension_loaded($name);
    }

    /**
     * Get a process PID.
     */
    public static function getPid(): ?int
    {
        if (!function_exists('getmypid')) {
            return null;
        }

        $pid = getmypid();

        if ($pid === false) {
            return null;
        }

        return $pid;
    }

    public static function isProcessActive(?int $pid): bool
    {
        if ($pid === null) {
            return false;
        }

        if (!self::isPosixSupported()) {
            return false;
        }

        if (posix_getsid($pid) === false) {
            return false;
        }

        return true;
    }

    public static function isPosixSupported(): bool
    {
        return function_exists('posix_getsid');
    }
}
