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

namespace Espo\Core\Job\Processing;

use Espo\Core\Job\ConfigDataProvider;
use Espo\Core\Job\Job\Status;
use Espo\Core\Job\Processing\Util\ExitPolicy;
use Espo\Core\Job\Processing\Util\ExitSetup;
use Espo\Core\Job\QueueName;
use Espo\Core\Job\QueueProcessor\Params;
use Espo\Core\Job\QueueProcessor\Picker;
use Espo\Core\Job\QueueUtil;
use Espo\Core\Utils\Log;
use Espo\Entities\Job;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use RuntimeException;
use Throwable;

/**
 * @since 10.1.0
 *
 * @internal
 */
class EnqueueDaemon
{
    private const float INTERVAL = 1.0;

    private bool $stopped = false;

    public function __construct(
        private Picker $picker,
        private ConfigDataProvider $configDataProvider,
        private Publisher $publisher,
        private EntityManager $entityManager,
        private QueueUtil $queueUtil,
        private Log $log,
        private ExitSetup $exitSetup,
        private ExitPolicy $exitPolicy,
    ) {}

    public function run(EnqueueDaemon\Params $params): void
    {
        $interval = $this->getInterval($params);
        $limit = $params->limit;

        $pickerParams = $this->getPickerParams($params);
        $publisherParams = $this->preparePublisherParams($params);

        $this->publisher->initialize($publisherParams);

        $this->exitSetup->setup(function () {
            $this->stopped = true;
        });

        $count = 0;

        while (true) {
            $jobs = $this->picker->pick($pickerParams);

            foreach ($jobs as $job) {
                $this->processJob($job);

                $count ++;

                if ($this->toForceExit() || $this->toExit($limit, $count)) {
                    break 2;
                }
            }

            usleep($interval);

            if ($this->toForceExit()) {
                break;
            }
        }

        $this->publisher->close();
    }

    private function isStopped(): bool
    {
        return $this->stopped;
    }

    private function getPickerParams(EnqueueDaemon\Params $daemonParams): Params
    {
        $limit = $daemonParams->portion ?? $this->configDataProvider->getMaxPortion();

        $params = Params::create()
            ->withLimit($limit);

        if ($daemonParams->queue !== null) {
            return $params->withQueue($daemonParams->queue);
        }

        return $params->withSubQueueParamsList([
            $params->withWeight(0.5),
            $params->withQueue(QueueName::M0)->withWeight(0.5),
        ]);
    }

    private function getInterval(EnqueueDaemon\Params $params): int
    {
        $interval = $params->interval ?? self::INTERVAL;

        return (int) ($interval * 1000000);
    }

    private function prepareJob(Job $job): void
    {
        $job
            // Needed for failing not started.
            ->setStartedAtNow()
            ->setStatus(Status::READY);

        $this->entityManager->saveEntity($job);
    }

    private function processJob(Job $job): void
    {
        $this->entityManager->getTransactionManager()->run(function () use ($job) {
            $this->processJobInternal($job);
        });
    }

    private function toSkip(Job $job): bool
    {
        if ($job->getStatus() !== Status::PENDING) {
            return true;
        }

        if ($this->queueUtil->isScheduledJobRunning($job)) {
            return true;
        }

        return false;
    }

    private function fetchLocked(Job $job): ?Job
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(Job::class)
            ->forUpdate()
            ->where([Attribute::ID => $job->getId()])
            ->findOne();
    }

    private function processJobInternal(Job $job): void
    {
        $job = $this->fetchLocked($job);

        if (!$job || $this->toSkip($job)) {
            return;
        }

        $this->prepareJob($job);

        try {
            $this->publisher->publish($job);
        } catch (Throwable $e) {
            $this->log->error("Enqueue: Could not publish job {id}.", [
                'id' => $job->getId(),
                'exception' => $e,
            ]);

            throw new RuntimeException(previous: $e);
        }
    }

    private function toExit(?int $limit, int $count): bool
    {
        return $limit && $count >= $limit;
    }

    /**
     * @phpstan-impure
     */
    private function toForceExit(): bool
    {
        return $this->isStopped() || $this->exitPolicy->toExit();
    }

    private function preparePublisherParams(EnqueueDaemon\Params $params): Publisher\Params
    {
        return new Publisher\Params(
            queue: $params->queue,
        );
    }
}
