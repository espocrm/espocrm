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

namespace Espo\Core\Authentication\Oidc;

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Log;
use JsonException;
use RuntimeException;
use SensitiveParameter;

class UserInfoDataProvider
{
    private const REQUEST_TIMEOUT = 10;

    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private Log $log,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(#[SensitiveParameter] string $accessToken): array
    {
        return $this->load($accessToken);
    }

    /**
     * @return array<string, mixed>
     */
    private function load(#[SensitiveParameter] string $accessToken): array
    {
        $endpoint = $this->configDataProvider->getUserInfoEndpoint();

        if (!$endpoint) {
            throw new RuntimeException("No userinfo endpoint.");
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
        ]);

        /** @var string|false $response */
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($response === false) {
            $response = '';
        }

        if ($error || is_int($status) && ($status >= 400 && $status < 500)) {
            $this->log->error(self::composeLogMessage('UserInfo response error.', $status, $response));

            throw new RuntimeException("OIDC: Userinfo request error.");
        }

        $parsedResponse = null;

        try {
            $parsedResponse = Json::decode($response, true);
        } catch (JsonException) {}

        if (!is_array($parsedResponse)) {
            throw new RuntimeException("OIDC: Bad userinfo response.");
        }

        return $parsedResponse;
    }

    private static function composeLogMessage(string $text, ?int $status = null, ?string $response = null): string
    {
        if ($status === null) {
            return "OIDC: $text";
        }

        return "OIDC: $text; Status: $status; Response: $response";
    }
}
