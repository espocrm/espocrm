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

namespace Espo\Core\ExternalAccount\OAuth2;

use Exception;
use RuntimeException;
use LogicException;

class Client
{
    const AUTH_TYPE_URI = 0;
    const AUTH_TYPE_AUTHORIZATION_BASIC = 1;
    const AUTH_TYPE_FORM = 2;

    const TOKEN_TYPE_URI = 'Uri';
    const TOKEN_TYPE_BEARER = 'Bearer';
    const TOKEN_TYPE_OAUTH = 'OAuth';

    /**
     * @noinspection PhpUnused
     * @noinspection SpellCheckingInspection
     */
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
    /** @noinspection PhpUnused */
    const GRANT_TYPE_PASSWORD = 'password';
    /** @noinspection PhpUnused */
    const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';

    private const REFRESH_TOKEN_TIMEOUT = 10;
    private const DEFAULT_TIMEOUT = 3600 * 2;

    /** @var ?string */
    protected $clientId = null;
    /** @var ?string */
    protected $clientSecret = null;
    /** @var ?string */
    protected $accessToken = null;
    /** @var ?string */
    protected $expiresAt = null;
    /** @var int */
    protected $authType = self::AUTH_TYPE_URI;
    /** @var string */
    protected $tokenType = self::TOKEN_TYPE_URI;
    /** @var ?string */
    protected $accessTokenSecret = null;
    /** @var string */
    protected $accessTokenParamName = 'access_token';
    /** @var ?string */
    protected $certificateFile = null;
    /** @var array<string, mixed> */
    protected $curlOptions = [];

    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('CURL extension not found.');
        }
    }

    /**
     * @param string $clientId
     * @return void
     * @noinspection PhpUnused
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @param ?string $clientSecret
     * @return void
     * @noinspection PhpUnused
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @param ?string $accessToken
     * @return void
     * @noinspection PhpUnused
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @param int $authType
     * @return void
     * @noinspection PhpUnused
     */
    public function setAuthType($authType)
    {
        $this->authType = $authType;
    }

    /**
     * @param string $certificateFile
     * @return void
     * @noinspection PhpUnused
     */
    public function setCertificateFile($certificateFile)
    {
        $this->certificateFile = $certificateFile;
    }

    /**
     * @param string $option
     * @param mixed $value
     * @return void
     * @noinspection PhpUnused
     */
    public function setCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;
    }

    /**
     * @param array<string, mixed> $options
     * @return void
     * @noinspection PhpUnused
     */
    public function setCurlOptions($options)
    {
        $this->curlOptions = array_merge($this->curlOptions, $options);
    }

    /**
     * @param string $tokenType
     * @return void
     * @noinspection PhpUnused
     */
    public function setTokenType($tokenType)
    {
        $lower = strtolower($tokenType);

        if ($lower === 'bearer') {
            $tokenType = self::TOKEN_TYPE_BEARER;
        }

        if ($lower === 'uri') {
            $tokenType = self::TOKEN_TYPE_URI;
        }

        if ($lower === 'oauth') {
            $tokenType = self::TOKEN_TYPE_OAUTH;
        }

        $this->tokenType = $tokenType;
    }

    /**
     * @param ?string $value
     * @return void
     * @noinspection PhpUnused
     */
    public function setExpiresAt($value)
    {
        $this->expiresAt = $value;
    }

    /**
     * @param ?string $accessTokenSecret
     * @return void
     * @noinspection PhpUnused
     */
    public function setAccessTokenSecret($accessTokenSecret)
    {
        $this->accessTokenSecret = $accessTokenSecret;
    }

    /**
     * @param string $url
     * @param array<string, mixed>|string|null $params
     * @param string $httpMethod
     * @param array<string, string> $httpHeaders
     * @return array{
     *   result: array<string, mixed>|string,
     *   code: int,
     *   contentType: string|false,
     *   header: string,
     * }
     * @throws Exception
     */
    public function request(
        $url,
        $params = null,
        $httpMethod = self::HTTP_METHOD_GET,
        array $httpHeaders = []
    ) {

        if ($this->accessToken) {
            switch ($this->tokenType) {
                case self::TOKEN_TYPE_URI:
                    if (is_string($params) || $params === null) {
                        $params = [];
                    }

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

    /**
     * @param string $url
     * @param array<string, mixed>|string|null $params
     * @param string $httpMethod
     * @param array<string, string> $httpHeaders
     * @return array{
     *   result: array<string, mixed>|string,
     *   code: int,
     *   contentType: string|false,
     *   header: string,
     * }
     * @throws Exception
     */
    private function execute(
        $url,
        $params,
        $httpMethod,
        array $httpHeaders = [],
        ?int $timeout = null
    ) {

        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST => $httpMethod,
            CURLOPT_TIMEOUT => $timeout ?: self::DEFAULT_TIMEOUT,
        ];

        switch ($httpMethod) {
            /** @noinspection PhpMissingBreakStatementInspection */
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

            /** @noinspection PhpMissingBreakStatementInspection */
            case self::HTTP_METHOD_HEAD:
                $curlOptions[CURLOPT_NOBODY] = true;

            case self::HTTP_METHOD_DELETE:
            case self::HTTP_METHOD_GET:

                if (!str_contains($url, '?')) {
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

            $curlOptHttpHeader[] = "$key: $value";
        }

        $curlOptions[CURLOPT_HTTPHEADER] = $curlOptHttpHeader;

        $ch = curl_init();

        curl_setopt_array($ch, $curlOptions);

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        if (!empty($this->certificateFile)) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->certificateFile);
        }

        if (!empty($this->curlOptions)) {
            curl_setopt_array($ch, $this->curlOptions);
        }

        /** @var string|false $response */
        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception("Curl failure.");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $responseHeader = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        if ($curlError = curl_error($ch)) {
            throw new Exception($curlError);
        }

        $resultArray = json_decode($responseBody, true);

        curl_close($ch);

        /** @var array<string, mixed>|string $result */
        $result = ($resultArray !== null) ?
            $resultArray :
            $responseBody;

        return [
            'result' => $result,
            'code' => intval($httpCode),
            'contentType' => $contentType,
            'header' => $responseHeader,
        ];
    }

    /**
     * @param string $url
     * @param string $grantType
     * @param array{
     *     client_id?: string,
     *     client_secret?: string,
     *     redirect_uri?: string,
     *     code?: string,
     *     refresh_token?: string,
     * } $params
     * @return array{
     *   result: array<string, mixed>|string,
     *   code: int,
     *   contentType: string|false,
     *   header: string,
     * }
     * @throws Exception
     */
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
                throw new LogicException("Bad auth type.");
        }

        return $this->execute($url, $params, self::HTTP_METHOD_POST, $httpHeaders, self::REFRESH_TOKEN_TIMEOUT);
    }
}
