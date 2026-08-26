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

namespace Espo\Core\Authentication;

use Espo\Core\Authentication\Repository\UserRepository;
use Espo\Core\Authentication\TwoFactor\MethodProvider as TwoFactorMethodProvider;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Language\LanguageProxy;
use Espo\ORM\Name\Attribute;
use Espo\Entities\Portal;
use Espo\Entities\User;
use Espo\Entities\AuthLogRecord;
use Espo\Entities\AuthToken as AuthTokenEntity;
use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Authentication\Logout\Params as LogoutParams;
use Espo\Core\Authentication\Util\MethodProvider;
use Espo\Core\Authentication\Result\FailReason;
use Espo\Core\Authentication\TwoFactor\LoginFactory as TwoFactorLoginFactory;
use Espo\Core\Authentication\AuthToken\Manager as AuthTokenManager;
use Espo\Core\Authentication\AuthToken\Data as AuthTokenData;
use Espo\Core\Authentication\AuthToken\AuthToken;
use Espo\Core\Authentication\Hook\Manager as HookManager;
use Espo\Core\Authentication\Login\Data as LoginData;
use Espo\Core\ApplicationUser;
use Espo\Core\ApplicationState;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\Util;
use Espo\Core\Utils\Log;
use Espo\Core\ORM\EntityManagerProxy;
use Espo\Core\Exceptions\ServiceUnavailable;
use Espo\Core\Authentication\Repository\AuthLogRecordRepository;
use LogicException;
use RuntimeException;

/**
 * Handles authentication. The entry point of the auth process.
 */
class Authentication
{
    private const string LOGOUT_USERNAME = '**logout';

    private const string HEADER_ANOTHER_USER = 'X-Another-User';
    private const string HEADER_LOGOUT_REDIRECT_URL = 'X-Logout-Redirect-Url';

    private const string COOKIE_AUTH_TOKEN_SECRET = 'auth-token-secret';

    public function __construct(
        private ApplicationUser $applicationUser,
        private ApplicationState $applicationState,
        private ConfigDataProvider $configDataProvider,
        private EntityManagerProxy $entityManager,
        private LoginFactory $loginFactory,
        private TwoFactorLoginFactory $twoFactorLoginFactory,
        private AuthTokenManager $authTokenManager,
        private HookManager $hookManager,
        private Log $log,
        private LogoutFactory $logoutFactory,
        private MethodProvider $methodProvider,
        private Util $util,
        private LanguageProxy $language,
        private TwoFactorMethodProvider $twoFactorMethodProvider,
        private UserRepository $userRepository,
        private AuthLogRecordRepository $authLogRecordRepository,
    ) {}

    /**
     * Process logging in.
     *
     * @throws ServiceUnavailable
     * @throws Forbidden
     */
    public function login(AuthenticationData $data, Request $request, Response $response): Result
    {
        $username = $data->getUsername();
        $password = $data->getPassword();
        $byTokenOnly = $data->byTokenOnly();

        if ($failResult = $this->processMethodCheck($data, $request)) {
            return $failResult;
        }

        $this->processBeforeLoginHook($data, $request);

        if ($failResult = $this->processHasPasswordCheck($data)) {
            return $failResult;
        }

        [$authToken, $authTokenIsFound] = $this->getAuthToken($data->getMethod(), $password, $request);

        if ($authToken && !$this->processAuthTokenCheck($authToken)) {
            return Result::fail(FailReason::DENIED);
        }

        if ($data->getMethod() && $this->isByTokenAndUsername($request)) {
            return Result::fail(FailReason::DISCREPANT_DATA);
        }

        if ($failResult = $this->processAuthTokenNotFoundCheck($data, $request, $authToken)) {
            return $failResult;
        }

        if ($byTokenOnly) {
            if ($authToken === null) {
                throw new LogicException();
            }

            $username = $this->getUsernameByAuthToken($authToken);

            if (!$username) {
                return $this->processFail(Result::fail(FailReason::USER_NOT_FOUND), $data, $request);
            }
        }

        $method = $data->getMethod() ?? $this->methodProvider->get();

        $result = $this->processLogin(
            method: $method,
            username: $username,
            password: $password,
            authToken: $authToken,
            request: $request,
        );

        $user = $result->getUser();

        $authLogRecord = !$authTokenIsFound ?
            $this->createAuthLogRecord($username, $user, $request, $method) : null;

        if ($result->isFail()) {
            return $this->processFail($result, $data, $request);
        }

        if (!$user) {
            // Supposed not to ever happen.
            return $this->processFail(Result::fail(FailReason::USER_NOT_FOUND), $data, $request);
        }

        if (!$user->isAdmin() && $this->configDataProvider->isMaintenanceMode()) {
            $this->throwMaintenanceModeException();
        }

        if (!$this->processUserCheck($user, $authLogRecord)) {
            return $this->processFail(Result::fail(FailReason::DENIED), $data, $request);
        }

        $this->prepareUser($user, $request);

        [$loggedUser, $anotherUserFailReason] = $this->getLoggedUser($request, $user);

        if (!$loggedUser) {
            $anotherUserFailReason = $anotherUserFailReason ?? FailReason::ANOTHER_USER_NOT_FOUND;

            return $this->processFail(Result::fail($anotherUserFailReason), $data, $request);
        }

        if ($this->toProcessTwoFactor($result, $authToken)) {
            $result = $this->processTwoFactor($result, $request);

            if ($result->isFail()) {
                return $this->processTwoFactorFail($result, $data, $request, $authLogRecord);
            }
        }

        if ($result->isSuccess()) {
            $this->applicationUser->setUser($loggedUser);
        }

        try {
            $this->hookManager->processOnLogin($result, $data, $request);
        } catch (Forbidden $e) {
            $this->processForbidden($e, $authLogRecord);
        }

        if (!$result->isSecondStepRequired() && $request->getHeader(HeaderKey::AUTHORIZATION)) {
            $authToken = $this->processAuthTokenFinal(
                authToken: $authToken,
                authLogRecord: $authLogRecord,
                user: $user,
                loggedUser: $loggedUser,
                request: $request,
                response: $response,
            );
        }

        $this->processAuthLogRecord($authLogRecord, $authToken, $loggedUser);

        if ($result->isSuccess()) {
            return $this->processSuccess($result, $data, $request, $authTokenIsFound);
        }

        if ($result->isSecondStepRequired()) {
            return $this->processSecondStepRequired($result, $data, $request);
        }

        return $result;
    }

    private function processAuthTokenFinal(
        ?AuthToken $authToken,
        ?AuthLogRecord $authLogRecord,
        User $user,
        User $loggedUser,
        Request $request,
        Response $response,
    ): AuthToken {

        if ($authToken) {
            $this->authTokenManager->renew($authToken);
        }

        if (!$authToken) {
            $authToken = $this->createAuthToken($user, $request, $response);
        }

        $authTokenId = null;

        if ($authToken instanceof AuthTokenEntity) {
            $authTokenId = $authToken->hasId() ? $authToken->getId() : null;
        }

        $loggedUser->set(User::FIELD_TOKEN, $authToken->getToken());
        $loggedUser->set(User::ATTR_AUTH_TOKEN_ID, $authTokenId);

        $authLogRecord?->setAuthTokenId($authTokenId);

        return $authToken;
    }

    private function processAuthLogRecord(
        ?AuthLogRecord $authLogRecord,
        ?AuthToken $authToken,
        User $loggedUser,
    ): void {

        if ($authLogRecord) {
            $this->entityManager->saveEntity($authLogRecord);
        }

        if (
            !$authLogRecord &&
            $authToken instanceof AuthTokenEntity &&
            $authToken->hasId()
        ) {
            $authLogRecord = $this->authLogRecordRepository->findOneByAuthTokenId($authToken->getId(), [Attribute::ID]);
        }

        if ($authLogRecord) {
            $loggedUser->set(User::ATTR_AUTH_LOG_RECORD_ID, $authLogRecord->getId());
        }
    }

    private function isPortal(): bool
    {
        return $this->applicationState->isPortal();
    }

    private function getPortal(): Portal
    {
        return $this->applicationState->getPortal();
    }

    private function processAuthTokenCheck(AuthToken $authToken): bool
    {
        if ($this->isPortal() && $authToken->getPortalId() !== $this->getPortal()->getId()) {
            $this->log->info("Auth: Trying to log in to portal with a token not related to portal.");

            return false;
        }

        if (!$this->isPortal() && $authToken->getPortalId()) {
            $this->log->info("Auth: Trying to log in to crm with a token related to portal.");

            return false;
        }

        return true;
    }

    private function processUserCheck(User $user, ?AuthLogRecord $authLogRecord): bool
    {
        if (!$user->isActive()) {
            $this->log->info("Auth: Trying to log in as user '{username}' which is not active.", [
                'username' => $user->getUserName(),
            ]);

            $this->logDenied($authLogRecord, AuthLogRecord::DENIAL_REASON_INACTIVE_USER);

            return false;
        }

        if ($user->isSystem()) {
            $this->log->info("Auth: Trying to log in to crm as a system user '{username}'.", [
                'username' => $user->getUserName(),
            ]);

            $this->logDenied($authLogRecord, AuthLogRecord::DENIAL_REASON_IS_SYSTEM_USER);

            return false;
        }

        if (!$user->isAdmin() && !$this->isPortal() && $user->isPortal()) {
            $this->log->info("Auth: Trying to log in to crm as a portal user '{username}'.", [
                'username' => $user->getUserName(),
            ]);

            $this->logDenied($authLogRecord, AuthLogRecord::DENIAL_REASON_IS_PORTAL_USER);

            return false;
        }

        if ($this->isPortal() && !$user->isPortal()) {
            $this->log->info("Auth: Trying to log in to portal as user '{username}' which is not portal user.", [
                'username' => $user->getUserName(),
            ]);

            $this->logDenied($authLogRecord, AuthLogRecord::DENIAL_REASON_IS_NOT_PORTAL_USER);

            return false;
        }

        if ($this->isPortal()) {
            $isUserInPortal = $user->getPortals()->hasId($this->getPortal()->getId());

            if (!$isUserInPortal) {
                $message = "Auth: Trying to log in to portal as user '{username}' " .
                    "which is portal user but does not belong to portal.";

                $this->log->info($message, ['username' => $user->getUserName()]);

                $this->logDenied($authLogRecord, AuthLogRecord::DENIAL_REASON_USER_IS_NOT_IN_PORTAL);

                return false;
            }
        }

        return true;
    }

    private function processTwoFactor(Result $result, Request $request): Result
    {
        $user = $result->getUser();

        if (!$user) {
            throw new RuntimeException("No user.");
        }

        $method = $this->twoFactorMethodProvider->get($user->getId());

        if (!$method) {
            return $result;
        }

        $login = $this->twoFactorLoginFactory->create($method);

        return $login->login($result, $request);
    }

    private function createAuthToken(User $user, Request $request, Response $response): AuthToken
    {
        $createSecret =
            $request->getHeader(HeaderKey::CREATE_TOKEN_SECRET) === 'true' &&
            !$this->configDataProvider->isAuthTokenSecretDisabled();

        $ipAddress = $this->util->obtainIpFromRequest($request);

        $authTokenData = AuthTokenData::create([
            'ipAddress' => $ipAddress,
            'userId' => $user->getId(),
            'passwordVersion' => $user->getPasswordVersion(),
            'portalId' => $this->isPortal() ? $this->getPortal()->getId() : null,
            'createSecret' => $createSecret,
        ]);

        $authToken = $this->authTokenManager->create($authTokenData);

        if ($createSecret) {
            $this->setSecretInCookie($authToken->getSecret(), $response, $request);
        }

        if ($this->configDataProvider->preventConcurrentAuthToken()) {
            $this->authTokenManager->inactiveOther($authToken);
        }

        return $authToken;
    }

    /**
     * Destroy an auth token.
     *
     * @param string $token A token to destroy.
     * @param Request $request A request.
     * @param Response $response A response.
     * @throws Forbidden
     * @throws NotFound
     */
    public function destroyAuthToken(string $token, Request $request, Response $response): void
    {
        $authToken = $this->authTokenManager->get($token);

        if (!$authToken) {
            throw new NotFound("Auth token not found.");
        }

        if (!$this->applicationState->hasUser()) {
            throw new LogicException("No logged user.");
        }

        $user = $this->applicationState->getUser();

        $this->authTokenManager->inactivate($authToken);

        if ($authToken->getSecret()) {
            $sentSecret = $request->getCookieParam(self::COOKIE_AUTH_TOKEN_SECRET);

            if (
                // Still need the ability to destroy auth tokens of another users
                // for login-as-another-user feature.
                $authToken->getUserId() !== $user->getId() &&
                $sentSecret !== $authToken->getSecret()
            ) {
                throw new Forbidden("Can't destroy auth token.");
            }

            if ($sentSecret === $authToken->getSecret()) {
                $this->setSecretInCookie(null, $response);
            }
        }

        $method = $this->methodProvider->get();

        if (!$this->logoutFactory->isCreatable($method)) {
            return;
        }

        $result = $this->logoutFactory
            ->create($method)
            ->logout($authToken, LogoutParams::create());

        $redirectUrl = $result->getRedirectUrl();

        if ($redirectUrl) {
            $response->setHeader(self::HEADER_LOGOUT_REDIRECT_URL, $redirectUrl);
        }
    }

    private function createAuthLogRecord(
        ?string $username,
        ?User $user,
        Request $request,
        ?string $method = null,
    ): ?AuthLogRecord {

        if ($username === self::LOGOUT_USERNAME) {
            return null;
        }

        if ($this->configDataProvider->isAuthLogDisabled()) {
            return null;
        }

        /** @var AuthLogRecord $authLogRecord */
        $authLogRecord = $this->entityManager->getNewEntity(AuthLogRecord::ENTITY_TYPE);

        $requestUrl =
            $request->getUri()->getScheme() . '://' .
            $request->getUri()->getHost() .
            $request->getUri()->getPath();

        if (!$username && $user) {
            $username = $user->getUserName();
        }

        $authLogRecord
            ->setUsername($username)
            ->setIpAddress($this->util->obtainIpFromRequest($request))
            ->setRequestTime($request->getServerParam('REQUEST_TIME_FLOAT'))
            ->setRequestMethod($request->getMethod())
            ->setRequestUrl($requestUrl)
            ->setAuthenticationMethod($method)
            ->setPortalId($this->isPortal() ? $this->getPortal()->getId() : null);

        if ($user && $user->isApi() && $this->configDataProvider->isApiUserAuthLogDisabled()) {
            return null;
        }

        if ($user) {
            $authLogRecord->setUserId($user->hasId() ? $user->getId() : null);

            return $authLogRecord;
        }

        $authLogRecord
            ->setIsDenied()
            ->setDenialReason(AuthLogRecord::DENIAL_REASON_CREDENTIALS);

        $this->entityManager->saveEntity($authLogRecord);

        return $authLogRecord;
    }

    private function logDenied(?AuthLogRecord $authLogRecord, string $denialReason): void
    {
        if (!$authLogRecord) {
            return;
        }

        $authLogRecord
            ->setIsDenied()
            ->setDenialReason($denialReason);

        $this->entityManager->saveEntity($authLogRecord);
    }

    private function setSecretInCookie(?string $secret, Response $response, ?Request $request = null): void
    {
        $time = $secret ? strtotime('+1000 days') : 1;

        $value = $secret ?? 'deleted';

        $headerValue =
            self::COOKIE_AUTH_TOKEN_SECRET . '=' . urlencode($value) .
            '; path=/' .
            '; expires=' . gmdate('D, d M Y H:i:s T', $time) .
            '; HttpOnly' .
            '; SameSite=Lax';

        if ($request && self::isSecureRequest($request)) {
            $headerValue .= "; Secure";
        }

        $response->addHeader('Set-Cookie', $headerValue);
    }

    private static function isSecureRequest(Request $request): bool
    {
        $https = $request->getServerParam('HTTPS');

        if ($https === 'on') {
            return true;
        }

        $scheme = $request->getServerParam('REQUEST_SCHEME');

        if ($scheme === 'https') {
            return true;
        }

        $forwardedProto = $request->getServerParam('HTTP_X_FORWARDED_PROTO');

        if ($forwardedProto === 'https') {
            return true;
        }

        return false;
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
        bool $byToken,
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
        Request $request,
    ): Result {

        $this->hookManager->processOnSecondStepRequired($result, $data, $request);

        return $result;
    }

    private function getUsernameByAuthToken(AuthToken $authToken): ?string
    {
        $user = $this->userRepository->findOneById($authToken->getUserId(), [User::FIELD_USER_NAME]);

        return $user?->getUserName();
    }

    /**
     * @return array{?User, (FailReason::*)|null}
     */
    private function getLoggedUser(Request $request, User $user): array
    {
        $username = $request->getHeader(self::HEADER_ANOTHER_USER);

        if (!$username) {
            return [$user, null];
        }

        if ($this->configDataProvider->isAnotherUserDisabled()) {
            return [null, FailReason::ANOTHER_USER_NOT_ALLOWED];
        }

        // Important check.
        if (!$user->isAdmin()) {
            return [null, FailReason::ANOTHER_USER_NOT_ALLOWED];
        }

        $loggedUser = $this->userRepository->findOneByUsername($username);

        if (!$loggedUser) {
            return [null, FailReason::ANOTHER_USER_NOT_FOUND];
        }

        if (!$loggedUser->isRegular()) {
            return [null, FailReason::ANOTHER_USER_NOT_ALLOWED];
        }

        $loggedUser->loadLinkMultipleField(Field::TEAMS);

        return [$loggedUser, null];
    }

    private function prepareUser(User $user, Request $request): void
    {
        if ($this->isPortal()) {
            $user->set(User::ATTR_PORTAL_ID, $this->getPortal()->getId());
        }

        if (!$this->isPortal()) {
            $user->loadLinkMultipleField(Field::TEAMS);
        }

        $user->set(User::FIELD_IP_ADDRESS, $this->util->obtainIpFromRequest($request));
    }

    /**
     * @throws Forbidden
     */
    private function processForbidden(Forbidden $exception, ?AuthLogRecord $authLogRecord = null): never
    {
        $this->log->warning('Auth: Forbidden. {message}', [
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);

        if ($authLogRecord) {
            $authLogRecord
                ->setIsDenied()
                ->setDenialReason(AuthLogRecord::DENIAL_REASON_FORBIDDEN);

            $this->entityManager->saveEntity($authLogRecord);
        }

        throw new Forbidden();
    }

    private function processTwoFactorFail(
        Result $result,
        AuthenticationData $data,
        Request $request,
        ?AuthLogRecord $authLogRecord
    ): Result {

        if ($authLogRecord) {
            $authLogRecord
                ->setIsDenied()
                ->setDenialReason(AuthLogRecord::DENIAL_REASON_WRONG_CODE);

            $this->entityManager->saveEntity($authLogRecord);
        }

        return $this->processFail($result, $data, $request);
    }

    private function getTwoFactorEnabled(): bool
    {
        if ($this->isPortal() && !$this->configDataProvider->isTwoFactorInPortalEnabled()) {
            return false;
        }

        return $this->configDataProvider->isTwoFactorEnabled();
    }

    /**
     * @throws Forbidden
     * @throws ServiceUnavailable
     */
    private function processBeforeLoginHook(AuthenticationData $data, Request $request): void
    {
        try {
            $this->hookManager->processBeforeLogin($data, $request);
        } catch (Forbidden $e) {
            $this->processForbidden($e);
        }
    }

    private function toProcessTwoFactor(Result $result, ?AuthToken $authToken): bool
    {
        return
            !$result->bypassSecondStep() &&
            !$result->isSecondStepRequired() &&
            !$authToken &&
            $this->getTwoFactorEnabled();
    }

    /**
     * @throws ServiceUnavailable
     */
    private function throwMaintenanceModeException(): never
    {
        throw ServiceUnavailable::createWithBody(
            "Application is in maintenance mode.",
            Body::create()
                ->withMessage($this->language->translateLabel('maintenanceModeError', 'messages'))
        );
    }

    private function processLogin(
        string $method,
        ?string $username,
        ?string $password,
        ?AuthToken $authToken,
        Request $request,
    ): Result {

        $login = $this->loginFactory->create($method, $this->isPortal());

        $loginData = LoginData::createBuilder()
            ->setUsername($username)
            ->setPassword($password)
            ->setAuthToken($authToken)
            ->build();

        return $login->login($loginData, $request);
    }

    private function processMethodCheck(AuthenticationData $data, Request $request): ?Result
    {
        $method = $data->getMethod();

        if ($method && !$this->configDataProvider->authenticationMethodIsApi($method)) {
            $this->log->warning("Auth: Trying to use not allowed authentication method '{method}'.", [
                'method' => $method,
            ]);

            return $this->processFail(Result::fail(FailReason::METHOD_NOT_ALLOWED), $data, $request);
        }

        return null;
    }

    private function processHasPasswordCheck(AuthenticationData $data): ?Result
    {
        $password = $data->getPassword();
        $method = $data->getMethod();

        if (!$method && $password === null) {
            $this->log->error("Auth: Trying to log in w/o password.");

            return Result::fail(FailReason::NO_PASSWORD);
        }

        return null;
    }

    /**
     * @return array{?AuthToken, bool}
     */
    private function getAuthToken(?string $method, ?string $password, Request $request): array
    {
        $authToken = null;

        if (!$method) {
            if ($password === null) {
                throw new LogicException();
            }

            $authToken = $this->authTokenManager->get($password);
        }

        if ($authToken && $authToken->getSecret()) {
            $sentSecret = $request->getCookieParam(self::COOKIE_AUTH_TOKEN_SECRET);

            if ($sentSecret !== $authToken->getSecret()) {
                $authToken = null;
            }
        }

        $authTokenIsFound = $authToken !== null;

        if ($authToken && !$authToken->isActive()) {
            $authToken = null;
        }

        return [$authToken, $authTokenIsFound];
    }

    private function processAuthTokenNotFoundCheck(
        AuthenticationData $data,
        Request $request,
        ?AuthToken $authToken,
    ): ?Result {

        if ($authToken) {
            return null;
        }

        if ($this->isByTokenAndUsername($request) || $data->byTokenOnly()) {
            if ($data->getUsername()) {
                $this->log->info("Auth: Trying to log in as user '{username}' by token but token is not found.", [
                    'username' => $data->getUsername(),
                ]);
            }

            return $this->processFail(Result::fail(FailReason::TOKEN_NOT_FOUND), $data, $request);
        }

        return null;
    }

    private function isByTokenAndUsername(Request $request): bool
    {
        return $request->getHeader(HeaderKey::AUTHORIZATION_BY_TOKEN) === 'true';
    }
}
