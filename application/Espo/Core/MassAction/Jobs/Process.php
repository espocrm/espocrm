<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\MassAction\Jobs;

use Espo\Core\Exceptions\Error;
use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data as JobData;
use Espo\Core\MassAction\MassActionFactory;
use Espo\Core\MassAction\Result;
use Espo\Core\Utils\Language;

use Espo\ORM\EntityManager;

use Espo\Entities\MassAction as MassActionEntity;
use Espo\Entities\Notification;
use Espo\Entities\User;

use Throwable;

class Process implements Job
{
    private $entityManager;

    private $factory;

    private $language;

    public function __construct(
        EntityManager $entityManager,
        MassActionFactory $factory,
        Language $language
    ) {
        $this->entityManager = $entityManager;
        $this->factory = $factory;
        $this->language = $language;
    }

    public function run(JobData $data): void
    {
        $id = $data->getTargetId();

        if ($id === null) {
            throw new Error("ID not passed to the mass action job.");
        }

        /** @var MassActionEntity|null $entity */
        $entity = $this->entityManager->getEntity(MassActionEntity::ENTITY_TYPE, $id);

        if ($entity === null) {
            throw new Error("MassAction '{$id}' not found.");
        }

        /** @var User|null $user */
        $user = $this->entityManager->getEntity(User::ENTITY_TYPE, $entity->getCreatedBy()->getId());

        if (!$user) {
            throw new Error("MassAction '{$id}', user not found.");
        }

        $params = $entity->getParams();

        try {
            $massAction = $this->factory->createForUser($entity->getAction(), $params->getEntityType(), $user);

            $this->setRunning($entity);

            $result = $massAction->process(
                $params,
                $entity->getData()
            );
        }
        catch (Throwable $e) {
            $this->setFailed($entity);

            throw new Error("Mass action job error: " . $e->getMessage());
        }

        $this->setSuccess($entity, $result);

        $this->entityManager->refreshEntity($entity);

        if ($entity->notifyOnFinish()) {
            $this->notifyFinish($entity);
        }
    }

    private function notifyFinish(MassActionEntity $entity): void
    {
        /** @var Notification $notification */
        $notification = $this->entityManager->getNewEntity(Notification::ENTITY_TYPE);

        $message = $this->language->translateLabel('massActionProcessed', 'messages');

        $notification
            ->setType(Notification::TYPE_MESSAGE)
            ->setMessage($message)
            ->setUserId($entity->getCreatedBy()->getId());

        $this->entityManager->saveEntity($notification);
    }

    private function setFailed(MassActionEntity $entity): void
    {
        $entity->setStatus(MassActionEntity::STATUS_FAILED);

        $this->entityManager->saveEntity($entity);
    }

    private function setRunning(MassActionEntity $entity): void
    {
        $entity->setStatus(MassActionEntity::STATUS_RUNNING);

        $this->entityManager->saveEntity($entity);
    }

    private function setSuccess(MassActionEntity $entity, Result $result): void
    {
        $entity
            ->setStatus(MassActionEntity::STATUS_SUCCESS)
            ->setProcessedCount($result->getCount());

        $this->entityManager->saveEntity($entity);
    }
}
