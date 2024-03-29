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
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Select as Query;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

class ScannerTest extends \PHPUnit\Framework\TestCase
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

    public function testApplyLeftJoins1()
    {
        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test1',
                    'value' => 'value1',
                ],
                [
                    'type' => 'equals',
                    'attribute' => 'TEST:(link2.test2)',
                    'value' => 'value2',
                ],
                [
                    'type' => 'linkedWith',
                    'attribute' => 'link3',
                    'value' => 'value3',
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
            ->method('leftJoin')
            ->withConsecutive(
                ['link2'],
                ['link3'],
                ['link4']
            );

        $this->queryBuilder
            ->expects($this->once())
            ->method('distinct');

        $this->entity
            ->expects($this->any())
            ->method('getAttributeType')
            ->will(
                $this->returnValueMap(
                    [
                        ['test1', Entity::VARCHAR],
                        ['test4', Entity::FOREIGN],
                    ]
                )
            );

        $this->entity
            ->expects($this->any())
            ->method('getRelationType')
            ->will(
                $this->returnValueMap(
                    [
                        ['link2', Entity::HAS_MANY],
                        ['link3', Entity::BELONGS_TO],
                    ]
                )
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
}
