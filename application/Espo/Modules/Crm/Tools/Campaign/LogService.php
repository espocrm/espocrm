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

namespace Espo\Modules\Crm\Tools\Campaign;

use Espo\Core\Field\DateTime;
use Espo\Core\Utils\Config;
use Espo\Entities\EmailTemplate;
use Espo\Modules\Crm\Entities\CampaignLogRecord;
use Espo\Modules\Crm\Entities\CampaignTrackingUrl;
use Espo\Modules\Crm\Entities\EmailQueueItem as QueueItem;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

class LogService
{
    private EntityManager $entityManager;
    private Config $config;

    public function __construct(
        EntityManager $entityManager,
        Config $config
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    public function logLeadCreated(string $campaignId, Lead $target): void
    {
        $actionDate = DateTime::createNow();

        $logRecord = $this->entityManager->getNewEntity(CampaignLogRecord::ENTITY_TYPE);

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate->toString(),
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => CampaignLogRecord::ACTION_LEAD_CREATED,
        ]);

        $this->entityManager->saveEntity($logRecord);
    }

    public function logSent(string $campaignId, QueueItem $queueItem, ?Entity $emailOrEmailTemplate = null): void
    {
        $queueItemId = $queueItem->getId();
        $isTest = $queueItem->isTest();

        $actionDate = DateTime::createNow();

        $logRecord = $this->entityManager->getNewEntity(CampaignLogRecord::ENTITY_TYPE);

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate->toString(),
            'parentId' => $queueItem->getTargetId(),
            'parentType' => $queueItem->getTargetType(),
            'action' => CampaignLogRecord::ACTION_SENT,
            'stringData' => $queueItem->getEmailAddress(),
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

    public function logBounced(string $campaignId, QueueItem $queueItem, bool $isHard = false): void
    {
        $queueItemId = $queueItem->getId();
        $isTest = $queueItem->isTest();
        $emailAddress = $queueItem->getEmailAddress();

        if (
            $this->entityManager
                ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
                ->where([
                    'queueItemId' => $queueItemId,
                    'action' => CampaignLogRecord::ACTION_BOUNCED,
                    'isTest' => $isTest,
                ])
                ->findOne()
        ) {
            return;
        }

        $actionDate = DateTime::createNow();

        $logRecord = $this->entityManager->getNewEntity(CampaignLogRecord::ENTITY_TYPE);

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate->toString(),
            'parentId' => $queueItem->getTargetId(),
            'parentType' => $queueItem->getTargetType(),
            'action' => CampaignLogRecord::ACTION_BOUNCED,
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest,
        ]);

        $logRecord->set(
            'stringAdditionalData',
            $isHard ?
                CampaignLogRecord::BOUNCED_TYPE_HARD :
                CampaignLogRecord::BOUNCED_TYPE_SOFT
        );

        $this->entityManager->saveEntity($logRecord);
    }

    public function logOptedIn(
        string $campaignId,
        ?QueueItem $queueItem,
        Entity $target,
        ?string $emailAddress = null
    ): void {

        if (
            $queueItem &&
            $this->entityManager
                ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
                ->where([
                    'queueItemId' => $queueItem->getId(),
                    'action' => CampaignLogRecord::ACTION_OPTED_IN,
                    'isTest' => $queueItem->isTest(),
                ])
                ->findOne()
        ) {
            return;
        }

        $actionDate = DateTime::createNow();
        $emailAddress = $emailAddress ?? $target->get('emailAddress');

        if (!$emailAddress && $queueItem) {
            $emailAddress = $queueItem->getEmailAddress();
        }

        $queueItemId = null;
        $isTest = false;

        if ($queueItem) {
            $queueItemId = $queueItem->getId();
            $isTest = $queueItem->isTest();
        }

        $logRecord = $this->entityManager->getNewEntity(CampaignLogRecord::ENTITY_TYPE);

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate->toString(),
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => CampaignLogRecord::ACTION_OPTED_IN,
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest,
        ]);

        $this->entityManager->saveEntity($logRecord);
    }

    public function logOptedOut(
        string $campaignId,
        ?QueueItem $queueItem,
        Entity $target,
        ?string $emailAddress = null
    ): void {

        if (
            $queueItem &&
            $this->entityManager
                ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
                ->where([
                    'queueItemId' => $queueItem->getId(),
                    'action' => CampaignLogRecord::ACTION_OPTED_OUT,
                    'isTest' => $queueItem->isTest(),
                ])
                ->findOne()
        ) {
            return;
        }

        $actionDate = DateTime::createNow();

        $queueItemId = null;
        $isTest = false;

        if ($queueItem) {
            $queueItemId = $queueItem->getId();
            $isTest = $queueItem->isTest();
        }

        if (!$emailAddress && $queueItem) {
            $emailAddress = $queueItem->getEmailAddress();
        }

        $logRecord = $this->entityManager->getNewEntity(CampaignLogRecord::ENTITY_TYPE);

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate->toString(),
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => CampaignLogRecord::ACTION_OPTED_OUT,
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ]);

        $this->entityManager->saveEntity($logRecord);
    }

    public function logOpened(string $campaignId, QueueItem $queueItem): void
    {
        $actionDate = DateTime::createNow();

        if (
            $this->entityManager
                ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
                ->where([
                    'queueItemId' => $queueItem->getId(),
                    'action' => CampaignLogRecord::ACTION_OPENED,
                    'isTest' => $queueItem->isTest(),
                ])
                ->findOne()
        ) {
            return;
        }

        $massEmail = $queueItem->getMassEmail();

        if (!$massEmail) {
            return;
        }

        $logRecord = $this->entityManager->getNewEntity(CampaignLogRecord::ENTITY_TYPE);

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate->toString(),
            'parentId' => $queueItem->getTargetId(),
            'parentType' => $queueItem->getTargetType(),
            'action' => CampaignLogRecord::ACTION_OPENED,
            'objectId' => $massEmail->getEmailTemplateId(),
            'objectType' => EmailTemplate::ENTITY_TYPE,
            'queueItemId' => $queueItem->getId(),
            'isTest' => $queueItem->isTest(),
        ]);

        $this->entityManager->saveEntity($logRecord);
    }

    public function logClicked(
        string $campaignId,
        QueueItem $queueItem,
        CampaignTrackingUrl $trackingUrl
    ): void {

        $actionDate = DateTime::createNow();

        if ($this->config->get('massEmailOpenTracking')) {
            $this->logOpened($campaignId, $queueItem);
        }

        if (
            $this->entityManager
                ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
                ->where([
                    'queueItemId' => $queueItem->getId(),
                    'action' => CampaignLogRecord::ACTION_CLICKED,
                    'objectId' => $trackingUrl->getId(),
                    'objectType' => $trackingUrl->getEntityType(),
                    'isTest' => $queueItem->isTest(),
                ])
                ->findOne()
        ) {
            return;
        }

        $logRecord = $this->entityManager->getNewEntity(CampaignLogRecord::ENTITY_TYPE);

        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate->toString(),
            'parentId' => $queueItem->getTargetId(),
            'parentType' => $queueItem->getTargetType(),
            'action' => CampaignLogRecord::ACTION_CLICKED,
            'objectId' => $trackingUrl->getId(),
            'objectType' => $trackingUrl->getEntityType(),
            'queueItemId' => $queueItem->getId(),
            'isTest' => $queueItem->isTest(),
        ]);

        $this->entityManager->saveEntity($logRecord);
    }
}
