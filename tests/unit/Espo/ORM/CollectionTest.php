<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\unit\Espo\ORM;

use Espo\ORM\BaseEntity;
use Espo\ORM\Defs;
use Espo\ORM\Defs\DefsData;
use Espo\ORM\EntityCollection;
use Espo\ORM\Metadata;
use Espo\ORM\MetadataDataProvider;
use Espo\Core\ORM\EntityManager;

use PHPUnit\Framework\TestCase;

require_once 'tests/unit/testData/DB/Entities.php';

class CollectionTest extends TestCase
{
    protected function setUp(): void
    {
        $ormMetadata = include('tests/unit/testData/DB/ormMetadata.php');

        $metadataDataProvider = $this->createMock(MetadataDataProvider::class);

        $metadataDataProvider
            ->expects($this->any())
            ->method('get')
            ->willReturn($ormMetadata);

        $this->metadata = new Metadata($metadataDataProvider);
        $defsData = new DefsData($this->metadata);
        $defs = new Defs($defsData);

        $this->entityManager = $this->createMock(EntityManager::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getDefs')
            ->willReturn($defs);
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function createEntity(string $entityType): BaseEntity
    {
        $defs = $this->metadata->get($entityType);

        return new BaseEntity($entityType, $defs, $this->entityManager);
    }

    public function testEntityCollectionAppend(): void
    {
        $entity1 = $this->createEntity('Account');
        $entity2 = $this->createEntity('Account');
        $entity3 = $this->createEntity('Account');

        $collection = new EntityCollection([$entity1]);

        $collection[] = $entity2;
        $collection->append($entity3);

        $this->assertEquals(3, $collection->count());
    }

    public function testEntityCollectionIteratorToArray(): void
    {
        $entity1 = $this->createEntity('Account');
        $entity2 = $this->createEntity('Account');
        $entity3 = $this->createEntity('Account');

        $collection1 = new EntityCollection([$entity1]);
        $collection2 = new EntityCollection([$entity2, $entity3]);

        $collection = new EntityCollection([
            ...iterator_to_array($collection1),
            ...iterator_to_array($collection2),
        ]);

        $this->assertEquals(3, $collection->count());
    }

    public function testEntityCollectionUnset(): void
    {
        $entity1 = $this->createEntity('Account');
        $entity2 = $this->createEntity('Account');

        $collection = new EntityCollection([$entity1]);

        $collection[] = $entity2;
        unset($collection[1]);

        $this->assertEquals(1, $collection->count());
    }
}
