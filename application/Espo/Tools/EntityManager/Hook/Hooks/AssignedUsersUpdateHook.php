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

namespace Espo\Tools\EntityManager\Hook\Hooks;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\ORM\Type\RelationType;
use Espo\Tools\EntityManager\Hook\UpdateHook;
use Espo\Tools\EntityManager\Params;

/**
 * @noinspection PhpUnused
 */
class AssignedUsersUpdateHook implements UpdateHook
{
    private const PARAM = 'assignedUsers';
    private const FIELD = 'assignedUsers';
    private const RELATION_NAME = 'entityUser';
    private const FIELD_ASSIGNED_USER = 'assignedUser';

    public function __construct(
        private Metadata $metadata,
    ) {}

    public function process(Params $params, Params $previousParams): void
    {
        if ($params->get(self::PARAM) && !$previousParams->get(self::PARAM)) {
            $this->add($params->getName());
        } else if (!$params->get(self::PARAM) && $previousParams->get(self::PARAM)) {
            $this->remove($params->getName());
        }
    }

    private function add(string $entityType): void
    {
        $this->metadata->set('entityDefs', $entityType, [
            'fields' => [
                self::FIELD => [
                    'type' => FieldType::LINK_MULTIPLE,
                    'view' => 'views/fields/assigned-users',
                ],
            ],
            'links' => [
                self::FIELD => [
                    'type' => RelationType::HAS_MANY,
                    'entity' => User::ENTITY_TYPE,
                    'relationName' => self::RELATION_NAME,
                    'layoutRelationshipsDisabled' => true,
                ],
            ],
        ]);

        $this->metadata->set('entityAcl', $entityType, [
            'links' => [
                self::FIELD => [
                    'readOnly' => true,
                ],
            ],
        ]);

        if ($this->metadata->get("entityDefs.$entityType.'fields.assignedUser")) {
            $this->metadata->set('entityDefs', $entityType, [
                'fields' => [
                    self::FIELD_ASSIGNED_USER => [
                        'disabled' => true,
                    ],
                ],
                'links' => [
                    self::FIELD_ASSIGNED_USER => [
                        'disabled' => true,
                    ],
                ],
            ]);
        }

        $this->metadata->save();
    }

    private function remove(string $entityType): void
    {
        $this->metadata->delete('entityDefs', $entityType, [
            'fields.' . self::FIELD,
            'links.' . self::FIELD,
            'fields.' . self::FIELD_ASSIGNED_USER . '.disabled',
            'links.' . self::FIELD_ASSIGNED_USER . '.disabled',
        ]);

        $this->metadata->delete('entityAcl', $entityType, [
            'links.' . self::FIELD,
        ]);

        $this->metadata->save();
    }
}
