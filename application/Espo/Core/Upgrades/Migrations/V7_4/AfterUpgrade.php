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

namespace Espo\Core\Upgrades\Migrations\V7_4;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Upgrades\Migration\Script;
use Espo\Core\Utils\Metadata;
use Espo\Core\Templates\Entities\Event;

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
        $this->metadata->set('app', 'recordId', [
            'length' => 24,
        ]);

        $this->fixParent();
        $this->updateEventMetadata();

        $this->metadata->save();
    }

    private function fixParent(): void
    {
        $metadata = $this->metadata;

        foreach ($metadata->get(['entityDefs']) as $scope => $defs) {
            foreach ($metadata->get(['entityDefs', $scope, 'fields']) as $field => $fieldDefs) {
                $custom = $metadata->getCustom('entityDefs', $scope);

                if (!$custom) {
                    continue;
                }

                if (
                    ($fieldDefs['type'] ?? null) === FieldType::LINK_PARENT &&
                    ($fieldDefs['notStorable'] ?? false)
                ) {
                    if ($custom->fields?->$field?->notStorable) {
                        $metadata->delete('entityDefs', $scope, "fields.$field.notStorable");
                    }
                }
            }
        }
    }

    private function updateEventMetadata(): void
    {
        $metadata = $this->metadata;

        $defs = $metadata->get(['scopes']);

        foreach ($defs as $entityType => $item) {
            $isCustom = $item['isCustom'] ?? false;
            $type = $item['type'] ?? false;

            if (!$isCustom || $type !== Event::TEMPLATE_TYPE) {
                continue;
            }

            if (!is_string($metadata->get(['entityDefs', $entityType, 'fields', 'duration', 'select']))) {
                continue;
            }

            $metadata->delete('entityDefs', $entityType, 'fields.duration.orderBy');

            $metadata->set('entityDefs', $entityType, [
                'fields' => [
                    'duration' => [
                        'select' => [
                            'select' => "TIMESTAMPDIFF_SECOND:(dateStart, dateEnd)"
                        ],
                        'order' => [
                            'order' => [["TIMESTAMPDIFF_SECOND:(dateStart, dateEnd)", "{direction}"]]
                        ],
                    ]
                ]
            ]);
        }
    }
}
