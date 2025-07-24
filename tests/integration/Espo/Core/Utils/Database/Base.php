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

namespace tests\integration\Espo\Core\Utils\Database;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Database\Helper;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use tests\integration\Core\BaseTestCase;

abstract class Base extends BaseTestCase
{
    protected ?string $dataFile = 'InitData.php';
    protected ?string $pathToFiles = 'Core/Database/customFiles';

    protected function beforeSetUp(): void
    {}

    protected function getColumnInfo($entityName, $fieldName)
    {
        $pdo = $this->getInjectableFactory()
            ->create(Helper::class)
            ->getPDO();

        $dbName = $this->getContainer()
            ->getByClass(Config::class)
            ->get('database.dbname');

        $query = "
            SELECT * FROM information_schema.columns
            WHERE table_name = '" . Util::toUnderScore($entityName) . "'
            AND column_name = '" . Util::toUnderScore($fieldName) . "'
            AND table_schema = '" . $dbName . "'
        ";

        $sth = $pdo->prepare($query);
        $sth->execute();

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    protected function updateDefs($entityName, $fieldName, array $fieldDefs = [], array $linkDefs = null)
    {
        $metadata = $this->getContainer()->getByClass(Metadata::class);

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

            $this->getDataManager()->rebuild([$entityName]);
        }
    }

    protected function executeQuery($query)
    {
        $pdo = $this->getInjectableFactory()
            ->create(Helper::class)
            ->getPDO();

        $sth = $pdo->prepare($query);
        $sth->execute();
    }
}
