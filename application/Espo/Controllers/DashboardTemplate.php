<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Tools\Dashboard\Service;

use Espo\Core\Api\Request;
use Espo\Core\Controllers\Record;

class DashboardTemplate extends Record
{
    protected function checkAccess(): bool
    {
        return $this->user->isAdmin();
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function postActionDeployToUsers(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (empty($data->userIdList)) {
            throw new BadRequest();
        }

        $this->getDashboardTemplateService()->deployTemplateToUsers(
            $data->id,
            $data->userIdList,
            !empty($data->append)
        );

        return true;
    }

    /**
     * @throws BadRequest
     * @throws NotFound
     */
    public function postActionDeployToTeam(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (empty($data->teamId)) {
            throw new BadRequest();
        }

        $this->getDashboardTemplateService()->deployTemplateToTeam(
            $data->id,
            $data->teamId,
            !empty($data->append)
        );

        return true;
    }

    private function getDashboardTemplateService(): Service
    {
        return $this->injectableFactory->create(Service::class);
    }
}
