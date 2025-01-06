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

namespace Espo\Modules\Crm\Classes\Select\Meeting\AccessControlFilters;

use Espo\Core\Select\AccessControl\Filter;
use Espo\ORM\Defs;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\Part\Condition as Cond;

use Espo\Entities\User;

class OnlyOwn implements Filter
{
    public function __construct(
        private User $user,
        private string $entityType,
        private Defs $defs
    ) {}

    public function apply(SelectBuilder $queryBuilder): void
    {
        $relationDefs = $this->defs
            ->getEntity($this->entityType)
            ->getRelation('users');

        $middleEntityType = ucfirst($relationDefs->getRelationshipName());
        $key1 = $relationDefs->getMidKey();

        $queryBuilder->where(
            Cond::in(
                Cond::column(Attribute::ID),
                SelectBuilder::create()
                    ->select(Attribute::ID)
                    ->from($this->entityType)
                    ->leftJoin($middleEntityType, 'usersMiddle', [
                        "usersMiddle.{$key1}:" => 'id',
                        'usersMiddle.deleted' => false,
                    ])
                    ->where(
                        Cond::or(
                            Cond::equal(
                                Cond::column('usersMiddle.userId'),
                                $this->user->getId()
                            ),
                            Cond::equal(
                                Cond::column('assignedUserId'),
                                $this->user->getId()
                            )
                        )
                    )
                    ->build()
            )
        );
    }
}
