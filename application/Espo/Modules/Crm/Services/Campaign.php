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
 ************************************************************************/

namespace Espo\Modules\Crm\Services;

use \Espo\ORM\Entity;

class Campaign extends \Espo\Services\Record
{
    protected function loadAdditionalFields($entity)
    {
        parent::loadAdditionalFields($entity);


        $sentCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Clicked'
        ))->count();
        $entity->set('sentCount', $sentCount);

        $openedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Opened'
        ))->count();
        $entity->set('openedCount', $openedCount);

        $clickedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Clicked'
        ))->count();
        $entity->set('clickedCount', $clickedCount);

        $optedOutCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Opted Out'
        ))->count();
        $entity->set('optedOutCount', $optedOutCount);

        $bouncedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Bounced'
        ))->count();
        $entity->set('bouncedCount', $bouncedCount);

        $leadCreatedCount = $this->getEntityManager()->getRepository('CampaignLogRecord')->where(array(
            'campaignId' => $entity->id,
            'action' => 'Lead Created'
        ))->count();
        $entity->set('leadCreatedCount', $leadCreatedCount);

        $entity->set('revenueCurrency', $this->getConfig()->get('defaultCurrency'));

        $params = array(
            'select' => array('SUM:amountConverted'),
            'whereClause' => array(
                'status' => 'Closed Won',
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

}

