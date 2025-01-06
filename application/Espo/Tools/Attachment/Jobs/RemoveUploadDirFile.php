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

use Espo\Core\Utils\File\Manager as FileManager;

use Espo\Core\FileStorage\Factory as FileStorageFactory;
use Espo\Core\FileStorage\Storages\EspoUploadDir;
use Espo\Core\FileStorage\Local;

use Espo\Entities\Attachment;

use Espo\Core\FileStorage\AttachmentEntityWrapper;

use Espo\ORM\EntityManager;

use LogicException;

class RemoveUploadDirFile implements Job
{
    private FileManager $fileManager;

    private FileStorageFactory $fileStorageFactory;

    private EntityManager $entityManager;

    public function __construct(
        FileManager $fileManager,
        FileStorageFactory $fileStorageFactory,
        EntityManager $entityManager
    ) {
        $this->fileManager = $fileManager;
        $this->fileStorageFactory = $fileStorageFactory;
        $this->entityManager = $entityManager;
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

        if ($attachment->getStorage() === EspoUploadDir::NAME) {
            return;
        }

        $storage = $this->fileStorageFactory->create(EspoUploadDir::NAME);

        if (!$storage instanceof Local) {
            throw new LogicException();
        }

        $filePath = $storage->getLocalFilePath(
            new AttachmentEntityWrapper($attachment)
        );

        if (!$this->fileManager->isFile($filePath)) {
            return;
        }

        $this->fileManager->remove($filePath);
    }
}
