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
use Espo\Core\Api\Request;

use Espo\Services\Layout as Service;

use Espo\Entities\User;

class Layout
{
    private $user;

    private $service;

    public function __construct(User $user, Service $service)
    {
        $this->user = $user;
        $this->service = $service;
    }

    /**
     * @return mixed
     */
    public function getActionRead(Request $request)
    {
        $params = $request->getRouteParams();

        $scope = $params['scope'] ?? null;
        $name = $params['name'] ?? null;

        return $this->service->getForFrontend($scope, $name);
    }

    /**
     * @return mixed
     */
    public function putActionUpdate(Request $request)
    {
        $params = $request->getRouteParams();

        $data = json_decode($request->getBodyContents());

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $scope = $params['scope'] ?? null;
        $name = $params['name'] ?? null;
        $setId = $params['setId'] ?? null;

        return $this->service->update($scope, $name, $setId, $data);
    }

    /**
     * @return mixed
     */
    public function postActionResetToDefault(Request $request)
    {
        $data = $request->getParsedBody();

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        if (empty($data->scope) || empty($data->name)) {
            throw new BadRequest();
        }

        return $this->service->resetToDefault($data->scope, $data->name, $data->setId ?? null);
    }

    /**
     * @return mixed
     */
    public function getActionGetOriginal(Request $request)
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        return $this->service->getOriginal(
            $request->getQueryParam('scope'),
            $request->getQueryParam('name'),
            $request->getQueryParam('setId')
        );
    }
}
