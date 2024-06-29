<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\Tools\MassEmail;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\HookManager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Hasher;
use Espo\Entities\EmailAddress;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Entities\CampaignLogRecord;
use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\Crm\Entities\TargetList;
use Espo\Modules\Crm\Tools\Campaign\LogService;
use Espo\Modules\Crm\Tools\MassEmail\Util as MassEmailUtil;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\Repositories\EmailAddress as EmailAddressRepository;

class UnsubscribeService
{
    public function __construct(
        private EntityManager $entityManager,
        private HookManager $hookManager,
        private LogService $service,
        private MassEmailUtil $util,
        private Hasher $hasher,
    ) {}

    /**
     * @throws NotFound
     */
    public function unsubscribe(string $queueItemId): void
    {
        [$queueItem, $campaign, $massEmail, $target] = $this->getRecords($queueItemId);

        if ($massEmail->optOutEntirely()) {
            $emailAddress = $target->get('emailAddress');

            if ($emailAddress) {
                $address = $this->getEmailAddressRepository()->getByAddress($emailAddress);

                if ($address) {
                    $address->setOptedOut(true);
                    $this->entityManager->saveEntity($address);
                }
            }
        }

        $link = $this->util->getLinkByEntityType($target->getEntityType());

        /** @var Collection<TargetList> $targetListList */
        $targetListList = $this->entityManager
            ->getRDBRepository(MassEmail::ENTITY_TYPE)
            ->getRelation($massEmail, 'targetLists')
            ->find();

        foreach ($targetListList as $targetList) {
            $relation = $this->entityManager
                ->getRDBRepository(TargetList::ENTITY_TYPE)
                ->getRelation($targetList, $link);

            if ($relation->getColumn($target, 'optedOut')) {
                continue;
            }

            $relation->updateColumnsById($target->getId(), ['optedOut' => true]);

            $hookData = [
                'link' => $link,
                'targetId' => $target->getId(),
                'targetType' => $target->getEntityType(),
            ];

            $this->hookManager->process(
                TargetList::ENTITY_TYPE,
                'afterOptOut',
                $targetList,
                [],
                $hookData
            );
        }

        $this->hookManager->process($target->getEntityType(), 'afterOptOut', $target);

        if ($campaign) {
            $this->service->logOptedOut($campaign->getId(), $queueItem, $target);
        }
    }

    /**
     * @throws NotFound
     */
    public function subscribeAgain(string $queueItemId): void
    {
        [, $campaign, $massEmail, $target] = $this->getRecords($queueItemId);

        if ($massEmail->optOutEntirely()) {
            $emailAddress = $target->get('emailAddress');

            if ($emailAddress) {
                $ea = $this->getEmailAddressRepository()->getByAddress($emailAddress);

                if ($ea) {
                    $ea->setOptedOut(false);
                    $this->entityManager->saveEntity($ea);
                }
            }
        }

        $link = $this->util->getLinkByEntityType($target->getEntityType());

        /** @var Collection<TargetList> $targetListList */
        $targetListList = $this->entityManager
            ->getRDBRepository(MassEmail::ENTITY_TYPE)
            ->getRelation($massEmail, 'targetLists')
            ->find();

        foreach ($targetListList as $targetList) {
            $relation = $this->entityManager
                ->getRDBRepository(TargetList::ENTITY_TYPE)
                ->getRelation($targetList, $link);

            if (!$relation->getColumn($target, 'optedOut')) {
                continue;
            }

            $relation->updateColumnsById($target->getId(), ['optedOut' => false]);

            $hookData = [
                'link' => $link,
                'targetId' => $target->getId(),
                'targetType' => $target->getEntityType(),
            ];

            $this->hookManager
                ->process(TargetList::ENTITY_TYPE, 'afterCancelOptOut', $targetList, [], $hookData);
        }

        $this->hookManager->process($target->getEntityType(), 'afterCancelOptOut', $target);

        if ($campaign) {
            $logRecord = $this->entityManager
                ->getRDBRepository(CampaignLogRecord::ENTITY_TYPE)
                ->where([
                    'queueItemId' => $queueItemId,
                    'action' => CampaignLogRecord::ACTION_OPTED_OUT,
                ])
                ->order('createdAt', true)
                ->findOne();

            if ($logRecord) {
                $this->entityManager->removeEntity($logRecord);
            }
        }
    }


    /**
     * @throws NotFound
     */
    public function unsubscribeWithHash(string $emailAddress, string $hash): void
    {
        $address = $this->getEmailAddressWithHash($emailAddress, $hash);

        if ($address->isOptedOut()) {
            return;
        }

        $address->setOptedOut(true);
        $this->entityManager->saveEntity($address);

        $entityList = $this->getEmailAddressRepository()->getEntityListByAddressId($address->getId());

        foreach ($entityList as $entity) {
            $this->hookManager->process($entity->getEntityType(), 'afterOptOut', $entity);
        }
    }

    /**
     * @throws NotFound
     */
    public function subscribeAgainWithHash(string $emailAddress, string $hash): void
    {
        $address = $this->getEmailAddressWithHash($emailAddress, $hash);

        if (!$address->isOptedOut()) {
            return;
        }

        $entityList = $this->getEmailAddressRepository()->getEntityListByAddressId($address->getId());

        $address->setOptedOut(false);
        $this->entityManager->saveEntity($address);

        foreach ($entityList as $entity) {
            $this->hookManager->process($entity->getEntityType(), 'afterCancelOptOut', $entity);
        }
    }

    /**
     * @throws NotFound
     */
    public function isSubscribed(string $queueItemId): bool
    {
        [,, $massEmail, $target] = $this->getRecords($queueItemId);

        if ($massEmail->optOutEntirely()) {
            $emailAddress = $target->get('emailAddress');

            if ($emailAddress) {
                $address = $this->getEmailAddressRepository()->getByAddress($emailAddress);

                if ($address && !$address->isOptedOut()) {
                    return true;
                }
            }
        }

        $link = $this->util->getLinkByEntityType($target->getEntityType());

        /** @var Collection<TargetList> $targetListList */
        $targetListList = $this->entityManager
            ->getRDBRepository(MassEmail::ENTITY_TYPE)
            ->getRelation($massEmail, 'targetLists')
            ->find();

        foreach ($targetListList as $targetList) {
            $relation = $this->entityManager
                ->getRDBRepository(TargetList::ENTITY_TYPE)
                ->getRelation($targetList, $link);

            if (!$relation->getColumn($target, 'optedOut')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws NotFound
     */
    public function isSubscribedWithHash(string $emailAddress, string $hash): bool
    {
        $address = $this->getEmailAddressWithHash($emailAddress, $hash);

        return !$address->isOptedOut();
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }

    /**
     * @return array{EmailQueueItem, ?Campaign, MassEmail, Entity}
     * @throws NotFound
     */
    private function getRecords(string $queueItemId): array
    {
        /** @var ?EmailQueueItem $queueItem */
        $queueItem = $this->entityManager->getEntityById(EmailQueueItem::ENTITY_TYPE, $queueItemId);

        if (!$queueItem) {
            throw new NotFound("No item.");
        }

        $campaign = null;
        $massEmailId = $queueItem->getMassEmailId();

        if (!$massEmailId) {
            throw new NotFound("No Mass Email ID.");
        }

        /** @var ?MassEmail $massEmail */
        $massEmail = $this->entityManager->getEntityById(MassEmail::ENTITY_TYPE, $massEmailId);

        if (!$massEmail) {
            throw new NotFound("Mass Email not found.");
        }

        if ($massEmail->getCampaignId()) {
            /** @var ?Campaign $campaign */
            $campaign = $this->entityManager->getEntityById(Campaign::ENTITY_TYPE, $massEmail->getCampaignId());
        }

        $targetType = $queueItem->getTargetType();
        $targetId = $queueItem->getTargetId();

        $target = $this->entityManager->getEntityById($targetType, $targetId);

        if (!$target) {
            throw new NotFound();
        }

        return [$queueItem, $campaign, $massEmail, $target];
    }

    /**
     * @throws NotFound
     */
    private function getEmailAddressWithHash(string $emailAddress, string $hash): EmailAddress
    {
        $hash2 = $this->hasher->hash($emailAddress);

        if ($hash2 !== $hash) {
            throw new NotFound();
        }

        $address = $this->getEmailAddressRepository()->getByAddress($emailAddress);

        if (!$address) {
            throw new NotFound();
        }

        return $address;
    }
}
