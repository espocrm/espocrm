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

namespace Espo\Core\Formula\Functions\ExtGroup\OauthGroup;

use Espo\Core\Formula\EvaluatedArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;
use Espo\Tools\OAuth\Exceptions\AccountNotFound;
use Espo\Tools\OAuth\Exceptions\NoToken;
use Espo\Tools\OAuth\Exceptions\ProviderNotAvailable;
use Espo\Tools\OAuth\Exceptions\TokenObtainingFailure;
use Espo\Tools\OAuth\TokensProvider;

/**
 * @noinspection PhpUnused
 */
class GetAccessTokenType implements Func
{
    public function __construct(
        private TokensProvider $provider,
    ) {}

    public function process(EvaluatedArgumentList $arguments): string
    {
        if (count($arguments) < 1) {
            throw TooFewArguments::create(1);
        }

        $id = $arguments[0];

        if (!is_string($id)) {
            throw BadArgumentType::create(1, 'string');
        }

        try {
            $tokens = $this->provider->get($arguments[0]);
        } catch (AccountNotFound|NoToken|ProviderNotAvailable|TokenObtainingFailure $e) {
            $message = "Could not obtain access token for OAuth account $id.";

            throw new Error($message, 500, $e);
        }

        $accessToken = $tokens->getAccessToken();

        if (!$accessToken) {
            throw new Error("No access token.");
        }

        return $accessToken;
    }
}
