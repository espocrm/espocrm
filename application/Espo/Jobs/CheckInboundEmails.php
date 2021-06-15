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

namespace Espo\Jobs;

use Espo\Core\Exceptions\Error;

use Espo\Core\{
    Job\JobStatus,
    Job\JobPreparable,
    Job\JobData,
    Job\ScheduledJobData,
    ServiceFactory,
    ORM\EntityManager,
    Utils\DateTime,
};

use Throwable;
use DateTimeImmutable;

class CheckInboundEmails implements JobPreparable
{
    private $serviceFactory;

    private $entityManager;

    public function __construct(ServiceFactory $serviceFactory, EntityManager $entityManager)
    {
        $this->serviceFactory = $serviceFactory;
        $this->entityManager = $entityManager;
    }

    public function run(JobData $data): void
    {
        $targetId = $data->getTargetId();

        if (!$targetId) {
            throw new Error("No target.");
        }

        $service = $this->serviceFactory->create('InboundEmail');

        $entity = $this->entityManager->getEntity('InboundEmail', $targetId);

        if (!$entity) {
            throw new Error("Job CheckInboundEmails '{$targetId}': InboundEmail does not exist.", -1);
        }

        if ($entity->get('status') !== 'Active') {
            throw new Error("Job CheckInboundEmails '{$targetId}': InboundEmail is not active.", -1);
        }

        try {
            $service->fetchFromMailServer($entity);
        }
        catch (Throwable $e) {
            throw new Error(
                'Job CheckInboundEmails ' . $entity->getId() . ': [' . $e->getCode() . '] ' .$e->getMessage()
            );
        }
    }

    public function prepare(ScheduledJobData $data, DateTimeImmutable $executeTime): void
    {
        $collection = $this->entityManager
            ->getRDBRepository('InboundEmail')
            ->where([
                'status' => 'Active',
                'useImap' => true,
            ])
            ->find();

        foreach ($collection as $entity) {
            $running = $this->entityManager
                ->getRDBRepository('Job')
                ->where([
                    'scheduledJobId' => $data->getId(),
                    'status' => [
                        JobStatus::RUNNING,
                        JobStatus::READY,
                    ],
                    'targetType' => 'InboundEmail',
                    'targetId' => $entity->getId(),
                ])
                ->findOne();

            if ($running) {
                continue;
            }

            $countPending = $this->entityManager
                ->getRDBRepository('Job')
                ->where([
                    'scheduledJobId' => $data->getId(),
                    'status' => JobStatus::PENDING,
                    'targetType' => 'InboundEmail',
                    'targetId' => $entity->getId(),
                ])
                ->count();

            if ($countPending > 1) {
                continue;
            }

            $jobEntity = $this->entityManager->getEntity('Job');

            $jobEntity->set([
                'name' => $data->getName(),
                'scheduledJobId' => $data->getId(),
                'executeTime' => $executeTime->format(DateTime::SYSTEM_DATE_TIME_FORMAT),
                'targetType' => 'InboundEmail',
                'targetId' => $entity->getId(),
            ]);

            $this->entityManager->saveEntity($jobEntity);
        }
    }
}
