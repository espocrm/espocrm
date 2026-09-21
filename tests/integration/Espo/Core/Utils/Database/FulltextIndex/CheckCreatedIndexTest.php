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

namespace tests\integration\Espo\Core\Utils\Database\FulltextIndex;

use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\Table;
use Espo\Core\ORM\DatabaseParamsFactory;
use Espo\Core\Utils\Database\Helper;
use Espo\Core\Utils\Util;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use tests\integration\Core\BaseTestCase;

class CheckCreatedIndexTest extends BaseTestCase
{
    protected ?string $dataFile = 'InitData.php';
    protected ?string $pathToFiles = 'Core/FulltextIndex/customFiles';

    static public function entityTypeList(): array
    {
        return [
            ['Email'],
            ['Account'],
            ['Contact'],
        ];
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    #[DataProvider('entityTypeList')]
    public function testCreatedIndexes(string $entityType): void
    {
        if ($this->getPlatform() === 'Postgresql') {
            // DBAL uses two different codes to fetch indexes.
            return;
        }

        $entityManager = $this->getEntityManager();

        $fulltextFieldList = $entityManager->getMetadata()->get($entityType, 'fullTextSearchColumnList');

        if (!$fulltextFieldList) {
            $this->assertNull($fulltextFieldList);

            return;
        }

        $expectedList = array_map(fn (string $it) => Util::toUnderScore($it), $fulltextFieldList);

        $helper = $this->getInjectableFactory()->create(Helper::class);

        $table = $this->getTable($helper, Util::camelCaseToUnderscore($entityType));

        $foundIndex = null;

        foreach ($table->getIndexes() as $index) {
            if ($index->getType() !== IndexType::FULLTEXT) {
                continue;
            }

            $foundIndex = $index;
        }

        if (!$foundIndex) {
            throw new RuntimeException("Full-text index not found.");
        }

        $columnNames = [];

        foreach ($foundIndex->getIndexedColumns() as $indexedColumn) {
            $columnNames[] = $indexedColumn->getColumnName()->getIdentifier()->getValue();
        }

        asort($expectedList);
        asort($columnNames);

        $this->assertEquals($expectedList, $columnNames);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function getTable(Helper $helper, string $name): Table
    {
        $schemaManager = $helper->getDbalConnection()->createSchemaManager();

        return $schemaManager->introspectTableByUnquotedName($name);
    }

    private function getPlatform(): ?string
    {
        $params = $this->getInjectableFactory()
            ->create(DatabaseParamsFactory::class)
            ->create();

        return $params->getPlatform();
    }
}
