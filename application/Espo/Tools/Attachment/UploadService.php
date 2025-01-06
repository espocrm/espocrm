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

namespace Espo\Tools\Attachment;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\FileStorage\Storages\EspoUploadDir;
use Espo\Core\Job\Job\Data as JobData;
use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Entities\Attachment;
use Espo\ORM\EntityManager;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Tools\Attachment\Jobs\MoveToStorage;
use Espo\Core\ORM\Type\FieldType;

class UploadService
{
    private JobSchedulerFactory $jobSchedulerFactory;
    private ServiceContainer $recordServiceContainer;
    private Acl $acl;
    private EntityManager $entityManager;
    private FileManager $fileManager;
    private DetailsObtainer $detailsObtainer;
    private Checker $checker;

    public function __construct(
        JobSchedulerFactory $jobSchedulerFactory,
        ServiceContainer $recordServiceContainer,
        Acl $acl,
        EntityManager $entityManager,
        FileManager $fileManager,
        DetailsObtainer $detailsObtainer,
        Checker $checker
    ) {
        $this->jobSchedulerFactory = $jobSchedulerFactory;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->fileManager = $fileManager;
        $this->detailsObtainer = $detailsObtainer;
        $this->checker = $checker;
    }

    /**
     * Upload a chunk.
     *
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function uploadChunk(string $id, string $fileData): void
    {
        if (!$this->acl->checkScope(Attachment::ENTITY_TYPE, Table::ACTION_CREATE)) {
            throw new Forbidden();
        }

        /** @var ?Attachment $attachment */
        $attachment = $this->recordServiceContainer
            ->get(Attachment::ENTITY_TYPE)
            ->getEntity($id);

        if (!$attachment) {
            throw new NotFound();
        }

        if (!$attachment->isBeingUploaded()) {
            throw new Forbidden("Attachment is not being-uploaded.");
        }

        if ($attachment->getStorage() !== EspoUploadDir::NAME) {
            throw new Forbidden("Attachment storage is not 'EspoUploadDir'.");
        }

        $arr = explode(';base64,', $fileData);

        if (count($arr) < 2) {
            throw new BadRequest("Bad file data.");
        }

        $contents = base64_decode($arr[1]);

        $filePath = $this->getAttachmentRepository()->getFilePath($attachment);

        $chunkSize = strlen($contents);

        $actualFileSize = 0;

        if ($this->fileManager->isFile($filePath)) {
            $actualFileSize = $this->fileManager->getSize($filePath);
        }

        $maxFileSize = $this->detailsObtainer->getUploadMaxSize($attachment);

        if ($actualFileSize + $chunkSize > $maxFileSize) {
            throw new Forbidden("Max attachment size exceeded.");
        }

        $this->fileManager->appendContents($filePath, $contents);

        if ($actualFileSize + $chunkSize > $attachment->getSize()) {
            throw new Error("File size mismatch.");
        }

        $isLastChunk = $actualFileSize + $chunkSize === $attachment->getSize();

        if (!$isLastChunk) {
            return;
        }

        if ($this->detailsObtainer->getFieldType($attachment) === FieldType::IMAGE) {
            try {
                $this->checker->checkTypeImage($attachment, $filePath);
            } catch (Forbidden $e) {
                $this->entityManager->removeEntity($attachment);

                throw new ForbiddenSilent($e->getMessage());
            }
        }

        $attachment->set('isBeingUploaded', false);

        $this->entityManager->saveEntity($attachment);

        $this->createJobMoveToStorage($attachment);
    }

    private function getAttachmentRepository(): AttachmentRepository
    {
        /** @var AttachmentRepository */
        return $this->entityManager->getRepositoryByClass(Attachment::class);
    }

    private function createJobMoveToStorage(Attachment $attachment): void
    {
         $this->jobSchedulerFactory
            ->create()
            ->setClassName(MoveToStorage::class)
            ->setData(
                JobData::create()
                    ->withTargetId($attachment->getId())
            )
            ->schedule();
    }
}
