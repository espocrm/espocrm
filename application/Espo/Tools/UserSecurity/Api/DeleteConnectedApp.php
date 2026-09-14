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

namespace Espo\Tools\UserSecurity\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\EntityProvider;
use Espo\Entities\User;
use Espo\Tools\OAuthServer\ConnectedApp\ConnectedAppService;
use Espo\Tools\OAuthServer\ConnectedApp\UserCheck;

/**
 * @noinspection PhpUnused
 */
class DeleteConnectedApp implements Action
{
    public function __construct(
        private EntityProvider $entityProvider,
        private User $user,
        private ConnectedAppService $oAuthService,
        private UserCheck $userCheck,
    ) {}

    /**
     * @inheritDoc
     */
    public function process(Request $request): Response
    {
        $user = $this->fetchUser($request);
        $appId = $request->getRouteParam('appId') ?? throw new BadRequest();

        $this->oAuthService->disconnect($user, $appId);

        return ResponseComposer::json(true);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    private function fetchUser(Request $request): User
    {
        $id = $request->getRouteParam('id') ?? throw new BadRequest();

        if ($id !== $this->user->getId() && !$this->user->isEffectiveAdmin()) {
            throw new Forbidden();
        }

        $user = $this->entityProvider->getByClass(User::class, $id);

        $this->userCheck->assert($user);

        return $user;
    }
}
