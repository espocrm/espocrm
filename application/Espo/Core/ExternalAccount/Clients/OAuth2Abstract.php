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

namespace Espo\Core\ExternalAccount\Clients;

use \Espo\Core\Exceptions\Error;

use \Espo\Core\ExternalAccount\OAuth2\Client;

abstract class OAuth2Abstract implements IClient
{
    protected $client = null;

    protected $manager = null;

    protected $paramList = array(
        'endpoint',
        'tokenEndpoint',
        'clientId',
        'clientSecret',
        'tokenType',
        'accessToken',
        'refreshToken',
        'redirectUri',
    );

    protected $clientId = null;

    protected $clientSecret = null;

    protected $accessToken = null;

    protected $refreshToken = null;

    protected $redirectUri = null;

    public function __construct($client, array $params = array(), $manager = null)
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
            if (!empty($params[$name])) {
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

    public function getAccessTokenFromAuthorizationCode($code)
    {
        $r = $this->client->getAccessToken($this->getParam('tokenEndpoint'), Client::GRANT_TYPE_AUTHORIZATION_CODE, array(
            'code' => $code,
            'redirect_uri' => $this->getParam('redirectUri')
        ));

        if ($r['code'] == 200) {
            $data = array();
            if (!empty($r['result'])) {
                $data['accessToken'] = $r['result']['access_token'];
                $data['tokenType'] = $r['result']['token_type'];
                $data['refreshToken'] = $r['result']['refresh_token'];
            }
            return $data;
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

    public function request($url, $params = null, $httpMethod = Client::HTTP_METHOD_GET, $contentType = null, $allowRenew = true)
    {
        $httpHeaders = array();
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
        if (!empty($this->refreshToken)) {
            $r = $this->client->getAccessToken($this->getParam('tokenEndpoint'), Client::GRANT_TYPE_REFRESH_TOKEN, array(
                'refresh_token' => $this->refreshToken,
            ));
            if ($r['code'] == 200) {
                if (is_array($r['result'])) {
                    if (!empty($r['result']['access_token'])) {
                        $data = array();
                        $data['accessToken'] = $r['result']['access_token'];
                        $data['tokenType'] = $r['result']['token_type'];

                        $this->setParams($data);
                        $this->afterTokenRefreshed($data);
                        return true;
                    }
                }
            }
        }
    }

    protected function handleErrorResponse($r)
    {
        if ($r['code'] == 401 && !empty($r['result'])) {
            $result = $r['result'];
            if (strpos($r['header'], 'error=invalid_token') !== false) {
                return array(
                    'action' => 'refreshToken'
                );
            } else {
                return array(
                    'action' => 'renew'
                );
            }
        } else if ($r['code'] == 400 && !empty($r['result'])) {
            if ($r['result']['error'] == 'invalid_token') {
                return array(
                    'action' => 'refreshToken'
                );
            }
        }
    }
}

