<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils;

use Espo\Core\Exceptions\{
    Error,
    Forbidden,
    ServiceUnavailable,
};

use Espo\Entities\{
    Portal,
    User,
    AuthLogRecord,
};

use Espo\Core\Authentication\{
    Login,
    TwoFA\СodeVerify as TwoFACodeVerify,
    Utils\AuthenticationFactory,
    TwoFA\Utils\Factory as Auth2FAFactory,
};

use Espo\Core\Api\Request;

use Espo\Core\{
    Container,
    ApplicationState,
    Utils\Config,
    Utils\Metadata,
    ORM\EntityManager,
};

/**
 * Handles authentication. The entry point of the auth process.
 */
class Auth
{
    const FAILED_ATTEMPTS_PERIOD = '60 seconds';

    const MAX_FAILED_ATTEMPT_NUMBER = 10;

    const STATUS_SUCCESS = 'success';
    const STATUS_SECOND_STEP_REQUIRED = 'secondStepRequired';

    protected $allowAnyAccess;

    private $portal;

    protected $container;
    protected $applicationState;
    protected $config;
    protected $metadata;
    protected $entityManager;
    protected $authenticationFactory;
    protected $auth2FAFactory;

    public function __construct(
        bool $allowAnyAccess = false,
        Container $container,
        ApplicationState $applicationState,
        Config $config,
        Metadata $metadata,
        EntityManager $entityManager,
        AuthenticationFactory $authenticationFactory,
        Auth2FAFactory $auth2FAFactory
    ) {
        $this->allowAnyAccess = $allowAnyAccess;

        $this->container = $container;
        $this->applicationState = $applicationState;
        $this->config = $config;
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->authenticationFactory = $authenticationFactory;
        $this->auth2FAFactory = $auth2FAFactory;
    }

    protected function getDefaultAuthenticationMethod()
    {
        return $this->config->get('authenticationMethod', 'Espo');
    }

    protected function getAuthenticationImpl(string $method) : Login
    {
        return $this->authenticationFactory->create($method);
    }

    protected function get2FAImpl(string $method) : TwoFACodeVerify
    {
        return $this->auth2FAFactory->create($method);
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
     * Process a username and password check.
     *
     * @return Status and additional data. NULL if failed.
     */
    public function login(
        string $username, ?string $password = null, Request $request, ?string $authenticationMethod = null
    ) : ?array {
        $isByTokenOnly = false;

        if (!$authenticationMethod) {
            if ($request->getHeader('Espo-Authorization-By-Token') === 'true') {
                $isByTokenOnly = true;
            }
        }

        $createTokenSecret = $request->getHeader('Espo-Authorization-Create-Token-Secret') === 'true';

        if ($createTokenSecret) {
            if ($this->config->get('authTokenSecretDisabled')) {
                $createTokenSecret = false;
            }
        }

        if (!$isByTokenOnly) {
            $this->checkFailedAttemptsLimit($request);
        }

        $authToken = null;
        $authTokenIsFound = false;

        if (!$authenticationMethod) {
            $authToken = $this->entityManager->getRepository('AuthToken')->where(['token' => $password])->findOne();

            if ($authToken) {
                if ($authToken->get('secret')) {
                    $sentSecret = $request->getCookieParam('auth-token-secret');
                    if ($sentSecret !== $authToken->get('secret')) {
                        $authToken = null;
                    }
                }
            }
        }

        if ($authToken) {
            $authTokenIsFound = true;
        }

        if ($authToken && $authToken->get('isActive')) {
            if (!$this->allowAnyAccess) {
                if ($this->isPortal() && $authToken->get('portalId') !== $this->getPortal()->id) {
                    $GLOBALS['log']->info("AUTH: Trying to login to portal with a token not related to portal.");
                    return null;
                }
                if (!$this->isPortal() && $authToken->get('portalId')) {
                    $GLOBALS['log']->info("AUTH: Trying to login to crm with a token related to portal.");
                    return null;
                }
            }
            if ($this->allowAnyAccess) {
                if ($authToken->get('portalId') && !$this->isPortal()) {
                    $portal = $this->entityManager->getEntity('Portal', $authToken->get('portalId'));
                    if ($portal) {
                        $this->setPortal($portal);
                    }
                }
            }
        } else {
            $authToken = null;
        }

        if ($isByTokenOnly && !$authToken) {
            $GLOBALS['log']->info("AUTH: Trying to login as user '{$username}' by token but token is not found.");
            return null;
        }

        if (!$authenticationMethod) {
            $authenticationMethod = $this->getDefaultAuthenticationMethod();
        }

        $authenticationImpl = $this->getAuthenticationImpl($authenticationMethod);

        $params = [
            'isPortal' => $this->isPortal(),
        ];

        $loginResultData = [];

        $user = $authenticationImpl->login($username, $password, $authToken, $request, $params, $loginResultData);

        $authLogRecord = null;

        if (!$authTokenIsFound) {
            $authLogRecord = $this->createAuthLogRecord($username, $user, $request, $authenticationMethod);
        }

        if (!$user) {
            return null;
        }

        if (!$user->isAdmin() && $this->config->get('maintenanceMode')) {
            throw new ServiceUnavailable("Application is in maintenance mode.");
        }

        if (!$user->isActive()) {
            $GLOBALS['log']->info("AUTH: Trying to login as user '".$user->get('userName')."' which is not active.");
            $this->logDenied($authLogRecord, 'INACTIVE_USER');
            return null;
        }

        if (!$user->isAdmin() && !$this->isPortal() && $user->isPortal()) {
            $GLOBALS['log']->info("AUTH: Trying to login to crm as a portal user '".$user->get('userName')."'.");
            $this->logDenied($authLogRecord, 'IS_PORTAL_USER');
            return null;
        }

        if ($this->isPortal() && !$user->isPortal()) {
            $GLOBALS['log']->info(
                "AUTH: Trying to login to portal as user '".$user->get('userName')."' which is not portal user."
            );
            $this->logDenied($authLogRecord, 'IS_NOT_PORTAL_USER');
            return null;
        }

        if ($this->isPortal()) {
            if (!$this->entityManager->getRepository('Portal')->isRelated($this->getPortal(), 'users', $user)) {
                $GLOBALS['log']->info(
                    "AUTH: Trying to login to portal as user '".$user->get('userName')."' ".
                    "which is portal user but does not belongs to portal."
                );
                $this->logDenied($authLogRecord, 'USER_IS_NOT_IN_PORTAL');
                return null;
            }
            $user->set('portalId', $this->getPortal()->id);
        } else {
            $user->loadLinkMultipleField('teams');
        }

        $user->set('ipAddress', $request->getServerParam('REMOTE_ADDR') ?? null);

        $this->container->set('user', $user);

        $secondStepRequired = false;

        if (!$authToken && $this->config->get('auth2FA')) {
            $twoFAMethod = $this->getUser2FAMethod($user);
            if ($twoFAMethod) {
                $twoFAImpl = $this->get2FAImpl($twoFAMethod);

                $twoFACode = $request->getHeader('Espo-Authorization-Code');

                if ($twoFACode) {
                    if (!$twoFAImpl->verifyCode($user, $twoFACode)) {
                        return null;
                    }
                } else {
                    $loginResultData = $twoFAImpl->getLoginData($user);
                    $secondStepRequired = true;
                }
            }
        }

        if (!$secondStepRequired) {
            $secondStepRequired = $loginResultData['secondStepRequired'] ?? false;
        }

        if (!$secondStepRequired && $request->getHeader('Http-Espo-Authorization')) {
            if (!$authToken) {
                $authToken = $this->entityManager->getEntity('AuthToken');
                $token = $this->generateToken();
                $authToken->set('token', $token);
                $authToken->set('hash', $user->get('password'));
                $authToken->set('ipAddress', $request->getServerParam('REMOTE_ADDR'));
                $authToken->set('userId', $user->id);

                if ($createTokenSecret) {
                    $secret = $this->generateToken();
                    $authToken->set('secret', $secret);
                    $this->setSecretInCookie($secret);
                }

                if ($this->isPortal()) {
                    $authToken->set('portalId', $this->getPortal()->id);
                }

                if ($this->config->get('authTokenPreventConcurrent')) {
                    $concurrentAuthTokenList = $this->entityManager->getRepository('AuthToken')->select(['id'])->where([
                        'userId' => $user->id,
                        'isActive' => true,
                    ])->find();
                    foreach ($concurrentAuthTokenList as $concurrentAuthToken) {
                        $concurrentAuthToken->set('isActive', false);
                        $this->entityManager->saveEntity($concurrentAuthToken);
                    }
                }
            }
        	$authToken->set('lastAccess', date('Y-m-d H:i:s'));

        	$this->entityManager->saveEntity($authToken);
        	$user->set('token', $authToken->get('token'));
            $user->set('authTokenId', $authToken->id);

            if ($authLogRecord) {
                $authLogRecord->set('authTokenId', $authToken->id);
            }
        }

        if ($authLogRecord) {
            $this->entityManager->saveEntity($authLogRecord);
        }

        if ($authToken && !$authLogRecord) {
            $authLogRecord = $this->entityManager->getRepository('AuthLogRecord')->select(['id'])->where([
                'authTokenId' => $authToken->id
            ])->order('requestTime', true)->findOne();
        }

        if ($authLogRecord) {
            $user->set('authLogRecordId', $authLogRecord->id);
        }

        if ($secondStepRequired) {
            return [
                'status' => self::STATUS_SECOND_STEP_REQUIRED,
                'message' => $loginResultData['message'] ?? null,
                'token' => $loginResultData['token'] ?? null,
                'view' => $loginResultData['view'] ?? null,
            ];
        }

        return [
            'status' => self::STATUS_SUCCESS,
        ];
    }

    protected function getUser2FAMethod(User $user) : ?string
    {
        $userData = $this->entityManager->getRepository('UserData')->getByUserId($user->id);
        if (!$userData) return null;
        if (!$userData->get('auth2FA')) return null;

        $method = $userData->get('auth2FAMethod');

        if (!$method) return null;
        if (!in_array($method, $this->config->get('auth2FAMethodList', []))) return null;

        return $method;
    }

    protected function checkFailedAttemptsLimit(Request $request)
    {
        $failedAttemptsPeriod = $this->config->get('authFailedAttemptsPeriod', self::FAILED_ATTEMPTS_PERIOD);
        $maxFailedAttempts = $this->config->get('authMaxFailedAttemptNumber', self::MAX_FAILED_ATTEMPT_NUMBER);

        $requestTime = intval($request->getServerParam('REQUEST_TIME_FLOAT'));
        $requestTimeFrom = (new \DateTime('@' . $requestTime))->modify('-' . $failedAttemptsPeriod);

        $failAttemptCount = 0;

        $ip = $request->getServerParam('REMOTE_ADDR');

        $where = [
            'requestTime>' => $requestTimeFrom->format('U'),
            'ipAddress' => $ip,
            'isDenied' => true,
        ];

        $wasFailed = (bool) $this->entityManager->getRepository('AuthLogRecord')->select(['id'])->where($where)->findOne();

        if ($wasFailed) {
            $failAttemptCount = $this->entityManager->getRepository('AuthLogRecord')->where($where)->count();
        }

        if ($failAttemptCount > $maxFailedAttempts) {
            $GLOBALS['log']->warning("AUTH: Max failed login attempts exceeded for IP '{$ip}'.");
            throw new Forbidden("Max failed login attempts exceeded.");
        }
    }

    protected function generateToken()
    {
        $length = 16;

        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, \MCRYPT_DEV_URANDOM));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
    }

    public function destroyAuthToken(string $token, Request $request)
    {
        $authToken = $this->entityManager->getRepository('AuthToken')->select(['id', 'isActive', 'secret'])->where(
            ['token' => $token]
        )->findOne();

        if ($authToken) {
            $authToken->set('isActive', false);
            $this->entityManager->saveEntity($authToken);
            if ($authToken->get('secret')) {
                $sentSecret = $request->getCookieParam('auth-token-secret');
                if ($sentSecret === $authToken->get('secret')) {
                    setcookie('auth-token-secret', null, -1, '/');
                }
            }
            return true;
        }
    }

    protected function createAuthLogRecord(
        string $username, ?User $user, Request $request, ?string $authenticationMethod = null
    ) : ?AuthLogRecord {
        if ($username === '**logout') return null;

        $authLogRecord = $this->entityManager->getEntity('AuthLogRecord');

        $requestUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost() . $request->getUri()->getPath();

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
        } else {
            $authLogRecord->set('isDenied', true);
            $authLogRecord->set('denialReason', 'CREDENTIALS');
            $this->entityManager->saveEntity($authLogRecord);
        }

        return $authLogRecord;
    }

    protected function logDenied(AuthLogRecord $authLogRecord, string $denialReason)
    {
        if (!$authLogRecord) return;

        $authLogRecord->set('denialReason', $denialReason);
        $this->entityManager->saveEntity($authLogRecord);
    }

    protected function setSecretInCookie(string $secret)
    {
        if (version_compare(\PHP_VERSION, '7.3.0') < 0) {
            setcookie('auth-token-secret', $secret, strtotime('+1000 days'), '/', '', false, true);
            return;
        }

        setcookie('auth-token-secret', $secret, [
            'expires' => strtotime('+1000 days'),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
