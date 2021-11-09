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

namespace Espo\Core\Authentication;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ServiceUnavailable;

use Espo\Repositories\UserData as UserDataRepository;

use Espo\Entities\{
    Portal,
    User,
    AuthLogRecord,
    AuthToken as AuthTokenEntity,
    UserData,
};

use Espo\Core\Authentication\{
    Result,
    Result\FailReason,
    LoginFactory,
    TwoFactor\LoginFactory as TwoFactorLoginFactory,
    AuthToken\Manager as AuthTokenManager,
    AuthToken\Data as AuthTokenData,
    AuthToken\AuthToken,
    Hook\Manager as HookManager,
    Login\Data as LoginData,
};

use Espo\Core\{
    ApplicationUser,
    ApplicationState,
    ORM\EntityManagerProxy,
    Api\Request,
    Api\Response,
    Utils\Log,
};

/**
 * Handles authentication. The entry point of the auth process.
 */
class Authentication
{
    private const LOGOUT_USERNAME = '**logout';

    private $allowAnyAccess;

    private $portal;

    private $applicationUser;

    private $applicationState;

    private $configDataProvider;

    /**
     * @var EntityManagerProxy
     */
    private $entityManager;

    private $loginFactory;

    private $twoFactorLoginFactory;

    private $authTokenManager;

    private $hookManager;

    private $log;

    public function __construct(
        ApplicationUser $applicationUser,
        ApplicationState $applicationState,
        ConfigDataProvider $configDataProvider,
        EntityManagerProxy $entityManagerProxy,
        LoginFactory $loginFactory,
        TwoFactorLoginFactory $twoFactorLoginFactory,
        AuthTokenManager $authTokenManager,
        HookManager $hookManager,
        Log $log,
        bool $allowAnyAccess = false
    ) {
        $this->allowAnyAccess = $allowAnyAccess;

        $this->applicationUser = $applicationUser;
        $this->applicationState = $applicationState;
        $this->configDataProvider = $configDataProvider;
        $this->entityManager = $entityManagerProxy;
        $this->loginFactory = $loginFactory;
        $this->twoFactorLoginFactory = $twoFactorLoginFactory;
        $this->authTokenManager = $authTokenManager;
        $this->hookManager = $hookManager;
        $this->log = $log;
    }

    /**
     * Process logging in.
     *
     * Warning: This method can change the state of the object (by setting the `portal` prop.).
     *
     * @throws Forbidden
     * @throws ServiceUnavailable
     */
    public function login(AuthenticationData $data, Request $request, Response $response): Result
    {
        $username = $data->getUsername();
        $password = $data->getPassword();
        $authenticationMethod = $data->getMethod();

        if (
            $authenticationMethod &&
            !$this->configDataProvider->authenticationMethodIsApi($authenticationMethod)
        ) {
            $this->log->warning(
                "AUTH: Trying to use not allowed authentication method '{$authenticationMethod}'."
            );

            return $this->processFail(
                Result::fail(FailReason::METHOD_NOT_ALLOWED),
                $data,
                $request
            );
        }

        $this->hookManager->processBeforeLogin($data, $request);

        if (!$authenticationMethod && $password === null) {
            $this->log->error("AUTH: Trying to login w/o password.");

            return Result::fail(FailReason::NO_PASSWORD);
        }

        $authToken = null;

        if (!$authenticationMethod) {
            $authToken = $this->authTokenManager->get($password);
        }

        if ($authToken && $authToken->getSecret()) {
            $sentSecret = $request->getCookieParam('auth-token-secret');

            if ($sentSecret !== $authToken->getSecret()) {
                $authToken = null;
            }
        }

        $authTokenIsFound = $authToken !== null;

        if ($authToken && !$authToken->isActive()) {
            $authToken = null;
        }

        if ($authToken) {
            $authTokenCheckResult = $this->processAuthTokenCheck($authToken);

            if (!$authTokenCheckResult) {
                return Result::fail(FailReason::DENIED);
            }
        }

        $isByTokenOnly = !$authenticationMethod && $request->getHeader('Espo-Authorization-By-Token') === 'true';

        if ($isByTokenOnly && !$authToken) {
            if ($username) {
                $this->log->info(
                    "AUTH: Trying to login as user '{$username}' by token but token is not found."
                );
            }

            return $this->processFail(
                Result::fail(FailReason::TOKEN_NOT_FOUND),
                $data,
                $request
            );
        }

        if (!$authenticationMethod) {
            $authenticationMethod = $this->configDataProvider->getDefaultAuthenticationMethod();
        }

        $login = $this->loginFactory->create($authenticationMethod, $this->isPortal());

        $loginData = LoginData
            ::createBuilder()
            ->setUsername($username)
            ->setPassword($password)
            ->setAuthToken($authToken)
            ->build();

        $result = $login->login($loginData, $request);

        $user = $result->getUser();

        $authLogRecord = null;

        if (!$authTokenIsFound) {
            $authLogRecord = $this->createAuthLogRecord($username, $user, $request, $authenticationMethod);
        }

        if ($result->isFail()) {
            return $this->processFail(
                $result,
                $data,
                $request
            );
        }

        if (!$user) {
            // Supposed not to ever happen.
            return $this->processFail(
                Result::fail(FailReason::USER_NOT_FOUND),
                $data,
                $request
            );
        }

        if (!$user->isAdmin() && $this->configDataProvider->isMaintenanceMode()) {
            throw new ServiceUnavailable("Application is in maintenance mode.");
        }

        if (!$this->processUserCheck($user, $authLogRecord)) {
            return $this->processFail(
                Result::fail(FailReason::DENIED),
                $data,
                $request
            );
        }

        if ($this->isPortal()) {
            $user->set('portalId', $this->getPortal()->getId());
        }

        if (!$this->isPortal()) {
            $user->loadLinkMultipleField('teams');
        }

        $user->set('ipAddress', $request->getServerParam('REMOTE_ADDR') ?? null);

        $this->applicationUser->setUser($user);

        if (
            !$result->isSecondStepRequired() &&
            !$authToken &&
            $this->configDataProvider->isTwoFactorEnabled()
        ) {
            $result = $this->processTwoFactor($result, $request);

            if ($result->isFail()) {
                return $this->processFail(
                    $result,
                    $data,
                    $request
                );
            }
        }

        if (!$result->isSecondStepRequired() && $request->getHeader('Espo-Authorization')) {
            if (!$authToken) {
                $authToken = $this->createAuthToken($user, $request, $response);
            }
            else {
                $this->authTokenManager->renew($authToken);
            }

            $authTokenId = null;

            if (property_exists($authToken, 'id')) {
                $authTokenId = $authToken->id ?? null;
            }

            $user->set('token', $authToken->getToken());
            $user->set('authTokenId', $authTokenId);

            if ($authLogRecord) {
                $authLogRecord->set('authTokenId', $authTokenId);
            }
        }

        if ($authLogRecord) {
            $this->entityManager->saveEntity($authLogRecord);
        }

        if ($authToken && !$authLogRecord && isset($authToken->id)) {
            $authLogRecord = $this->entityManager
                ->getRDBRepository('AuthLogRecord')
                ->select(['id'])
                ->where([
                    'authTokenId' => $authToken->id
                ])
                ->order('requestTime', true)
                ->findOne();
        }

        if ($authLogRecord) {
            $user->set('authLogRecordId', $authLogRecord->getId());
        }

        if ($result->isSuccess()) {
            return $this->processSuccess($result, $data, $request, $authTokenIsFound);
        }

        if ($result->isSecondStepRequired()) {
            return $this->processSecondStepRequired($result, $data, $request);
        }

        return $result;
    }

    private function setPortal(Portal $portal): void
    {
        $this->portal = $portal;
    }

    private function isPortal(): bool
    {
        return (bool) $this->portal || $this->applicationState->isPortal();
    }

    private function getPortal(): Portal
    {
        if ($this->portal) {
            return $this->portal;
        }

        return $this->applicationState->getPortal();
    }

    private function processAuthTokenCheck(AuthToken $authToken): bool
    {
        if ($this->allowAnyAccess && $authToken->getPortalId() && !$this->isPortal()) {
            /** @var ?Portal */
            $portal = $this->entityManager->getEntity('Portal', $authToken->getPortalId());

            if ($portal) {
                $this->setPortal($portal);
            }
        }

        if ($this->allowAnyAccess) {
            return true;
        }

        if ($this->isPortal() && $authToken->getPortalId() !== $this->getPortal()->getId()) {
            $this->log->info(
                "AUTH: Trying to login to portal with a token not related to portal."
            );

            return false;
        }

        if (!$this->isPortal() && $authToken->getPortalId()) {
            $this->log->info(
                "AUTH: Trying to login to crm with a token related to portal."
            );

            return false;
        }

        return true;
    }

    private function processUserCheck(User $user, ?AuthLogRecord $authLogRecord): bool
    {
        if (!$user->isActive()) {
            $this->log->info(
                "AUTH: Trying to login as user '".$user->get('userName')."' which is not active."
            );

            $this->logDenied($authLogRecord, 'INACTIVE_USER');

            return false;
        }

        if (!$user->isAdmin() && !$this->isPortal() && $user->isPortal()) {
            $this->log->info(
                "AUTH: Trying to login to crm as a portal user '".$user->get('userName')."'."
            );

            $this->logDenied($authLogRecord, 'IS_PORTAL_USER');

            return false;
        }

        if ($this->isPortal() && !$user->isPortal()) {
            $this->log->info(
                "AUTH: Trying to login to portal as user '".$user->get('userName')."' which is not portal user."
            );

            $this->logDenied($authLogRecord, 'IS_NOT_PORTAL_USER');

            return false;
        }

        if ($this->isPortal()) {
            $isPortalRelatedToUser = $this->entityManager
                ->getRDBRepository('Portal')
                ->isRelated($this->getPortal(), 'users', $user);

            if (!$isPortalRelatedToUser) {
                $this->log->info(
                    "AUTH: Trying to login to portal as user '".$user->get('userName')."' ".
                    "which is portal user but does not belongs to portal."
                );

                $this->logDenied($authLogRecord, 'USER_IS_NOT_IN_PORTAL');

                return false;
            }
        }

        return true;
    }

    private function processTwoFactor(Result $result, Request $request): Result
    {
        $loggedUser = $result->getLoggedUser();

        $method = $this->getUser2FAMethod($loggedUser);

        if (!$method) {
            return $result;
        }

        $login = $this->twoFactorLoginFactory->create($method);

        return $login->login($result, $request);
    }

    private function getUser2FAMethod(User $user): ?string
    {
        $userData = $this->getUserDataRepository()->getByUserId($user->getId());

        if (!$userData) {
            return null;
        }

        if (!$userData->get('auth2FA')) {
            return null;
        }

        $method = $userData->get('auth2FAMethod');

        if (!$method) {
            return null;
        }

        if (!in_array($method, $this->configDataProvider->getTwoFactorMethodList())) {
            return null;
        }

        return $method;
    }

    private function createAuthToken(User $user, Request $request, Response $response): AuthToken
    {
        $createSecret = $request->getHeader('Espo-Authorization-Create-Token-Secret') === 'true';

        if ($createSecret) {
            if ($this->configDataProvider->isAuthTokenSecretDisabled()) {
                $createSecret = false;
            }
        }

        $arrayData = [
            'hash' => $user->get('password'),
            'ipAddress' => $request->getServerParam('REMOTE_ADDR'),
            'userId' => $user->id,
            'portalId' => $this->isPortal() ? $this->getPortal()->getId() : null,
            'createSecret' => $createSecret,
        ];

        $authToken = $this->authTokenManager->create(
            AuthTokenData::create($arrayData)
        );

        if ($createSecret) {
            $this->setSecretInCookie($authToken->getSecret(), $response);
        }

        if (
            $this->configDataProvider->preventConcurrentAuthToken() &&
            $authToken instanceof AuthTokenEntity
        ) {
            $concurrentAuthTokenList = $this->entityManager
                ->getRDBRepository('AuthToken')
                ->select(['id'])
                ->where([
                    'userId' => $user->id,
                    'isActive' => true,
                    'id!=' => $authToken->get('id'),
                ])
                ->find();

            foreach ($concurrentAuthTokenList as $concurrentAuthToken) {
                $concurrentAuthToken->set('isActive', false);

                $this->entityManager->saveEntity($concurrentAuthToken);
            }
        }

        return $authToken;
    }

    public function destroyAuthToken(string $token, Request $request, Response $response): bool
    {
        $authToken = $this->authTokenManager->get($token);

        if (!$authToken) {
            return false;
        }

        $this->authTokenManager->inactivate($authToken);

        if ($authToken->getSecret()) {
            $sentSecret = $request->getCookieParam('auth-token-secret');

            if ($sentSecret === $authToken->getSecret()) {
                $this->setSecretInCookie(null, $response);
            }
        }

        return true;
    }

    private function createAuthLogRecord(
        ?string $username,
        ?User $user,
        Request $request,
        ?string $authenticationMethod = null
    ): ?AuthLogRecord {

        if ($username === self::LOGOUT_USERNAME) {
            return null;
        }

        /** @var ?AuthLogRecord */
        $authLogRecord = $this->entityManager->getEntity('AuthLogRecord');

        $requestUrl =
            $request->getUri()->getScheme() . '://' .
            $request->getUri()->getHost() .
            $request->getUri()->getPath();

        if (!$username && $user) {
            $username = $user->get('userName');
        }

        $authLogRecord->set([
            'username' => $username,
            'ipAddress' => $request->getServerParam('REMOTE_ADDR'),
            'requestTime' => $request->getServerParam('REQUEST_TIME_FLOAT'),
            'requestMethod' => $request->getMethod(),
            'requestUrl' => $requestUrl,
            'authenticationMethod' => $authenticationMethod,
        ]);

        if ($this->isPortal()) {
            $authLogRecord->set('portalId', $this->getPortal()->getId());
        }

        if ($user) {
            $authLogRecord->set('userId', $user->id);
        }
        else {
            $authLogRecord->set('isDenied', true);
            $authLogRecord->set('denialReason', 'CREDENTIALS');

            $this->entityManager->saveEntity($authLogRecord);
        }

        return $authLogRecord;
    }

    private function logDenied(?AuthLogRecord $authLogRecord, string $denialReason): void
    {
        if (!$authLogRecord) {
            return;
        }

        $authLogRecord->set('denialReason', $denialReason);

        $this->entityManager->saveEntity($authLogRecord);
    }

    private function setSecretInCookie(?string $secret, Response $response): void
    {
        $time = $secret ? strtotime('+1000 days') : 1;

        $value = $secret ? $secret : 'deleted';

        $headerValue =
            'auth-token-secret=' . urlencode($value) .
            '; path=/' .
            '; expires=' . gmdate('D, d M Y H:i:s T', $time) .
            '; HttpOnly' .
            '; SameSite=Lax';

        $response->addHeader('Set-Cookie', $headerValue);
    }

    private function processFail(Result $result, AuthenticationData $data, Request $request): Result
    {
        $this->hookManager->processOnFail($result, $data, $request);

        return $result;
    }

    private function processSuccess(
        Result $result,
        AuthenticationData $data,
        Request $request,
        bool $byToken
    ): Result {

        if ($byToken) {
            $this->hookManager->processOnSuccessByToken($result, $data, $request);

            return $result;
        }

        $this->hookManager->processOnSuccess($result, $data, $request);

        return $result;
    }

    private function processSecondStepRequired(
        Result $result,
        AuthenticationData $data,
        Request $request
    ): Result {

        $this->hookManager->processOnSecondStepRequired($result, $data, $request);

        return $result;
    }

    private function getUserDataRepository(): UserDataRepository
    {
        /** @var UserDataRepository */
        return $this->entityManager->getRepository(UserData::ENTITY_TYPE);
    }
}
