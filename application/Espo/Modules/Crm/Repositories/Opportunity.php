<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\Crm\Repositories;

use Espo\Modules\Crm\Entities\Opportunity as OpportunityEntity;
use Espo\ORM\Entity;
use Espo\Core\Repositories\Database;

/**
 * @extends Database<OpportunityEntity>
 */
class Opportunity extends Database
{
    public function beforeSave(Entity $entity, array $options = [])
    {
        $this->processProbability($entity);
        $this->processLastStage($entity);

        parent::beforeSave($entity, $options);
    }

    public function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);

        if ($entity->isAttributeChanged('amount') || $entity->isAttributeChanged('probability')) {
            $amountConverted = $entity->get('amountConverted');
            $probability = $entity->get('probability');

            $amountWeightedConverted = round($amountConverted * $probability / 100, 2);

            $entity->set('amountWeightedConverted', $amountWeightedConverted);
        }

        $this->handleAfterSaveContacts($entity);
    }

    protected function handleAfterSaveContacts(OpportunityEntity $entity): void
    {
        if (!$entity->isAttributeChanged('contactId')) {
            return;
        }

        $contactId = $entity->get('contactId');
        $contactIdList = $entity->get('contactsIds') ?? [];
        $fetchedContactId = $entity->getFetched('contactId');

        if (!$contactId) {
            if ($fetchedContactId) {
                $this->unrelate($entity, 'contacts', $fetchedContactId);
            }

            return;
        }

        if (!in_array($contactId, $contactIdList) && !$this->isRelated($entity, 'contacts', $contactId)) {
            $this->relate($entity, 'contacts', $contactId);
        }
    }

    private function processProbability(OpportunityEntity $entity): void
    {
        if (!$entity->isNew()) {
            return;
        }

        if ($entity->has('probability')) {
            return;
        }

        if (!$entity->getStage()) {
            return;
        }

        $probability = $this->metadata
            ->get('entityDefs.Opportunity.fields.stage.probabilityMap.' . $entity->getStage()) ?? 0;

        if ($probability === null) {
            return;
        }

        $entity->setProbability($probability);
    }

    private function processLastStage(OpportunityEntity $entity): void
    {
        if (
            $entity->isAttributeChanged('lastStage') ||
            !$entity->isAttributeChanged('stage')
        ) {
            return;
        }

        $probability = $this->metadata
            ->get(['entityDefs', 'Opportunity', 'fields', 'stage', 'probabilityMap', $entity->getStage()]) ?? 0;

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
