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

namespace Espo\Modules\Crm\Classes\FieldProcessing\Campaign;

use Espo\ORM\Entity;

use Espo\Core\{
    FieldProcessing\Loader,
    FieldProcessing\Loader\Params,
    ORM\EntityManager,
    Acl,
    Field\Currency\CurrencyConfigDataProvider,
};

use PDO;

use const PHP_ROUND_HALF_EVEN;

class StatsLoader implements Loader
{
    private $entityManager;

    private $acl;

    private $currencyDataProvider;

    public function __construct(
        EntityManager $entityManager,
        Acl $acl,
        CurrencyConfigDataProvider $currencyDataProvider
    ) {
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->currencyDataProvider = $currencyDataProvider;
    }

    public function process(Entity $entity, Params $params): void
    {
        $sentCount = $this->entityManager
            ->getRDBRepository('CampaignLogRecord')
            ->where([
                'campaignId' => $entity->getId(),
                'action' => 'Sent',
                'isTest' => false,
            ])
            ->count();

        if (!$sentCount) {
            $sentCount = null;
        }

        $entity->set('sentCount', $sentCount);

        $openedCount = $this->entityManager
            ->getRDBRepository('CampaignLogRecord')
            ->where([
                'campaignId' => $entity->getId(),
                'action' => 'Opened',
                'isTest' => false,
            ])
            ->count();

        $entity->set('openedCount', $openedCount);

        $openedPercentage = null;

        if ($sentCount > 0) {
            $openedPercentage = round(
                $openedCount / $sentCount * 100, 2,
                PHP_ROUND_HALF_EVEN
            );
        }

        $entity->set('openedPercentage', $openedPercentage);

        $clickedCount = $this->entityManager
            ->getRDBRepository('CampaignLogRecord')
            ->where([
                'campaignId' => $entity->getId(),
                'action' => 'Clicked',
                'isTest' => false,
                'id=s' => [
                    'entityType' => 'CampaignLogRecord',
                    'selectParams' => [
                        'select' => ['MIN:id'],
                        'whereClause' => [
                            'action' => 'Clicked',
                            'isTest' => false,
                            'campaignId' => $entity->getId(),
                        ],
                        'groupBy' => ['queueItemId'],
                    ],
                ],
            ])
            ->count();

        $entity->set('clickedCount', $clickedCount);

        $clickedPercentage = null;

        if ($sentCount > 0) {
            $clickedPercentage = round(
                $clickedCount / $sentCount * 100, 2,
                PHP_ROUND_HALF_EVEN
            );
        }

        $entity->set('clickedPercentage', $clickedPercentage);

        $optedInCount = $this->entityManager
            ->getRDBRepository('CampaignLogRecord')
            ->where([
                'campaignId' => $entity->getId(),
                'action' => 'Opted In',
                'isTest' => false,
            ])
            ->count();

        if (!$optedInCount) {
            $optedInCount = null;
        }

        $entity->set('optedInCount', $optedInCount);

        $optedOutCount = $this->entityManager
            ->getRDBRepository('CampaignLogRecord')
            ->where([
                'campaignId' => $entity->getId(),
                'action' => 'Opted Out',
                'isTest' => false,
            ])
            ->count();

        $entity->set('optedOutCount', $optedOutCount);

        $optedOutPercentage = null;

        if ($sentCount > 0) {
            $optedOutPercentage = round(
                $optedOutCount / $sentCount * 100, 2,
                PHP_ROUND_HALF_EVEN
            );
        }

        $entity->set('optedOutPercentage', $optedOutPercentage);

        $bouncedCount = $this->entityManager
            ->getRDBRepository('CampaignLogRecord')
            ->where([
                'campaignId' => $entity->getId(),
                'action' => 'Bounced',
                'isTest' => false,
            ])
            ->count();

        $entity->set('bouncedCount', $bouncedCount);

        $bouncedPercentage = null;

        if ($sentCount && $sentCount > 0) {
            $bouncedPercentage = round(
                $bouncedCount / $sentCount * 100, 2,
                PHP_ROUND_HALF_EVEN
            );
        }

        $entity->set('bouncedPercentage', $bouncedPercentage);

        if ($this->acl->check('Lead')) {
            $leadCreatedCount = $this->entityManager
                ->getRDBRepository('Lead')
                ->where([
                    'campaignId' => $entity->getId(),
                ])
                ->count();

            if (!$leadCreatedCount) {
                $leadCreatedCount = null;
            }

            $entity->set('leadCreatedCount', $leadCreatedCount);
        }

        if ($this->acl->check('Opportunity')) {
            $entity->set('revenueCurrency', $this->currencyDataProvider->getDefaultCurrency());

            $query = $this->entityManager
                ->getQueryBuilder()
                ->select()
                ->from('Opportunity')
                ->select(['SUM:amountConverted'])
                ->where([
                    'stage' => 'Closed Won',
                    'campaignId' => $entity->getId(),
                ])
                ->group('opportunity.campaignId')
                ->build();

            $sth = $this->entityManager->getQueryExecutor()->execute($query);

            $revenue = null;

            $row = $sth->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $revenue = floatval($row['SUM:amountConverted']);
            }

            if (!$revenue) {
                $revenue = null;
            }

            $entity->set('revenue', $revenue);
        }
    }
}
