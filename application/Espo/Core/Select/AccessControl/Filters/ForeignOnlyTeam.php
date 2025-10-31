<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\Select\AccessControl\Filters;

use Espo\Core\Acl\AssignmentChecker\Helper;
use Espo\Core\Name\Field;
use Espo\Core\Select\AccessControl\Filter;
use Espo\Entities\Team;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\Part\Where\OrGroupBuilder;
use Espo\ORM\Query\SelectBuilder;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs;
use Espo\Entities\User;

use LogicException;

/**
 * @noinspection PhpUnused
 */
class ForeignOnlyTeam implements Filter
{
    public function __construct(
        private string $entityType,
        private User $user,
        private Metadata $metadata,
        private Defs $defs,
        private Helper $helper,
    ) {}

    public function apply(SelectBuilder $queryBuilder): void
    {
        $link = $this->metadata->get(['aclDefs', $this->entityType, 'link']);

        if (!$link) {
            throw new LogicException("No `link` in aclDefs for $this->entityType.");
        }

        $alias = "{$link}Access";

        $foreignEntityType = $this->getForeignEntityType($link);

        $queryBuilder->leftJoin($link, $alias);

        $orBuilder = OrGroup::createBuilder();

        $this->applyCollaborators($foreignEntityType, $orBuilder, $alias);
        $this->applyAssignedUsers($foreignEntityType, $orBuilder, $alias);
        $this->applyAssignedUser($foreignEntityType, $orBuilder, $alias);
        $this->applyCreatedBy($foreignEntityType, $orBuilder, $alias);
        $this->applyTeams($foreignEntityType, $orBuilder, $alias);

        if ($orBuilder->build()->getItemCount() === 0) {
            $queryBuilder->where([Attribute::ID => null]);

            return;
        }

        $queryBuilder
            ->where($orBuilder->build())
            ->where(["$alias.id!=" => null]);
    }

    private function getForeignEntityType(string $link): string
    {
        return $this->defs
            ->getEntity($this->entityType)
            ->getRelation($link)
            ->getForeignEntityType();
    }

    private function applyCollaborators(string $foreignEntityType, OrGroupBuilder $orBuilder, string $alias): void
    {
        if (!$this->helper->hasCollaboratorsField($foreignEntityType)) {
            return;
        }

        $orBuilder->add(
            Cond::equal(
                Expr::column("$alias." . Attribute::ID),
                SelectBuilder::create()
                    ->from(User::RELATIONSHIP_ENTITY_COLLABORATOR, 's')
                    ->select('s.entityId')
                    ->where(
                        Cond::and(
                            Cond::equal(
                                Expr::column('s.entityType'),
                                $foreignEntityType,
                            ),
                            Cond::equal(
                                Expr::column('s.userId'),
                                $this->user->getId(),
                            )
                        )
                    )
                    ->build()
            )
        );
    }

    private function applyAssignedUsers(
        string $foreignEntityType,
        OrGroupBuilder $orBuilder,
        string $alias,
    ): void {

        if (!$this->helper->hasAssignedUsersField($foreignEntityType)) {
            return;
        }

        $orBuilder->add(
            Cond::equal(
                Expr::column("$alias." . Attribute::ID),
                SelectBuilder::create()
                    ->from(User::RELATIONSHIP_ENTITY_USER, 's')
                    ->select('s.entityId')
                    ->where(
                        Cond::and(
                            Cond::equal(
                                Expr::column('s.entityType'),
                                $foreignEntityType,
                            ),
                            Cond::equal(
                                Expr::column('s.userId'),
                                $this->user->getId(),
                            )
                        )
                    )
                    ->build()
            )
        );
    }

    private function applyAssignedUser(string $foreignEntityType, OrGroupBuilder $orBuilder, string $alias): void
    {
        $foreignEntityDefs = $this->defs->getEntity($foreignEntityType);

        if (
            $this->helper->hasAssignedUsersField($foreignEntityType) ||
            !$foreignEntityDefs->hasField(Field::ASSIGNED_USER)
        ) {
            return;
        }

        $orBuilder->add(
            Cond::equal(
                Expr::column("$alias.assignedUserId"),
                $this->user->getId()
            )
        );
    }

    private function applyCreatedBy(string $foreignEntityType, OrGroupBuilder $orBuilder, string $alias): void
    {
        $foreignEntityDefs = $this->defs->getEntity($foreignEntityType);

        if (
            $this->helper->hasAssignedUsersField($foreignEntityType) ||
            $foreignEntityDefs->hasField(Field::ASSIGNED_USER) ||
            !$foreignEntityDefs->hasField(Field::CREATED_BY)
        ) {
            return;
        }

        $orBuilder->add(
            Cond::equal(
                Expr::column("$alias.createdById"),
                $this->user->getId()
            )
        );
    }

    private function applyTeams(string $foreignEntityType, OrGroupBuilder $orBuilder, string $alias): void
    {
        $teamIdList = $this->user->getTeamIdList();

        if (count($teamIdList) === 0) {
            return;
        }

        $orBuilder->add(
            Cond::in(
                Expr::column("$alias.id"),
                SelectBuilder::create()
                    ->from(Team::RELATIONSHIP_ENTITY_TEAM)
                    ->select('entityId')
                    ->where([
                        'teamId' => $teamIdList,
                        'entityType' => $foreignEntityType,
                    ])
                    ->build()
            )
        );
    }
}
