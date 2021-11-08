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

namespace Espo\Controllers;

use Espo\Services\Attachment as Service;

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Api\Request,
    Api\Response,
    Controllers\RecordBase,
};

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

    public function postActionGetAttachmentFromImageUrl(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->url)) {
            throw new BadRequest();
        }

        if (empty($data->field)) {
            throw new BadRequest('postActionGetAttachmentFromImageUrl: No field specified.');
        }

        return $this->getAttachmentService()
            ->getAttachmentFromImageUrl($data)
            ->getValueMap();
    }

    public function postActionGetCopiedAttachment(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (empty($data->field)) {
            throw new BadRequest('postActionGetCopiedAttachment copy: No field specified.');
        }

        return $this->getAttachmentService()
            ->getCopiedAttachment($data)
            ->getValueMap();
    }

    public function getActionFile(Request $request, Response $response): void
    {
        $id = $request->getRouteParam('id');

        if (!$id) {
            throw new BadRequest();
        }

        $fileData = $this->getAttachmentService()->getFileData($id);

        $response
            ->setHeader('Content-Type', $fileData->type)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileData->name . '"')
            ->setHeader('Content-Length', (string) $fileData->size)
            ->setBody($fileData->stream);
    }

    private function getAttachmentService(): Service
    {
        /** @var Service */
        return $this->getRecordService();
    }
}
