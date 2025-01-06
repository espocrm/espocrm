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

class BeforeUpgrade
{
    public function run($container)
    {
        $this->container = $container;
        $this->checkDatabaseRequirements();

        $pdo = $container->get('entityManager')->getPDO();

        try {
            $pdo->query("TRUNCATE TABLE `scheduled_job_log_record`");
        } catch (\Exception $e) {}
    }

    protected function checkDatabaseRequirements()
    {
        $databaseRequirements = [
            'mysql' => '5.6.0',
            'mariadb' => '10.0.0',
        ];

        $databaseHelper = new \Espo\Core\Utils\Database\Helper($this->container->get('config'));

        $databaseType = $databaseHelper->getDatabaseType();
        $fullVersion = $databaseHelper->getPdoDatabaseVersion($this->container->get('entityManager')->getPDO());

        if (preg_match('/[0-9]+\.[0-9]+\.[0-9]+/', $fullVersion, $match)) {
            $version = $match[0];
            $databaseTypeLc = strtolower($databaseType);

            if (isset($databaseRequirements[$databaseTypeLc])) {
                if (version_compare($version, $databaseRequirements[$databaseTypeLc], '<')) {
                    $msg = "Your {$databaseType} version is not supported. Please upgrade {$databaseType} to a newer version (5.6 or later).";
                    throw new \Espo\Core\Exceptions\Error($msg);
                }
            }
        }
    }
}
