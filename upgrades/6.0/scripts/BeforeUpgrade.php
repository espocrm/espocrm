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

use Espo\Core\Exceptions\Error;

class BeforeUpgrade
{
    public function run($container)
    {
        $this->container = $container;

        $this->processCheckCLI();

        $this->processCheckExtensions();

        $this->processMyIsamCheck();

        $this->processNextNumberAlterTable();
    }

    protected function processCheckCLI()
    {
        $isCli = (substr(php_sapi_name(), 0, 3) == 'cli') ? true : false;

        if (!$isCli) {
            throw new Error("This upgrade can be run only from CLI.");
        }
    }

    protected function processCheckExtensions()
    {
        $em = $this->container->get('entityManager');

        $extension = $em->getRepository('Extension')
            ->where([
                'name' => 'Google Integration',
                'isInstalled' => true,
            ])
            ->findOne();

        if ($extension) {
            $version = $extension->get('version');

            if (version_compare($version, '1.4.2', '<')) {
                $message =
                    "EspoCRM 6.0.0 is not compatible with Google Integration extension of a version lower than 1.4.2. " .
                    "Please upgrade the extension or uninstall it. Then run the upgrade command again.";

                throw new Error($message);
            }
        }

        $extension = $em->getRepository('Extension')
            ->where([
                'name' => 'Real Estate',
                'isInstalled' => true,
            ])
            ->findOne();

        if ($extension) {
            $version = $extension->get('version');

            if (version_compare($version, '1.4.0', '<')) {
                $message =
                    "EspoCRM 6.0.0 is not compatible with Real Estate extension of a version lower than 1.4.0. " .
                    "Please upgrade the extension or uninstall it. Then run the upgrade command again.";

                throw new Error($message);
            }
        }

        $extension = $em->getRepository('Extension')
            ->where([
                'name' => 'VoIP Integration',
                'isInstalled' => true,
            ])
            ->findOne();

        if ($extension) {
            $version = $extension->get('version');

            if (version_compare($version, '1.15.0', '<')) {
                $message =
                    "EspoCRM 6.0.0 is not compatible with VoIP Integration extension of a version lower than 1.15.0. " .
                    "Please upgrade the extension or uninstall it. Then run the upgrade command again.";

                throw new Error($message);
            }
        }
    }

    protected function processMyIsamCheck()
    {
        $myisamTableList = $this->getMyIsamTableList();

        if (empty($myisamTableList)) {
            return;
        }

        $isCli = (substr(php_sapi_name(), 0, 3) == 'cli') ? true : false;

        $tableListString = implode(", ", $myisamTableList);

        $lineBreak = $isCli ? "\n" : "<br>";

        $link = "https://www.espocrm.com/blog/converting-myisam-engine-to-innodb";

        $linkString = $isCli ? $link : "<a href=\"{$link}\" target=\"_blank\">link</a>";

        $message =
            "In v6.0 we have dropped a support of MyISAM engine for DB tables. " .
            "You have the following tables that use MyISAM: {$tableListString}.{$lineBreak}{$lineBreak}" .
            "Please change the engine to InnoDB for these tables then run upgrade again.{$lineBreak}{$lineBreak}" .
            "See: {$linkString}.";

        throw new Error($message);
    }

    protected function getMyIsamTableList()
    {
        $container = $this->container;

        $pdo = $container->get('entityManager')->getPDO();
        $databaseInfo = $container->get('config')->get('database');

        try {
            $sth = $pdo->prepare("
                SELECT TABLE_NAME as tableName
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = '". $databaseInfo['dbname'] ."'
                AND ENGINE = 'MyISAM'
            ");

            $sth->execute();
        }
        catch (Exception $e) {
            return [];
        }

        $tableList = $sth->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tableList)) {
            return [];
        }

        return $tableList;
    }

    protected function processNextNumberAlterTable()
    {
        $pdo = $this->container->get('entityManager')->getPDO();

        $q1 = "ALTER TABLE `next_number` CHANGE `entity_type` `entity_type` VARCHAR(100)";
        $q2 = "ALTER TABLE `next_number` CHANGE `field_name` `field_name` VARCHAR(100)";

        $pdo->exec($q1);
        $pdo->exec($q2);
    }
}
