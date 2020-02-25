<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Utils as Utils;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

class Layout extends \Espo\Core\Controllers\Base
{
    public function getActionRead($params, $data)
    {
        $scope = $params['scope'] ?? null;
        $name = $params['name'] ?? null;

        return $this->getServiceFactory()->create('Layout')->getForFrontend($scope, $name);
    }

    public function putActionUpdate($params, $data, $request)
    {
        if (is_object($data)) $data = get_object_vars($data);

        if (!$this->getUser()->isAdmin()) throw new Forbidden();

        $scope = $params['scope'] ?? null;
        $name = $params['name'] ?? null;
        $setId = $params['setId'] ?? null;

        return $this->getServiceFactory()->create('Layout')->update($scope, $name, $setId, $data);
    }

    public function postActionResetToDefault($params, $data, $request)
    {
        if (!$this->getUser()->isAdmin()) throw new Forbidden();

        if (empty($data->scope) || empty($data->name)) throw new BadRequest();

        return $this->getServiceFactory()->create('Layout')->resetToDefault($data->scope, $data->name, $data->setId ?? null);
    }

    public function getActionGetOriginal($params, $data, $request)
    {
        if (!$this->getUser()->isAdmin()) throw new Forbidden();

        return $this->getServiceFactory()->create('Layout')->getOriginal(
            $request->get('scope'), $request->get('name'), $request->get('setId')
        );
    }
}
