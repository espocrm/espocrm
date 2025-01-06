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

namespace Espo\Core\Upgrades\Migrations\V8_4;

use Espo\Core\Upgrades\Migration\Script;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Type\RelationType;

class AfterUpgrade implements Script
{
    public function __construct(
        private Metadata $metadata,
    ) {}

    public function run(): void
    {
        $this->updateMetadata();
    }

    private function updateMetadata(): void
    {
        $defs = $this->metadata->get(['entityDefs']);

        $toSave = false;

        foreach ($defs as $entityType => $item) {
            if (!isset($item['links'])) {
                continue;
            }

            foreach ($item['links'] as $link => $linkDefs) {
                $type = $linkDefs['type'] ?? null;
                $foreignEntityType = $linkDefs['entity'] ?? null;
                $midKeys = $linkDefs[RelationParam::MID_KEYS] ?? null;
                $isCustom = $linkDefs['isCustom'] ?? false;

                if ($type !== RelationType::HAS_MANY) {
                    continue;
                }

                if ($foreignEntityType !== $entityType) {
                    continue;
                }

                if (!$midKeys) {
                    continue;
                }

                if (!$isCustom) {
                    continue;
                }

                if ($linkDefs['_keysSwappedAfterUpgrade'] ?? false) {
                    continue;
                }

                $this->metadata->set('entityDefs', $entityType, [
                    'links' => [
                        $link => [
                            RelationParam::MID_KEYS => array_reverse($midKeys),
                            '_keysSwappedAfterUpgrade' => true,
                        ]
                    ]
                ]);

                $toSave = true;
            }

            if ($toSave) {
                $this->metadata->save();
            }
        }
    }
}
