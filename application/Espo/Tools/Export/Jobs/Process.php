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

namespace Espo\Tools\Export\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data as JobData;
use Espo\Tools\Export\Factory;
use Espo\Tools\Export\Result;
use Espo\Core\Utils\Language;
use Espo\ORM\EntityManager;
use Espo\Entities\Export;
use Espo\Entities\Notification;
use Espo\Entities\User;

use RuntimeException;
use Throwable;

class Process implements Job
{
    public const string PARAM_USER_SCOPES = 'userScopes';

    public function __construct(
        private EntityManager $entityManager,
        private Factory $factory,
        private Language $language,
    ) {}

    public function run(JobData $data): void
    {
        $id = $data->getTargetId();

        if (!$id) {
            throw new RuntimeException("ID not passed to the export job.");
        }

        $entity = $this->entityManager->getRDBRepositoryByClass(Export::class)->getById($id);

        if (!$entity) {
            throw new RuntimeException("Export '$id' not found.");
        }

        $user = $this->getUser($entity, $data);

        try {
            $export = $this->factory->createForUser($user);

            $this->setRunning($entity);

            $result = $export
                ->setParams($entity->getParams())
                ->run();
        } catch (Throwable $e) {
            $this->setFailed($entity);

            throw new RuntimeException("Export job error: " . $e->getMessage());
        }

        $this->setSuccess($entity, $result);

        $this->entityManager->refreshEntity($entity);

        if ($entity->notifyOnFinish()) {
            $this->notifyFinish($entity);
        }
    }

    private function notifyFinish(Export $entity): void
    {
        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $url = '?entryPoint=download&id=' . $entity->getAttachmentId();

        $message = str_replace(
            '{url}',
            $url,
            $this->language->translateLabel('exportProcessed', 'messages', 'Export')
        );

        $notification
            ->setType(Notification::TYPE_MESSAGE)
            ->setMessage($message)
            ->setUserId($entity->getCreatedBy()->getId());

        $this->entityManager->saveEntity($notification);
    }

    private function setFailed(Export $entity): void
    {
        $entity->setStatus(Export::STATUS_FAILED);

        $this->entityManager->saveEntity($entity);
    }

    private function setRunning(Export $entity): void
    {
        $entity->setStatus(Export::STATUS_RUNNING);

        $this->entityManager->saveEntity($entity);
    }

    private function setSuccess(Export $entity, Result $result): void
    {
        $entity
            ->setStatus(Export::STATUS_SUCCESS)
            ->setAttachmentId($result->getAttachmentId());

        $this->entityManager->saveEntity($entity);
    }

    private function getUser(Export $entity, JobData $data): User
    {
        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($entity->getCreatedBy()->getId());

        if (!$user) {
            throw new RuntimeException("Export entity '{$entity->getId()}', user not found.");
        }

        $userScopes = $data->get(self::PARAM_USER_SCOPES);

        if (is_array($userScopes)) {
            $user->setScopes($userScopes);
        }

        return $user;
    }
}
