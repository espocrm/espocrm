<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Job\QueueProcessor\QueueProcessors;

use Espo\Core\Job\AsyncPoolFactory;
use Espo\Core\Job\ConfigDataProvider;
use Espo\Core\Job\Job\Status;
use Espo\Core\Job\JobTask;
use Espo\Core\Job\QueueProcessor;
use Espo\Core\Job\QueueProcessor\Params;
use Espo\Core\Job\QueueProcessor\Picker;
use Espo\Core\Job\QueueUtil;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\Job;
use Spatie\Async\Pool as AsyncPool;

class ProcessPoolQueueProcessor implements QueueProcessor
{
    public function __construct(
        private QueueUtil $queueUtil,
        private AsyncPoolFactory $asyncPoolFactory,
        private EntityManager $entityManager,
        private Picker $picker,
        private ConfigDataProvider $configDataProvider,
    ) {}

    public function process(Params $params): void
    {
        $noLock = $this->skipLock($params);

        $pool = $this->asyncPoolFactory->create();

        foreach ($this->picker->pick($params) as $job) {
            $this->processJob($noLock, $job, $pool);
        }

        $pool->wait();
    }

    private function processJob(bool $noLock, Job $job, AsyncPool $pool): void
    {
        $lockTable = !$noLock && $job->getScheduledJobId();

        if ($lockTable) {
            // MySQL doesn't allow to lock non-existent rows. We resort to locking an entire table.
            $this->entityManager->getLocker()->lockExclusive(Job::ENTITY_TYPE);
        }

        $skip = $this->toSkip($noLock, $job);

        if ($skip) {
            if ($lockTable) {
                $this->entityManager->getLocker()->rollback();
            }

            return;
        }

        $this->prepareJob($job);

        if ($lockTable) {
            $this->entityManager->getLocker()->commit();
        }

        $this->runJob($job, $pool);
    }

    private function toSkip(bool $noLock, Job $job): bool
    {
        if (!$noLock && !$this->queueUtil->isJobPending($job)) {
            return true;
        }

        if ($this->queueUtil->isScheduledJobRunning($job)) {
            return true;
        }

        return false;
    }

    private function prepareJob(Job $job): void
    {
        $job
            // Needed for failing not started.
            ->setStartedAtNow()
            ->setStatus(Status::READY);

        $this->entityManager->saveEntity($job);
    }

    private function runJob(Job $job, AsyncPool $pool): void
    {
        $task = new JobTask($job->getId());

        $pool->add($task);
    }

    private function skipLock(Params $params): bool
    {
        return
            $params->getGroup() !== null ||
            $params->getQueue() !== null ||
            $this->configDataProvider->noTableLocking();
    }
}
