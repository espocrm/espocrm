<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Controllers;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Controllers\RecordBase;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Tools\Attachment\FieldData;
use Espo\Tools\Attachment\Service;
use Espo\Tools\Attachment\UploadUrlService;
use Espo\Tools\Attachment\UploadService;
use stdClass;

class Attachment extends RecordBase
{
    public function getActionList(Request $request, Response $response): stdClass
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        return parent::getActionList($request, $response);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     */
    public function postActionGetAttachmentFromImageUrl(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        $url = $data->url ?? null;
        $field = $data->field ?? null;
        $parentType = $data->parentType ?? null;
        $relatedType = $data->relatedType ?? null;

        if (!$url || !$field) {
            throw new BadRequest("No `url` or `field`.");
        }

        try {
            $fieldData = new FieldData(
                $field,
                $parentType,
                $relatedType
            );
        }
        catch (Error $e) {
            throw new BadRequest($e->getMessage());
        }

        return $this->injectableFactory
            ->create(UploadUrlService::class)
            ->uploadImage($url, $fieldData)
            ->getValueMap();
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function postActionGetCopiedAttachment(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        $id = $data->id ?? null;
        $field = $data->field ?? null;
        $parentType = $data->parentType ?? null;
        $relatedType = $data->relatedType ?? null;

        if (!$id || !$field) {
            throw new BadRequest("No `id` or `field`.");
        }

        try {
            $fieldData = new FieldData(
                $field,
                $parentType,
                $relatedType
            );
        }
        catch (Error $e) {
            throw new BadRequest($e->getMessage());
        }

        return $this->getAttachmentService()
            ->copy($id, $fieldData)
            ->getValueMap();
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function getActionFile(Request $request, Response $response): void
    {
        $id = $request->getRouteParam('id');

        if (!$id) {
            throw new BadRequest();
        }

        $fileData = $this->getAttachmentService()->getFileData($id);

        if ($fileData->getType()) {
            $response->setHeader('Content-Type', $fileData->getType());
        }

        $response
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileData->getName() . '"')
            ->setHeader('Content-Length', (string) $fileData->getSize())
            ->setBody($fileData->getStream());
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function postActionChunk(Request $request, Response $response): void
    {
        $id = $request->getRouteParam('id');
        $body = $request->getBodyContents();

        if (!$id || !$body) {
            throw new BadRequest();
        }

        $this->injectableFactory
            ->create(UploadService::class)
            ->uploadChunk($id, $body);

        $response->writeBody('true');
    }

    private function getAttachmentService(): Service
    {
        return $this->injectableFactory->create(Service::class);
    }
}
