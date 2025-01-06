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

namespace Espo\Classes\DefaultLayouts;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Defs\Params\RelationParam;
use stdClass;

class DefaultSidePanelType
{
    public function __construct(private Metadata $metadata)
    {}

    /**
     * @return stdClass[]
     */
    public function get(string $scope): array
    {
        $list = [];

        if (
            $this->metadata->get(['entityDefs', $scope, 'fields', Field::ASSIGNED_USER, FieldParam::TYPE]) ===
                FieldType::LINK &&
            $this->metadata->get(['entityDefs', $scope, 'links', Field::ASSIGNED_USER, RelationParam::ENTITY]) ===
                User::ENTITY_TYPE
            ||
            $this->metadata->get(['entityDefs', $scope, 'fields', Field::ASSIGNED_USERS, FieldParam::TYPE]) ===
                FieldType::LINK_MULTIPLE &&
            $this->metadata->get(['entityDefs', $scope, 'links', Field::ASSIGNED_USERS, RelationParam::ENTITY]) ===
                User::ENTITY_TYPE
        ) {
            $list[] = (object) ['name' => ':assignedUser'];
        }

        if (
            $this->metadata->get(['entityDefs', $scope, 'fields', Field::TEAMS, FieldParam::TYPE]) ===
                FieldType::LINK_MULTIPLE &&
            $this->metadata->get(['entityDefs', $scope, 'links', Field::TEAMS, RelationParam::ENTITY]) ===
                Team::ENTITY_TYPE
        ) {
            $list[] = (object) ['name' => Field::TEAMS];
        }

        if (
            $this->metadata->get("entityDefs.$scope.fields.collaborators.type") === FieldType::LINK_MULTIPLE &&
            $this->metadata->get("entityDefs.$scope.links.collaborators.entity") === User::ENTITY_TYPE
        ) {
            $list[] = (object) ['name' => Field::COLLABORATORS];
        }

        return $list;
    }
}
