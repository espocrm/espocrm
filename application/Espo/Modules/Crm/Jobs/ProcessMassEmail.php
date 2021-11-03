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

namespace Espo\Modules\Crm\Jobs;

use Espo\Core\{
    ORM\EntityManager,
    Job\JobDataLess,
    Utils\Log,
};

use Espo\{
    Modules\Crm\Tools\MassEmail\Processor,
    Modules\Crm\Tools\MassEmail\Queue,
};

use Throwable;

class ProcessMassEmail implements JobDataLess
{
    private $processor;

    private $queue;

    private $entityManager;

    private $log;

    public function __construct(Processor $processor, Queue $queue, EntityManager $entityManager, Log $log)
    {
        $this->processor = $processor;
        $this->queue = $queue;
        $this->entityManager = $entityManager;
        $this->log = $log;
    }

    public function run(): void
    {
        $pendingMassEmailList = $this->entityManager
            ->getRDBRepository('MassEmail')
            ->where([
                'status' => 'Pending',
                'startAt<=' => date('Y-m-d H:i:s'),
            ])
            ->find();

        foreach ($pendingMassEmailList as $massEmail) {
            try {
                $this->queue->create($massEmail);
            }
            catch (Throwable $e) {
                $this->log->error(
                    'Job ProcessMassEmail#createQueue ' . $massEmail->getId() . ': [' . $e->getCode() . '] ' .
                    $e->getMessage()
                );
            }
        }

        $massEmailList = $this->entityManager
            ->getRDBRepository('MassEmail')
            ->where([
                'status' => 'In Process',
            ])
            ->find();

        foreach ($massEmailList as $massEmail) {
            try {
                $this->processor->process($massEmail);
            }
            catch (Throwable $e) {
                $this->log->error(
                    'Job ProcessMassEmail#processSending '. $massEmail->getId() . ': [' . $e->getCode() . '] ' .
                    $e->getMessage()
                );
            }
        }
    }
}
