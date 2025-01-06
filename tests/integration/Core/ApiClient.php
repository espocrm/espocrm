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

namespace tests\integration\Core;

class ApiClient
{
    private $url;
    private $userName;
    private $password;
    private $portalId;
    protected $apiPath = '/api/v1/';
    protected $portalApiPath = '/api/v1/portal-access/{PORTAL_ID}/';
    protected $userAgent =
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.82 Safari/537.36';
    private $lastCh;

    public function __construct($url = null, $userName = null, $password = null, $portalId = null)
    {
        if (isset($url)) {
            $this->url = $url;
        }

        if (isset($userName)) {
            $this->userName = $userName;
        }

        if (isset($password)) {
            $this->password = $password;
        }

        if (isset($portalId)) {
            $this->portalId = $portalId;
        }
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setPortalId($portalId)
    {
        $this->portalId = $portalId;
    }

    /**
     * Send request to EspoCRM.
     *
     * @param string $method
     * @param string $action
     * @param array|string|null $jsonData
     *
     * @return array|\Exception
     */
    public function request($method, $action, $jsonData = null)
    {
        $this->checkParams();

        $this->lastCh = null;

        if (is_array($jsonData)) {
            $jsonData = json_encode($jsonData);
        }

        $url = $this->normalizeUrl($action);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->userName.':'.$this->password);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if (isset($jsonData)) {
            if ($method == 'GET') {
                $data = json_decode($jsonData, true);
                curl_setopt($ch, CURLOPT_URL, $url. '?' . http_build_query($data));
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData),
                ]);
            }
        }

        $lastResponse = curl_exec($ch);
        $this->lastCh = $ch;

        $parsedResponse = $this->parseResponse($lastResponse);

        if ($this->getResponseHttpCode() == 200 && !empty($parsedResponse['body'])) {
            curl_close($ch);
            return json_decode($parsedResponse['body'], true);
        }

        $header = $this->normalizeHeader($parsedResponse['header']);
        $errorCode = $this->getResponseHttpCode();
        $errorMessage = !empty($header['X-Status-Reason']) ? $header['X-Status-Reason'] : $errorCode;

        curl_close($ch);
        throw new \Exception($errorMessage, $errorCode);
    }

    public function getResponseContentType()
    {
        return $this->getInfo(CURLINFO_CONTENT_TYPE);
    }

    public function getResponseTotalTime()
    {
        return $this->getInfo(CURLINFO_TOTAL_TIME);
    }

    public function getResponseHttpCode()
    {
        return $this->getInfo(CURLINFO_HTTP_CODE);
    }

    protected function normalizeUrl($action)
    {
        $apiPath = $this->portalId ? str_replace('{PORTAL_ID}', $this->portalId, $this->portalApiPath) : $this->apiPath;

        return $this->url . $apiPath . $action;
    }

    protected function checkParams()
    {
        $paramList = [
            'url',
            'userName',
            'password',
        ];

        foreach ($paramList as $name) {
            if (empty($this->$name)) {
                throw new \Exception('EspoClient: Parameter "'.$name.'" is not defined.');
            }
        }

        return true;
    }

    protected function getInfo($option)
    {
        if (isset($this->lastCh)) {
            return curl_getinfo($this->lastCh, $option);
        }

        return null;
    }

    /**
     * Parse response to get header and body.
     *
     * @param string $response
     * @return array
     */
    protected function parseResponse($response)
    {
        $headerSize = $this->getInfo(CURLINFO_HEADER_SIZE);

        return [
            'header' => trim( substr($response, 0, $headerSize) ),
            'body' => substr($response, $headerSize),
        ];
    }

    /**
     * Convert header string to array.
     *
     * @param string $header
     * @return array
     */
    protected function normalizeHeader($header)
    {
        preg_match_all('/(.*): (.*)\r\n/', $header, $matches);

        $headerArray = [];

        foreach ($matches[1] as $index => $name) {
            if (isset($matches[2][$index])) {
                $headerArray[$name] = trim($matches[2][$index]);
            }
        }

        return $headerArray;
    }
}
