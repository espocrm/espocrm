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

use Espo\Core\Select\AccessControl\Filter;
use Espo\Core\Select\Helpers\RelationQueryHelper;
use Espo\Core\Select\Helpers\FieldHelper;
use Espo\Entities\User;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

class OnlyOwn implements Filter
{
    public function __construct(
        private User $user,
        private FieldHelper $fieldHelper,
        private string $entityType,
        private RelationQueryHelper $relationQueryHelper,
    ) {}

    public function apply(QueryBuilder $queryBuilder): void
    {
        $ownItem = $this->getOwnWhereItem();

        if ($this->fieldHelper->hasCollaboratorsField()) {
            $this->applyCollaborators($queryBuilder, $ownItem);

            return;
        }

        if (!$ownItem) {
            $queryBuilder->where([Attribute::ID => null]);

            return;
        }

        $queryBuilder->where($ownItem);
    }

    private function applyCollaborators(QueryBuilder $queryBuilder, ?WhereItem $ownItem): void
    {
        $sharedItem = $this->relationQueryHelper->prepareCollaboratorsWhere($this->entityType, $this->user->getId());

        $orBuilder = OrGroup::createBuilder();

        if ($ownItem) {
            $orBuilder->add($ownItem);
        }

        $orBuilder->add($sharedItem);

        $queryBuilder->where($orBuilder->build());
    }

    private function getOwnWhereItem(): ?WhereItem
    {
        if ($this->fieldHelper->hasAssignedUsersField()) {
            return $this->relationQueryHelper->prepareAssignedUsersWhere($this->entityType, $this->user->getId());
        }

        if ($this->fieldHelper->hasAssignedUserField()) {
            return WhereClause::fromRaw(['assignedUserId' => $this->user->getId()]);
        }

        if ($this->fieldHelper->hasCreatedByField()) {
            return WhereClause::fromRaw(['createdById' => $this->user->getId()]);
        }

        return null;
    }
}
