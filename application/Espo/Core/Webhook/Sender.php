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

namespace Espo\Core\Webhook;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\Entities\Webhook;

/**
 * Sends a portion.
 */
class Sender
{
    private const CONNECT_TIMEOUT = 5;
    private const TIMEOUT = 10;

    public function __construct(private Config $config)
    {}

    /**
     * @param array<int, mixed> $dataList
     * @throws Error
     */
    public function send(Webhook $webhook, array $dataList): int
    {
        $payload = Json::encode($dataList);

        $signature = null;
        $legacySignature = null;

        $secretKey = $webhook->getSecretKey();

        if ($secretKey) {
            $signature = $this->buildSignature($webhook, $payload, $secretKey);
            $legacySignature = $this->buildSignatureLegacy($webhook, $payload, $secretKey);
        }

        $connectTimeout = $this->config->get('webhookConnectTimeout', self::CONNECT_TIMEOUT);
        $timeout = $this->config->get('webhookTimeout', self::TIMEOUT);

        $headerList = [];

        $headerList[] = 'Content-Type: application/json';
        $headerList[] = 'Content-Length: ' . strlen($payload);

        if ($signature) {
            $headerList[] = 'Signature: ' . $signature;
        }

        if ($legacySignature) {
            $headerList[] = 'X-Signature: ' . $legacySignature;
        }

        $url = $webhook->getUrl();

        if (!$url) {
            throw new Error("Webhook does not have URL.");
        }

        $handler = curl_init($url);

        if ($handler === false) {
            throw new Error("Could not init CURL for URL {$url}.");
        }

        curl_setopt($handler, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, \CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handler, \CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handler, \CURLOPT_HEADER, true);
        curl_setopt($handler, \CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($handler, \CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($handler, \CURLOPT_TIMEOUT, $timeout);
        curl_setopt($handler, \CURLOPT_PROTOCOLS, \CURLPROTO_HTTPS | \CURLPROTO_HTTP);
        curl_setopt($handler, \CURLOPT_REDIR_PROTOCOLS, \CURLPROTO_HTTPS);
        curl_setopt($handler, \CURLOPT_HTTPHEADER, $headerList);
        curl_setopt($handler, \CURLOPT_POSTFIELDS, $payload);

        curl_exec($handler);

        $code = curl_getinfo($handler, \CURLINFO_HTTP_CODE);

        if (!is_numeric($code)) {
            $code = 0;
        }

        if (!is_int($code)) {
            $code = intval($code);
        }

        $errorNumber = curl_errno($handler);

        if (
            $errorNumber &&
            in_array($errorNumber, [\CURLE_OPERATION_TIMEDOUT, \CURLE_OPERATION_TIMEOUTED])
        ) {
            $code = 408;
        }

        curl_close($handler);

        return $code;
    }

    private function buildSignature(Webhook $webhook, string $payload, string $secretKey): string
    {
        $webhookId = $webhook->getId();
        $hash = hash_hmac('sha256', $payload, $secretKey);

        return base64_encode("$webhookId:$hash");
    }

    /**
     * @todo Remove in v11.0.
     */
    private function buildSignatureLegacy(Webhook $webhook, string $payload, string $secretKey): string
    {
        return base64_encode($webhook->getId() . ':' . hash_hmac('sha256', $payload, $secretKey, true));
    }
}
