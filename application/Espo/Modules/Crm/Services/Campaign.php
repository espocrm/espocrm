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

namespace Espo\Modules\Crm\Services;

use Espo\Services\Pdf as PdfService;

use Espo\Modules\Crm\Entities\Campaign as CampaignEntity;

use Espo\ORM\Entity;

use Espo\Core\Exceptions\Error,
    Espo\Core\Exceptions\Forbidden,
    Espo\Core\Exceptions\BadRequest;

use Espo\Core\Di;

class Campaign extends \Espo\Services\Record implements

    Di\DefaultLanguageAware
{
    use Di\DefaultLanguageSetter;

    protected $entityTypeAddressFieldListMap = [
        'Account' => ['billingAddress', 'shippingAddress'],
        'Contact' => ['address'],
        'Lead' => ['address'],
        'User' => [],
    ];

    public function logLeadCreated($campaignId, Entity $target, $actionDate = null, $isTest = false)
    {
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->getId(),
            'parentType' => $target->getEntityType(),
            'action' => 'Lead Created',
            'isTest' => $isTest,
        ]);

        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logSent(
        string $campaignId,
        ?string $queueItemId,
        Entity $target,
        ?Entity $emailOrEmailTemplate,
        $emailAddress,
        $actionDate = null,
        $isTest = false
    ) {
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');

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

        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logBounced(
        $campaignId,
        $queueItemId,
        Entity $target,
        $emailAddress,
        $isHard = false,
        $actionDate = null,
        $isTest = false
    ) {
        if (
            $queueItemId &&
            $this->getEntityManager()
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

        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');

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
        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logOptedIn(
        $campaignId,
        $queueItemId,
        Entity $target,
        $emailAddress = null,
        $actionDate = null,
        $isTest = false
    ) {
        if (
            $queueItemId &&
            $this->getEntityManager()
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

        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');

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

        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logOptedOut(
        $campaignId,
        $queueItemId,
        Entity $target,
        $emailAddress = null,
        $actionDate = null,
        $isTest = false
    ) {
        if (
            $queueItemId &&
            $this->getEntityManager()
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

        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');

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

        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logOpened($campaignId, $queueItemId, Entity $target, $actionDate = null, $isTest = false)
    {
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        if (
            $queueItemId &&
            $this->getEntityManager()
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

        $queueItem = $this->getEntityManager()->getEntity('EmailQueueItem', $queueItemId);

        if ($queueItem) {
            $massEmail = $this->getEntityManager()->getEntity('MassEmail', $queueItem->get('massEmailId'));

            if ($massEmail && $massEmail->getId()) {
                $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
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

                $this->getEntityManager()->saveEntity($logRecord);
            }
        }
    }

    public function logClicked(
        $campaignId,
        $queueItemId,
        Entity $target,
        Entity $trackingUrl,
        $actionDate = null,
        $isTest = false
    ) {
        if ($this->getConfig()->get('massEmailOpenTracking')) {
            $this->logOpened($campaignId, $queueItemId, $target);
        }

        if (
            $queueItemId &&
            $this->getEntityManager()
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

        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
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
        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function generateMailMergePdf(string $campaignId, string $link, bool $checkAcl = false)
    {
        /** @var CampaignEntity $campaign */
        $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);

        if ($checkAcl && !$this->getAcl()->check($campaign, 'read')) {
            throw new Forbidden();
        }

        $targetEntityType = null;

        if ($checkAcl) {
            $targetEntityType = $campaign->getRelationParam($link, 'entity');

            if (!$this->getAcl()->check($targetEntityType, 'read')) {
                throw new Forbidden("Could not mail merge campaign because access to target entity type is forbidden.");
            }
        }

        if (!in_array($link, ['accounts', 'contacts', 'leads', 'users'])) {
            throw new BadRequest();
        }

        if ($campaign->get('type') !== 'Mail') {
            throw new Error("Could not mail merge campaign not of Mail type.");
        }

        if (
            !$campaign->get($link . 'TemplateId')
        ) {
            throw new Error("Could not mail merge campaign w/o specified template.");
        }

        $template = $this->getEntityManager()->getEntity('Template', $campaign->get($link . 'TemplateId'));

        if (!$template) {
            throw new Error("Template not found");
        }

        if ($template->get('entityType') !== $targetEntityType) {
            throw new Error("Template is not of proper entity type.");
        }

        $campaign->loadLinkMultipleField('targetLists');
        $campaign->loadLinkMultipleField('excludingTargetLists');

        if (count($campaign->getLinkMultipleIdList('targetLists')) === 0) {
            throw new Error("Could not mail merge campaign w/o any specified target list.");
        }

        $metTargetHash = [];
        $targetEntityList = [];

        /** @var iterable<\Espo\Modules\Crm\Entities\TargetList> */
        $excludingTargetListList = $this->getEntityManager()
            ->getRDBRepository('Campaign')
            ->getRelation($campaign, 'excludingTargetLists')
            ->find();

        foreach ($excludingTargetListList as $excludingTargetList) {
            $recordList = $this->getEntityManager()
                ->getRDBRepository('TargetList')
                ->getRelation($excludingTargetList, $link)
                ->find();

            foreach ($recordList as $excludingTarget) {
                $hashId = $excludingTarget->getEntityType() . '-' . $excludingTarget->getId();
                $metTargetHash[$hashId] = true;
            }
        }

        $addressFieldList = $this->entityTypeAddressFieldListMap[$targetEntityType];

        /** @var iterable<\Espo\Modules\Crm\Entities\TargetList> */
        $targetListCollection = $this->getEntityManager()
            ->getRDBRepository('Campaign')
            ->getRelation($campaign, 'targetLists')
            ->find();

        foreach ($targetListCollection as $targetList) {
            if (!$campaign->get($link . 'TemplateId')) {
                continue;
            }

            $entityList = $this->getEntityManager()
                ->getRDBRepository('TargetList')
                ->getRelation($targetList, $link)
                ->where([
                    '@relation.optedOut' => false,
                ])
                ->find();

            foreach ($entityList as $e) {
                $hashId = $e->getEntityType() . '-'. $e->getId();

                if (!empty($metTargetHash[$hashId])) {
                    continue;
                }

                $metTargetHash[$hashId] = true;

                if ($campaign->get('mailMergeOnlyWithAddress')) {
                    if (empty($addressFieldList)) {
                        continue;
                    }

                    $hasAddress = false;

                    foreach ($addressFieldList as $addressField) {
                        if ($e->get($addressField . 'Street') || $e->get($addressField . 'PostalCode')) {
                            $hasAddress = true;
                            break;
                        }
                    }

                    if (!$hasAddress) {
                        continue;
                    }
                }

                $targetEntityList[] = $e;
            }
        }

        if (empty($targetEntityList)) {
            throw new Error("No targets available for mail merge.");
        }

        $filename = $campaign->get('name') . ' - ' .
            $this->getDefaultLanguage()->translate($targetEntityType, 'scopeNamesPlural');

        return $this->getPdfService()->generateMailMerge(
            $targetEntityType,
            $targetEntityList,
            $template,
            $filename,
            $campaign->getId()
        );
    }

    protected function getDefaultLanguage()
    {
        return $this->defaultLanguage;
    }

    private function getPdfService(): PdfService
    {
        return $this->injectableFactory->create(PdfService::class);
    }
}
