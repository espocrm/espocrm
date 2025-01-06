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

namespace Espo\Core\Select\Bool\Filters;

use Espo\Core\Name\Field;
use Espo\Core\Select\Bool\Filter;
use Espo\Core\Select\Helpers\FieldHelper;
use Espo\Entities\User;
use Espo\ORM\Defs;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Where\OrGroupBuilder;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

/**
 * @noinspection PhpUnused
 */
class Shared implements Filter
{
    public const NAME = 'shared';

    public function __construct(
        private string $entityType,
        private User $user,
        private FieldHelper $fieldHelper,
        private Defs $defs
    ) {}

    public function apply(QueryBuilder $queryBuilder, OrGroupBuilder $orGroupBuilder): void
    {
        if (!$this->fieldHelper->hasCollaboratorsField()) {
            return;
        }

        $relationDefs = $this->defs
            ->getEntity($this->entityType)
            ->getRelation(Field::COLLABORATORS);

        $middleEntityType = ucfirst($relationDefs->getRelationshipName());
        $key1 = $relationDefs->getMidKey();
        $key2 = $relationDefs->getForeignMidKey();

        $subQuery = QueryBuilder::create()
            ->select(Attribute::ID)
            ->from($this->entityType)
            ->leftJoin($middleEntityType, 'collaboratorsMiddle', [
                "collaboratorsMiddle.$key1:" => Attribute::ID,
                'collaboratorsMiddle.deleted' => false,
            ])
            ->where(["collaboratorsMiddle.$key2" => $this->user->getId()])
            ->build();

        $orGroupBuilder->add(
            Cond::in(
                Cond::column('id'),
                $subQuery
            )
        );
    }
}
