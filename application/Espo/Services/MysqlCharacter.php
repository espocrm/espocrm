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

namespace Espo\Services;

use Espo\Core\Utils\Database\Schema\Utils as SchemaUtils;

/**
 * @todo Remove.
 * @deprecated
 */
class MysqlCharacter extends \Espo\Core\Services\Base
{
    protected function init()
    {
        $this->addDependency('container');
    }

    protected function getContainer()
    {
        return $this->getInjection('container');
    }

    public function jobConvertToMb4()
    {
        $container = $this->getContainer();


        /* @var $em \Espo\ORM\EntityManager */
        $em = $container->get('entityManager');

        $sqlExecutor = $em->getSqlExecutor();

        $ormMeta = $container->get('ormMetadata')->getData(true);

        $databaseSchema = $container->get('schema');
        $maxIndexLength = $databaseSchema->getDatabaseHelper()->getMaxIndexLength();

        if ($maxIndexLength > 1000) {
            $maxIndexLength = 1000;
        }

        $sth = $sqlExecutor->execute("SELECT `name` FROM `account` WHERE LENGTH(name) > 249");

        if (!$sth->fetch()) {
            $sqlExecutor->execute("ALTER TABLE `account` MODIFY `name` VARCHAR(249)");
        }

        $fieldListExceededIndexMaxLength = SchemaUtils::getFieldListExceededIndexMaxLength($ormMeta, $maxIndexLength);

        foreach ($ormMeta as $entityName => $entityParams) {
            $tableName = \Espo\Core\Utils\Util::toUnderScore($entityName);

            // Get table columns params
            $query = "SHOW FULL COLUMNS FROM `". $tableName ."` WHERE `Collation` <> 'utf8mb4_unicode_ci'";

            try {
                $sth = $sqlExecutor->execute($query);
            }
            catch (\Exception $e) {
                $GLOBALS['log']->debug('Utf8mb4: Table does not exist - ' . $e->getMessage());

                continue;
            }

            $columnParams = [];

            $rowList = $sth->fetchAll();

            foreach ($rowList as $row) {
                $columnParams[ $row['Field'] ] = $row;
            }
            // END: get table columns params

            foreach ($entityParams['fields'] as $fieldName => $fieldParams) {
                $columnName = \Espo\Core\Utils\Util::toUnderScore($fieldName);

                if (isset($fieldParams['notStorable']) && $fieldParams['notStorable']) {
                    continue;
                }

                if (isset($fieldListExceededIndexMaxLength[$entityName]) && in_array($fieldName, $fieldListExceededIndexMaxLength[$entityName])) {
                    continue;
                }

                if (!isset($columnParams[$columnName]) || empty($columnParams[$columnName]['Type'])) {
                    continue;
                }

                $query = null;

                switch ($fieldParams['type']) {
                    case 'varchar':
                    case 'text':
                    case 'jsonObject':
                    case 'jsonArray':
                        $query = "ALTER TABLE `".$tableName."`
                            CHANGE COLUMN `". $columnName ."` `". $columnName ."` ". $columnParams[$columnName]['Type'] ."
                            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                        ";

                        break;
                }

                if (!empty($query)) {
                    $GLOBALS['log']->debug('Utf8mb4: execute the query - [' . $query . '].');

                    try {
                        $sqlExecutor->execute($query);
                    }
                    catch (\Exception $e) {
                        $GLOBALS['log']->warning(
                            'Utf8mb4: FAILED executing the query - [' . $query . '], details: '. $e->getMessage() .'.'
                        );
                    }
                }
            }
        }

        $config = $container->get('config');
        $database = $config->get('database');

        if (!isset($database['charset']) || $database['charset'] != 'utf8mb4') {
            $database['charset'] = 'utf8mb4';
            $config->set('database', $database);
            $config->save();
        }

        $this->getContainer()->get('dataManager')->rebuild();
    }
}
