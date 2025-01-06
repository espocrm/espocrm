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

namespace Espo\Modules\Crm\Classes\RecordHooks\TargetList;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Entities\CampaignLogRecord;
use Espo\Modules\Crm\Entities\TargetList;
use Espo\Modules\Crm\Tools\TargetList\MetadataProvider;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

/**
 * @implements SaveHook<TargetList>
 */
class AfterCreate implements SaveHook
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private MetadataProvider $metadataProvider
    ) {}

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function process(Entity $entity): void
    {
        if (
            !$entity->get('sourceCampaignId') ||
            !$entity->get('includingActionList')
        ) {
            return;
        }

        /** @var string $campaignId */
        $campaignId = $entity->get('sourceCampaignId');
        /** @var string[] $includingActionList */
        $includingActionList = $entity->get('includingActionList');
        /** @var string[] $excludingActionList */
        $excludingActionList = $entity->get('excludingActionList') ?? [];

        $this->populateFromCampaignLog(
            $entity,
            $campaignId,
            $includingActionList,
            $excludingActionList
        );
    }

    /**
     * @param string[] $includingActionList
     * @param string[] $excludingActionList
     * @throws NotFound
     * @throws Forbidden
     */
    protected function populateFromCampaignLog(
        TargetList $entity,
        string $sourceCampaignId,
        array $includingActionList,
        array $excludingActionList
    ): void {

        $campaign = $this->entityManager->getEntityById(Campaign::ENTITY_TYPE, $sourceCampaignId);

        if (!$campaign) {
            throw new NotFound("Campaign not found.");
        }

        if (!$this->acl->check($campaign, Table::ACTION_READ)) {
            throw new Forbidden("No access to campaign.");
        }

        $queryBuilder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(CampaignLogRecord::ENTITY_TYPE)
            ->select([Attribute::ID, 'parentId', 'parentType'])
            ->where([
                'isTest' => false,
                'campaignId' => $sourceCampaignId,
            ]);

        $notQueryBuilder = clone $queryBuilder;

        $queryBuilder->where([
            'action=' => $includingActionList,
        ]);

        $queryBuilder->group([
            'parentId',
            'parentType',
            Attribute::ID,
        ]);

        $notQueryBuilder->where(['action=' => $excludingActionList]);
        $notQueryBuilder->select([Attribute::ID]);

        /** @var iterable<CampaignLogRecord> $logRecords */
        $logRecords = $this->entityManager
            ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
            ->clone($queryBuilder->build())
            ->find();

        $entityTypeLinkMap = $this->metadataProvider->getEntityTypeLinkMap();

        foreach ($logRecords as $logRecord) {
            if (!$logRecord->getParent()) {
                continue;
            }

            $parentType = $logRecord->getParent()->getEntityType();
            $parentId = $logRecord->getParent()->getId();

            if (!$parentType) {
                continue;
            }

            if (empty($entityTypeLinkMap[$parentType])) {
                continue;
            }

            $existing = null;

            if (!empty($excludingActionList)) {
                $cloneQueryBuilder = clone $notQueryBuilder;

                $cloneQueryBuilder->where([
                    'parentType' => $parentType,
                    'parentId' => $parentId,
                ]);

                $existing = $this->entityManager
                    ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
                    ->clone($cloneQueryBuilder->build())
                    ->findOne();
            }

            if ($existing) {
                continue;
            }

            $relation = $entityTypeLinkMap[$parentType];

            $this->entityManager
                ->getRDBRepositoryByClass(TargetList::class)
                ->getRelation($entity, $relation)
                ->relateById($parentId);
        }
    }
}
