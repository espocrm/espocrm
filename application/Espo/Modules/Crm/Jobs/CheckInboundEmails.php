<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\CronManager;

use \Espo\Core\Exceptions\Error;

class CheckInboundEmails extends \Espo\Core\Jobs\Base
{
    public function run($data, $targetId)
    {
        if (!$targetId) {
            throw new Error();
        }

        $service = $this->getServiceFactory()->create('InboundEmail');
        $entity = $this->getEntityManager()->getEntity('InboundEmail', $targetId);

        if (!$entity) {
            throw new Error("Job CheckInboundEmails '".$targetId."': InboundEmail does not exist.", -1);
        }

        if ($entity->get('status') !== 'Active') {
            throw new Error("Job CheckInboundEmails '".$targetId."': InboundEmail is not active.", -1);
        }

        try {
            $service->fetchFromMailServer($entity);
        } catch (\Exception $e) {
            throw new Error('Job CheckInboundEmails '.$entity->id.': [' . $e->getCode() . '] ' .$e->getMessage());
        }
        return true;
    }

    public function prepare($scheduledJob, $executeTime)
    {
        $collection = $this->getEntityManager()->getRepository('InboundEmail')->where([
            'status' => 'Active',
            'useImap' => true
        ])->find();

        foreach ($collection as $entity) {
            $running = $this->getEntityManager()->getRepository('Job')->where([
                'scheduledJobId' => $scheduledJob->id,
                'status' => [CronManager::RUNNING, CronManager::READY],
                'targetType' => 'InboundEmail',
                'targetId' => $entity->id
            ])->findOne();
            if ($running) continue;

            $countPending = $this->getEntityManager()->getRepository('Job')->where([
                'scheduledJobId' => $scheduledJob->id,
                'status' => CronManager::PENDING,
                'targetType' => 'InboundEmail',
                'targetId' => $entity->id
            ])->count();
            if ($countPending > 1) continue;

            $jobEntity = $this->getEntityManager()->getEntity('Job');
            $jobEntity->set([
                'name' => $scheduledJob->get('name'),
                'scheduledJobId' => $scheduledJob->id,
                'executeTime' => $executeTime,
                'targetType' => 'InboundEmail',
                'targetId' => $entity->id
            ]);
            $this->getEntityManager()->saveEntity($jobEntity);
        }

        return true;
    }
}
