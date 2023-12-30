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

namespace Espo\Core\Job;

use Espo\Entities\Job as JobEntity;
use Espo\Core\Job\QueueProcessor\Params;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\System;
use Espo\Core\Job\Job\Status;

use Spatie\Async\Pool as AsyncPool;

class QueueProcessor
{
    private bool $noTableLocking;

    public function __construct(
        private QueueUtil $queueUtil,
        private JobRunner $jobRunner,
        private AsyncPoolFactory $asyncPoolFactory,
        private EntityManager $entityManager,
        ConfigDataProvider $configDataProvider
    ) {
        $this->noTableLocking = $configDataProvider->noTableLocking();
    }

    public function process(Params $params): void
    {
        $pool = $params->useProcessPool() ?
            $this->asyncPoolFactory->create() :
            null;

        $pendingJobList = $this->queueUtil->getPendingJobList(
            $params->getQueue(),
            $params->getGroup(),
            $params->getLimit()
        );

        foreach ($pendingJobList as $job) {
            $this->processJob($params, $job, $pool);
        }

        $pool?->wait();
    }

    private function processJob(Params $params, JobEntity $job, ?AsyncPool $pool = null): void
    {
        $noLock = $params->noLock();
        $lockTable = $job->getScheduledJobId() && !$noLock && !$this->noTableLocking;

        if ($lockTable) {
            // MySQL doesn't allow to lock non-existent rows. We resort to locking an entire table.
            $this->entityManager->getLocker()->lockExclusive(JobEntity::ENTITY_TYPE);
        }

        $skip = !$noLock && !$this->queueUtil->isJobPending($job->getId());

        if (
            !$skip &&
            $job->getScheduledJobId() &&
            $this->queueUtil->isScheduledJobRunning(
                $job->getScheduledJobId(),
                $job->getTargetId(),
                $job->getTargetType(),
                $job->getTargetGroup()
            )
        ) {
            $skip = true;
        }

        if ($skip && $lockTable) {
            $this->entityManager->getLocker()->rollback();
        }

        if ($skip) {
            return;
        }

        $job->setStartedAtNow();

        if ($pool) {
            $job->setStatus(Status::READY);
        }
        else {
            $job->setStatus(Status::RUNNING);
            $job->setPid(System::getPid());
        }

        $this->entityManager->saveEntity($job);

        if ($lockTable) {
            $this->entityManager->getLocker()->commit();
        }

        if (!$pool) {
            $this->jobRunner->run($job);

            return;
        }

        $task = new JobTask($job->getId());

        $pool->add($task);
    }
}
