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
    Utils\ApiKey,
    Authentication\Login,
    Authentication\Login\Data,
    Authentication\Result,
    Authentication\Helper\UserFinder,
    Exceptions\Error,
    Authentication\Result\FailReason,
};

class Hmac implements Login
{
    private $userFinder;

    private $apiKeyUtil;

    public function __construct(UserFinder $userFinder, ApiKey $apiKeyUtil)
    {
        $this->userFinder = $userFinder;
        $this->apiKeyUtil = $apiKeyUtil;
    }

    public function login(Data $data, Request $request): Result
    {
        $authString = base64_decode($request->getHeader('X-Hmac-Authorization'));

        list($apiKey, $hash) = explode(':', $authString, 2);

        $user = $this->userFinder->findApiHmac($apiKey);

        if (!$user) {
            return Result::fail(FailReason::WRONG_CREDENTIALS);
        }

        $secretKey = $this->apiKeyUtil->getSecretKeyForUserId($user->getId());

        if (!$secretKey) {
            throw new Error("No secret key for API user '" . $user->getId() . "'.");
        }

        $string = $request->getMethod() . ' ' . $request->getResourcePath();

        if ($hash === ApiKey::hash($secretKey, $string)) {
            return Result::success($user);
        }

        return Result::fail(FailReason::HASH_NOT_MATCHED);
    }
}
