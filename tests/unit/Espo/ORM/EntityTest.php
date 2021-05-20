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

namespace tests\unit\Espo\ORM;

use Espo\ORM\{
    Metadata,
    MetadataDataProvider,
    Defs,
    Defs\DefsData,
    BaseEntity,
};

use tests\unit\testData\DB\Job;

use Espo\Core\ORM\EntityManager;

require_once 'tests/unit/testData/DB/Entities.php';

class EntityTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
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

    protected function tearDown() : void
    {
    }

    protected function createEntity(string $entityType, ?string $className = null)
    {
        $defs = $this->metadata->get($entityType);

        $classNameToUse = $className ?? BaseEntity::class;

        $entity = new $classNameToUse($entityType, $defs, $this->entityManager);

        return $entity;
    }

    public function testIsAttributeChanged()
    {
        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('string', 'test');
        $this->assertFalse($job->isAttributeChanged('string'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('string', 'test');
        $job->set('string', 'hello');
        $this->assertTrue($job->isAttributeChanged('string'));

        $job = $this->createEntity('Job', Job::class);
        $job->set('string', 'hello');
        $this->assertTrue($job->isAttributeChanged('string'));

        $job = $this->createEntity('Job', Job::class);
        $job->set('string', null);
        $this->assertTrue($job->isAttributeChanged('string'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['2', '1']);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['1', '2']);
        $this->assertFalse($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['1', 2]);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('array', [
            (object) ['1' => 'v1']
        ]);
        $job->set('array', [
            (object) ['1' => 'v1']
        ]);
        $this->assertFalse($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('array', [
            (object) ['k1' => 'v1']
        ]);
        $job->set('array', [
            (object) ['k1' => 'v2']
        ]);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->set('array', [
            (object) ['k1' => 'v1']
        ]);
        $job->setAsFetched();
        $job->set('array', [
            (object) ['k1' => 'v1', 'k2' => 'v2'],
        ]);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $v = [
            (object) ['k1' => 'v1']
        ];
        $job->setFetched('array', $v);
        $v[0]->k2 = 'v2';
        $job->set('array', $v);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['1', '2', '3']);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->set('array', ['1', '2', '3']);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->set('array', null);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('array', null);
        $this->assertFalse($job->isAttributeChanged('array'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('arrayUnordered', ['1', '2']);
        $job->set('arrayUnordered', ['2', '1']);
        $this->assertFalse($job->isAttributeChanged('arrayUnordered'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('arrayUnordered', ['1', '2']);
        $job->set('arrayUnordered', ['1', '2']);
        $this->assertFalse($job->isAttributeChanged('arrayUnordered'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('arrayUnordered', ['1', '2']);
        $job->set('arrayUnordered', ['1', '2', '3']);
        $this->assertTrue($job->isAttributeChanged('arrayUnordered'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('arrayUnordered', ['1', '2']);
        $job->set('arrayUnordered', null);
        $this->assertTrue($job->isAttributeChanged('arrayUnordered'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('object', (object) ['a1' => 'value-1']);
        $job->set('object', (object) ['a1' => 'value-1']);
        $this->assertFalse($job->isAttributeChanged('object'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('object', (object) ['a1' => 'value-1']);
        $job->set('object', ['a1' => 'value-1']);
        $this->assertFalse($job->isAttributeChanged('object'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('object', (object) ['1' => '1']);
        $job->set('object', (object) ['1' => 1]);
        $this->assertTrue($job->isAttributeChanged('object'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('object', (object) [
            'k1' => (object) [
                'k11' => 'v1'
            ]
        ]);
        $job->set('object', (object) [
            'k1' => (object) [
                'k11' => 'v2'
            ]
        ]);
        $this->assertTrue($job->isAttributeChanged('object'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('object', (object) [
            'k1' => [
                'k11' => 'v1'
            ]
        ]);
        $job->set('object', (object) [
            'k1' => (object) [
                'k11' => 'v1'
            ]
        ]);
        $this->assertTrue($job->isAttributeChanged('object'));

        $job = $this->createEntity('Job', Job::class);
        $job->setFetched('object', [
            'k1' => [
                'k11' => 'v1'
            ]
        ]);
        $job->set('object', (object) [
            'k1' => (object) [
                'k11' => 'v1'
            ]
        ]);
        $this->assertFalse($job->isAttributeChanged('object'));
    }

    public function testCloningObject() : void
    {
        $original = (object) [
            'k1' => (object) [
                'k11' => 'v1'
            ]
        ];

        $job = $this->createEntity('Job', Job::class);

        $job->set('object', $original);

        $gotten = $job->get('object');

        $this->assertEquals($gotten, $original);
        $this->assertNotSame($gotten, $original);
        $this->assertNotSame($gotten->k1, $original->k1);

        $this->assertEquals($job->getFromContainerOriginal('object'), $original);
        $this->assertNotSame($job->getFromContainerOriginal('object'), $original);

    }

    public function testEmptyArray() : void
    {
        $job = $this->createEntity('Job', Job::class);

        $job->set('array', []);

        $this->assertEquals([], $job->get('array'));
    }

    public function testEmptyObject() : void
    {
        $job = $this->createEntity('Job', Job::class);

        $job->set('object', (object) []);

        $this->assertEquals((object) [], $job->get('object'));
    }

    public function testCloningArray() : void
    {
        $original =  [
            (object) [
                'k1' => 'v1',
            ]
        ];

        $job = $this->createEntity('Job', Job::class);

        $job->set('array', $original);

        $gotten = $job->get('array');

        $this->assertEquals($gotten, $original);
        $this->assertNotSame($gotten[0], $original[0]);

        $this->assertEquals($job->getFromContainerOriginal('array'), $original);
        $this->assertNotSame($job->getFromContainerOriginal('array'), $original);
    }

    public function testSetForeign()
    {
        $entity = $this->createEntity('Comment');

        $entity->set([
            'postName' => 'test',
        ]);

        $this->assertEquals('test', $entity->get('postName'));
    }

    public function testSetJsonObject()
    {
        $entity = $this->createEntity('Test');

        $value = '{"test": "1"}';

        $entity->set([
            'object' => '{"test": "1"}',
        ]);

        $this->assertEquals(json_decode($value), $entity->get('object'));
    }

    public function testSetWrongType()
    {
        $entity = $this->createEntity('Test');

        $entity->set([
            'int' => '1',
        ]);

        $this->assertEquals(1, $entity->get('int'));

        $entity->set('int', '1');

        $this->assertEquals(1, $entity->get('int'));
    }
}
