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

        $stage = $entity->getStage();

        $probability = $this->metadata->get("entityDefs.Opportunity.fields.stage.probabilityMap.$stage");

        if ($probability !== 0) {
            $entity->setLastStage($entity->getStage());

            return;
        }

        // Lost.

        $probabilityMap = $this->getProbabilityMap();
        $stageList = $this->getStageList();

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
                    $itemProbability == 100 ||
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

            $entity->setLastStage($minStage);

            return;
        }

        // Won changed to Lost.

        if (!$entity->getLastStage()) {
            return;
        }

        $lastStageProbability = $probabilityMap[$entity->getLastStage()] ?? null;

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

        $entity->setLastStage($maxStage);
    }

    /**
     * @return array<string, ?int>
     */
    private function getProbabilityMap(): array
    {
        /** @var array<string, ?int> $probabilityMap */
        $probabilityMap = $this->metadata->get('entityDefs.Opportunity.fields.stage.probabilityMap') ?? [];

        return $probabilityMap;
    }

    /**
     * @return string[]
     */
    private function getStageList(): array
    {
        return $this->metadata->get('entityDefs.Opportunity.fields.stage.options') ?? [];
    }
}
