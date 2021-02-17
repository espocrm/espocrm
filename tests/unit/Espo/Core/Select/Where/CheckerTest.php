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

namespace tests\unit\Espo\Core\Select\Where;

use Espo\{
    Core\Exceptions\Forbidden,
    Core\Exceptions\BadRequest,
    Core\Select\Where\Checker,
    Core\Select\Where\Item,
    Core\Select\Where\Params,
    Core\Acl,
    ORM\EntityManager,
    ORM\BaseEntity as Entity,
};

class CheckerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->acl = $this->createMock(Acl::class);

        $this->entityType = 'Test';

        $this->foreignEntityType = 'Test';

        $this->checker = new Checker(
            $this->entityType,
            $this->entityManager,
            $this->acl
        );

        $this->params = $this->createMock(Params::class);

        $this->entity = $this->createMock(Entity::class);

        $this->foreignEntity = $this->createMock(Entity::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->entityType)
            ->willReturn($this->entity);

        $this->entityManager
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->foreignEntityType)
            ->willReturn($this->foreignEntity);
    }

    public function testAttributeExistence1()
    {
        $this->params
            ->expects($this->any())
            ->method('forbidComplexExpressions')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('applyPermissionCheck')
            ->willReturn(false);

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
                    'attribute' => 'TEST:(test2)',
                    'value' => 'value2',
                ],
                [
                    'type' => 'linkedWith',
                    'attribute' => 'test3',
                    'value' => 'value3',
                ],
            ],
        ]);

        $this->entity
            ->method('hasAttribute')
            ->withConsecutive(
                ['test1'],
                ['test2']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->entity
            ->expects($this->once())
            ->method('hasRelation')
            ->with('test3')
            ->willReturn(true);

        $this->checker->check($item, $this->params);
    }

    public function testAttributeExistence2()
    {
        $this->params
            ->expects($this->any())
            ->method('forbidComplexExpressions')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('applyPermissionCheck')
            ->willReturn(false);

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test1',
                    'value' => 'value1',
                ],
            ],
        ]);

        $this->entity
            ->method('hasAttribute')
            ->withConsecutive(
                ['test1']
            )
            ->willReturnOnConsecutiveCalls(
                false
            );

        $this->expectException(BadRequest::class);

        $this->checker->check($item, $this->params);
    }

    public function testAttributeExistence3()
    {
        $this->params
            ->expects($this->any())
            ->method('forbidComplexExpressions')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('applyPermissionCheck')
            ->willReturn(false);

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'linkedWith',
                    'attribute' => 'test3',
                    'value' => 'value3',
                ],
            ],
        ]);

        $this->entity
            ->expects($this->once())
            ->method('hasRelation')
            ->with('test3')
            ->willReturn(false);

        $this->expectException(BadRequest::class);

        $this->checker->check($item, $this->params);
    }

    public function testPrermissions1()
    {
        $this->params
            ->expects($this->any())
            ->method('forbidComplexExpressions')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('applyPermissionCheck')
            ->willReturn(true);

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
                    'attribute' => 'test3.test2',
                    'value' => 'value2',
                ],
                [
                    'type' => 'linkedWith',
                    'attribute' => 'test3',
                    'value' => 'value3',
                ],
            ],
        ]);

        $this->entity
            ->expects($this->any())
            ->method('hasAttribute')
            ->willReturn(true);

        $this->entity
            ->expects($this->any())
            ->method('hasRelation')
            ->with('test3')
            ->willReturn(true);

        $this->entity
            ->expects($this->any())
            ->method('getRelationParam')
            ->with('test3', 'entity')
            ->willReturn($this->foreignEntityType);

        $this->acl
            ->expects($this->any())
            ->method('checkScope')
            ->with($this->foreignEntityType)
            ->willReturn(true);

        $this->acl
            ->expects($this->any())
            ->method('getScopeForbiddenAttributeList')
            ->with($this->entityType)
            ->willReturn([]);

        $this->acl
            ->expects($this->any())
            ->method('getScopeForbiddenFieldList')
            ->with($this->entityType)
            ->willReturn([]);

        $this->acl
            ->expects($this->any())
            ->method('getScopeForbiddenLinkList')
            ->with($this->entityType)
            ->willReturn([]);

        $this->acl
            ->expects($this->any())
            ->method('checkScope')
            ->with($this->entityType)
            ->willReturn(true);

        $this->checker->check($item, $this->params);

        $this->assertTrue(true);
    }

    public function testPrermissions2()
    {
        $this->params
            ->expects($this->any())
            ->method('forbidComplexExpressions')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('applyPermissionCheck')
            ->willReturn(true);

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test1',
                    'value' => 'value1',
                ],
            ],
        ]);

        $this->entity
            ->expects($this->any())
            ->method('hasAttribute')
            ->willReturn(true);

        $this->entity
            ->expects($this->any())
            ->method('hasRelation')
            ->with('test3')
            ->willReturn(true);

        $this->acl
            ->expects($this->any())
            ->method('getScopeForbiddenAttributeList')
            ->with($this->entityType)
            ->willReturn(['test1']);

        $this->expectException(Forbidden::class);

        $this->checker->check($item, $this->params);
    }

    public function testPrermissions3()
    {
        $this->params
            ->expects($this->any())
            ->method('forbidComplexExpressions')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('applyPermissionCheck')
            ->willReturn(true);

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'test3.test2',
                    'value' => 'value2',
                ],
            ],
        ]);

        $this->entity
            ->expects($this->any())
            ->method('hasAttribute')
            ->willReturn(true);

        $this->entity
            ->expects($this->any())
            ->method('hasRelation')
            ->with('test3')
            ->willReturn(true);

        $this->entity
            ->expects($this->any())
            ->method('getRelationParam')
            ->with('test3', 'entity')
            ->willReturn($this->foreignEntityType);

        $this->acl
            ->expects($this->any())
            ->method('checkScope')
            ->with($this->foreignEntityType)
            ->willReturn(false);

        $this->expectException(Forbidden::class);

        $this->checker->check($item, $this->params);
    }

    public function testPrermissions4()
    {
        $this->params
            ->expects($this->any())
            ->method('forbidComplexExpressions')
            ->willReturn(false);

        $this->params
            ->expects($this->any())
            ->method('applyPermissionCheck')
            ->willReturn(true);

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'linkedWith',
                    'attribute' => 'test3',
                    'value' => 'value3',
                ],
            ],
        ]);

        $this->entity
            ->expects($this->any())
            ->method('hasAttribute')
            ->willReturn(true);

        $this->entity
            ->expects($this->any())
            ->method('hasRelation')
            ->with('test3')
            ->willReturn(true);

        $this->entity
            ->expects($this->any())
            ->method('getRelationParam')
            ->with('test3', 'entity')
            ->willReturn($this->foreignEntityType);

        $this->acl
            ->expects($this->any())
            ->method('checkScope')
            ->with($this->foreignEntityType)
            ->willReturn(false);

        $this->expectException(Forbidden::class);

        $this->checker->check($item, $this->params);
    }

    public function testComplexExpressions1()
    {
        $this->params
            ->expects($this->any())
            ->method('forbidComplexExpressions')
            ->willReturn(true);

        $this->params
            ->expects($this->any())
            ->method('applyPermissionCheck')
            ->willReturn(false);

        $item = Item::fromRaw([
            'type' => 'and',
            'value' => [
                [
                    'type' => 'equals',
                    'attribute' => 'TEST:(test2)',
                    'value' => 'value2',
                ]
            ],
        ]);

        $this->expectException(Forbidden::class);

        $this->checker->check($item, $this->params);
    }
}
