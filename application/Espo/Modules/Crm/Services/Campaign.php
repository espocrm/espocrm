<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error,
    \Espo\Core\Exceptions\Forbidden,
    \Espo\Core\Exceptions\BadRequest;

class Campaign extends \Espo\Services\Record
{
    protected function init()
    {
        parent::init();
        $this->addDependency('container');
    }

    protected $entityTypeAddressFieldListMap = [
        'Account' => ['billingAddress', 'shippingAddress'],
        'Contact' => ['address'],
        'Lead' => ['address'],
        'User' => [],
    ];

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);

        $sentCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where([
            'campaignId' => $entity->id,
            'action' => 'Sent',
            'isTest' => false
        ])->count();
        if (!$sentCount) {
            $sentCount = null;
        }
        $entity->set('sentCount', $sentCount);

        $openedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where([
            'campaignId' => $entity->id,
            'action' => 'Opened',
            'isTest' => false
        ])->count();
        $entity->set('openedCount', $openedCount);

        $openedPercentage = null;
        if ($sentCount > 0) {
            $openedPercentage = round($openedCount / $sentCount * 100, 2, \PHP_ROUND_HALF_EVEN);
        }
        $entity->set('openedPercentage', $openedPercentage);

        $clickedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where([
            'campaignId' => $entity->id,
            'action' => 'Clicked',
            'isTest' => false
        ])->count();
        $entity->set('clickedCount', $clickedCount);

        $clickedPercentage = null;
        if ($sentCount > 0) {
            $clickedPercentage = round($clickedCount / $sentCount * 100, 2, \PHP_ROUND_HALF_EVEN);
        }
        $entity->set('clickedPercentage', $clickedPercentage);

        $optedInCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where([
            'campaignId' => $entity->id,
            'action' => 'Opted In',
            'isTest' => false
        ])->count();
        if (!$optedInCount) $optedInCount = null;
        $entity->set('optedInCount', $optedInCount);

        $optedOutCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where([
            'campaignId' => $entity->id,
            'action' => 'Opted Out',
            'isTest' => false
        ])->count();
        $entity->set('optedOutCount', $optedOutCount);

        $optedOutPercentage = null;
        if ($sentCount > 0) {
            $optedOutPercentage = round($optedOutCount / $sentCount * 100, 2, \PHP_ROUND_HALF_EVEN);
        }
        $entity->set('optedOutPercentage', $optedOutPercentage);

        $bouncedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where([
            'campaignId' => $entity->id,
            'action' => 'Bounced',
            'isTest' => false
        ])->count();
        $entity->set('bouncedCount', $bouncedCount);

        $bouncedPercentage = null;
        if ($sentCount && $sentCount > 0) {
            $bouncedPercentage = round($bouncedCount / $sentCount * 100, 2, \PHP_ROUND_HALF_EVEN);
        }
        $entity->set('bouncedPercentage', $bouncedPercentage);

        $leadCreatedCount = $this->getEntityManager()->getRepository('Lead')->where([
            'campaignId' => $entity->id
        ])->count();
        if (!$leadCreatedCount) {
            $leadCreatedCount = null;
        }
        $entity->set('leadCreatedCount', $leadCreatedCount);

        $entity->set('revenueCurrency', $this->getConfig()->get('defaultCurrency'));

        $params = [
            'select' => ['SUM:amountConverted'],
            'whereClause' => [
                'stage' => 'Closed Won',
                'campaignId' => $entity->id
            ],
            'groupBy' => ['opportunity.campaignId']
        ];

        $this->getEntityManager()->getRepository('Opportunity')->handleSelectParams($params);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Opportunity', $params);

        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute();

        $revenue = null;
        if ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $revenue = floatval($row['SUM:amountConverted']);
            if (!$revenue) {
                $revenue = null;
            }
        }
        $entity->set('revenue', $revenue);
    }

    public function logLeadCreated($campaignId, Entity $target, $actionDate = null, $isTest = false)
    {
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }
        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->id,
            'parentType' => $target->getEntityType(),
            'action' => 'Lead Created',
            'isTest' => $isTest
        ]);

        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logSent($campaignId, $queueItemId = null, Entity $target, Entity $emailOrEmailTemplate = null, $emailAddress, $actionDate = null, $isTest = false)
    {
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }
        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->id,
            'parentType' => $target->getEntityType(),
            'action' => 'Sent',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ]);

        if ($emailOrEmailTemplate) {
            $logRecord->set([
                'objectId' => $emailOrEmailTemplate->id,
                'objectType' => $emailOrEmailTemplate->getEntityType()
            ]);
        }
        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logBounced($campaignId, $queueItemId = null, Entity $target, $emailAddress, $isHard = false, $actionDate = null, $isTest = false)
    {
        if ($queueItemId && $this->getEntityManager()->getRepository('CampaignLogRecord')->where([
            'queueItemId' => $queueItemId,
            'action' => 'Bounced',
            'isTest' => $isTest
        ])->findOne()) {
            return;
        }
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }
        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->id,
            'parentType' => $target->getEntityType(),
            'action' => 'Bounced',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ]);
        if ($isHard) {
            $logRecord->set('stringAdditionalData', 'Hard');
        } else {
            $logRecord->set('stringAdditionalData', 'Soft');
        }
        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logOptedIn($campaignId, $queueItemId = null, Entity $target, $emailAddress = null, $actionDate = null, $isTest = false)
    {
        if ($queueItemId && $this->getEntityManager()->getRepository('CampaignLogRecord')->where([
            'queueItemId' => $queueItemId,
            'action' => 'Opted In',
            'isTest' => $isTest
        ])->findOne()) return;

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
            'parentId' => $target->id,
            'parentType' => $target->getEntityType(),
            'action' => 'Opted In',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ]);
        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logOptedOut($campaignId, $queueItemId = null, Entity $target, $emailAddress = null, $actionDate = null, $isTest = false)
    {
        if ($queueItemId && $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'queueItemId' => $queueItemId,
            'action' => 'Opted Out',
            'isTest' => $isTest
        ))->findOne()) {
            return;
        }
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }
        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
        $logRecord->set(array(
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->id,
            'parentType' => $target->getEntityType(),
            'action' => 'Opted Out',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ));
        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logOpened($campaignId, $queueItemId = null, Entity $target, $actionDate = null, $isTest = false)
    {
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        if ($queueItemId && $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'queueItemId' => $queueItemId,
            'action' => 'Opened',
            'isTest' => $isTest
        ))->findOne()) {
            return;
        }
        $queueItem = $this->getEntityManager()->getEntity('EmailQueueItem', $queueItemId);
        if ($queueItem) {
            $massEmail = $this->getEntityManager()->getEntity('MassEmail', $queueItem->get('massEmailId'));
            if ($massEmail && $massEmail->id) {
                $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
                $logRecord->set([
                    'campaignId' => $campaignId,
                    'actionDate' => $actionDate,
                    'parentId' => $target->id,
                    'parentType' => $target->getEntityType(),
                    'action' => 'Opened',
                    'objectId' => $massEmail->get('emailTemplateId'),
                    'objectType' => 'EmailTemplate',
                    'queueItemId' => $queueItemId,
                    'isTest' => $isTest
                ]);
                $this->getEntityManager()->saveEntity($logRecord);
            }
        }
    }

    public function logClicked($campaignId, $queueItemId = null, Entity $target, Entity $trackingUrl, $actionDate = null, $isTest = false)
    {
        if ($this->getConfig()->get('massEmailOpenTracking')) {
            $this->logOpened($campaignId, $queueItemId, $target);
        }

        if ($queueItemId && $this->getEntityManager()->getRepository('CampaignLogRecord')->where([
            'queueItemId' => $queueItemId,
            'action' => 'Clicked',
            'objectId' => $trackingUrl->id,
            'objectType' => $trackingUrl->getEntityType(),
            'isTest' => $isTest
        ])->findOne()) {
            return;
        }
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }

        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
        $logRecord->set([
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->id,
            'parentType' => $target->getEntityType(),
            'action' => 'Clicked',
            'objectId' => $trackingUrl->id,
            'objectType' => $trackingUrl->getEntityType(),
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ]);
        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function generateMailMergePdf($campaignId, $link, $checkAcl = false)
    {
        $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);

        if ($checkAcl && !$this->getAcl()->check($campaign, 'read')) {
            throw new Forbidden();
        }

        if ($checkAcl) {
            $targetEntityType = $campaign->getRelationParam($link, 'entity');
            if (!$this->getAcl()->check($targetEntityType, 'read')) {
                throw new Forbidden("Could not mail merge campaign because access to target enity type is forbidden.");
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

        $excludingTargetListList = $campaign->get('excludingTargetLists');
        foreach ($excludingTargetListList as $excludingTargetList) {
            foreach ($excludingTargetList->get($link) as $excludingTarget) {
                $hashId = $excludingTarget->getEntityType() . '-'. $excludingTarget->id;
                $metTargetHash[$hashId] = true;
            }
        }

        $addressFieldList = $this->entityTypeAddressFieldListMap[$targetEntityType];

        $targetListCollection = $campaign->get('targetLists');
        foreach ($targetListCollection as $targetList) {
            if (!$campaign->get($link . 'TemplateId')) continue;
            $entityList = $targetList->get($link, [
                'additionalColumnsConditions' => [
                    'optedOut' => false
                ]
            ]);
            foreach ($entityList as $e) {
                $hashId = $e->getEntityType() . '-'. $e->id;
                if (!empty($metTargetHash[$hashId])) {
                    continue;
                }
                $metTargetHash[$hashId] = true;

                if ($campaign->get('mailMergeOnlyWithAddress')) {
                    if (empty($addressFieldList)) continue;
                    $hasAddress = false;
                    foreach ($addressFieldList as $addressField) {
                        if ($e->get($addressField . 'Street') || $e->get($addressField . 'PostalCode')) {
                            $hasAddress = true;
                            break;
                        }
                    }
                    if (!$hasAddress) continue;
                }

                $targetEntityList[] = $e;
            }
        }

        if (empty($targetEntityList)) {
            throw new Error("No targets available for mail merge.");
        }

        $filename = $campaign->get('name') . ' - ' . $this->getDefaultLanguage()->translate($targetEntityType, 'scopeNamesPlural');

        return $this->getServiceFactory()->create('Pdf')->generateMailMerge($targetEntityType, $targetEntityList, $template, $filename, $campaign->id);
    }

    protected function getDefaultLanguage()
    {
        return $this->getInjection('container')->get('defaultLanguage');
    }
}
