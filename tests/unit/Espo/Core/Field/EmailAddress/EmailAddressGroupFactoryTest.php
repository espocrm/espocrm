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

namespace tests\unit\Espo\Core\Field\EmailAddress;

use Espo\Core\Field\EmailAddress\EmailAddressGroupFactory;
use Espo\Core\Utils\Metadata;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Espo\Repositories\EmailAddress as EmailAddressRepository;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class EmailAddressGroupFactoryTest extends TestCase
{
    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var EmailAddressRepository
     */
    private $emailAddressRepository;

    /**
     * @var EmailAddressGroupFactory
     */
    private $factory;

    public function setUp() : void
    {
        $this->metadata = $this->createMock(Metadata::class);

        $entityManager = $this->createMock(EntityManager::class);

        $this->emailAddressRepository = $this->createMock(EmailAddressRepository::class);

        $this->factory = new EmailAddressGroupFactory($this->metadata, $entityManager);

        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with('EmailAddress')
            ->willReturn($this->emailAddressRepository);
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

    private function initEmailAddressRepository(Entity $entity, array $dataList) : void
    {
        $this->emailAddressRepository
            ->expects($this->once())
            ->method('getEmailAddressData')
            ->with($entity)
            ->willReturn($dataList);
    }

    public function testIsCreatableFromEntityTrue() : void
    {
        $this->initField('Test', 'test', 'email');

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

    public function testCreateFromEmailAddressRepository() : void
    {
        $this->initField('Test', 'test', 'email');

        $entity = $this->createEntityMock('Test');

        $dataList = [
            (object) [
                'emailAddress' => 'ONE@test.com',
                'lower' => 'one@test.com',
                'primary' => true,
                'optOut' => false,
                'invalid' => false,
            ],
            (object) [
                'emailAddress' => 'TWO@test.com',
                'lower' => 'two@test.com',
                'primary' => false,
                'optOut' => false,
                'invalid' => true,
            ],
            (object) [
                'emailAddress' => 'THREE@test.com',
                'lower' => 'three@test.com',
                'primary' => false,
                'optOut' => true,
                'invalid' => false,
            ],
        ];

        $this->initEmailAddressRepository($entity, $dataList);

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
                    ['test', 'ONE@test.com'],
                ]
            );

        $group = $this->factory->createFromEntity($entity, 'test');

        $this->assertEquals(3, $group->getCount());

        $one = $group->getList()[0];
        $two = $group->getList()[1];
        $three = $group->getList()[2];

        $this->assertEquals('ONE@test.com', $one->getAddress());

        $this->assertTrue($two->isInvalid());

        $this->assertTrue($three->isOptedOut());

        $this->assertEquals('ONE@test.com', $group->getPrimary()->getAddress());
    }

    public function testCreateFromDataAttribute() : void
    {
        $this->initField('Test', 'test', 'email');

        $entity = $this->createEntityMock('Test');

        $dataList = [
            (object) [
                'emailAddress' => 'ONE@test.com',
                'lower' => 'one@test.com',
                'primary' => true,
                'optOut' => false,
                'invalid' => false,
            ],
            (object) [
                'emailAddress' => 'TWO@test.com',
                'lower' => 'two@test.com',
                'primary' => false,
                'optOut' => false,
                'invalid' => true,
            ],
            (object) [
                'emailAddress' => 'THREE@test.com',
                'lower' => 'three@test.com',
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

        $this->assertEquals('ONE@test.com', $one->getAddress());

        $this->assertTrue($two->isInvalid());

        $this->assertTrue($three->isOptedOut());

        $this->assertEquals('ONE@test.com', $group->getPrimary()->getAddress());
    }

    public function testCreateEmpty1() : void
    {
        $this->initField('Test', 'test', 'email');

        $entity = $this->createEntityMock('Test');

        $dataList = [];

        $this->initEmailAddressRepository($entity, $dataList);

        $group = $this->factory->createFromEntity($entity, 'test');

        $this->assertEquals(0, $group->getCount());
    }

    public function testCreateEmpty2() : void
    {
        $this->initField('Test', 'test', 'email');

        $entity = $this->createEntityMock('Test');

        $dataList = [];

        $this->initEmailAddressRepository($entity, $dataList);

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
