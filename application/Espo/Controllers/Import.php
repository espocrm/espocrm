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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\{
    Controllers\Record,
    Api\Request,
    Api\Response,
};

use StdClass;

class Import extends Record
{
    protected function checkAccess(): bool
    {
        return $this->acl->check('Import');
    }

    public function postActionUploadFile(Request $request): StdClass
    {
        $contents = $request->getBodyContents();

        $attachmentId = $this->getService('Import')->uploadFile($contents);

        return (object) ['attachmentId' => $attachmentId];
    }

    public function postActionRevert(Request $request): bool
    {
        $data = $request->getParsedBody();

        $this->getService('Import')->revert($data->id);

        return true;
    }

    public function postActionRemoveDuplicates(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $this->getService('Import')->removeDuplicates($data->id);

        return true;
    }

    public function postActionCreate(Request $request, Response $response): StdClass
    {
        $data = $request->getParsedBody();

        if (!isset($data->delimiter)) {
            throw new BadRequest();
        }

        if (!isset($data->textQualifier)) {
            throw new BadRequest();
        }

        if (!isset($data->dateFormat)) {
            throw new BadRequest();
        }

        if (!isset($data->timeFormat)) {
            throw new BadRequest();
        }

        if (!isset($data->personNameFormat)) {
            throw new BadRequest();
        }

        if (!isset($data->decimalMark)) {
            throw new BadRequest();
        }

        if (!isset($data->defaultValues)) {
            throw new BadRequest();
        }

        if (!isset($data->action)) {
            throw new BadRequest();
        }

        if (!isset($data->attachmentId)) {
            throw new BadRequest();
        }

        if (!isset($data->entityType)) {
            throw new BadRequest();
        }

        if (!isset($data->attributeList)) {
            throw new BadRequest();
        }

        $timezone = 'UTC';
        if (isset($data->timezone)) {
           $timezone = $data->timezone;
        }

        $importParams = [
            'headerRow' => !empty($data->headerRow),
            'delimiter' => $data->delimiter,
            'textQualifier' => $data->textQualifier,
            'dateFormat' => $data->dateFormat,
            'timeFormat' => $data->timeFormat,
            'timezone' => $timezone,
            'personNameFormat' => $data->personNameFormat,
            'decimalMark' => $data->decimalMark,
            'currency' => $data->currency,
            'defaultValues' => $data->defaultValues,
            'action' => $data->action,
            'skipDuplicateChecking' => !empty($data->skipDuplicateChecking),
            'idleMode' => !empty($data->idleMode),
            'silentMode' => !empty($data->silentMode),
            'manualMode' => !empty($data->manualMode),
            'defaultFieldList' => $data->defaultFieldList ?? [],
        ];

        if (property_exists($data, 'updateBy')) {
            $importParams['updateBy'] = $data->updateBy;
        }

        $attachmentId = $data->attachmentId;

        if (!$this->acl->check($data->entityType, 'edit')) {
            throw new Forbidden();
        }

        return $this->getService('Import')->import(
            $data->entityType,
            $data->attributeList,
            $attachmentId,
            $importParams
        );
    }

    public function postActionUnmarkAsDuplicate(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id) || empty($data->entityType) || empty($data->entityId)) {
            throw new BadRequest();
        }

        $this->getService('Import')->unmarkAsDuplicate($data->id, $data->entityType, $data->entityId);

        return true;
    }

    public function beforePatch(): void
    {
        throw new BadRequest();
    }

    public function beforeUpdate(): void
    {
        throw new BadRequest();
    }

    public function beforeCreateLink(): void
    {
        throw new BadRequest();
    }

    public function beforeRemoveLink(): void
    {
        throw new BadRequest();
    }
}
