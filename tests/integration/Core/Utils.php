<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

class Utils
{
    /**
     * Get latest EspoCRM builded path
     *
     * @param  string $path
     *
     * @return string|null
     */
    public static function getLatestBuildedPath($path)
    {
        $archives = [];

        $buildDir = dir($path);
        while ($folderName = $buildDir->read()) {
            if ($folderName === '.'|| $folderName === '..' || empty($folderName)) continue;

            $pattern = '/^EspoCRM-([0-9]+)\.([0-9]+)(?:\.([0-9]+))?(?:-((a|alpha|b|beta|pre|rc)([0-9]+)?)?)?$/';

            if (preg_match($pattern, $folderName)) {
                $archives[] = $folderName;
            }
        }

        if (count($archives) > 0) {
            static::sortVersions($archives);
            return $path . '/' . $archives[count($archives) - 1];
        }
    }

    protected static function sortVersions(& $existVersions)
    {
        usort($existVersions, ["\\tests\\integration\\Core\\Utils", "versionCmp"]);
    }

    public static function versionCmp($a, $b)
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

    public static function fixUndefinedVariables()
    {
        /*SET UNDEFINED $_SERVER VARIABLES*/
        $list = array(
            'REQUEST_METHOD',
            'REMOTE_ADDR',
            'SERVER_NAME',
            'SERVER_PORT',
            'REQUEST_URI',
            'HTTPS',
        );

        foreach ($list as $name) {
            if (!array_key_exists($name, $_SERVER)) {
                $_SERVER[$name] = '';
            }
        } /*END: SET UNDEFINED VARIABLES*/
    }

    public static function checkCreateDatabase(array $options)
    {
        if (!isset($options['dbname'])) {
            throw new \Espo\Core\Exceptions\Error('Option "dbname" is not found.');
        }

        $dbname = $options['dbname'];
        unset($options['dbname']);

        $pdo = static::createPdoConnection($options);
        $pdo->query("CREATE DATABASE IF NOT EXISTS `". $dbname ."`");
    }

    public static function dropTables(array $options)
    {
        $pdo = static::createPdoConnection($options);

        $result = $pdo->query("show tables");
        while ($row = $result->fetch(\PDO::FETCH_NUM)) {
            $pdo->query("DROP TABLE IF EXISTS `".$row[0]."`;");
        }
    }

    public static function truncateTables(array $options)
    {
        $pdo = static::createPdoConnection($options);

        $result = $pdo->query("show tables");
        while ($row = $result->fetch(\PDO::FETCH_NUM)) {
            $pdo->query("TRUNCATE TABLE `".$row[0]."`;");
        }
    }

    public static function createPdoConnection(array $params)
    {
        $platform = !empty($params['platform']) ? strtolower($params['platform']) : 'mysql';
        $port = empty($params['port']) ? '' : ';port=' . $params['port'];
        $dbname = empty($params['dbname']) ? '' : ';dbname=' . $params['dbname'];

        $dsn = $platform . ':host='.$params['host'].$port.$dbname;
        $dbh = new \PDO($dsn, $params['user'], $params['password']);

        return $dbh;
    }
}
