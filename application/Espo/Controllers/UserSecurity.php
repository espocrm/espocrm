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

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Api\Request;

use Espo\Core\Exceptions\NotFound;
use Espo\Tools\UserSecurity\Service as Service;

use Espo\Entities\User;

use stdClass;

class UserSecurity
{
    private Service $service;
    private User $user;

    /**
     * @throws Forbidden
     */
    public function __construct(Service $service, User $user)
    {
        $this->service = $service;
        $this->user = $user;

        if (
            !$this->user->isAdmin() &&
            !$this->user->isRegular() &&
            !$this->user->isPortal()
        ) {
            throw new Forbidden();
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function getActionRead(Request $request): stdClass
    {
        $id = $request->getRouteParam('id');

        if (!$id) {
            throw new BadRequest();
        }

        if (!$this->user->isAdmin() && $id !== $this->user->getId()) {
            throw new Forbidden();
        }

        return $this->service->read($id);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function postActionGetTwoFactorUserSetupData(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        $id = $data->id ?? null;

        if (!$id) {
            throw new BadRequest("No 'id'.");
        }

        if (!$this->user->isAdmin() && $id !== $this->user->getId()) {
            throw new Forbidden();
        }

        return $this->service->getTwoFactorUserSetupData($id, $data);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function putActionUpdate(Request $request): stdClass
    {
        $id = $request->getRouteParam('id');

        $data = $request->getParsedBody();

        if (!$id) {
            throw new BadRequest();
        }

        if (!$this->user->isAdmin() && $id !== $this->user->getId()) {
            throw new Forbidden();
        }

        return $this->service->update($id, $data);
    }
}
