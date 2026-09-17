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

namespace tests\integration\Espo\Core\Utils\Database;

use Doctrine\DBAL\Schema\Column;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Templates\Entities\Base;
use Espo\Core\Utils\Database\Helper;
use Espo\Core\Utils\Database\Schema\RebuildMode;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\EntityManager\EntityManager as EntityTypeManager;
use Espo\Tools\FieldManager\FieldManager;
use integration\Core\NoTransaction;
use tests\integration\Core\BaseTestCase;

#[NoTransaction]
class RebuildTest extends BaseTestCase
{
    private const string ENTITY_TYPE_TEST = 'TestEntityType';

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testTablesAndColumns(): void
    {
        // Create entity type.

        $emTool = $this->getInjectableFactory()->create(EntityTypeManager::class);

        $emTool->create(self::ENTITY_TYPE_TEST, Base::TEMPLATE_TYPE);

        $this->reCreateApplication();

        $table = 'c_test_entity_type';

        $this->assertTrue($this->tableExists($table));

        // Create field.

        $fm = $this->getInjectableFactory()->create(FieldManager::class);

        $column = 'test';

        $fm->create('C' . self::ENTITY_TYPE_TEST, 'test', [
            'type' => FieldType::VARCHAR,
        ]);

        //

        $em = $this->getEntityManager();

        $em->createEntity('C' . self::ENTITY_TYPE_TEST);

        // Column exists.

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase();

        $this->assertTrue($this->columnExists($table, $column));

        // Increase column length on soft rebuild.

        $fm->update('C' . self::ENTITY_TYPE_TEST, 'test', [
            FieldParam::MAX_LENGTH => 300,
        ]);

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase();

        $this->assertEquals(300, $this->getColumn($table, $column)?->getLength());

        // Do not decrease column length on soft rebuild.

        $fm->update('C' . self::ENTITY_TYPE_TEST, 'test', [
            FieldParam::MAX_LENGTH => 100,
        ]);

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase();

        $this->assertEquals(300, $this->getColumn($table, $column)?->getLength());

        // Decrease column length on hard rebuild.

        $fm->update('C' . self::ENTITY_TYPE_TEST, 'test', [
            FieldParam::MAX_LENGTH => 100,
        ]);

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase(mode: RebuildMode::HARD);

        $this->assertEquals(100, $this->getColumn($table, $column)?->getLength());

        // Delete field.

        $fm->delete('C' . self::ENTITY_TYPE_TEST, 'test');

        // Column exists after soft rebuild.

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase();

        $this->assertTrue($this->columnExists($table, $column));

        // Column does not exist after hard rebuild.

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase(mode: RebuildMode::HARD);

        $this->assertFalse($this->columnExists($table, $column));

        // Add autoincrement field.

        $fm = $this->getInjectableFactory()->create(FieldManager::class);

        $fm->create('C' . self::ENTITY_TYPE_TEST, 'testSecond', [
            'type' => FieldType::AUTOINCREMENT,
        ]);

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase();

        $em = $this->getEntityManager();

        $em->createEntity('C' . self::ENTITY_TYPE_TEST);
        $entity = $em->createEntity('C' . self::ENTITY_TYPE_TEST);
        $em->refreshEntity($entity);

        $this->assertEquals(3, $entity->get('testSecond'));

        // Disable autoincrement.

        $this->getMetadata()->set('entityDefs', 'C' . self::ENTITY_TYPE_TEST, [
            'fields' => [
                'testSecond' => [
                    'type' => FieldType::INT,
                    'autoincrement' => false,
                ],
            ],
        ]);
        $this->getMetadata()->save();

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase();

        //

        $entity = $em->createEntity('C' . self::ENTITY_TYPE_TEST);
        $em->refreshEntity($entity);

        $this->assertEquals(null, $entity->get('testSecond'));

        // Enable autoincrement back.

        $this->getMetadata()->set('entityDefs', 'C' . self::ENTITY_TYPE_TEST, [
            'fields' => [
                'testSecond' => [
                    'type' => FieldType::INT,
                    'autoincrement' => true,
                ],
            ],
        ]);
        $this->getMetadata()->save();

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase();

        $this->assertTrue($this->getColumn($table, 'test_second')?->getAutoincrement());

        //

        $em->refreshEntity($entity);
        $this->assertEquals(4, $entity->get('testSecond'));

        $entity = $em->createEntity('C' . self::ENTITY_TYPE_TEST);
        $em->refreshEntity($entity);

        // Sequence is not preserved in MariaDB for some reason.
        $this->assertIsInt($entity->get('testSecond'));

        // Delete autoincrement column.

        $fm->delete('C' . self::ENTITY_TYPE_TEST, 'testSecond');

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase();

        $this->assertFalse($this->getColumn($table, 'test_second')?->getAutoincrement());

        // Delete entity type.

        $emTool->delete('C' . self::ENTITY_TYPE_TEST);

        // Table exists after soft rebuild.

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase();

        $this->assertTrue($this->tableExists($table));

        // Table exists after hard rebuild.

        $this->reCreateApplication();
        $this->getDataManager()->rebuildDatabase(mode: RebuildMode::HARD);

        $this->assertTrue($this->tableExists($table));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function tableExists(string $table): bool
    {
        $helper = $this->getInjectableFactory()->create(Helper::class);

        return $helper->getDbalConnection()
            ->createSchemaManager()
            ->tablesExist([$table]);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function columnExists(string $table, string $column): bool
    {
        return $this->getColumn($table, $column) !== null;
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function getColumn(string $table, string $column): ?Column
    {
        $helper = $this->getInjectableFactory()->create(Helper::class);

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
}
