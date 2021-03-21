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
    Job\JobManager,
    Job\JobTargeted,
    ServiceFactory,
    ORM\EntityManager,
};

use Espo\Entities\ScheduledJob;

use Throwable;
use StdClass;

class CheckEmailAccounts implements JobTargeted
{
    protected $serviceFactory;

    protected $entityManager;

    public function __construct(ServiceFactory $serviceFactory, EntityManager $entityManager)
    {
        $this->serviceFactory = $serviceFactory;
        $this->entityManager = $entityManager;
    }

    public function run(string $targetType, string $targetId, StdClass $data) : void
    {
        if (!$targetId) {
            throw new Error();
        }

        $service = $this->serviceFactory->create('EmailAccount');

        $entity = $this->entityManager->getEntity('EmailAccount', $targetId);

        if (!$entity) {
            throw new Error("Job CheckEmailAccounts '".$targetId."': EmailAccount does not exist.", -1);
        }

        if ($entity->get('status') !== 'Active') {
            throw new Error("Job CheckEmailAccounts '".$targetId."': EmailAccount is not active.", -1);
        }

        try {
            $service->fetchFromMailServer($entity);
        }
        catch (Throwable $e) {
            throw new Error('Job CheckEmailAccounts '.$entity->id.': [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    public function prepare(ScheduledJob $scheduledJob, string $executeTime) : void
    {
        $collection = $this->entityManager->getRepository('EmailAccount')
            ->join('assignedUser', 'assignedUserAdditional')
            ->where([
                'status' => 'Active',
                'useImap' => true,
                'assignedUserAdditional.isActive' => true,
            ])
            ->find();

        foreach ($collection as $entity) {
            $running = $this->entityManager
                ->getRepository('Job')
                ->where([
                    'scheduledJobId' => $scheduledJob->id,
                    'status' => [JobManager::RUNNING, JobManager::READY],
                    'targetType' => 'EmailAccount',
                    'targetId' => $entity->id,
                ])
                ->findOne();

            if ($running) {
                continue;
            }

            $countPending = $this->entityManager
                ->getRepository('Job')
                ->where([
                    'scheduledJobId' => $scheduledJob->id,
                    'status' => JobManager::PENDING,
                    'targetType' => 'EmailAccount',
                    'targetId' => $entity->id,
                ])
                ->count();

            if ($countPending > 1) {
                continue;
            }

            $jobEntity = $this->entityManager->getEntity('Job');

            $jobEntity->set([
                'name' => $scheduledJob->get('name'),
                'scheduledJobId' => $scheduledJob->id,
                'executeTime' => $executeTime,
                'targetType' => 'EmailAccount',
                'targetId' => $entity->id,
            ]);

            $this->entityManager->saveEntity($jobEntity);
        }
    }
}
