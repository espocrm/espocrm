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

namespace Espo\Tools\Attachment\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\Job\JobSchedulerFactory;

use Espo\Core\Field\DateTime;

use Espo\Core\FileStorage\Storages\EspoUploadDir;
use Espo\Core\Utils\Config;
use Espo\Core\FileStorage\Manager as FileStorageManager;

use Espo\ORM\EntityManager;

use Espo\Entities\Attachment;

use LogicException;

class MoveToStorage implements Job
{
    private const REMOVE_FILE_PERIOD = '3 hours';

    private EntityManager $entityManager;

    private Config $config;

    private FileStorageManager $fileStorageManager;

    private JobSchedulerFactory $jobSchedulerFactory;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        FileStorageManager $fileStorageManager,
        JobSchedulerFactory $jobSchedulerFactory
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->fileStorageManager = $fileStorageManager;
        $this->jobSchedulerFactory = $jobSchedulerFactory;
    }

    public function run(Data $data): void
    {
        $id = $data->getTargetId();

        if (!$id) {
            throw new LogicException();
        }

        /** @var Attachment|null $attachment */
        $attachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $id);

        if (!$attachment) {
            return;
        }

        if ($attachment->getStorage() !== EspoUploadDir::NAME) {
            return;
        }

        $defaultFileStorage = $this->config->get('defaultFileStorage');

        if (!$defaultFileStorage || $defaultFileStorage === EspoUploadDir::NAME) {
            return;
        }

        $stream = $this->fileStorageManager->getStream($attachment);

        $attachment->set('storage', $defaultFileStorage);

        $this->fileStorageManager->putStream($attachment, $stream);

        $this->entityManager->saveEntity($attachment);

        $this->jobSchedulerFactory->create()
            ->setClassName(RemoveUploadDirFile::class)
            ->setData(
                Data::create()
                    ->withTargetId($attachment->getId())
            )
            ->setTime(
                DateTime::createNow()
                    ->modify('+' . self::REMOVE_FILE_PERIOD)
                    ->toDateTime()
            )
            ->schedule();
    }
}
