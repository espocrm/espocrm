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

namespace Espo\EntryPoints;

use Espo\Entities\Attachment as AttachmentEntity;

use Espo\Core\{
    Exceptions\BadRequest,
    Exceptions\Forbidden,
    Exceptions\NotFoundSilent,
    EntryPoint\EntryPoint,
    Acl,
    ORM\EntityManager,
    Api\Request,
    Api\Response,
    FileStorage\Manager as FileStorageManager,
    Utils\Metadata};

class Download implements EntryPoint
{
    protected FileStorageManager $fileStorageManager;
    protected Acl $acl;
    protected EntityManager $entityManager;
    private Metadata $metadata;

    public function __construct(
        FileStorageManager $fileStorageManager,
        Acl $acl,
        EntityManager $entityManager,
        Metadata $metadata
    ) {
        $this->fileStorageManager = $fileStorageManager;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
    }

    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');

        if (!$id) {
            throw new BadRequest();
        }

        /** @var AttachmentEntity|null $attachment */
        $attachment = $this->entityManager->getEntityById('Attachment', $id);

        if (!$attachment) {
            throw new NotFoundSilent();
        }

        if (!$this->acl->checkEntity($attachment)) {
            throw new Forbidden();
        }

        if ($attachment->isBeingUploaded()) {
            throw new Forbidden();
        }

        $stream = $this->fileStorageManager->getStream($attachment);

        $outputFileName = str_replace("\"", "\\\"", $attachment->get('name'));

        $type = $attachment->get('type');

        $disposition = 'attachment';

        /** @var string[] $inlineMimeTypeList */
        $inlineMimeTypeList = $this->metadata->get(['app', 'file', 'inlineMimeTypeList']) ?? [];

        if (in_array($type, $inlineMimeTypeList)) {
            $disposition = 'inline';

            $response->setHeader('Content-Security-Policy', "default-src 'self'");
        }

        $response->setHeader('Content-Description', 'File Transfer');

        if ($type) {
            $response->setHeader('Content-Type', $type);
        }

        $size = $stream->getSize() ?? $this->fileStorageManager->getSize($attachment);

        $response
            ->setHeader('Content-Disposition', $disposition . ";filename=\"" . $outputFileName . "\"")
            ->setHeader('Expires', '0')
            ->setHeader('Cache-Control', 'must-revalidate')
            ->setHeader('Pragma', 'public')
            ->setHeader('Content-Length', (string) $size)
            ->setBody($stream);
    }
}
