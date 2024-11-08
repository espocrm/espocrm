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
use Espo\Core\Select\Helpers\FieldHelper;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Type\RelationType;

class PortalOnlyContact implements Filter
{
    public function __construct(
        private User $user,
        private FieldHelper $fieldHelper
    ) {}

    public function apply(QueryBuilder $queryBuilder): void
    {
        $orBuilder = OrGroup::createBuilder();

        $contactId = $this->user->getContactId();

        if ($contactId) {
            if ($this->fieldHelper->hasContactField()) {
                $orBuilder->add(
                    WhereClause::fromRaw(['contactId' => $contactId])
                );

                if ($this->fieldHelper->getRelationDefs('contact')->getType() === RelationType::HAS_ONE) {
                    $queryBuilder->leftJoin('contact');
                }
            }

            if ($this->fieldHelper->hasContactsRelation()) {
                $defs = $this->fieldHelper->getRelationDefs('contacts');

                $orBuilder->add(
                    Cond::in(
                        Expr::column('id'),
                        QueryBuilder::create()
                            ->from(ucfirst($defs->getRelationshipName()), 'm')
                            ->select($defs->getMidKey())
                            ->where([$defs->getForeignMidKey() => $contactId])
                            ->build()
                    )
                );
            }

            if ($this->fieldHelper->hasParentField()) {
                $orBuilder->add(
                    WhereClause::fromRaw([
                        'parentType' => Contact::ENTITY_TYPE,
                        'parentId' => $contactId,
                    ])
                );
            }
        }

        if ($this->fieldHelper->hasCreatedByField()) {
            $orBuilder->add(
                WhereClause::fromRaw(['createdById' => $this->user->getId()])
            );
        }

        $orGroup = $orBuilder->build();

        if ($orGroup->getItemCount() === 0) {
            $queryBuilder->where(['id' => null]);

            return;
        }

        $queryBuilder->where($orGroup);
    }
}
