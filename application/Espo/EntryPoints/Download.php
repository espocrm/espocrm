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
};

class Download implements EntryPoint
{
    protected $fileTypesToShowInline = [
        'application/pdf',
        'application/vnd.ms-word',
        'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'text/plain',
        'application/msword',
        'application/msexcel',
    ];

    /** @var FileStorageManager */
    protected $fileStorageManager;

    /** @var Acl */
    protected $acl;

    /** @var EntityManager */
    protected $entityManager;

    public function __construct(FileStorageManager $fileStorageManager, Acl $acl, EntityManager $entityManager)
    {
        $this->fileStorageManager = $fileStorageManager;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
    }

    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');

        if (!$id) {
            throw new BadRequest();
        }

        $attachment = $this->entityManager->getEntity('Attachment', $id);

        if (!$attachment) {
            throw new NotFoundSilent();
        }

        if (!$this->acl->checkEntity($attachment)) {
            throw new Forbidden();
        }

        $stream = $this->fileStorageManager->getStream($attachment);

        $outputFileName = str_replace("\"", "\\\"", $attachment->get('name'));

        $type = $attachment->get('type');

        $disposition = 'attachment';

        if (in_array($type, $this->fileTypesToShowInline)) {
            $disposition = 'inline';
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
