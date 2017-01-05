<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

class Campaign extends \Espo\Services\Record
{
    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);

        $sentCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Sent',
            'isTest' => false
        ))->count();
        $entity->set('sentCount', $sentCount);

        $openedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Opened',
            'isTest' => false,
            'groupBy' => ['queueItemId']
        ))->count();
        $entity->set('openedCount', $openedCount);

        $openedPercentage = null;
        if ($sentCount > 0) {
            $openedPercentage = round($openedCount / $sentCount * 100, 2, \PHP_ROUND_HALF_EVEN);
        }
        $entity->set('openedPercentage', $openedPercentage);

        $clickedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Clicked',
            'isTest' => false,
            'groupBy' => ['queueItemId']
        ))->count();
        $entity->set('clickedCount', $clickedCount);

        $clickedPercentage = null;
        if ($sentCount > 0) {
            $clickedPercentage = round($clickedCount / $sentCount * 100, 2, \PHP_ROUND_HALF_EVEN);
        }
        $entity->set('clickedPercentage', $clickedPercentage);

        $optedOutCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Opted Out',
            'isTest' => false,
            'groupBy' => ['queueItemId']
        ))->count();
        $entity->set('optedOutCount', $optedOutCount);

        $optedOutPercentage = null;
        if ($sentCount > 0) {
            $optedOutPercentage = round($optedOutCount / $sentCount * 100, 2, \PHP_ROUND_HALF_EVEN);
        }
        $entity->set('optedOutPercentage', $optedOutPercentage);

        $bouncedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Bounced',
            'isTest' => false,
            'groupBy' => ['queueItemId']
        ))->count();
        $entity->set('bouncedCount', $bouncedCount);

        $bouncedPercentage = null;
        if ($sentCount > 0) {
            $bouncedPercentage = round($bouncedCount / $sentCount * 100, 2, \PHP_ROUND_HALF_EVEN);
        }
        $entity->set('bouncedPercentage', $bouncedPercentage);

        $leadCreatedCount = $this->getEntityManager()->getRepository('Lead')->where(array(
            'campaignId' => $entity->id
        ))->count();
        $entity->set('leadCreatedCount', $leadCreatedCount);

        $entity->set('revenueCurrency', $this->getConfig()->get('defaultCurrency'));

        $params = array(
            'select' => array('SUM:amountConverted'),
            'whereClause' => array(
                'stage' => 'Closed Won',
                'campaignId' => $entity->id
            ),
            'groupBy' => array('opportunity.campaignId')
        );

        $this->getEntityManager()->getRepository('Opportunity')->handleSelectParams($params);


        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Opportunity', $params);


        $pdo = $this->getEntityManager()->getPDO();
        $sth = $pdo->prepare($sql);
        $sth->execute();

        if ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $revenue = floatval($row['SUM:amountConverted']);
            if ($revenue > 0) {
                $entity->set('revenue', $revenue);
            }
        }
    }

    public function logSent($campaignId, $queueItemId = null, Entity $target, Entity $emailOrEmailTemplate = null, $emailAddress, $actionDate = null, $isTest = false)
    {
        if (empty($actionDate)) {
            $actionDate = date('Y-m-d H:i:s');
        }
        $logRecord = $this->getEntityManager()->getEntity('CampaignLogRecord');
        $logRecord->set(array(
            'campaignId' => $campaignId,
            'actionDate' => $actionDate,
            'parentId' => $target->id,
            'parentType' => $target->getEntityType(),
            'action' => 'Sent',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ));

        if ($emailOrEmailTemplate) {
            $logRecord->set(array(
                'objectId' => $emailOrEmailTemplate->id,
                'objectType' => $emailOrEmailTemplate->getEntityType()
            ));
        }
        $this->getEntityManager()->saveEntity($logRecord);
    }

    public function logBounced($campaignId, $queueItemId = null, Entity $target, $emailAddress, $isHard = false, $actionDate = null, $isTest = false)
    {
        if ($queueItemId && $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'queueItemId' => $queueItemId,
            'action' => 'Bounced',
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
            'action' => 'Bounced',
            'stringData' => $emailAddress,
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ));
        if ($isHard) {
            $logRecord->set('stringAdditionalData', 'Hard');
        } else {
            $logRecord->set('stringAdditionalData', 'Soft');
        }
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
                $logRecord->set(array(
                    'campaignId' => $campaignId,
                    'actionDate' => $actionDate,
                    'parentId' => $target->id,
                    'parentType' => $target->getEntityType(),
                    'action' => 'Opened',
                    'objectId' => $massEmail->get('emailTemplateId'),
                    'objectType' => 'EmailTemplate',
                    'queueItemId' => $queueItemId,
                    'isTest' => $isTest
                ));
                $this->getEntityManager()->saveEntity($logRecord);
            }
        }
    }

    public function logClicked($campaignId, $queueItemId = null, Entity $target, Entity $trackingUrl, $actionDate = null, $isTest = false)
    {
        $this->logOpened($campaignId, $queueItemId, $target);

        if ($queueItemId && $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'queueItemId' => $queueItemId,
            'action' => 'Clicked',
            'objectId' => $trackingUrl->id,
            'objectType' => $trackingUrl->getEntityType(),
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
            'action' => 'Clicked',
            'objectId' => $trackingUrl->id,
            'objectType' => $trackingUrl->getEntityType(),
            'queueItemId' => $queueItemId,
            'isTest' => $isTest
        ));
        $this->getEntityManager()->saveEntity($logRecord);
    }

}

