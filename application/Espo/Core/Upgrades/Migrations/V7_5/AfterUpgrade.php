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

namespace Espo\Core\Upgrades\Migrations\V7_5;

use Espo\Core\Templates\Entities\Event;
use Espo\Core\Upgrades\Migration\Script;
use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;

class AfterUpgrade implements Script
{
    public function __construct(
        private Metadata $metadata,
        private Manager $fileManger
    ) {}

    public function run(): void
    {
        $this->updateEventMetadata();
    }

    private function updateEventMetadata(): void
    {
        $metadata = $this->metadata;
        $fileManager = $this->fileManger;

        $defs = $metadata->get(['scopes']);

        $path1 = "application/Espo/Core/Templates/Metadata/Event/selectDefs.json";
        $contents1 = $fileManager->getContents($path1);
        $data1 = Json::decode($contents1, true);

        $primaryFilterClassNameMap = (object) $data1['primaryFilterClassNameMap'];

        foreach ($defs as $entityType => $item) {
            $isCustom = $item['isCustom'] ?? false;
            $type = $item['type'] ?? false;

            if (!$isCustom || $type !== Event::TEMPLATE_TYPE) {
                continue;
            }

            $data1 = $metadata->getCustom('selectDefs', $entityType) ?? (object) [];
            $data1->primaryFilterClassNameMap = $primaryFilterClassNameMap;

            $metadata->saveCustom('selectDefs', $entityType, $data1);

            $data2 = $metadata->getCustom('scopes', $entityType) ?? (object) [];
            $data2->completedStatusList = ['Held'];
            $data2->canceledStatusList = ['Not Held'];

            $metadata->saveCustom('scopes', $entityType, $data2);
        }
    }
}
