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

use Espo\Core\Exceptions\{
    Forbidden,
    ServiceUnavailable,
};

use Espo\Entities\{
    Portal,
    User,
    AuthLogRecord,
    AuthToken as AuthTokenEntity,
};

use Espo\Core\Authentication\{
    Result,
    LoginFactory,
    TwoFactor\Factory as TwoFAFactory,
    AuthToken\AuthTokenManager,
    AuthToken\AuthTokenData,
    AuthToken\AuthToken,
};

use Espo\Core\{
    ApplicationUser,
    ApplicationState,
    ORM\EntityManagerProxy,
    Api\Request,
    Utils\Log,
};

use DateTime;

/**
 * Handles authentication. The entry point of the auth process.
 */
class Authentication
{
    private $allowAnyAccess;

    private $portal;

    private $applicationUser;

    private $applicationState;

    private $configDataProvider;

    private $entityManager;

    private $authLoginFactory;

    private $auth2FAFactory;

    private $log;

    public function __construct(
        ApplicationUser $applicationUser,
        ApplicationState $applicationState,
        ConfigDataProvider $configDataProvider,
        EntityManagerProxy $entityManagerProxy,
        LoginFactory $authLoginFactory,
        TwoFAFactory $auth2FAFactory,
        AuthTokenManager $authTokenManager,
        Log $log,
        bool $allowAnyAccess = false
    ) {
        $this->allowAnyAccess = $allowAnyAccess;

        $this->applicationUser = $applicationUser;
        $this->applicationState = $applicationState;

        $this->configDataProvider = $configDataProvider;
        $this->entityManager = $entityManagerProxy;
        $this->authLoginFactory = $authLoginFactory;
        $this->auth2FAFactory = $auth2FAFactory;
        $this->authTokenManager = $authTokenManager;
        $this->log = $log;
    }

    protected function setPortal(Portal $portal)
    {
        $this->portal = $portal;
    }

    protected function isPortal() : bool
    {
        return (bool) $this->portal || $this->applicationState->isPortal();
    }

    protected function getPortal() : Portal
    {
        if ($this->portal) {
            return $this->portal;
        }

        return $this->applicationState->getPortal();
    }

    /**
     * Process logging in.
     *
     * @throws Forbidden
     * @throws ServiceUnavailable
     */
    public function login(
        ?string $username, ?string $password, Request $request, ?string $authenticationMethod = null
    ) : Result {

        if (
            $authenticationMethod &&
            !$this->configDataProvider->authenticationMethodIsApi($authenticationMethod)
        ) {
            $this->log->warning(
                "AUTH: Trying to use not allowed authentication method '{$authenticationMethod}'."
            );

            return Result::fail('Not allowed authentication method');
        }

        $isByTokenOnly = !$authenticationMethod && $request->getHeader('Espo-Authorization-By-Token') === 'true';

        if (!$isByTokenOnly) {
            $this->checkFailedAttemptsLimit($request);
        }

        $authToken = null;
        $authTokenIsFound = false;

        if (!$authenticationMethod) {
            $authToken = $this->authTokenManager->get($password);
        }

        if ($authToken && $authToken->getSecret()) {
            $sentSecret = $request->getCookieParam('auth-token-secret');

            if ($sentSecret !== $authToken->getSecret()) {
                $authToken = null;
            }
        }

        if ($authToken) {
            $authTokenIsFound = true;
        }

        if ($authToken && !$authToken->isActive()) {
            $authToken = null;
        }

        if ($authToken) {
            $authTokenCheckResult = $this->processAuthTokenCheck($authToken);

            if (!$authTokenCheckResult) {
                return Result::fail('Denied');
            }
        }

        if ($isByTokenOnly && !$authToken) {
            if ($username) {
                $this->log->info(
                    "AUTH: Trying to login as user '{$username}' by token but token is not found."
                );
            }

            return Result::fail('Token not found');
        }

        if (!$authenticationMethod) {
            $authenticationMethod = $this->configDataProvider->getDefaultAuthenticationMethod();
        }

        $login = $this->authLoginFactory->create($authenticationMethod, $this->isPortal());

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
            return $result;
        }

        if (!$user) {
            return Result::fail();
        }

        if (!$user->isAdmin() && $this->configDataProvider->isMaintenanceMode()) {
            throw new ServiceUnavailable("Application is in maintenance mode.");
        }

        if (!$this->processUserCheck($user, $authLogRecord)) {
            return Result::fail('Denied');
        }

        if ($this->isPortal()) {
            $user->set('portalId', $this->getPortal()->id);
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
                return $result;
            }
        }

        if (!$result->isSecondStepRequired() && $request->getHeader('Espo-Authorization')) {
            if (!$authToken) {
                $authToken = $this->createAuthToken($user, $request);
            } else {
                $this->authTokenManager->renew($authToken);
            }

            $user->set('token', $authToken->getToken());
            $user->set('authTokenId', $authToken->id ?? null);

            if ($authLogRecord) {
                $authLogRecord->set('authTokenId', $authToken->id ?? null);
            }
        }

        if ($authLogRecord) {
            $this->entityManager->saveEntity($authLogRecord);
        }

        if ($authToken && !$authLogRecord && isset($authToken->id)) {
            $authLogRecord = $this->entityManager
                ->getRepository('AuthLogRecord')
                ->select(['id'])
                ->where([
                    'authTokenId' => $authToken->id
                ])
                ->order('requestTime', true)
                ->findOne();
        }

        if ($authLogRecord) {
            $user->set('authLogRecordId', $authLogRecord->id);
        }

        return $result;
    }

    private function processAuthTokenCheck(AuthToken $authToken) : bool
    {
        if ($this->allowAnyAccess && $authToken->getPortalId() && !$this->isPortal()) {
            $portal = $this->entityManager->getEntity('Portal', $authToken->getPortalId());

            if ($portal) {
                $this->setPortal($portal);
            }
        }

        if ($this->allowAnyAccess) {
            return true;
        }

        if ($this->isPortal() && $authToken->getPortalId() !== $this->getPortal()->id) {
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

    private function processUserCheck(User $user, ?AuthLogRecord $authLogRecord) : bool
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
                ->getRepository('Portal')
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

    private function processTwoFactor(Result $result, Request $request) : Result
    {
        $loggedUser = $result->getLoggedUser();

        $method = $this->getUser2FAMethod($loggedUser);

        if (!$method) {
            return $result;
        }

        $impl = $this->auth2FAFactory->create($method);

        $code = $request->getHeader('Espo-Authorization-Code');

        if ($code) {
            if (!$impl->verifyCode($loggedUser, $code)) {
                return Result::fail('Code not verified');
            }

            return $result;
        }

        return Result::secondStepRequired($result->getUser(), $impl->getLoginData($loggedUser));
    }

    private function getUser2FAMethod(User $user) : ?string
    {
        $userData = $this->entityManager->getRepository('UserData')->getByUserId($user->id);

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

    private function checkFailedAttemptsLimit(Request $request) : void
    {
        $failedAttemptsPeriod = $this->configDataProvider->getFailedAttemptsPeriod();
        $maxFailedAttempts = $this->configDataProvider->getMaxFailedAttemptNumber();

        $requestTime = intval($request->getServerParam('REQUEST_TIME_FLOAT'));

        $requestTimeFrom = (new DateTime('@' . $requestTime))->modify('-' . $failedAttemptsPeriod);

        $failAttemptCount = 0;

        $ip = $request->getServerParam('REMOTE_ADDR');

        $where = [
            'requestTime>' => $requestTimeFrom->format('U'),
            'ipAddress' => $ip,
            'isDenied' => true,
        ];

        $wasFailed = (bool) $this->entityManager
            ->getRepository('AuthLogRecord')
            ->select(['id'])
            ->where($where)
            ->findOne();

        if ($wasFailed) {
            $failAttemptCount = $this->entityManager
                ->getRepository('AuthLogRecord')
                ->where($where)
                ->count();
        }

        if ($failAttemptCount > $maxFailedAttempts) {
            $this->log->warning(
                "AUTH: Max failed login attempts exceeded for IP '{$ip}'."
            );

            throw new Forbidden("Max failed login attempts exceeded.");
        }
    }

    private function createAuthToken(User $user, Request $request) : AuthToken
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
            'portalId' => $this->isPortal() ? $this->getPortal()->id : null,
            'createSecret' => $createSecret,
        ];

        $authToken = $this->authTokenManager->create(
            AuthTokenData::create($arrayData)
        );

        if ($createSecret) {
            $this->setSecretInCookie($authToken->getSecret());
        }

        if ($this->configDataProvider->preventConcurrentAuthToken() && $authToken instanceof AuthTokenEntity) {
            $concurrentAuthTokenList = $this->entityManager
                ->getRepository('AuthToken')
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

    public function destroyAuthToken(string $token, Request $request) : bool
    {
        $authToken = $this->authTokenManager->get($token);

        if (!$authToken) {
            return false;
        }

        $this->authTokenManager->inactivate($authToken);

        if ($authToken->getSecret()) {
            $sentSecret = $request->getCookieParam('auth-token-secret');

            if ($sentSecret === $authToken->getSecret()) {
                $this->setSecretInCookie(null);
            }
        }

        return true;
    }

    protected function createAuthLogRecord(
        ?string $username, ?User $user, Request $request, ?string $authenticationMethod = null
    ) : ?AuthLogRecord {

        if ($username === '**logout') {
            return null;
        }

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
            $authLogRecord->set('portalId', $this->getPortal()->id);
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

    private function logDenied(?AuthLogRecord $authLogRecord, string $denialReason) : void
    {
        if (!$authLogRecord) {
            return;
        }

        $authLogRecord->set('denialReason', $denialReason);

        $this->entityManager->saveEntity($authLogRecord);
    }

    private function setSecretInCookie(?string $secret) : void
    {
        $time = $secret ? strtotime('+1000 days') : -1;

        setcookie('auth-token-secret', $secret, [
            'expires' => $time,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
