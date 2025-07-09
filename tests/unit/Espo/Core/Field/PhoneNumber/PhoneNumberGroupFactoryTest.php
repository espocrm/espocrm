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

namespace tests\unit\Espo\Core\Field\PhoneNumber;

use Espo\Core\{
    Field\PhoneNumber\PhoneNumberGroupFactory,
    Utils\Metadata,
};

use Espo\ORM\{
    EntityManager,
    Entity,
};

use Espo\Repositories\PhoneNumber as PhoneNumberRepository;

use RuntimeException;

class PhoneNumberGroupFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PhoneNumberRepository
     */
    private $phoneNumberRepository;

    /**
     * @var PhoneNumberGroupFactory
     */
    private $factory;

    public function setUp() : void
    {
        $this->metadata = $this->createMock(Metadata::class);

        $this->entityManager = $this->createMock(EntityManager::class);

        $this->phoneNumberRepository = $this->createMock(PhoneNumberRepository::class);

        $this->factory = new PhoneNumberGroupFactory($this->metadata, $this->entityManager);

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with('PhoneNumber')
            ->willReturn($this->phoneNumberRepository);
    }

    private function initField(string $entityType, string $field, string $type) : void
    {
        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->with(['entityDefs', $entityType, 'fields', $field, 'type'])
            ->willReturn($type);
    }

    private function createEntityMock(string $entityType) : Entity
    {
        $entity = $this->createMock(Entity::class);

        $entity
            ->expects($this->any())
            ->method('getEntityType')
            ->willReturn($entityType);

        return $entity;
    }

    private function initPhoneNumberRepository(Entity $entity, array $dataList) : void
    {
        $this->phoneNumberRepository
            ->expects($this->once())
            ->method('getPhoneNumberData')
            ->with($entity)
            ->willReturn($dataList);
    }

    public function testIsCreatableFromEntityTrue() : void
    {
        $this->initField('Test', 'test', 'phone');

        $entity = $this->createEntityMock('Test');

        $this->assertTrue($this->factory->isCreatableFromEntity($entity, 'test'));
    }

    public function testIsCreatableFromEntityBad() : void
    {
        $this->initField('Test', 'test', 'varchar');

        $entity = $this->createEntityMock('Test');

        $this->assertFalse($this->factory->isCreatableFromEntity($entity, 'test'));
    }

    public function testCreateException() : void
    {
        $this->initField('Test', 'test', 'varchar');

        $entity = $this->createEntityMock('Test');

        $this->expectException(RuntimeException::class);

        $this->factory->createFromEntity($entity, 'test');
    }

    public function testCreateFromPhoneNumberRepository() : void
    {
        $this->initField('Test', 'test', 'phone');

        $entity = $this->createEntityMock('Test');

        $dataList = [
            (object) [
                'phoneNumber' => '+1',
                'type' => 'Test-1',
                'primary' => true,
                'optOut' => false,
                'invalid' => false,
            ],
            (object) [
                'phoneNumber' => '+2',
                'type' => 'Test-2',
                'primary' => false,
                'optOut' => false,
                'invalid' => true,
            ],
            (object) [
                'phoneNumber' => '+3',
                'type' => 'Test-3',
                'primary' => false,
                'optOut' => true,
                'invalid' => false,
            ],
        ];

        $this->initPhoneNumberRepository($entity, $dataList);

        $entity
            ->method('has')
            ->willReturnMap(
                [
                    ['testData', false],
                    ['test', true],
                ]
            );

        $entity
            ->method('get')
            ->willReturnMap(
                [
                    ['testData', null],
                    ['test', '+1'],
                ]
            );

        $group = $this->factory->createFromEntity($entity, 'test');

        $this->assertEquals(3, $group->getCount());

        $one = $group->getList()[0];
        $two = $group->getList()[1];
        $three = $group->getList()[2];

        $this->assertEquals('+1', $one->getNumber());

        $this->assertTrue($two->isInvalid());

        $this->assertTrue($three->isOptedOut());

        $this->assertEquals('Test-1', $one->getType());

        $this->assertEquals('+1', $group->getPrimary()->getNumber());
    }

    public function testCreateFromDataAttribute() : void
    {
        $this->initField('Test', 'test', 'phone');

        $entity = $this->createEntityMock('Test');

        $dataList = [
            (object) [
                'phoneNumber' => '+1',
                'type' => '1',
                'primary' => true,
                'optOut' => false,
                'invalid' => false,
            ],
            (object) [
                'phoneNumber' => '+2',
                'type' => '2',
                'primary' => false,
                'optOut' => false,
                'invalid' => true,
            ],
            (object) [
                'phoneNumber' => '+3',
                'type' => '3',
                'primary' => false,
                'optOut' => true,
                'invalid' => false,
            ],
        ];

        $entity
            ->expects($this->once())
            ->method('has')
            ->with('testData')
            ->willReturn(true);

        $entity
            ->expects($this->once())
            ->method('get')
            ->with('testData')
            ->willReturn($dataList);

        $group = $this->factory->createFromEntity($entity, 'test');

        $this->assertEquals(3, $group->getCount());

        $one = $group->getList()[0];
        $two = $group->getList()[1];
        $three = $group->getList()[2];

        $this->assertEquals('+1', $one->getNumber());

        $this->assertTrue($two->isInvalid());

        $this->assertTrue($three->isOptedOut());

        $this->assertEquals('+1', $group->getPrimary()->getNumber());
    }

    public function testCreateEmpty1() : void
    {
        $this->initField('Test', 'test', 'phone');

        $entity = $this->createEntityMock('Test');

        $dataList = [];

        $this->initPhoneNumberRepository($entity, $dataList);

        $group = $this->factory->createFromEntity($entity, 'test');

        $this->assertEquals(0, $group->getCount());
    }

    public function testCreateEmpty2() : void
    {
        $this->initField('Test', 'test', 'phone');

        $entity = $this->createEntityMock('Test');

        $dataList = [];

        $this->initPhoneNumberRepository($entity, $dataList);

        $entity
            ->method('has')
            ->willReturnMap(
                [
                    ['testData', false],
                    ['test', true],
                ]
            );

        $entity
            ->method('get')
            ->willReturnMap(
                [
                    ['testData', null],
                    ['test', null],
                ]
            );

        $group = $this->factory->createFromEntity($entity, 'test');

        $this->assertEquals(0, $group->getCount());
    }
}
