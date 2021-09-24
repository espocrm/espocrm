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

namespace Espo\Core\Authentication\Logins;

use Espo\Core\{
    Api\Request,
    Utils\PasswordHash,
    Authentication\Login,
    Authentication\Login\Data,
    Authentication\Result,
    Authentication\Helper\UserFinder,
    Authentication\Result\FailReason,
};

class Espo implements Login
{
    private $userFinder;

    private $passwordHash;

    public function __construct(UserFinder $userFinder, PasswordHash $passwordHash)
    {
        $this->userFinder = $userFinder;
        $this->passwordHash = $passwordHash;
    }

    public function login(Data $data, Request $request): Result
    {
        $username = $data->getUsername();
        $password = $data->getPassword();
        $authToken = $data->getAuthToken();

        if (!$password) {
            return Result::fail(FailReason::NO_PASSWORD);
        }

        $hash = $authToken ?
            $authToken->getHash() :
            $this->passwordHash->hash($password);

        $user = $this->userFinder->find($username, $hash);

        if (!$user) {
            return Result::fail(FailReason::WRONG_CREDENTIALS);
        }

        if ($authToken && $user->id !== $authToken->getUserId()) {
            return Result::fail(FailReason::USER_TOKEN_MISMATCH);
        }

        return Result::success($user);
    }
}
