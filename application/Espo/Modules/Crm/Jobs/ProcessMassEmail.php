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

namespace Espo\Modules\Crm\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\Log;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\Crm\Tools\MassEmail\QueueCreator;
use Espo\Modules\Crm\Tools\MassEmail\SendingProcessor;
use Throwable;

/**
 * @noinspection PhpUnused
 */
class ProcessMassEmail implements JobDataLess
{
    public function __construct(
        private SendingProcessor $processor,
        private QueueCreator $queue,
        private EntityManager $entityManager,
        private Log $log
    ) {}

    public function run(): void
    {
        $this->processCreateQueue();
        $this->processSend();
    }

    private function processCreateQueue(): void
    {
        $pendingMassEmails = $this->entityManager
            ->getRDBRepositoryByClass(MassEmail::class)
            ->where([
                'status' => MassEmail::STATUS_PENDING,
                'startAt<=' => date(DateTime::SYSTEM_DATE_TIME_FORMAT),
            ])
            ->find();

        foreach ($pendingMassEmails as $massEmail) {
            try {
                $this->queue->create($massEmail);
            } catch (Throwable $e) {
                $this->log->error("Create queue error. {id}. {message}", [
                    'id' => $massEmail->getId(),
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }
    }

    private function processSend(): void
    {
        $inProcessMassEmails = $this->entityManager
            ->getRDBRepositoryByClass(MassEmail::class)
            ->where([
                'status' => MassEmail::STATUS_IN_PROCESS,
            ])
            ->find();

        foreach ($inProcessMassEmails as $massEmail) {
            try {
                $this->processor->process($massEmail);
            } catch (Throwable $e) {
                $this->log->error("Sending mass email error. {id}. {message}", [
                    'id' => $massEmail->getId(),
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }
    }
}
