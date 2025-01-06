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

namespace Espo\Modules\Crm\Classes\FieldProcessing\Campaign;

use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Entities\CampaignLogRecord;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\Entity;

use Espo\Core\Acl;
use Espo\Core\Currency\ConfigDataProvider as CurrencyConfigDataProvider;
use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\ORM\EntityManager;

use Espo\ORM\Query\SelectBuilder;
use PDO;

use const PHP_ROUND_HALF_EVEN;

/**
 * @implements Loader<Campaign>
 */
class StatsLoader implements Loader
{
    private EntityManager $entityManager;
    private Acl $acl;
    private CurrencyConfigDataProvider $currencyDataProvider;

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
            ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
            ->where([
                'campaignId' => $entity->getId(),
                'action' => CampaignLogRecord::ACTION_SENT,
                'isTest' => false,
            ])
            ->count();

        if (!$sentCount) {
            $sentCount = null;
        }

        $entity->set('sentCount', $sentCount);

        $openedCount = $this->entityManager
            ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
            ->where([
                'campaignId' => $entity->getId(),
                'action' => CampaignLogRecord::ACTION_OPENED,
                'isTest' => false,
            ])
            ->count();

        $entity->set('openedCount', $openedCount);

        $openedPercentage = null;

        if ($sentCount > 0) {
            $openedPercentage = round($openedCount / $sentCount * 100, 2, PHP_ROUND_HALF_EVEN);
        }

        $entity->set('openedPercentage', $openedPercentage);

        $clickedCount = $this->entityManager
            ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
            ->where([
                'campaignId' => $entity->getId(),
                'action' => CampaignLogRecord::ACTION_CLICKED,
                'isTest' => false,
                'id=s' => SelectBuilder::create()
                    ->select('MIN:id')
                    ->from(CampaignLogRecord::ENTITY_TYPE)
                    ->where([
                        'action' => CampaignLogRecord::ACTION_CLICKED,
                        'isTest' => false,
                        'campaignId' => $entity->getId(),
                    ])
                    ->group('queueItemId')
                    ->build()
                    ->getRaw(),
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
            ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
            ->where([
                'campaignId' => $entity->getId(),
                'action' => CampaignLogRecord::ACTION_OPTED_IN,
                'isTest' => false,
            ])
            ->count();

        if (!$optedInCount) {
            $optedInCount = null;
        }

        $entity->set('optedInCount', $optedInCount);

        $optedOutCount = $this->entityManager
            ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
            ->where([
                'campaignId' => $entity->getId(),
                'action' => CampaignLogRecord::ACTION_OPTED_OUT,
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
            ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
            ->where([
                'campaignId' => $entity->getId(),
                'action' => CampaignLogRecord::ACTION_BOUNCED,
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

        if ($this->acl->check(Lead::ENTITY_TYPE)) {
            $leadCreatedCount = $this->entityManager
                ->getRDBRepository(Lead::ENTITY_TYPE)
                ->where([
                    'campaignId' => $entity->getId(),
                ])
                ->count();

            if (!$leadCreatedCount) {
                $leadCreatedCount = null;
            }

            $entity->set('leadCreatedCount', $leadCreatedCount);
        }

        if ($this->acl->check(Opportunity::ENTITY_TYPE)) {
            $entity->set('revenueCurrency', $this->currencyDataProvider->getDefaultCurrency());

            $query = $this->entityManager
                ->getQueryBuilder()
                ->select()
                ->from(Opportunity::ENTITY_TYPE)
                ->select(['SUM:amountConverted'])
                ->where([
                    'stage' => Opportunity::STAGE_CLOSED_WON,
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
