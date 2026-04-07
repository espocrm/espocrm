<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Controllers;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\ExternalAccount as ExternalAccountEntity;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Controllers\RecordBase;
use Espo\Core\Record\ReadParams;
use Espo\Tools\ExternalAccount\Service;
use stdClass;

class ExternalAccount extends RecordBase
{
    protected function checkAccess(): bool
    {
        return $this->acl->checkScope(ExternalAccountEntity::ENTITY_TYPE);
    }

    public function getActionList(Request $request, Response $response): stdClass
    {
       return  $this->createService()->getList();
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function getActionGetOAuth2Info(Request $request): ?stdClass
    {
        $id = $request->getQueryParam('id') ?? throw new BadRequest();

        return $this->createService()->getActionGetOAuth2Info($id);
    }

    public function getActionRead(Request $request, Response $response): stdClass
    {
        $id = $request->getRouteParam('id') ?: throw new BadRequest();

        return $this->getRecordService()
            ->read($id, ReadParams::create())
            ->getValueMap();
    }

    public function putActionUpdate(Request $request, Response $response): stdClass
    {
        $id = $request->getRouteParam('id') ?? throw new BadRequest();

        $data = $request->getParsedBody();

        return $this->createService()->update($id, $data);
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     * @throws BadRequest
     */
    public function postActionAuthorizationCode(Request $request): bool
    {
        $data = $request->getParsedBody();

        $id = $data->id ?? throw new BadRequest("No ID.");
        $code = $data->code ?? throw new BadRequest("No code.");

        $this->createService()->authorizationCode($id, $code);

        return true;
    }

    private function createService(): Service
    {
        return $this->injectableFactory->create(Service::class);
    }
}
