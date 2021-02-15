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

namespace tests\integration\Espo\Core\Utils\Database;

use PDO;
use Espo\Core\Utils\Util;

abstract class Base extends \tests\integration\Core\BaseTestCase
{
    protected $dataFile = 'InitData.php';

    protected $pathToFiles = 'Core/Database/customFiles';

    protected function beforeSetUp()
    {
        $this->fullReset();
    }

    protected function getColumnInfo($entityName, $fieldName)
    {
        $entityManager = $this->getContainer()->get('entityManager');
        $pdo = $entityManager->getPDO();

        $dbName = $this->getContainer()->get('config')->get('database.dbname');

        $query = "
            SELECT * FROM information_schema.columns
            WHERE table_name = '" . Util::toUnderScore($entityName) . "'
            AND column_name = '" . Util::toUnderScore($fieldName) . "'
            AND table_schema = '" . $dbName . "'
        ";

        $sth = $pdo->prepare($query);
        $sth->execute();

        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    protected function updateDefs($entityName, $fieldName, array $fieldDefs = [], array $linkDefs = null)
    {
        $metadata = $this->getContainer()->get('metadata');

        $entityDefs = $metadata->get(['entityDefs', $entityName]);

        if (empty($entityDefs)) {
            return;
        }

        $save = false;

        if (!empty($fieldDefs)) {
            $currentFieldDefs = $entityDefs['fields'][$fieldName] ?? [];
            $entityDefs['fields'][$fieldName] = array_merge($currentFieldDefs, $fieldDefs);
            $save = true;
        }

        if (!empty($linkDefs)) {
            $currentLinkDefs = $entityDefs['links'][$fieldName] ?? [];
            $entityDefs['links'][$fieldName] = array_merge($currentLinkDefs, $linkDefs);
            $save = true;
        }

        if ($save) {
            $metadata->set('entityDefs', 'Test', $entityDefs);
            $metadata->save();

            $this->getContainer()->get('dataManager')->rebuild([$entityName]);
        }
    }

    protected function executeQuery($query)
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $sth = $entityManager->getPDO()->prepare($query);
        $sth->execute();
    }
}
