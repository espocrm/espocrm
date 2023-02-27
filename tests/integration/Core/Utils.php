<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Core;

use Espo\Core\Exceptions\Error;
use PDO;

class Utils
{
    /**
     * Get the latest EspoCRM built path.
     *
     * @param string $path
     * @return ?string
     */
    public static function getLatestBuiltPath($path): ?string
    {
        $archives = [];

        $buildDir = dir($path);

        while ($folderName = $buildDir->read()) {
            if ($folderName === '.'|| $folderName === '..' || empty($folderName)) {
                continue;
            }

            $pattern = '/^EspoCRM-([0-9]+)\.([0-9]+)(?:\.([0-9]+))?(?:-((a|alpha|b|beta|pre|rc)([0-9]+)?)?)?$/';

            if (preg_match($pattern, $folderName)) {
                $archives[] = $folderName;
            }
        }

        if (count($archives) > 0) {
            static::sortVersions($archives);

            return $path . '/' . $archives[count($archives) - 1];
        }

        return null;
    }

    private static function sortVersions(&$existVersions)
    {
        usort($existVersions, ["\\tests\\integration\\Core\\Utils", "versionCmp"]);
    }

    public static function versionCmp($a, $b): int
    {
        $order = ['a' => 0, 'alpha' => 1, 'b' => 2, 'beta' => 3, 'pre' => 4, 'rc' => 5];

        $ma = $mb = [];

        $pattern = '/^EspoCRM-([0-9]+)\.([0-9]+)(?:\.([0-9]+))?(?:-((a|alpha|b|beta|pre|rc)[0-9]+)?)?$/';

        preg_match($pattern, $a, $ma);
        preg_match($pattern, $b, $mb);

        if ($ma[1] != $mb[1]) {
            return (int) $ma[1] < (int) $mb[1] ? -1 : 1;
        }

        if ($ma[2] != $mb[2]) {
            return (int) $ma[2] < (int) $mb[2] ? -1 : 1;
        }

        if (!isset($ma[3])) {
            $ma[3] = 0;
        }

        if (!isset($mb[3])) {
            $mb[3] = 0;
        }

        if ($ma[3] != $mb[3]) {
            return (int) $ma[3] < (int) $mb[3] ? -1 : 1;
        }

        if (isset($ma[4]) && !isset($mb[4])) {
            return -1;
        }

        if (!isset($ma[4]) && isset($mb[4])) {
            return 1;
        }

        if (@$ma[5] != @$mb[5]) {
            return ($order[$ma[5]] < $order[$mb[5]]) ? -1 : 1;
        }

        if (@$ma[4] != @$mb[4]) {
            return ($ma[4] < $mb[4]) ? -1 : 1;
        }

        return 0;
    }

    public static function fixUndefinedVariables(): void
    {
        $list = [
            'REQUEST_METHOD',
            'REMOTE_ADDR',
            'SERVER_NAME',
            'SERVER_PORT',
            'REQUEST_URI',
            'HTTPS',
        ];

        foreach ($list as $name) {
            if (!array_key_exists($name, $_SERVER)) {
                $_SERVER[$name] = '';
            }
        }
    }

    public static function checkCreateDatabase(array $options): void
    {
        if (!isset($options['dbname'])) {
            throw new Error('Option "dbname" is not found.');
        }

        $dbname = $options['dbname'];
        unset($options['dbname']);

        $sql = "CREATE DATABASE IF NOT EXISTS `{$dbname}`";

        $pdo = static::createPdoConnection($options);

        $pdo->query($sql);
    }

    public static function dropTables(array $options): void
    {
        $pdo = static::createPdoConnection($options);

        $result = $pdo->query("SHOW TABLES");

        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $table = $row[0];

            $sql = "DROP TABLE IF EXISTS `{$table}`";

            $pdo->query($sql);
        }
    }

    public static function truncateTables(array $options)
    {
        $pdo = static::createPdoConnection($options);

        $result = $pdo->query("SHOW TABLES");

        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $table = $row[0];

            $sql = "TRUNCATE TABLE `{$table}`";

            $pdo->query($sql);
        }
    }

    public static function createPdoConnection(array $params): PDO
    {
        $platform = !empty($params['platform']) ? strtolower($params['platform']) : 'mysql';
        $port = empty($params['port']) ? '' : ';port=' . $params['port'];
        $dbname = empty($params['dbname']) ? '' : ';dbname=' . $params['dbname'];

        $dsn = $platform . ':host=' . $params['host'] . $port . $dbname;

        return new PDO($dsn, $params['user'], $params['password']);
    }
}
