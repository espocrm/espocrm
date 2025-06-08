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

namespace Espo\Core\Select\Helpers;

use Espo\Core\Name\Field;
use Espo\Entities\User;
use Espo\ORM\Defs;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Type\RelationType;
use LogicException;
use RuntimeException;

/**
 * @since 9.0.0
 */
class RelationQueryHelper
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function prepareAssignedUsersWhere(string $entityType, string $userId): WhereItem
    {
        return $this->prepareRelatedUsersWhere(
            $entityType,
            $userId,
            Field::ASSIGNED_USERS,
            User::RELATIONSHIP_ENTITY_USER
        );
    }

    public function prepareCollaboratorsWhere(string $entityType, string $userId): WhereItem
    {
        return $this->prepareRelatedUsersWhere(
            $entityType,
            $userId,
            Field::COLLABORATORS,
            User::RELATIONSHIP_ENTITY_COLLABORATOR
        );
    }

    private function prepareRelatedUsersWhere(
        string $entityType,
        string $userId,
        string $field,
        string $relationship
    ): WhereItem {

        $relationDefs = $this->defs
            ->getEntity($entityType)
            ->getRelation($field);

        $middleEntityType = ucfirst($relationDefs->getRelationshipName());
        $key1 = $relationDefs->getMidKey();
        $key2 = $relationDefs->getForeignMidKey();

        $joinWhere = [
            "m.$key1:" => Attribute::ID,
            'm.deleted' => false,
        ];

        if ($middleEntityType === $relationship) {
            $joinWhere['m.entityType'] = $entityType;
        }

        $subQuery = QueryBuilder::create()
            ->select(Attribute::ID)
            ->from($entityType)
            ->leftJoin($middleEntityType, 'm', $joinWhere)
            ->where(["m.$key2" => $userId])
            ->build();

        return Condition::in(
            Expression::column('id'),
            $subQuery
        );
    }

    /**
     * @param string|string[] $id
     *
     * @since 9.1.6
     */
    public function prepareLinkWhereMany(string $entityType, string $link, string|array $id): WhereItem
    {
        $defs = $this->defs
            ->getEntity($entityType)
            ->getRelation($link);

        if (!in_array($defs->getType(), [RelationType::HAS_MANY, RelationType::MANY_MANY])) {
            throw new LogicException("Only many-many and has-many allowed.");
        }

        $builder = SelectBuilder::create()->from($entityType);

        $whereItem = $this->prepareLinkWhere($defs, $entityType, $id, $builder);

        if (!$whereItem) {
            throw new RuntimeException("Not supported relationship.");
        }

        return $whereItem;
    }

    /**
     * @internal Signature can be changed in future.
     *
     * @param string|string[] $id
     */
    public function prepareLinkWhere(
        Defs\RelationDefs $defs,
        string $entityType,
        string|array $id,
        QueryBuilder $queryBuilder
    ): ?WhereItem {

        $type = $defs->getType();
        $link = $defs->getName();

        if (
            $type === RelationType::BELONGS_TO ||
            $type === RelationType::HAS_ONE
        ) {
            if ($type === RelationType::HAS_ONE) {
                $queryBuilder->leftJoin($link);
            }

            return WhereClause::fromRaw([$link . 'Id' => $id]);
        }

        if ($type === RelationType::BELONGS_TO_PARENT) {
            return WhereClause::fromRaw([
                'parentType' => $entityType,
                'parentId' => $id,
            ]);
        }

        if ($type === RelationType::MANY_MANY) {
            return Cond::in(
                Expr::column(Attribute::ID),
                QueryBuilder::create()
                    ->from(ucfirst($defs->getRelationshipName()), 'm')
                    ->select($defs->getMidKey())
                    ->where([$defs->getForeignMidKey() => $id])
                    ->build()
            );
        }

        if ($type === RelationType::HAS_MANY) {
            return Cond::in(
                Expr::column(Attribute::ID),
                QueryBuilder::create()
                    ->from($entityType, 's')
                    ->select($defs->getForeignKey())
                    ->where([Attribute::ID => $id])
                    ->build()
            );
        }

        return null;
    }
}
