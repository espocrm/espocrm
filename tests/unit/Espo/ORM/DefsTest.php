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

namespace tests\unit\Espo\ORM;

use Espo\ORM\Defs\Defs;
use Espo\ORM\Metadata;
use Espo\ORM\MetadataDataProvider;

use RuntimeException;

class DefsTest extends \PHPUnit\Framework\TestCase
{
    protected ?MetadataDataProvider $metadataDataProvider = null;
    protected ?Metadata $metadata = null;
    protected ?Defs $defs = null;

    protected function setUp() : void
    {}

    protected function initMetadata(array $data)
    {
        $this->metadataDataProvider = $this->createMock(MetadataDataProvider::class);

        $this->metadataDataProvider
            ->expects($this->any())
            ->method('get')
            ->willReturn($data);

        $this->metadata = new Metadata($this->metadataDataProvider);

        $this->defs = $this->metadata->getDefs();
    }

    public function testEntity1()
    {
        $data = [
            'Test1' => [
                'attributes' => [
                ],
                'test' => '1',
            ],
        ];

        $this->initMetadata($data);

        $this->assertEquals(['Test1'], $this->defs->getEntityTypeList());
        $this->assertEquals('Test1', $this->defs->getEntityList()[0]->getName());
        $this->assertEquals('Test1', $this->defs->getEntity('Test1')->getName());
        $this->assertEquals('1', $this->defs->getEntity('Test1')->getParam('test'));
        $this->assertTrue($this->defs->hasEntity('Test1'));
        $this->assertFalse($this->defs->hasEntity('Test2'));
        $this->assertNotNull($this->defs->tryGetEntity('Test1'));
        $this->assertNull($this->defs->tryGetEntity('Test2'));
    }

    public function testEntityNotExisting()
    {
        $data = [
            'Test1' => [
                'attributes' => [
                ],
            ],
        ];

        $this->initMetadata($data);

        $this->expectException(RuntimeException::class);

        $this->defs->getEntity('Test2');
    }

    public function testAttribute1()
    {
        $data = [
            'Test' => [
                'attributes' => [
                    'a1' => [
                        'type' => 'varchar',
                    ],
                    'a2' => [
                        'type' => 'int',
                        'notStorable' => true,
                        'len' => 20,
                    ],
                ],
            ],
        ];

        $this->initMetadata($data);

        $entityDefs = $this->defs->getEntity('Test');
        $this->assertTrue($entityDefs->hasAttribute('a1'));
        $this->assertFalse($entityDefs->hasAttribute('aNotExisting'));
        $this->assertNotNull($entityDefs->tryGetAttribute('a1'));
        $this->assertNull($entityDefs->tryGetAttribute('aNotExisting'));
        $this->assertEquals(['a1', 'a2'], $entityDefs->getAttributeNameList());
        $this->assertEquals('a1', $entityDefs->getAttributeList()[0]->getName());
        $this->assertEquals('a1', $entityDefs->getAttribute('a1')->getName());
        $this->assertEquals('a2', $entityDefs->getAttribute('a2')->getName());
        $this->assertEquals('varchar', $entityDefs->getAttribute('a1')->getType());
        $this->assertEquals('int', $entityDefs->getAttribute('a2')->getType());
        $this->assertEquals(false, $entityDefs->getAttribute('a1')->isNotStorable());
        $this->assertEquals(true, $entityDefs->getAttribute('a2')->isNotStorable());
        $this->assertEquals(null, $entityDefs->getAttribute('a1')->getLength());
        $this->assertEquals(20, $entityDefs->getAttribute('a2')->getLength());
        $this->assertTrue($entityDefs->getAttribute('a1')->hasParam('type'));
        $this->assertEquals('varchar', $entityDefs->getAttribute('a1')->getParam('type'));
        $this->assertFalse($entityDefs->getAttribute('a1')->hasParam('len'));
    }

    public function testAttributeNotExisting()
    {
        $data = [
            'Test' => [
                'attributes' => [
                ],
            ],
        ];

        $this->initMetadata($data);

        $this->expectException(RuntimeException::class);

        $this->defs->getEntity('Test1')->getAttribute('a1');
    }

    public function testRelation1()
    {
        $data = [
            'Test' => [
                'relations' => [
                    'r1' => [
                        'type' => 'belongsTo',
                        'foreign' => 'f1',
                        'entity' => 'Foreign1',
                        'key' => 'r1Id',
                        'foreignKey' => 'id',
                    ],
                    'r2' => [
                        'type' => 'manyMany',
                        'relationName' => 'r2Name',
                        'midKeys' => ['k1', 'k2'],
                        'foreign' => 'f2',
                        'conditions' => ['type' => 'Test'],
                    ],
                ],
            ],
        ];

        $this->initMetadata($data);

        $entityDefs = $this->defs->getEntity('Test');

        $this->assertNotNull($entityDefs->tryGetRelation('r1'));
        $this->assertEquals(['r1', 'r2'], $entityDefs->getRelationNameList());
        $this->assertEquals('r1', $entityDefs->getRelation('r1')->getName());
        $this->assertEquals('manyMany', $entityDefs->getRelation('r2')->getType());
        $this->assertTrue($entityDefs->getRelation('r2')->isManyToMany());
        $this->assertTrue($entityDefs->getRelation('r1')->hasForeignRelationName());
        $this->assertEquals('f1', $entityDefs->getRelation('r1')->getForeignRelationName());
        $this->assertFalse($entityDefs->getRelation('r1')->hasRelationshipName());
        $this->assertEquals('r2Name', $entityDefs->getRelation('r2')->getRelationshipName());
        $this->assertEquals('Foreign1', $entityDefs->getRelation('r1')->getForeignEntityType());
        $this->assertEquals('r1Id', $entityDefs->getRelation('r1')->getKey());
        $this->assertEquals('id', $entityDefs->getRelation('r1')->getForeignKey());
        $this->assertEquals('k1', $entityDefs->getRelation('r2')->getMidKey());
        $this->assertEquals('k2', $entityDefs->getRelation('r2')->getForeignMidKey());
        $this->assertEquals(['type' => 'Test'], $entityDefs->getRelation('r2')->getConditions());
    }

    public function testRelationNotExisting()
    {
        $data = [
            'Test' => [
                'attributes' => [],
            ],
        ];

        $this->initMetadata($data);

        $this->assertNull($this->defs->getEntity('Test')->tryGetRelation('r1'));

        $this->expectException(RuntimeException::class);

        $this->defs->getEntity('Test')->getRelation('r1');
    }

    public function testGetIndex1()
    {
        $data = [
            'Test' => [
                'indexes' => [
                    'i1' => [
                        'type' => 'unique',
                        'columns' => ['c1'],
                        'key' => 'I_1',
                    ],
                    'i2' => [
                        'type' => 'index',
                        'columns' => ['c1', 'c2'],
                        'key' => 'I_2',
                    ],
                ],
            ],
        ];

        $this->initMetadata($data);

        $entityDefs = $this->defs->getEntity('Test');

        $this->assertEquals(['i1', 'i2'], $entityDefs->getIndexNameList());

        $this->assertEquals('i1', $entityDefs->getIndex('i1')->getName());

        $this->assertTrue($entityDefs->getIndex('i1')->isUnique());
        $this->assertFalse($entityDefs->getIndex('i2')->isUnique());

        $this->assertEquals(['c1'], $entityDefs->getIndex('i1')->getColumnList());

        $this->assertEquals('I_1', $entityDefs->getIndex('i1')->getKey());
    }


    public function testIndexNotExisting()
    {
        $data = [
            'Test' => [
                'attributes' => [],
            ],
        ];

        $this->initMetadata($data);

        $this->assertNull($this->defs->getEntity('Test')->tryGetIndex('i1'));

        $this->expectException(RuntimeException::class);

        $this->defs->getEntity('Test')->getIndex('i1');
    }

    public function testGetField1()
    {
        $data = [
            'Test' => [
                'fields' => [
                    'f1' => [
                        'type' => 'varchar',
                        'length' => 100,
                    ],
                    'f2' => [
                        'type' => 'enum',
                        'notStorable' => true,
                    ],
                ],
            ],
        ];

        $this->initMetadata($data);

        $entityDefs = $this->defs->getEntity('Test');

        $this->assertEquals(['f1', 'f2'], $entityDefs->getFieldNameList());

        $this->assertNotNull($entityDefs->tryGetField('f1'));
        $this->assertNull($entityDefs->tryGetField('fBad'));
        $this->assertTrue($entityDefs->hasField('f1'));
        $this->assertFalse($entityDefs->hasField('fBad'));
        $this->assertEquals('varchar', $entityDefs->getField('f1')->getType());
        $this->assertEquals('f1', $entityDefs->getField('f1')->getName());
        $this->assertEquals(100, $entityDefs->getField('f1')->getParam('length'));
        $this->assertTrue($entityDefs->getField('f2')->isNotStorable());
    }

    public function testFieldNotExisting()
    {
        $data = [
            'Test' => [
                'attributes' => [],
            ],
        ];

        $this->initMetadata($data);

        $this->expectException(RuntimeException::class);

        $this->defs->getEntity('Test')->getField('f1');
    }

    public function testClearCache()
    {
        $data = [
            'Test' => [
                'attributes' => [
                    'a1' => [
                        'type' => 'varchar',
                    ],
                    'a2' => [
                        'type' => 'int',
                        'notStorable' => true,
                        'len' => 20,
                    ],
                ],
            ],
        ];

        $this->initMetadata($data);

        $entityDefs = $this->defs->getEntity('Test');

        $this->assertEquals('varchar', $entityDefs->getAttribute('a1')->getType());

        $attribute = $entityDefs->getAttribute('a1');

        $this->metadata->updateData();

        $entityDefsAfter = $this->defs->getEntity('Test');

        $this->assertEquals('varchar', $entityDefsAfter->getAttribute('a1')->getType());

        $attributeAfter = $entityDefsAfter->getAttribute('a1');

        $this->assertNotSame($entityDefs, $entityDefsAfter);
        $this->assertNotSame($attribute, $attributeAfter);
    }
}
