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

namespace Espo\Tools\LinkManager\Hook\Hooks;

use Espo\Tools\LinkManager\Hook\CreateHook;
use Espo\Tools\LinkManager\Params;
use Espo\Tools\LinkManager\Type;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;

class AssignedUsersCreate implements CreateHook
{
    private const LINK_NAME = 'assignedUsers';

    public function __construct(private Metadata $metadata)
    {}

    public function process(Params $params): void
    {
        if ($params->getType() !== Type::MANY_TO_MANY) {
            return;
        }

        $foreignEntityType = $params->getForeignEntityType();
        $entityType = $params->getEntityType();

        if (!$foreignEntityType || !$entityType) {
            return;
        }

        if (
            $params->getEntityType() === User::ENTITY_TYPE &&
            $params->getForeignLink() === self::LINK_NAME
        ) {
            $this->processInternal($foreignEntityType);

            return;
        }

        if (
            $params->getForeignEntityType() === User::ENTITY_TYPE &&
            $params->getLink() === self::LINK_NAME
        ) {
            $this->processInternal($entityType);
        }
    }

    private function processInternal(string $entityType): void
    {
        $fieldType = $this->metadata->get(['entityDefs', $entityType, 'fields', self::LINK_NAME, 'type']);

        if ($fieldType !== 'linkMultiple') {
            return;
        }

        $this->metadata->set('entityDefs', $entityType, [
            'fields' => [
                self::LINK_NAME => [
                    'view' => 'views/fields/assigned-users',
                ],
            ]
        ]);

        $this->metadata->save();
    }
}
