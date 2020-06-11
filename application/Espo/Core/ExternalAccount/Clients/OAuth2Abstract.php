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

namespace Espo\Core\ExternalAccount\Clients;

use Espo\Core\Exceptions\Error;

use Espo\Core\ExternalAccount\OAuth2\Client;

abstract class OAuth2Abstract implements IClient
{
    protected $client = null;

    protected $manager = null;

    protected $paramList = [
        'endpoint',
        'tokenEndpoint',
        'clientId',
        'clientSecret',
        'tokenType',
        'accessToken',
        'refreshToken',
        'redirectUri',
        'expiresAt',
    ];

    protected $clientId = null;

    protected $clientSecret = null;

    protected $accessToken = null;

    protected $refreshToken = null;

    protected $redirectUri = null;

    protected $expiresAt = null;

    const ACCESS_TOKEN_EXPIRATION_MARGIN = '20 seconds';

    const LOCK_TIMEOUT = 5;

    const LOCK_CHECK_STEP = 0.5;

    public function __construct($client, array $params = [], $manager = null)
    {
        $this->client = $client;

        $this->setParams($params);

        $this->manager = $manager;
    }

    public function getParam($name)
    {
        if (in_array($name, $this->paramList)) {
            return $this->$name;
        }
    }

    public function setParam($name, $value)
    {
        if (in_array($name, $this->paramList)) {
            $methodName = 'set' . ucfirst($name);
            if (method_exists($this->client, $methodName)) {
                $this->client->$methodName($value);
            }
            $this->$name = $value;
        }
    }

    public function setParams(array $params)
    {
        foreach ($this->paramList as $name) {
            if (array_key_exists($name, $params)) {
                $this->setParam($name, $params[$name]);
            }
        }
    }

    protected function afterTokenRefreshed($data)
    {
        if ($this->manager) {
            $this->manager->storeAccessToken(spl_object_hash($this), $data);
        }
    }

    protected function getAccessTokenDataFromResponseResult($result)
    {
        $data = [];

        $data['accessToken'] = $result['access_token'];
        $data['tokenType'] = $result['token_type'];

        $data['expiresAt'] = null;

        if (isset($result['refresh_token']) && $result['refresh_token'] !== $this->refreshToken) {
            $data['refreshToken'] = $result['refresh_token'];
        }

        if (isset($result['expires_in']) && is_numeric($result['expires_in'])) {
            $data['expiresAt'] = (new \DateTime())
                ->modify('+' . $result['expires_in'] . ' seconds')
                ->format('Y-m-d H:i:s');
        }

        return $data;
    }

    public function getAccessTokenFromAuthorizationCode($code)
    {
        $r = $this->client->getAccessToken($this->getParam('tokenEndpoint'), Client::GRANT_TYPE_AUTHORIZATION_CODE, [
            'code' => $code,
            'redirect_uri' => $this->getParam('redirectUri'),
        ]);

        if ($r['code'] == 200) {
            if (!empty($r['result'])) {
                $data = $this->getAccessTokenDataFromResponseResult($r['result']);

                $data['refreshToken'] = $r['result']['refresh_token'];

                return $data;
            } else {
                $GLOBALS['log']->debug("OAuth getAccessTokenFromAuthorizationCode; Response: " . json_encode($r));
                return null;
            }
        } else {
            $GLOBALS['log']->debug("OAuth getAccessTokenFromAuthorizationCode; Response: " . json_encode($r));
        }
        return null;
    }

    abstract protected function getPingUrl();

    public function ping()
    {
        if (empty($this->accessToken) || empty($this->clientId) || empty($this->clientSecret)) {
            return false;
        }

        $url = $this->getPingUrl();

        try {
            $this->request($url);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function handleAccessTokenActuality()
    {
        if ($this->getParam('expiresAt')) {
            try {
                $dt = new \DateTime($this->getParam('expiresAt'));
                $dt->modify('-' . $this::ACCESS_TOKEN_EXPIRATION_MARGIN);
            } catch (\Exception $e) {
                return;
            }

            if ($dt->format('U') <= (new \DateTime())->format('U')) {
                $GLOBALS['log']->debug("Oauth: Refreshing expired token for client {$this->clientId}.");

                $until = microtime(true) + $this::LOCK_TIMEOUT;

                if ($this->isLocked()) {
                    while (true) {
                        usleep($this::LOCK_CHECK_STEP * 1000000);

                        if (!$this->isLocked()) {
                            $GLOBALS['log']->debug("Oauth: Waited until unlocked for client {$this->clientId}.");
                            $this->reFetch();
                            return;
                        }

                        if (microtime(true) > $until) {
                            $GLOBALS['log']->debug("Oauth: Waited until unlocked but timed out for client {$this->clientId}.");
                            $this->unlock();
                            break;
                        }
                    }
                }

                $this->refreshToken();
            }
        }
    }

    protected function isLocked()
    {
        return $this->manager->isClientLocked($this);
    }

    protected function lock()
    {
        $this->manager->lockClient($this);
    }

    protected function unlock()
    {
        $this->manager->unlockClient($this);
    }

    protected function reFetch()
    {
        $this->manager->reFetchClient($this);
    }

    public function request($url, $params = null, $httpMethod = Client::HTTP_METHOD_GET, $contentType = null, $allowRenew = true)
    {
        $this->handleAccessTokenActuality();

        $httpHeaders = [];
        if (!empty($contentType)) {
            $httpHeaders['Content-Type'] = $contentType;
            switch ($contentType) {
                case Client::CONTENT_TYPE_MULTIPART_FORM_DATA:
                    $httpHeaders['Content-Length'] = strlen($params);
                    break;
                case Client::CONTENT_TYPE_APPLICATION_JSON:
                    $httpHeaders['Content-Length'] = strlen($params);
                    break;
            }
        }

        $r = $this->client->request($url, $params, $httpMethod, $httpHeaders);

        $code = null;
        if (!empty($r['code'])) {
            $code = $r['code'];
        }

        if ($code >= 200 && $code < 300) {
            return $r['result'];
        } else {
            $handledData = $this->handleErrorResponse($r);

            if ($allowRenew && is_array($handledData)) {
                if ($handledData['action'] == 'refreshToken') {
                    if ($this->refreshToken()) {
                        return $this->request($url, $params, $httpMethod, $contentType, false);
                    }
                } else if ($handledData['action'] == 'renew') {
                    return $this->request($url, $params, $httpMethod, $contentType, false);
                }
            }
        }

        $reasonPart = '';
        if (isset($r['result']['error']) && isset($r['result']['error']['message'])) {
            $reasonPart = '; Reason: ' . $r['result']['error']['message'];
        }

        throw new Error("Oauth: Error after requesting {$httpMethod} {$url}{$reasonPart}.", $code);
    }

    protected function refreshToken()
    {
        if (empty($this->refreshToken)) {
            throw new Error(
                "Oauth: Could not refresh token for client {$this->clientId}, because refreshToken is empty."
            );
        }

        $this->lock();

        try {
            $r = $this->client->getAccessToken($this->getParam('tokenEndpoint'), Client::GRANT_TYPE_REFRESH_TOKEN, [
                'refresh_token' => $this->refreshToken,
            ]);
        } catch (\Exception $e) {
            $this->unlock();
            throw new Error("Oauth: Error while refreshing token: " . $e->getMessage());
        }

        if ($r['code'] == 200) {
            if (is_array($r['result'])) {
                if (!empty($r['result']['access_token'])) {
                    $data = $this->getAccessTokenDataFromResponseResult($r['result']);

                    $this->setParams($data);
                    $this->afterTokenRefreshed($data);

                    $this->unlock();

                    return true;
                }
            }
        }

        $this->unlock();

        $GLOBALS['log']->error("Oauth: Refreshing token failed for client {$this->clientId}: " . json_encode($r));

        return false;
    }

    protected function handleErrorResponse($r)
    {
        if ($r['code'] == 401 && !empty($r['result'])) {
            $result = $r['result'];
            if (strpos($r['header'], 'error=invalid_token') !== false) {
                return [
                    'action' => 'refreshToken'
                ];
            } else {
                return [
                    'action' => 'renew'
                ];
            }
        } else if ($r['code'] == 400 && !empty($r['result'])) {
            if ($r['result']['error'] == 'invalid_token') {
                return [
                    'action' => 'refreshToken'
                ];
            }
        }
    }
}
