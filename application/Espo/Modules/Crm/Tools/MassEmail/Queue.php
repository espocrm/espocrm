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

namespace Espo\Modules\Crm\Tools\MassEmail;

use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Entities\EmailAddress;

use Espo\Modules\Crm\Entities\TargetList;
use Espo\Modules\Crm\Entities\MassEmail;

use Espo\Core\{
    Exceptions\Error,
    ORM\EntityManager,
};

class Queue
{
    protected $targetsLinkList = [
        'accounts',
        'contacts',
        'leads',
        'users',
    ];

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function cleanupQueueItems(MassEmail $massEmail): void
    {
        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('EmailQueueItem')
            ->where([
                 'massEmailId' => $massEmail->getId(),
                 'status' => ['Pending', 'Failed'],
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    public function create(MassEmail $massEmail, bool $isTest = false, iterable $additionalTargetList = []): void
    {
        if (!$isTest && $massEmail->get('status') !== 'Pending') {
            throw new Error("Mass Email '" . $massEmail->getId() . "' should be 'Pending'.");
        }

        $em = $this->entityManager;

        if (!$isTest) {
            $this->cleanupQueueItems($massEmail);
        }

        $metTargetHash = [];
        $metEmailAddressHash = [];
        $itemList = [];

        if (!$isTest) {
            $excludingTargetListList = $this->entityManager
                ->getRDBRepository('MassEmail')
                ->getRelation($massEmail, 'excludingTargetLists')
                ->find();

            foreach ($excludingTargetListList as $excludingTargetList) {
                foreach ($this->targetsLinkList as $link) {
                    $excludingList = $em->getRDBRepository('TargetList')
                        ->findRelated(
                            $excludingTargetList,
                            $link,
                            [
                                'select' => ['id', 'emailAddress'],
                            ]
                        );

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

            /** @var iterable<TargetList> */
            $targetListCollection = $em
                ->getRDBRepository('MassEmail')
                ->getRelation($massEmail, 'targetLists')
                ->find();

            foreach ($targetListCollection as $targetList) {
                foreach ($this->targetsLinkList as $link) {
                    $recordList = $em->getRDBRepository('TargetList')
                        ->getRelation($targetList, $link)
                        ->select(['id', 'emailAddress'])
                        ->sth()
                        ->where([
                            '@relation.optedOut' => false,
                        ])
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

            if (strpos($emailAddress, 'ERASED:') === 0) {
                continue;
            }

            $emailAddressRecord = $this->getEmailAddressRepository()->getByAddress($emailAddress);

            if ($emailAddressRecord) {
                if ($emailAddressRecord->get('invalid') || $emailAddressRecord->get('optOut')) {
                    continue;
                }
            }

            $queueItem = $this->entityManager->getEntity('EmailQueueItem');

            $queueItem->set([
                'massEmailId' => $massEmail->getId(),
                'status' => 'Pending',
                'targetId' => $item->id,
                'targetType' => $item->entityType,
                'isTest' => $isTest,
            ]);

            $this->entityManager->saveEntity($queueItem);
        }

        if (!$isTest) {
            $massEmail->set('status', 'In Process');

            if (empty($itemList)) {
                $massEmail->set('status', 'Complete');
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
