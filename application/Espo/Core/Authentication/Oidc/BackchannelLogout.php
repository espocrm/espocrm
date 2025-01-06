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

namespace Espo\Core\Authentication\Oidc;

use Espo\Core\Authentication\AuthToken\AuthToken;
use Espo\Core\Authentication\AuthToken\Manager as AuthTokenManager;
use Espo\Core\Authentication\Jwt\Exceptions\Invalid;
use Espo\Core\Authentication\Jwt\Exceptions\SignatureNotVerified;
use Espo\Core\Authentication\Jwt\Token;
use Espo\Core\Authentication\Jwt\Validator;
use Espo\Core\Authentication\Oidc\UserProvider\UserRepository;
use Espo\Core\Utils\Log;
use Espo\Entities\AuthToken as AuthTokenEntity;
use Espo\ORM\EntityManager;

/**
 * Compatible only with default Espo auth tokens.
 *
 * @todo Use a token-sessionId map to retrieve tokens. Send sid claim in id_token.
 */
class BackchannelLogout
{
    public function __construct(
        private Log $log,
        private Validator $validator,
        private TokenValidator $tokenValidator,
        private ConfigDataProvider $configDataProvider,
        private UserRepository $userRepository,
        private EntityManager $entityManager,
        private AuthTokenManager $authTokenManger
    ) {}

    /**
     * @throws SignatureNotVerified
     * @throws Invalid
     */
    public function logout(string $rawToken): void
    {
        $token = Token::create($rawToken);

        $this->log->debug("OIDC logout: JWT header: " . $token->getHeaderRaw());
        $this->log->debug("OIDC logout: JWT payload: " . $token->getPayloadRaw());

        $this->validator->validate($token);
        $this->tokenValidator->validateSignature($token);
        $this->tokenValidator->validateFields($token);

        $usernameClaim = $this->configDataProvider->getUsernameClaim();

        if (!$usernameClaim) {
            throw new Invalid("No username claim in config.");
        }

        $username = $token->getPayload()->get($usernameClaim);

        if (!$username) {
            throw new Invalid("No username claim `$usernameClaim` in token.");
        }

        $user = $this->userRepository->findByUsername($username);

        if (!$user) {
            return;
        }

        $authTokenList = $this->entityManager
            ->getRDBRepositoryByClass(AuthTokenEntity::class)
            ->where([
                'userId' => $user->getId(),
                'isActive' => true,
            ])
            ->find();

        foreach ($authTokenList as $authToken) {
            assert($authToken instanceof AuthToken);

            $this->authTokenManger->inactivate($authToken);
        }
    }
}
