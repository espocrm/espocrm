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

namespace Espo\Core\Select\AccessControl\Filters;

use Espo\Core\Select\AccessControl\Filter;
use Espo\ORM\Query\SelectBuilder;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs;
use Espo\Entities\User;

use LogicException;

class ForeignOnlyTeam implements Filter
{
    private string $entityType;
    private User $user;
    private Metadata $metadata;
    private Defs $defs;

    public function __construct(string $entityType, User $user, Metadata $metadata, Defs $defs)
    {
        $this->user = $user;
        $this->entityType = $entityType;
        $this->metadata = $metadata;
        $this->defs = $defs;
    }

    public function apply(SelectBuilder $queryBuilder): void
    {
        $link = $this->metadata->get(['aclDefs', $this->entityType, 'link']);

        if (!$link) {
            throw new LogicException("No `link` in aclDefs for {$this->entityType}.");
        }

        $alias = $link . 'Access';

        $queryBuilder->leftJoin($link, $alias);

        $ownerAttribute = $this->getOwnerAttribute($link);

        if (!$ownerAttribute) {
            $queryBuilder->where(['id' => null]);

            return;
        }

        $teamIdList = $this->user->getTeamIdList();

        if (count($teamIdList) === 0) {
            $queryBuilder->where([
                "{$alias}.{$ownerAttribute}" => $this->user->getId(),
            ]);

            return;
        }

        $foreignEntityType = $this->defs
            ->getEntity($this->entityType)
            ->getRelation($link)
            ->getForeignEntityType();

        $queryBuilder
            ->distinct()
            ->leftJoin(
                'EntityTeam',
                'entityTeamAccess',
                [
                    'entityTeamAccess.entityType' => $foreignEntityType,
                    'entityTeamAccess.entityId:' => "{$alias}.id",
                    'entityTeamAccess.deleted' => false,
                ]
            )
            ->where([
                'OR' => [
                    'entityTeamAccess.teamId' => $teamIdList,
                    "{$alias}.{$ownerAttribute}" => $this->user->getId(),
                ],
                "{$alias}.id!=" => null,
            ]);
    }

    private function getOwnerAttribute(string $link): ?string
    {
        $foreignEntityType = $this->defs
            ->getEntity($this->entityType)
            ->getRelation($link)
            ->getForeignEntityType();

        $foreignEntityDefs = $this->defs->getEntity($foreignEntityType);

        if ($foreignEntityDefs->hasField('assignedUser')) {
            return 'assignedUserId';
        }

        if ($foreignEntityDefs->hasField('createdBy')) {
            return 'createdById';
        }

        return null;
    }
}
