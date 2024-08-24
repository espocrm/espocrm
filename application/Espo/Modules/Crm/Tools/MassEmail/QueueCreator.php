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

use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Entities\EmailAddress;
use Espo\Modules\Crm\Entities\TargetList;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Core\Exceptions\Error;
use Espo\ORM\EntityManager;
use Espo\Core\Utils\Metadata;

class QueueCreator
{
    private const ERASED_PREFIX = 'ERASED:';

    /** @var string[] */
    protected array $targetLinkList;

    public function __construct(
        protected EntityManager $entityManager,
        private Metadata $metadata
    ) {
        $this->targetLinkList = $this->metadata->get(['scopes', 'TargetList', 'targetLinkList']) ?? [];
    }

    private function cleanupQueueItems(MassEmail $massEmail): void
    {
        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(EmailQueueItem::ENTITY_TYPE)
            ->where([
                 'massEmailId' => $massEmail->getId(),
                 'status' => [
                     EmailQueueItem::STATUS_PENDING,
                     EmailQueueItem::STATUS_FAILED,
                ],
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    /**
     * @param iterable<Entity> $additionalTargetList
     * @throws Error
     */
    public function create(MassEmail $massEmail, bool $isTest = false, iterable $additionalTargetList = []): void
    {
        if (!$isTest && $massEmail->getStatus() !== MassEmail::STATUS_PENDING) {
            throw new Error("Mass Email {$massEmail->getId()} should has status 'Pending'.");
        }

        if (!$isTest) {
            $this->cleanupQueueItems($massEmail);
        }

        $metTargetHash = [];
        $metEmailAddressHash = [];
        $itemList = [];

        if (!$isTest) {
            /** @var Collection<TargetList> $excludingTargetListList */
            $excludingTargetListList = $this->entityManager
                ->getRDBRepositoryByClass(MassEmail::class)
                ->getRelation($massEmail, 'excludingTargetLists')
                ->find();

            foreach ($excludingTargetListList as $excludingTargetList) {
                foreach ($this->targetLinkList as $link) {
                    $excludingList = $this->entityManager
                        ->getRDBRepositoryByClass(TargetList::class)
                        ->getRelation($excludingTargetList, $link)
                        ->sth()
                        ->select(['id', 'emailAddress'])
                        ->find();

                    foreach ($excludingList as $excludingTarget) {
                        $hashId = $excludingTarget->getEntityType() . '-'. $excludingTarget->getId();

                        $metTargetHash[$hashId] = true;

                        $emailAddress = $excludingTarget->get('emailAddress');

                        if ($emailAddress) {
                            $metEmailAddressHash[$emailAddress] = true;
                        }
                    }
                }
            }

            /** @var Collection<TargetList> $targetListCollection */
            $targetListCollection = $this->entityManager
                ->getRDBRepositoryByClass(MassEmail::class)
                ->getRelation($massEmail, 'targetLists')
                ->find();

            foreach ($targetListCollection as $targetList) {
                foreach ($this->targetLinkList as $link) {
                    $recordList = $this->entityManager
                        ->getRDBRepositoryByClass(TargetList::class)
                        ->getRelation($targetList, $link)
                        ->select(['id', 'emailAddress'])
                        ->sth()
                        ->where(['@relation.optedOut' => false])
                        ->find();

                    foreach ($recordList as $record) {
                        $hashId = $record->getEntityType() . '-'. $record->getId();

                        $emailAddress = $record->get('emailAddress');

                        if (!$emailAddress) {
                            continue;
                        }

                        if (!empty($metEmailAddressHash[$emailAddress])) {
                            continue;
                        }

                        if (!empty($metTargetHash[$hashId])) {
                            continue;
                        }

                        $item = $record->getValueMap();

                        $item->entityType = $record->getEntityType();

                        $itemList[] = $item;

                        $metTargetHash[$hashId] = true;
                        $metEmailAddressHash[$emailAddress] = true;
                    }
                }
            }
        }

        foreach ($additionalTargetList as $record) {
            $item = $record->getValueMap();

            $item->entityType = $record->getEntityType();

            $itemList[] = $item;
        }

        foreach ($itemList as $item) {
            $emailAddress = $item->emailAddress ?? null;

            if (!$emailAddress) {
                continue;
            }

            if (str_starts_with($emailAddress, self::ERASED_PREFIX)) {
                continue;
            }

            $emailAddressRecord = $this->getEmailAddressRepository()->getByAddress($emailAddress);

            if ($emailAddressRecord) {
                if (
                    $emailAddressRecord->isInvalid() ||
                    $emailAddressRecord->isOptedOut()
                ) {
                    continue;
                }
            }

            $queueItem = $this->entityManager->getNewEntity(EmailQueueItem::ENTITY_TYPE);

            $queueItem->set([
                'massEmailId' => $massEmail->getId(),
                'status' => EmailQueueItem::STATUS_PENDING,
                'targetId' => $item->id,
                'targetType' => $item->entityType,
                'isTest' => $isTest,
            ]);

            $this->entityManager->saveEntity($queueItem);
        }

        if (!$isTest) {
            $massEmail->set('status', MassEmail::STATUS_IN_PROCESS);

            if (empty($itemList)) {
                $massEmail->set('status', MassEmail::STATUS_COMPLETE);
            }

            $this->entityManager->saveEntity($massEmail);
        }
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }
}
