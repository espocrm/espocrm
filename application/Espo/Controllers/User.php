<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Core\Api\Request;
use Espo\Core\Controllers\Record;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Tools\UserSecurity\ApiService;
use Espo\Tools\UserSecurity\Password\RecoveryService;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Where\Item as WhereItem;

use Espo\Tools\UserSecurity\Password\Service as PasswordService;
use stdClass;

class User extends Record
{
    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function getActionAcl(Request $request): stdClass
    {
        $userId = $request->getQueryParam('id');

        if (empty($userId)) {
            throw new Error();
        }

        if (
            !$this->user->isAdmin() &&
            $this->user->getId() !== $userId
        ) {
            throw new Forbidden();
        }

        $user = $this->entityManager->getEntityById(\Espo\Entities\User::ENTITY_TYPE, $userId);

        if (empty($user)) {
            throw new NotFound();
        }

        return $this->aclManager->getMapData($user);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function postActionChangeOwnPassword(Request $request): bool
    {
        $data = $request->getParsedBody();

        $password = $data->password ?? null;
        $currentPassword = $data->currentPassword ?? null;

        if (
            !is_string($password) ||
            !is_string($currentPassword)
        ) {
            throw new BadRequest();
        }

        $this->getPasswordService()->changePasswordWithCheck($this->user->getId(), $password, $currentPassword);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function postActionChangePasswordByRequest(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->requestId) || empty($data->password)) {
            throw new BadRequest();
        }

        $url = $this->getPasswordService()->changePasswordByRecovery($data->requestId, $data->password);

        return (object) [
            'url' => $url,
        ];
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function postActionPasswordChangeRequest(Request $request): bool
    {
        $data = $request->getParsedBody();

        $userName = $data->userName ?? null;
        $emailAddress = $data->emailAddress ?? null;
        $url = $data->url ?? null;

        if (!$userName || !$emailAddress) {
            throw new BadRequest();
        }

        $this->injectableFactory
            ->create(RecoveryService::class)
            ->request($emailAddress, $userName, $url);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function postActionGenerateNewApiKey(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        return $this->injectableFactory
            ->create(ApiService::class)
            ->generateNewApiKey($data->id)
            ->getValueMap();
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     * @throws NotFound
     */
    public function postActionGenerateNewPassword(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $this->getPasswordService()->generateAndSendNewPasswordForUser($data->id);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function postActionSendPasswordChangeLink(Request $request): bool
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $id = $request->getParsedBody()->id ?? null;

        if (!$id) {
            throw new BadRequest();
        }

        $this->getPasswordService()->createAndSendPasswordRecovery($id);

        return true;
    }

    public function postActionCreateLink(Request $request): bool
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        return parent::postActionCreateLink($request);
    }

    public function deleteActionRemoveLink(Request $request): bool
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        return parent::deleteActionRemoveLink($request);
    }

    protected function fetchSearchParamsFromRequest(Request $request): SearchParams
    {
        $searchParams = parent::fetchSearchParamsFromRequest($request);

        $userType = $request->getQueryParam('userType');

        if (!$userType) {
            return $searchParams;
        }

        return $searchParams->withWhereAdded(
            WhereItem::fromRaw([
                'type' => 'isOfType',
                'attribute' => 'id',
                'value' => $userType,
            ])
        );
    }

    private function getPasswordService(): PasswordService
    {
        return $this->injectableFactory->create(PasswordService::class);
    }
}
