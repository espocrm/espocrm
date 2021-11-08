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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;

use Espo\Tools\Import\Params as ImportParams;
use Espo\Tools\Import\Service as Service;

use Espo\Core\{
    Controllers\Record,
    Api\Request,
    Api\Response,
};

use Espo\Core\Di\InjectableFactoryAware;
use Espo\Core\Di\InjectableFactorySetter;

use stdClass;

class Import extends Record

    implements InjectableFactoryAware
{
    use InjectableFactorySetter;

    protected function checkAccess(): bool
    {
        return $this->acl->check('Import');
    }

    private function getImportService(): Service
    {
        return $this->injectableFactory->create(Service::class);
    }

    public function postActionUploadFile(Request $request): stdClass
    {
        $contents = $request->getBodyContents();

        $attachmentId = $this->getImportService()->uploadFile($contents);

        return (object) [
            'attachmentId' => $attachmentId
        ];
    }

    public function postActionRevert(Request $request): bool
    {
        $data = $request->getParsedBody();

        $this->getImportService()->revert($data->id);

        return true;
    }

    public function postActionRemoveDuplicates(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $this->getImportService()->removeDuplicates($data->id);

        return true;
    }

    public function postActionCreate(Request $request, Response $response): stdClass
    {
        $data = $request->getParsedBody();

        $entityType = $data->entityType ?? null;
        $attributeList = $data->attributeList ?? null;
        $attachmentId = $data->attachmentId ?? null;

        if (!is_array($attributeList)) {
            throw new BadRequest("No attributeList.");
        }

        if (!$attachmentId) {
            throw new BadRequest("No attachmentId.");
        }

        if (!$entityType) {
            throw new BadRequest("No entityType.");
        }

        $params = ImportParams::fromRaw($data);

        $result = $this->getImportService()->import(
            $entityType,
            $attributeList,
            $attachmentId,
            $params
        );

        return $result->getValueMap();
    }

    public function postActionUnmarkAsDuplicate(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (
            empty($data->id) ||
            empty($data->entityType) ||
            empty($data->entityId)
        ) {
            throw new BadRequest();
        }

        $this->getImportService()->unmarkAsDuplicate($data->id, $data->entityType, $data->entityId);

        return true;
    }

    public function putActionUpdate(Request $request, Response $response): stdClass
    {
        throw new Forbidden();
    }

    public function postActionCreateLink(Request $request): bool
    {
        throw new Forbidden();
    }

    public function deleteActionRemoveLink(Request $request): bool
    {
        throw new Forbidden();
    }
}
