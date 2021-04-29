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

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\{
    Controllers\Record,
    Api\Request,
};

use StdClass;

class User extends Record
{
    public function getActionAcl(Request $request): StdClass
    {
        $userId = $request->getQueryParam('id');

        if (empty($userId)) {
            throw new Error();
        }

        if (!$this->user->isAdmin() && $this->user->getId() != $userId) {
            throw new Forbidden();
        }

        $user = $this->getEntityManager()->getEntity('User', $userId);

        if (empty($user)) {
            throw new NotFound();
        }

        return $this->getAclManager()->getMapData($user);
    }

    public function postActionChangeOwnPassword(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (
            !property_exists($data, 'password') ||
            !property_exists($data, 'currentPassword')
        ) {
            throw new BadRequest();
        }

        $this->getService('User')
            ->changePassword(
                $this->user->getId(),
                $data->password,
                true,
                $data->currentPassword
            );

        return true;
    }

    public function postActionChangePasswordByRequest(Request $request): StdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->requestId) || empty($data->password)) {
            throw new BadRequest();
        }

        return $this->getService('User')->changePasswordByRequest($data->requestId, $data->password);
    }

    public function postActionPasswordChangeRequest(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->userName) || empty($data->emailAddress)) {
            throw new BadRequest();
        }

        $userName = $data->userName;
        $emailAddress = $data->emailAddress;

        $url = null;

        if (!empty($data->url)) {
            $url = $data->url;
        }

        $this->getService('User')->passwordChangeRequest($userName, $emailAddress, $url);

        return true;
    }

    public function postActionGenerateNewApiKey(Request $request): StdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        return $this->getRecordService()
            ->generateNewApiKeyForEntity($data->id)
            ->getValueMap();
    }

    public function postActionGenerateNewPassword(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $this->getRecordService()->generateNewPasswordForUser($data->id);

        return true;
    }

    public function beforeCreateLink(): void
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function beforeRemoveLink(): void
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    protected function fetchSearchParamsFromRequest(Request $request): array
    {
        $params = parent::fetchSearchParamsFromRequest($request);

        $userType = $request->getQueryParam('userType');

        if ($userType) {
            $params['where'] = $params['where'] ?? [];

            $params['where'][] = [
                'type' => 'isOfType',
                'attribute' => 'id',
                'value' => $userType,
            ];
        }

        return $params;
    }
}
