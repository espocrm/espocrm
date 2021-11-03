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

namespace Espo\EntryPoints;

use Espo\Core\Utils\Metadata;

use Espo\Core\{
    Exceptions\NotFound,
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    EntryPoint\EntryPoint,
    Api\Request,
    Api\Response,
    FileStorage\Manager as FileStorageManager,
    ORM\EntityManager,
    Acl,
};

class Attachment implements EntryPoint
{
    private $fileStorageManager;

    private $entityManager;

    private $acl;

    private $metadata;

    public function __construct(
        FileStorageManager $fileStorageManager,
        EntityManager $entityManager,
        Acl $acl,
        Metadata $metadata
    ) {
        $this->fileStorageManager = $fileStorageManager;
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->metadata = $metadata;
    }

    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');

        if (!$id) {
            throw new BadRequest();
        }

        $attachment = $this->entityManager->getEntity('Attachment', $id);

        if (!$attachment) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntity($attachment)) {
            throw new Forbidden();
        }

        if (!$this->fileStorageManager->exists($attachment)) {
            throw new NotFound();
        }

        $fileType = $attachment->get('type');

        if (!in_array($fileType, $this->getAllowedFileTypeList())) {
            throw new Forbidden("Not allowed file type '{$fileType}'.");
        }

        if ($attachment->get('type')) {
            $response->setHeader('Content-Type', $fileType);
        }

        $stream = $this->fileStorageManager->getStream($attachment);

        $size = $stream->getSize() ?? $this->fileStorageManager->getSize($attachment);

        $response
            ->setHeader('Pragma', 'public')
            ->setHeader('Content-Length', (string) $size)
            ->setBody($stream);
    }

    private function getAllowedFileTypeList(): array
    {
        return $this->metadata->get(['app', 'image', 'allowedFileTypeList']) ?? [];
    }
}
