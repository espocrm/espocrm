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

use Espo\Core\Authentication\Jwt\Exceptions\UnsupportedKey;
use Espo\Core\Authentication\Jwt\Key;
use Espo\Core\Authentication\Jwt\KeyFactory;
use Espo\Core\Field\DateTime;
use Espo\Core\Utils\Config\SystemConfig;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Log;
use JsonException;
use RuntimeException;
use stdClass;

class KeysProvider
{
    private const CACHE_KEY = 'oidcJwks';
    private const REQUEST_TIMEOUT = 10;

    public function __construct(
        private DataCache $dataCache,
        private ConfigDataProvider $configDataProvider,
        private KeyFactory $factory,
        private Log $log,
        private SystemConfig $systemConfig,
    ) {}

    /**
     * @return Key[]
     */
    public function get(): array
    {
        $list = [];

        $rawKeys = $this->getRaw();

        foreach ($rawKeys as $raw) {
            try {
                $list[] = $this->factory->create($raw);
            } catch (UnsupportedKey) {
                $this->log->debug("OIDC: Unsupported key " . print_r($raw, true));
            }
        }

        return $list;
    }

    /**
     * @return stdClass[]
     */
    private function getRaw(): array
    {
        $raw = $this->getRawFromCache();

        if (!$raw) {
            $raw = $this->load();

            $this->storeRawToCache($raw);
        }

        return $raw;
    }

    /**
     * @return stdClass[]
     */
    private function load(): array
    {
        $endpoint = $this->configDataProvider->getJwksEndpoint();

        if (!$endpoint) {
            throw new RuntimeException("JSON Web Key Set endpoint not specified in settings.");
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
        ]);

        /** @var string|false $response */
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($response === false) {
            $response = '';
        }

        if ($error) {
            throw new RuntimeException("OIDC: JWKS request error. Status: $status.");
        }

        $parsedResponse = null;

        try {
            $parsedResponse = Json::decode($response);
        } catch (JsonException) {}

        if (!$parsedResponse instanceof stdClass || !isset($parsedResponse->keys)) {
            throw new RuntimeException("OIDC: JWKS bad response.");
        }

        return $parsedResponse->keys;
    }

    /**
     * @return ?stdClass[]
     */
    private function getRawFromCache(): ?array
    {
        if (!$this->systemConfig->useCache()) {
            return null;
        }

        if (!$this->dataCache->has(self::CACHE_KEY)) {
            return null;
        }

        $data = $this->dataCache->get(self::CACHE_KEY);

        if (!$data instanceof stdClass) {
            return null;
        }

        /** @var ?int $timestamp */
        $timestamp = $data->timestamp;

        if (!$timestamp) {
            return null;
        }

        $period = '-' . $this->configDataProvider->getJwksCachePeriod();

        if ($timestamp < DateTime::createNow()->modify($period)->toTimestamp()) {
            return null;
        }

        /** @var ?stdClass[] $keys */
        $keys = $data->keys ?? null;

        if ($keys === null) {
            return null;
        }

        return $keys;
    }

    /**
     * @param stdClass[] $raw
     */
    private function storeRawToCache(array $raw): void
    {
        if (!$this->systemConfig->useCache()) {
            return;
        }

        $data = (object) [
            'timestamp' => time(),
            'keys' => $raw,
        ];

        $this->dataCache->store(self::CACHE_KEY, $data);
    }
}
