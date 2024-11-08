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

namespace tests\unit\Espo\Core\Select\Where;

use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\Scanner;
use Espo\ORM\BaseEntity as Entity;
use Espo\ORM\Entity as OrmEntity;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Select as Query;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Type\RelationType;
use PHPUnit\Framework\TestCase;

class ScannerTest extends TestCase
{
    protected function setUp() : void
    {
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->scanner = new Scanner($this->entityManager);

        $this->entityType = 'Test';

        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->any())
            ->method('getFrom')
            ->willReturn($this->entityType);

        $this->queryBuilder
            ->expects($this->any())
            ->method('build')
            ->willReturn($query);

        $this->entity = $this->createMock(Entity::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getNewEntity')
            ->with($this->entityType)
            ->willReturn($this->entity);
    }

    public function testApplyLeftJoins1(): void
    {
        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test1',
                    'value' => 'value',
                ],
                [
                    'type' => 'equals',
                    'attribute' => 'TEST:(link2.test2)',
                    'value' => 'value',
                ],
                [
                    'type' => 'equals',
                    'attribute' => 'link3.test3',
                    'value' => 'value',
                ],
                [
                    'type' => 'equals',
                    'attribute' => 'test4',
                    'value' => 'value',
                ],
            ],
        ]);

        $this->entity
            ->expects($this->any())
            ->method('hasRelation')
            ->will(
                $this->returnValueMap([
                    ['link1', true],
                    ['link2', true],
                    ['link3', true],
                ])
            );

        $this->queryBuilder
            ->expects($this->exactly(3))
            ->method('leftJoin')
            ->withConsecutive(
                ['link2'],
                ['link3'],
                ['link4'],
            );

        /*$this->queryBuilder
            ->expects($this->once())
            ->method('distinct');*/

        $this->entity
            ->expects($this->any())
            ->method('getAttributeType')
            ->will(
                $this->returnValueMap([
                    ['test1', OrmEntity::VARCHAR],
                    ['test4', OrmEntity::FOREIGN],
                ])
            );

        $this->entity
            ->expects($this->any())
            ->method('getRelationType')
            ->will(
                $this->returnValueMap([
                    ['link2', OrmEntity::HAS_MANY],
                    ['link3', OrmEntity::BELONGS_TO],
                ])
            );

        $this->entity
            ->expects($this->any())
            ->method('getAttributeParam')
            ->will(
                $this->returnValueMap(
                    [
                        ['test4', 'relation', 'link4'],
                    ]
                )
            );

        $this->scanner->apply($this->queryBuilder, $item);
    }

    public function testHasRelatedMany(): void
    {
        $em = $this->createMock(EntityManager::class);
        $entity = $this->createMock(Entity::class);

        $em->expects($this->once())
            ->method('getNewEntity')
            ->willReturn($entity);

        $entity
            ->expects($this->any())
            ->method('hasRelation')
            ->will(
                $this->returnValueMap([
                    ['link2', true],
                    ['link3', true],
                ])
            );

        $entity
            ->expects($this->any())
            ->method('getRelationType')
            ->will(
                $this->returnValueMap([
                    ['link2', RelationType::HAS_MANY],
                    ['link3', RelationType::BELONGS_TO],
                ])
            );

        $scanner = new Scanner($em);

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test1',
                    'value' => 'value',
                ],
                [
                    'type' => 'equals',
                    'attribute' => 'TEST:(link2.test2)',
                    'value' => 'value',
                ],
                [
                    'type' => 'equals',
                    'attribute' => 'link3.test3',
                    'value' => 'value',
                ],
            ],
        ]);

        $this->assertTrue($scanner->hasRelatedMany('Test', $item));

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'link3.test3',
                    'value' => 'value',
                ],
            ],
        ]);

        $this->assertFalse($scanner->hasRelatedMany('Test', $item));
    }
}
