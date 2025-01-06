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

namespace Espo\Tools\EntityManager\Hook\Hooks;

use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Type\RelationType;
use Espo\Tools\EntityManager\Hook\DeleteHook;
use Espo\Tools\EntityManager\Params;

/**
 * @noinspection PhpUnused
 */
class DeleteHasChildrenLinks implements DeleteHook
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    public function process(Params $params): void
    {
        /** @var array<string, array<string, mixed>> $entityDefs */
        $entityDefs = $this->metadata->get('entityDefs', []);

        foreach ($entityDefs as $entityType => $defs) {
            /** @var array<string, array<string, mixed>> $links */
            $links = $defs['links'] ?? [];

            foreach ($links as $link => $linkDefs) {
                $isCustom = $linkDefs['isCustom'] ?? false;
                $foreignEntityType = $linkDefs[RelationParam::ENTITY] ?? null;
                $type = $linkDefs[RelationParam::TYPE] ?? null;

                if (
                    !$isCustom ||
                    $foreignEntityType !== $params->getName() ||
                    $type !== RelationType::HAS_CHILDREN
                ) {
                    continue;
                }

                $this->metadata->delete('entityDefs', $entityType, "links.$link");
            }
        }

        $this->metadata->save();
    }
}
