<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace tests\integration\Espo\Core\Utils\Database\Fields;

use Doctrine\DBAL\Schema\Column;
use Espo\Core\ORM\DatabaseParamsFactory;
use Espo\Core\Utils\Database\Helper;
use Espo\Core\Utils\Database\Schema\SchemaManager;
use Espo\Core\Utils\Util;
use tests\integration\Core\BaseTestCase;

abstract class Base extends BaseTestCase
{
    protected ?string $dataFile = 'InitData.php';
    protected ?string $pathToFiles = 'Core/Database/customFiles';

    protected function beforeSetUp(): void
    {}

    protected function getPlatform(): ?string
    {
        $params = $this->getInjectableFactory()->create(DatabaseParamsFactory::class)
            ->create();

        return $params->getPlatform();
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    protected function getColumn(string $entityType, string $attribute): ?Column
    {
        $table = Util::camelCaseToUnderscore($entityType);
        $column = Util::camelCaseToUnderscore($attribute);

        // Custom types are registered.
        $manager = $this->getInjectableFactory()->create(SchemaManager::class);

        $helper = $manager->getDatabaseHelper();

        $tables = $helper->getDbalConnection()
            ->createSchemaManager()
            ->introspectTables();

        foreach ($tables as $tableObject) {
            if ($tableObject->getObjectName()->getUnqualifiedName()->getValue() !== $table) {
                continue;
            }

            foreach ($tableObject->getColumns() as $columnObject) {
                if ($columnObject->getObjectName()->getIdentifier()->getValue() !== $column) {
                    continue;
                }

                return $columnObject;
            }
        }

        return null;
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    protected function updateDefs(
        string $entityType,
        string $attribute,
        array $fieldDefs = [],
        ?array $linkDefs = null,
    ): void {

        $metadata = $this->getMetadata();

        $entityDefs = $metadata->get(['entityDefs', $entityType]);

        if (empty($entityDefs)) {
            return;
        }

        $save = false;

        if (!empty($fieldDefs)) {
            $currentFieldDefs = $entityDefs['fields'][$attribute] ?? [];
            $entityDefs['fields'][$attribute] = array_merge($currentFieldDefs, $fieldDefs);
            $save = true;
        }

        if (!empty($linkDefs)) {
            $currentLinkDefs = $entityDefs['links'][$attribute] ?? [];
            $entityDefs['links'][$attribute] = array_merge($currentLinkDefs, $linkDefs);
            $save = true;
        }

        if ($save) {
            $metadata->set('entityDefs', 'Test', $entityDefs);
            $metadata->save();

            $this->getDataManager()->rebuild([$entityType]);
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
