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

namespace Espo\Core\Select\AccessControl\Filters;

use Espo\Core\Name\Field;
use Espo\Core\Select\AccessControl\Filter;
use Espo\Entities\Team;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Where\OrGroup;
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
        private Defs $defs
    ) {}

    public function apply(SelectBuilder $queryBuilder): void
    {
        $link = $this->metadata->get(['aclDefs', $this->entityType, 'link']);

        if (!$link) {
            throw new LogicException("No `link` in aclDefs for $this->entityType.");
        }

        $alias = "{$link}Access";

        $ownerAttribute = $this->getOwnerAttribute($link);

        if (!$ownerAttribute) {
            $queryBuilder->where([Attribute::ID => null]);

            return;
        }

        $teamIdList = $this->user->getTeamIdList();

        if (count($teamIdList) === 0) {
            $queryBuilder
                ->leftJoin($link, $alias)
                ->where(["$alias.$ownerAttribute" => $this->user->getId()]);

            return;
        }

        $foreignEntityType = $this->getForeignEntityType($link);

        $orGroup = OrGroup::create(
            Condition::equal(
                Expression::column("$alias.$ownerAttribute"),
                $this->user->getId()
            ),
            Condition::in(
                Expression::column("$alias.id"),
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

        $queryBuilder
            ->leftJoin($link, $alias)
            ->where($orGroup)
            ->where(["$alias.id!=" => null]);
    }

    private function getOwnerAttribute(string $link): ?string
    {
        $foreignEntityType = $this->getForeignEntityType($link);

        $foreignEntityDefs = $this->defs->getEntity($foreignEntityType);

        if ($foreignEntityDefs->hasField(Field::ASSIGNED_USER)) {
            return 'assignedUserId';
        }

        if ($foreignEntityDefs->hasField(Field::CREATED_BY)) {
            return 'createdById';
        }

        return null;
    }

    private function getForeignEntityType(string $link): string
    {
        return $this->defs
            ->getEntity($this->entityType)
            ->getRelation($link)
            ->getForeignEntityType();
    }
}
