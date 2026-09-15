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

namespace tests\unit\Espo\Core\Select\Bool;

use Espo\Core\Select\Bool\Filters\OnlyMy;
use Espo\Core\Select\Bool\Filters\Shared;
use Espo\Core\Select\Helpers\FieldHelper;
use Espo\Entities\User;
use Espo\ORM\Defs;
use Espo\ORM\Defs\EntityDefs;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Query\Part\Where\OrGroupBuilder;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Type\RelationType;
use PHPUnit\Framework\TestCase;

class FiltersTest extends TestCase
{
    public function testOnlyMyUsesMiddleConditions(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('user-id');
        $user->method('isPortal')->willReturn(false);

        $fieldHelper = $this->createMock(FieldHelper::class);
        $fieldHelper->method('hasAssignedUsersField')->willReturn(true);

        $filter = new OnlyMy('Test', $user, $fieldHelper, $this->createDefs('assignedUsers', 'entityUser'));

        $joinConditions = $this->applyAndGetJoinConditions($filter);

        $this->assertEquals('Test', $joinConditions['assignedUsersMiddle.entityType'] ?? null);
    }

    public function testSharedUsesMiddleConditions(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('user-id');

        $fieldHelper = $this->createMock(FieldHelper::class);
        $fieldHelper->method('hasCollaboratorsField')->willReturn(true);

        $filter = new Shared('Test', $user, $fieldHelper, $this->createDefs('collaborators', 'entityCollaborator'));

        $joinConditions = $this->applyAndGetJoinConditions($filter);

        $this->assertEquals('Test', $joinConditions['collaboratorsMiddle.entityType'] ?? null);
    }

    private function createDefs(string $link, string $relationName): Defs
    {
        $relationDefs = RelationDefs::fromRaw([
            'type' => RelationType::MANY_MANY,
            'entity' => User::ENTITY_TYPE,
            'relationName' => $relationName,
            'midKeys' => ['entityId', 'userId'],
            'conditions' => ['entityType' => 'Test'],
        ], $link);

        $entityDefs = $this->createMock(EntityDefs::class);
        $entityDefs->method('getRelation')->with($link)->willReturn($relationDefs);

        $defs = $this->createMock(Defs::class);
        $defs->method('getEntity')->with('Test')->willReturn($entityDefs);

        return $defs;
    }

    /**
     * @return array<string, mixed>
     */
    private function applyAndGetJoinConditions(OnlyMy|Shared $filter): array
    {
        $orGroupBuilder = new OrGroupBuilder();

        $filter->apply(SelectBuilder::create()->from('Test'), $orGroupBuilder);

        $rawValue = $orGroupBuilder->build()->getRawValue();

        $subQuery = $rawValue['id=s'] ?? null;

        $this->assertInstanceOf(Select::class, $subQuery);

        $joins = $subQuery->getRaw()['joins'] ?? [];

        $this->assertCount(1, $joins);

        return $joins[0][2];
    }
}
