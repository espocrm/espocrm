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

namespace Espo\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Utils\Json;
use Espo\Tools\Oidc\Service;

class Oidc
{
    private Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @throws Forbidden
     * @throws Error
     */
    public function getActionAuthorizationData(Request $request, Response $response): void
    {
        $data = $this->service->getAuthorizationData();

        $response->writeBody(Json::encode($data));
    }


    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function postActionBackchannelLogout(Request $request, Response $response): void
    {
        $token = $request->getParsedBody()->logout_token ?? null;

        if (!$token || !is_string($token)) {
            throw new BadRequest();
        }

        $this->service->backchannelLogout($token);

        $response->writeBody('true');
    }
}
