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
use Espo\ORM\Defs;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\SelectBuilder;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;

use LogicException;

class ForeignOnlyOwn implements Filter
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

        $alias = $link . 'Access';

        $queryBuilder->leftJoin($link, $alias);

        $foreignEntityType = $this->defs
            ->getEntity($this->entityType)
            ->getRelation($link)
            ->getForeignEntityType();

        $foreignEntityDefs = $this->defs->getEntity($foreignEntityType);

        $orBuilder = OrGroup::createBuilder();

        if ($this->helper->hasCollaboratorsField($foreignEntityType)) {
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

        if ($this->helper->hasAssignedUsersField($foreignEntityType)) {
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

            $queryBuilder->where($orBuilder->build());

            return;
        }

        if ($foreignEntityDefs->hasField(Field::ASSIGNED_USER)) {
            $orBuilder->add(
                Cond::equal(
                    Expr::column("$alias.assignedUserId"),
                    $this->user->getId()
                )
            );

            $queryBuilder->where($orBuilder->build());

            return;
        }

        if ($foreignEntityDefs->hasField(Field::CREATED_BY)) {
            $orBuilder->add(
                Cond::equal(
                    Expr::column("$alias.createdById"),
                    $this->user->getId()
                )
            );

            $queryBuilder->where($orBuilder->build());

            return;
        }

        $queryBuilder->where([Attribute::ID => null]);
    }
}
