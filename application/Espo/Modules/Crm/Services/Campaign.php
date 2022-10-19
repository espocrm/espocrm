<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\Services;

use Espo\ORM\Entity;
use Espo\Services\Record;
use Espo\Core\Di;

/**
 * @extends Record<\Espo\Modules\Crm\Entities\Campaign>
 */
class Campaign extends Record implements

    Di\DefaultLanguageAware
{
    use Di\DefaultLanguageSetter;


    public function logLeadCreated(
        string $campaignId,
        Entity $target,
        ?string $actionDate = null,
        bool $isTest = false
    ): void {

        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        $logRecord = $this->entityManager->getNewEntity('CampaignLogRecord');

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => 'Lead Created',
            'isTest' => $isTest,
        ]);

        $this->entityManager->saveEntity($logRecord);
    }

    public function logSent(
        string $campaignId,
        ?string $queueItemId,
        Entity $target,
        ?Entity $emailOrEmailTemplate,
        string $emailAddress,
        ?string $actionDate = null,
        bool $isTest = false
    ): void {

        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        $logRecord = $this->entityManager->getNewEntity('CampaignLogRecord');

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => 'Sent',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest,
        ]);

        if ($emailOrEmailTemplate) {
            $logRecord->set([
                'objectId' => $emailOrEmailTemplate->getId(),
                'objectType' => $emailOrEmailTemplate->getEntityType()
            ]);
        }

        $this->entityManager->saveEntity($logRecord);
    }

    public function logBounced(
        string $campaignId,
        ?string $queueItemId,
        Entity $target,
        string $emailAddress,
        bool $isHard = false,
        ?string $actionDate = null,
        bool $isTest = false
    ): void {

        if (
            $queueItemId &&
            $this->entityManager
                ->getRDBRepository('CampaignLogRecord')
                ->where([
                    'queueItemId' => $queueItemId,
                    'action' => 'Bounced',
                    'isTest' => $isTest,
                ])
                ->findOne()
        ) {
            return;
        }

        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        $logRecord = $this->entityManager->getNewEntity('CampaignLogRecord');

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => 'Bounced',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest,
        ]);

        if ($isHard) {
            $logRecord->set('stringAdditionalData', 'Hard');
        } else {
            $logRecord->set('stringAdditionalData', 'Soft');
        }
        $this->entityManager->saveEntity($logRecord);
    }

    public function logOptedIn(
        string $campaignId,
        ?string $queueItemId,
        Entity $target,
        ?string $emailAddress = null,
        ?string $actionDate = null,
        bool $isTest = false
    ): void {

        if (
            $queueItemId &&
            $this->entityManager
                ->getRDBRepository('CampaignLogRecord')
                ->where([
                    'queueItemId' => $queueItemId,
                    'action' => 'Opted In',
                    'isTest' => $isTest,
                ])
                ->findOne()
        ) {
            return;
        }

        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        if (!$emailAddress) {
            $emailAddress = $target->get('emailAddress');
        }

        $logRecord = $this->entityManager->getNewEntity('CampaignLogRecord');

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => 'Opted In',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest,
        ]);

        $this->entityManager->saveEntity($logRecord);
    }

    public function logOptedOut(
        string $campaignId,
        ?string $queueItemId,
        Entity $target,
        ?string $emailAddress = null,
        ?string $actionDate = null,
        bool $isTest = false
    ): void {

        if (
            $queueItemId &&
            $this->entityManager
                ->getRDBRepository('CampaignLogRecord')
                ->where([
                    'queueItemId' => $queueItemId,
                    'action' => 'Opted Out',
                    'isTest' => $isTest,
                ])
                ->findOne()
        ) {
            return;
        }

        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        $logRecord = $this->entityManager->getNewEntity('CampaignLogRecord');

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => 'Opted Out',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ]);

        $this->entityManager->saveEntity($logRecord);
    }

    public function logOpened(
        string $campaignId,
        ?string $queueItemId,
        Entity $target,
        ?string $actionDate = null,
        bool $isTest = false
    ): void {

        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        if (
            $queueItemId &&
            $this->entityManager
                ->getRDBRepository('CampaignLogRecord')
                ->where([
                    'queueItemId' => $queueItemId,
                    'action' => 'Opened',
                    'isTest' => $isTest,
                ])
                ->findOne()
        ) {
            return;
        }

        $queueItem = $this->entityManager->getEntity('EmailQueueItem', $queueItemId);

        if ($queueItem) {
            $massEmail = $this->entityManager->getEntity('MassEmail', $queueItem->get('massEmailId'));

            if ($massEmail && $massEmail->hasId()) {
                $logRecord = $this->entityManager->getNewEntity('CampaignLogRecord');

                $logRecord->set([
                    'campaignId' => $campaignId,
                    'actionDate' => $actionDate,
                    'parentId' => $target->getId(),
                    'parentType' => $target->getEntityType(),
                    'action' => 'Opened',
                    'objectId' => $massEmail->get('emailTemplateId'),
                    'objectType' => 'EmailTemplate',
                    'queueItemId' => $queueItemId,
                    'isTest' => $isTest,
                ]);

                $this->entityManager->saveEntity($logRecord);
            }
        }
    }

    public function logClicked(
        string $campaignId,
        string $queueItemId,
        Entity $target,
        Entity $trackingUrl,
        ?string $actionDate = null,
        bool $isTest = false
    ): void {

        if ($this->config->get('massEmailOpenTracking')) {
            $this->logOpened($campaignId, $queueItemId, $target);
        }

        if (
            $queueItemId &&
            $this->entityManager
                ->getRDBRepository('CampaignLogRecord')
                ->where([
                    'queueItemId' => $queueItemId,
                    'action' => 'Clicked',
                    'objectId' => $trackingUrl->getId(),
                    'objectType' => $trackingUrl->getEntityType(),
                    'isTest' => $isTest,
                ])
                ->findOne()
        ) {
            return;
        }

        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        $logRecord = $this->entityManager->getNewEntity('CampaignLogRecord');

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => 'Clicked',
            'objectId' => $trackingUrl->getId(),
            'objectType' => $trackingUrl->getEntityType(),
            'queueItemId' => $queueItemId,
            'isTest' => $isTest,
        ]);

        $this->entityManager->saveEntity($logRecord);
    }


}
