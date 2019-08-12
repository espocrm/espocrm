<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

use \Espo\Entities\Portal;

class Auth
{
    protected $container;

    protected $allowAnyAccess;

    const ACCESS_CRM_ONLY = 0;

    const ACCESS_PORTAL_ONLY = 1;

    const ACCESS_ANY = 3;

    const FAILED_ATTEMPTS_PERIOD = '60 seconds';

    const MAX_FAILED_ATTEMPT_NUMBER = 10;

    private $portal;

    public function __construct(\Espo\Core\Container $container, $allowAnyAccess = false)
    {
        $this->container = $container;

        $this->allowAnyAccess = $allowAnyAccess;

        $this->request = $container->get('slim')->request();
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getDefaultAuthenticationMethod()
    {
        return $this->getConfig()->get('authenticationMethod', 'Espo');
    }

    protected function getAuthentication($authenticationMethod)
    {
        $authenticationMethod = preg_replace('/[^a-zA-Z0-9]+/', '', $authenticationMethod);

        $authenticationClassName = "\\Espo\\Custom\\Core\\Utils\\Authentication\\" . $authenticationMethod;
        if (!class_exists($authenticationClassName)) {
            $authenticationClassName = "\\Espo\\Core\\Utils\\Authentication\\" . $authenticationMethod;
        }

        $authentication = new $authenticationClassName($this->getConfig(), $this->getEntityManager(), $this);

        return $authentication;
    }

    protected function setPortal(Portal $portal)
    {
        $this->portal = $portal;
    }

    protected function isPortal()
    {
        if ($this->portal) {
            return true;
        }
        return !!$this->getContainer()->get('portal');
    }

    protected function getPortal()
    {
        if ($this->portal) {
            return $this->portal;
        }
        return $this->getContainer()->get('portal');
    }

    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    public function useNoAuth()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $user = $entityManager->getRepository('User')->get('system');
        if (!$user) {
            throw new Error("System user is not found");
        }

        $user->set('isAdmin', true);
        $user->set('ipAddress', $_SERVER['REMOTE_ADDR']);

        $entityManager->setUser($user);
        $this->getContainer()->setUser($user);
    }

    public function login($username, $password = null, $authenticationMethod = null)
    {
        $isByTokenOnly = false;

        if (!$authenticationMethod) {
            if ($this->request->headers->get('Http-Espo-Authorization-By-Token') === 'true') {
                $isByTokenOnly = true;
            }
        }

        $createTokenSecret = $this->request->headers->get('Espo-Authorization-Create-Token-Secret') === 'true';

        if ($createTokenSecret) {
            if ($this->getConfig()->get('authTokenSecretDisabled')) {
                $createTokenSecret = false;
            }
        }

        if (!$isByTokenOnly) {
            $this->checkFailedAttemptsLimit($username);
        }

        $authToken = null;
        $authTokenIsFound = false;

        if (!$authenticationMethod) {
            $authToken = $this->getEntityManager()->getRepository('AuthToken')->where([
                'token' => $password
            ])->findOne();

            if ($authToken) {
                if ($authToken->get('secret')) {
                    $sentSecret = $_COOKIE['auth-token-secret'] ?? null;
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
                    return;
                }
                if (!$this->isPortal() && $authToken->get('portalId')) {
                    $GLOBALS['log']->info("AUTH: Trying to login to crm with a token related to portal.");
                    return;
                }
            }
            if ($this->allowAnyAccess) {
                if ($authToken->get('portalId') && !$this->isPortal()) {
                    $portal = $this->getEntityManager()->getEntity('Portal', $authToken->get('portalId'));
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
            return;
        }

        if (!$authenticationMethod) {
            $authenticationMethod = $this->getDefaultAuthenticationMethod();
        }

        $authentication = $this->getAuthentication($authenticationMethod);

        $params = [
            'isPortal' => $this->isPortal()
        ];

        $user = $authentication->login($username, $password, $authToken, $params, $this->request);

        $authLogRecord = null;

        if (!$authTokenIsFound) {
            $authLogRecord = $this->createAuthLogRecord($username, $user, $authenticationMethod);
        }

        if (!$user) {
            return;
        }

        if (!$user->isAdmin() && $this->getConfig()->get('maintenanceMode')) {
            throw new \Espo\Core\Exceptions\ServiceUnavailable("Application is in maintenance mode.");
        }

        if (!$user->isActive()) {
            $GLOBALS['log']->info("AUTH: Trying to login as user '".$user->get('userName')."' which is not active.");
            $this->logDenied($authLogRecord, 'INACTIVE_USER');
            return;
        }

        if (!$user->isAdmin() && !$this->isPortal() && $user->isPortal()) {
            $GLOBALS['log']->info("AUTH: Trying to login to crm as a portal user '".$user->get('userName')."'.");
            $this->logDenied($authLogRecord, 'IS_PORTAL_USER');
            return;
        }

        if ($this->isPortal() && !$user->isPortal()) {
            $GLOBALS['log']->info("AUTH: Trying to login to portal as user '".$user->get('userName')."' which is not portal user.");
            $this->logDenied($authLogRecord, 'IS_NOT_PORTAL_USER');
            return;
        }

        if ($this->isPortal()) {
            if (!$this->getEntityManager()->getRepository('Portal')->isRelated($this->getPortal(), 'users', $user)) {
                $GLOBALS['log']->info("AUTH: Trying to login to portal as user '".$user->get('userName')."' which is portal user but does not belongs to portal.");
                $this->logDenied($authLogRecord, 'USER_IS_NOT_IN_PORTAL');
                return;
            }
            $user->set('portalId', $this->getPortal()->id);
        } else {
            $user->loadLinkMultipleField('teams');
        }

        $user->set('ipAddress', $_SERVER['REMOTE_ADDR']);

        $this->getEntityManager()->setUser($user);
        $this->getContainer()->setUser($user);

        if ($this->request->headers->get('Http-Espo-Authorization')) {
            if (!$authToken) {
                $authToken = $this->getEntityManager()->getEntity('AuthToken');
                $token = $this->generateToken();
                $authToken->set('token', $token);
                $authToken->set('hash', $user->get('password'));
                $authToken->set('ipAddress', $_SERVER['REMOTE_ADDR']);
                $authToken->set('userId', $user->id);

                if ($createTokenSecret) {
                    $secret = $this->generateToken();
                    $authToken->set('secret', $secret);
                    $this->setSecretInCookie($secret);
                }

                if ($this->isPortal()) {
                    $authToken->set('portalId', $this->getPortal()->id);
                }

                if ($this->getConfig()->get('authTokenPreventConcurrent')) {
                    $concurrentAuthTokenList = $this->getEntityManager()->getRepository('AuthToken')->select(['id'])->where([
                        'userId' => $user->id,
                        'isActive' => true
                    ])->find();
                    foreach ($concurrentAuthTokenList as $concurrentAuthToken) {
                        $concurrentAuthToken->set('isActive', false);
                        $this->getEntityManager()->saveEntity($concurrentAuthToken);
                    }
                }
            }
        	$authToken->set('lastAccess', date('Y-m-d H:i:s'));

        	$this->getEntityManager()->saveEntity($authToken);
        	$user->set('token', $authToken->get('token'));
            $user->set('authTokenId', $authToken->id);

            if ($authLogRecord) {
                $authLogRecord->set('authTokenId', $authToken->id);
            }
        }

        if ($authLogRecord) {
            $this->getEntityManager()->saveEntity($authLogRecord);
        }

        if ($authToken && !$authLogRecord) {
            $authLogRecord = $this->getEntityManager()->getRepository('AuthLogRecord')->select(['id'])->where([
                'authTokenId' => $authToken->id
            ])->order('requestTime', true)->findOne();
        }

        if ($authLogRecord) {
            $user->set('authLogRecordId', $authLogRecord->id);
        }

        return true;
    }

    protected function checkFailedAttemptsLimit($username = null)
    {
        $failedAttemptsPeriod = $this->getConfig()->get('authFailedAttemptsPeriod', self::FAILED_ATTEMPTS_PERIOD);
        $maxFailedAttempts = $this->getConfig()->get('authMaxFailedAttemptNumber', self::MAX_FAILED_ATTEMPT_NUMBER);

        $requestTimeFrom = (new \DateTime('@' . intval($_SERVER['REQUEST_TIME_FLOAT'])))->modify('-' . $failedAttemptsPeriod);

        $failAttemptCount = $this->getEntityManager()->getRepository('AuthLogRecord')->where([
            'requestTime>' => $requestTimeFrom->format('U'),
            'ipAddress' => $_SERVER['REMOTE_ADDR'],
            'isDenied' => true
        ])->count();

        if ($failAttemptCount > $maxFailedAttempts) {
            $GLOBALS['log']->warning("AUTH: Max failed login attempts exceeded for IP '".$_SERVER['REMOTE_ADDR']."'.");
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

    public function destroyAuthToken($token)
    {
        $authToken = $this->getEntityManager()->getRepository('AuthToken')->select(['id', 'isActive', 'secret'])->where(['token' => $token])->findOne();
        if ($authToken) {
            $authToken->set('isActive', false);
            $this->getEntityManager()->saveEntity($authToken);
            if ($authToken->get('secret')) {
                $sentSecret = $_COOKIE['auth-token-secret'] ?? null;
                if ($sentSecret === $authToken->get('secret')) {
                    setcookie('auth-token-secret', null, -1, '/');
                }
            }
            return true;
        }
    }

    protected function createAuthLogRecord($username, $user, $authenticationMethod = null)
    {
        if ($username === '**logout') return;

        $authLogRecord = $this->getEntityManager()->getEntity('AuthLogRecord');

        $authLogRecord->set([
            'username' => $username,
            'ipAddress' => $_SERVER['REMOTE_ADDR'],
            'requestTime' => $_SERVER['REQUEST_TIME_FLOAT'],
            'requestMethod' => $this->request->getMethod(),
            'requestUrl' => $this->request->getUrl() . $this->request->getPath(),
            'authenticationMethod' => $authenticationMethod
        ]);

        if ($this->isPortal()) {
            $authLogRecord->set('portalId', $this->getPortal()->id);
        }

        if ($user) {
            $authLogRecord->set('userId', $user->id);
        } else {
            $authLogRecord->set('isDenied', true);
            $authLogRecord->set('denialReason', 'CREDENTIALS');
            $this->getEntityManager()->saveEntity($authLogRecord);
        }

        return $authLogRecord;
    }

    protected function logDenied($authLogRecord, $denialReason)
    {
        if (!$authLogRecord) return;

        $authLogRecord->set('denialReason', $denialReason);
        $this->getEntityManager()->saveEntity($authLogRecord);
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
