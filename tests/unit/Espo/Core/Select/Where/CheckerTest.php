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

namespace tests\unit\Espo\Core\Select\Where;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Select\Where\Checker;
use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\Params;
use Espo\ORM\BaseEntity as Entity;
use Espo\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class CheckerTest extends TestCase
{
    /** @var Checker|null */
    protected $checker = null;
    /** @var EntityManager|null */
    protected $entityManager = null;
    /** @var Acl|null  */
    protected $acl = null;

    protected ?string $entityType = null;
    protected ?string $foreignEntityType = null;

    private $entity;
    private $params;

    protected function setUp() : void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->acl = $this->createMock(Acl::class);

        $this->entityType = 'Test';
        $this->foreignEntityType = 'TestForeign';

        $this->checker = new Checker(
            $this->entityType,
            $this->entityManager,
            $this->acl,
        );

        $this->params = $this->createMock(Params::class);
        $this->entity = $this->createMock(Entity::class);
        $foreignEntity = $this->createMock(Entity::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getNewEntity')
            ->willReturnMap([
                [$this->entityType, $this->entity],
                [$this->foreignEntityType, $foreignEntity],
            ]);
    }

    /** @noinspection PhpUnhandledExceptionInspection */
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
            ->expects(self::any())
            ->method('hasAttribute')
            ->willReturnMap([
                ['test1', true],
                ['test2', true],
            ]);

        $this->entity
            ->expects($this->once())
            ->method('hasRelation')
            ->with('test3')
            ->willReturn(true);

        $this->checker->check($item, $this->params);
    }

    /** @noinspection PhpUnhandledExceptionInspection */
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
            ->expects(self::any())
            ->method('hasAttribute')
            ->willReturnMap([
                ['test1', false]
            ]);

        $this->expectException(BadRequest::class);

        $this->checker->check($item, $this->params);
    }

    /** @noinspection PhpUnhandledExceptionInspection */
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

    /** @noinspection PhpUnhandledExceptionInspection */
    public function testPermissions1()
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
            ->method('getScopeForbiddenAttributeList')
            ->willReturnMap([
                [$this->entityType, Table::ACTION_READ, Table::LEVEL_NO, []],
                [$this->foreignEntityType, Table::ACTION_READ, Table::LEVEL_NO, []],
            ]);

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
            ->with($this->foreignEntityType)
            ->willReturn(true);

        $foreign = $this->createMock(Entity::class);

        $this->entityManager
            ->expects($this->any())
            ->method('getEntityById')
            ->willReturnMap([
                [$this->foreignEntityType, 'value3', $foreign]
            ]);

        $this->acl
            ->expects($this->any())
            ->method('checkEntityRead')
            ->willReturnMap([
                [$foreign, true]
            ]);

        $this->checker->check($item, $this->params);

        $this->assertTrue(true);
    }

    public function testPermissions2()
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

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->checker->check($item, $this->params);
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function testPermissions3()
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

    /** @noinspection PhpUnhandledExceptionInspection */
    public function testPermissions4()
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

    /** @noinspection PhpUnhandledExceptionInspection */
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
