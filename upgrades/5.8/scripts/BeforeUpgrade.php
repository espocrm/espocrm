<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

class BeforeUpgrade
{
    public function run($container)
    {
        $this->container = $container;
        $this->checkDatabaseRequirements();
    }

    protected function checkDatabaseRequirements()
    {
        $databaseRequirements = [
            'mysql' => '5.7.0',
            'mariadb' => '10.1.0',
        ];

        $databaseHelper = new \Espo\Core\Utils\Database\Helper($this->container->get('config'));
        $pdo = $this->container->get('entityManager')->getPDO();

        $databaseType = $databaseHelper->getDatabaseType();
        $fullVersion = $databaseHelper->getPdoDatabaseVersion($pdo);

        if (preg_match('/[0-9]+\.[0-9]+\.[0-9]+/', $fullVersion, $match)) {
            $version = $match[0];
            $databaseTypeLc = strtolower($databaseType);

            if (isset($databaseRequirements[$databaseTypeLc])) {
                $requiredVersion = $databaseRequirements[$databaseTypeLc];
                if (version_compare($version, $requiredVersion, '<')) {
                    $msg = "Your {$databaseType} version is not supported. Please upgrade {$databaseType} to a newer version ({$requiredVersion} or later).";
                    throw new \Espo\Core\Exceptions\Error($msg);
                }
            }
        }

        $query = "
            ALTER TABLE `user` ADD `middle_name` VARCHAR(100) DEFAULT NULL COLLATE utf8mb4_unicode_ci
        ";

        try {
            $sth = $pdo->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {}


        $query = "
            UPDATE email_account SET port = NULL WHERE port = ''
        ";
        try {
            $sth = $pdo->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {}

        $query = "
            UPDATE inbound_email SET port = NULL WHERE port = ''
        ";
        try {
            $sth = $pdo->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {}
    }
}
