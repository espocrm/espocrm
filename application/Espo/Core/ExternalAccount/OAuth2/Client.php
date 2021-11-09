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

namespace Espo\Core\ExternalAccount\OAuth2;

use Exception;

class Client
{
    const AUTH_TYPE_URI = 0;
    const AUTH_TYPE_AUTHORIZATION_BASIC = 1;
    const AUTH_TYPE_FORM = 2;

    const TOKEN_TYPE_URI = 'Uri';
    const TOKEN_TYPE_BEARER = 'Bearer';
    const TOKEN_TYPE_OAUTH = 'OAuth';

    const CONTENT_TYPE_APPLICATION_X_WWW_FORM_URLENENCODED = 'application/x-www-form-urlencoded';
    const CONTENT_TYPE_MULTIPART_FORM_DATA = 'multipart/form-data';
    const CONTENT_TYPE_APPLICATION_JSON = 'application/json';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';

    const HTTP_METHOD_DELETE = 'DELETE';
    const HTTP_METHOD_HEAD = 'HEAD';
    const HTTP_METHOD_PATCH = 'PATCH';

    const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';
    const GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';
    const GRANT_TYPE_PASSWORD = 'password';
    const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';

    protected $clientId = null;

    protected $clientSecret = null;

    protected $accessToken = null;

    protected $expiresAt = null;

    protected $authType = self::AUTH_TYPE_URI;

    protected $tokenType = self::TOKEN_TYPE_URI;

    protected $accessTokenSecret = null;

    protected $accessTokenParamName = 'access_token';

    protected $certificateFile = null;

    protected $curlOptions = [];

    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new Exception('CURL extension not found.');
        }
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function setAuthType($authType)
    {
        $this->authType = $authType;
    }

    public function setCertificateFile($certificateFile)
    {
        $this->certificateFile = $certificateFile;
    }

    public function setCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;
    }

    public function setCurlOptions($options)
    {
        $this->curlOptions = array_merge($this->curlOptions, $options);
    }

    public function setTokenType($tokenType)
    {
        $this->tokenType = $tokenType;
    }

    public function setExpiresAt($value)
    {
        $this->expiresAt = $value;
    }

    public function setAccessTokenSecret($accessTokenSecret)
    {
        $this->accessTokenSecret = $accessTokenSecret;
    }

    public function request($url, $params = null, $httpMethod = self::HTTP_METHOD_GET, array $httpHeaders = [])
    {
        if ($this->accessToken) {
            switch ($this->tokenType) {
                case self::TOKEN_TYPE_URI:
                    $params[$this->accessTokenParamName] = $this->accessToken;

                    break;

                case self::TOKEN_TYPE_BEARER:
                    $httpHeaders['Authorization'] = 'Bearer ' . $this->accessToken;

                    break;

                case self::TOKEN_TYPE_OAUTH:
                    $httpHeaders['Authorization'] = 'OAuth ' . $this->accessToken;

                    break;

                default:
                    throw new Exception('Unknown access token type.');
            }
        }

        return $this->execute($url, $params, $httpMethod, $httpHeaders);
    }

    private function execute($url, $params, $httpMethod, array $httpHeaders = [])
    {
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => $httpMethod,
        ];

        switch ($httpMethod) {
            case self::HTTP_METHOD_POST:
                $curlOptions[CURLOPT_POST] = true;

            case self::HTTP_METHOD_PUT:
            case self::HTTP_METHOD_PATCH:
                if (is_array($params)) {
                    $postFields = http_build_query($params, '', '&');
                } else {
                    $postFields = $params;
                }

                $curlOptions[CURLOPT_POSTFIELDS] = $postFields;

                break;

            case self::HTTP_METHOD_HEAD:
                $curlOptions[CURLOPT_NOBODY] = true;

            case self::HTTP_METHOD_DELETE:
            case self::HTTP_METHOD_GET:

                if (strpos($url, '?') === false) {
                    $url .= '?';
                }

                if (is_array($params)) {
                    $url .= http_build_query($params, '', '&');
                }

                break;

            default:
                break;
        }

        $curlOptions[CURLOPT_URL] = $url;

        $curlOptHttpHeader = [];

        foreach ($httpHeaders as $key => $value) {
            if (is_int($key)) {
                $curlOptHttpHeader[] = $value;

                continue;
            }

            $curlOptHttpHeader[] = "{$key}: {$value}";
        }

        $curlOptions[CURLOPT_HTTPHEADER] = $curlOptHttpHeader;

        $ch = curl_init();

        curl_setopt_array($ch, $curlOptions);

        curl_setopt($ch, CURLOPT_HEADER, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if (!empty($this->certificateFile)) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->certificateFile);
        }

        if (!empty($this->curlOptions)) {
            curl_setopt_array($ch, $this->curlOptions);
        }

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $responceHeader = substr($response, 0, $headerSize);
        $responceBody = substr($response, $headerSize);

        $resultArray = null;

        if ($curlError = curl_error($ch)) {
            throw new Exception($curlError);
        }
        else {
            $resultArray = json_decode($responceBody, true);
        }

        curl_close($ch);

        return [
            'result' => (null !== $resultArray) ? $resultArray : $responceBody,
            'code' => intval($httpCode),
            'contentType' => $contentType,
            'header' => $responceHeader,
        ];
    }

    public function getAccessToken($url, $grantType, array $params)
    {
        $params['grant_type'] = $grantType;

        $httpHeaders = [];

        switch ($this->authType) {
            case self::AUTH_TYPE_URI:
            case self::AUTH_TYPE_FORM:
                $params['client_id'] = $this->clientId;
                $params['client_secret'] = $this->clientSecret;

                break;

            case self::AUTH_TYPE_AUTHORIZATION_BASIC:
                $params['client_id'] = $this->clientId;

                $httpHeaders['Authorization'] = 'Basic ' . base64_encode($this->clientId .  ':' . $this->clientSecret);

                break;

            default:
                throw new Exception("Bad auth type.");
        }

        return $this->execute($url, $params, self::HTTP_METHOD_POST, $httpHeaders);
    }
}
