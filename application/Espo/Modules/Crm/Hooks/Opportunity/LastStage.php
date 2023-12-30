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

namespace Espo\Modules\Crm\Hooks\Opportunity;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements BeforeSave<Opportunity>
 */
class LastStage implements BeforeSave
{
    public static int $order = 8;

    public function __construct(private Metadata $metadata) {}

    /**
     * @param Opportunity $entity
     */
    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (
            $entity->isAttributeChanged('lastStage') ||
            !$entity->isAttributeChanged('stage')
        ) {
            return;
        }

        $probability = $this->metadata
            ->get(['entityDefs', 'Opportunity', 'fields', 'stage', 'probabilityMap', $entity->getStage() ?? '']) ?? 0;

        if ($probability) {
            $entity->set('lastStage', $entity->getStage());

            return;
        }

        $probabilityMap =  $this->metadata
            ->get(['entityDefs', 'Opportunity', 'fields', 'stage', 'probabilityMap']) ?? [];

        $stageList = $this->metadata->get(['entityDefs', 'Opportunity', 'fields', 'stage', 'options']) ?? [];

        if (!count($stageList)) {
            return;
        }

        if ($entity->isNew()) {
            // Created as Lost.

            $min = 100;
            $minStage = null;

            foreach ($stageList as $stage) {
                $itemProbability = $probabilityMap[$stage] ?? null;

                if (
                    $itemProbability === null ||
                    $itemProbability === 100 ||
                    $itemProbability === 0 ||
                    $itemProbability >= $min
                ) {
                    continue;
                }

                $min = $itemProbability;
                $minStage = $stage;
            }

            if (!$minStage) {
                return;
            }

            $entity->set('lastStage', $minStage);

            return;
        }

        // Won changed to Lost.

        if (!$entity->getLastStage()) {
            return;
        }

        $lastStageProbability = $this->metadata
            ->get(['entityDefs', 'Opportunity', 'fields', 'stage', 'probabilityMap', $entity->getLastStage()]) ?? 0;

        if ($lastStageProbability !== 100) {
            return;
        }

        $max = 0;
        $maxStage = null;

        foreach ($stageList as $stage) {
            $itemProbability = $probabilityMap[$stage] ?? null;

            if (
                $itemProbability === null ||
                $itemProbability === 100 ||
                $itemProbability === 0 ||
                $itemProbability <= $max
            ) {
                continue;
            }

            $max = $itemProbability;
            $maxStage = $stage;
        }

        if (!$maxStage) {
            return;
        }

        $entity->set('lastStage', $maxStage);
    }
}
