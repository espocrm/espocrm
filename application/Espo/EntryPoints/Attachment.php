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

namespace Espo\EntryPoints;

use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment as AttachmentEntity;
use Espo\Core\Acl;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\ORM\EntityManager;

class Attachment implements EntryPoint
{
    public function __construct(
        private FileStorageManager $fileStorageManager,
        private EntityManager $entityManager,
        private Acl $acl,
        private Metadata $metadata
    ) {}

    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');

        if (!$id) {
            throw new BadRequest("No id.");
        }

        $attachment = $this->entityManager
            ->getRDBRepositoryByClass(AttachmentEntity::class)
            ->getById($id);

        if (!$attachment) {
            throw new NotFound("Attachment not found.");
        }

        if (!$this->acl->checkEntity($attachment)) {
            throw new Forbidden("No access to attachment.");
        }

        if (!$this->fileStorageManager->exists($attachment)) {
            throw new NotFound("File not found.");
        }

        $fileType = $attachment->getType();

        if (!in_array($fileType, $this->getAllowedFileTypeList())) {
            throw new Forbidden("Not allowed file type '{$fileType}'.");
        }

        if ($attachment->isBeingUploaded()) {
            throw new Forbidden("Attachment is being-uploaded.");
        }

        if ($fileType) {
            $response->setHeader('Content-Type', $fileType);
        }

        $stream = $this->fileStorageManager->getStream($attachment);

        $size = $stream->getSize() ?? $this->fileStorageManager->getSize($attachment);

        $response
            ->setHeader('Content-Length', (string) $size)
            ->setHeader('Cache-Control', 'private, max-age=864000, immutable')
            ->setHeader('Content-Security-Policy', "default-src 'self'")
            ->setBody($stream);
    }

    /**
     * @return string[]
     */
    private function getAllowedFileTypeList(): array
    {
        return $this->metadata->get(['app', 'image', 'allowedFileTypeList']) ?? [];
    }
}
