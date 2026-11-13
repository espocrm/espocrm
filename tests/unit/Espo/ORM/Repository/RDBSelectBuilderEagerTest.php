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

namespace tests\unit\Espo\ORM\Repository;

use Espo\ORM\Defs\Defs;
use Espo\ORM\Defs\EntityDefs;
use Espo\ORM\EntityManager;
use Espo\ORM\Mapper\BaseMapper;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Relation\RelationsMap;
use Espo\ORM\Repository\RDBRepository;
use Espo\ORM\Repository\RDBSelectBuilder;
use Espo\ORM\Repository\Util;
use Espo\ORM\SthCollection;
use LogicException;
use PHPUnit\Framework\TestCase;

class RDBSelectBuilderEagerTest extends TestCase
{
    private ?EntityManager $entityManager = null;
    private ?BaseMapper $mapper = null;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->mapper = $this->createMock(BaseMapper::class);

        $entityDefsMap = [
            'Comment' => EntityDefs::fromRaw([
                'attributes' => [
                    'id' => ['type' => 'id'],
                    'name' => ['type' => 'varchar'],
                    'postId' => ['type' => 'foreignId'],
                ],
                'relations' => [
                    'post' => [
                        'type' => 'belongsTo',
                        'entity' => 'Post',
                        'key' => 'postId',
                        'foreignKey' => 'id',
                    ],
                ],
            ], 'Comment'),
            'Post' => EntityDefs::fromRaw([
                'attributes' => [
                    'id' => ['type' => 'id'],
                    'name' => ['type' => 'varchar'],
                    'createdAt' => ['type' => 'datetime'],
                ],
                'relations' => [
                    'comments' => [
                        'type' => 'hasMany',
                        'entity' => 'Comment',
                        'foreignKey' => 'postId',
                    ],
                ],
            ], 'Post'),
        ];

        $defs = $this->createMock(Defs::class);

        $defs->method('getEntity')
            ->willReturnCallback(fn ($entityType) => $entityDefsMap[$entityType]);

        $this->entityManager->method('getDefs')->willReturn($defs);
        $this->entityManager->method('getMapper')->willReturn($this->mapper);
        $this->entityManager->method('getRDBRepository')->willReturn($this->createMock(RDBRepository::class));
        $this->entityManager->method('getRepository')->willReturn($this->createMock(RDBRepository::class));
    }

    public function testEagerAddsJoinAndAliasedSelect(): void
    {
        $builder = $this->createBuilder('Comment')->eager('post', ['name']);

        $query = $this->findAndCaptureQuery($builder);

        $expected = SelectBuilder::create()
            ->from('Comment')
            ->select('*')
            ->leftJoin('post')
            ->select('post.name', Util::composeEagerSelectAlias('post', 'name'))
            ->select('post.id',Util::composeEagerSelectAlias('post', 'id'))
            ->build();

        $this->assertEquals($expected->getRaw(), $query->getRaw());
    }

    public function testEagerMergesAttributesAndKeepsExistingSelectAndJoin(): void
    {
        $builder = $this->createBuilder('Comment')
            ->select(['id', 'name'])
            ->leftJoin('post')
            ->eager('post', ['name'])
            ->eager('post', ['name', 'createdAt']);

        $query = $this->findAndCaptureQuery($builder);


        $expected = SelectBuilder::create()
            ->from('Comment')
            ->select(['id', 'name'])
            ->leftJoin('post')
            ->select('post.name', Util::composeEagerSelectAlias('post', 'name'))
            ->select('post.createdAt', Util::composeEagerSelectAlias('post', 'createdAt'))
            ->select('post.id', Util::composeEagerSelectAlias('post', 'id'))
            ->build();

        $this->assertEquals($expected->getRaw(), $query->getRaw());
    }

    public function testEagerUnknownRelation(): void
    {
        $this->expectException(LogicException::class);

        $this->createBuilder('Comment')->eager('notExisting', ['name']);
    }

    public function testEagerNotSupportedRelationType(): void
    {
        $this->expectException(LogicException::class);

        $this->createBuilder('Post')->eager('comments', ['name']);
    }

    public function testEagerUnknownAttribute(): void
    {
        $this->expectException(LogicException::class);

        $this->createBuilder('Comment')->eager('post', ['notExisting']);
    }

    private function createBuilder(string $entityType): RDBSelectBuilder
    {
        return new RDBSelectBuilder($this->entityManager, $entityType);
    }

    private function findAndCaptureQuery(RDBSelectBuilder $builder): Select
    {
        $captured = null;

        $this->mapper
            ->expects($this->once())
            ->method('select')
            ->willReturnCallback(function (Select $query) use (&$captured) {
                $captured = $query;

                /** @var RelationsMap $relationsMap */
                $relationsMap = $this->createMock(RelationsMap::class);

                return SthCollection::fromQuery(
                    query: $query,
                    entityManager: $this->entityManager,
                    relationsMap: $relationsMap,
                );
            });

        $collection = $builder->sth()->find();

        $this->assertInstanceOf(SthCollection::class, $collection);
        $this->assertInstanceOf(Select::class, $captured);

        return $captured;
    }
}
