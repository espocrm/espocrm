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

namespace Espo\Core\Utils;

use RuntimeException;

class Crypt
{
    private string $cryptKey;
    private ?string $key = null;
    private ?string $iv = null;

    public function __construct(Config $config)
    {
        $this->cryptKey = $config->get('cryptKey', '');
    }

    private function getKey(): string
    {
        if ($this->key === null) {
            $this->key = hash('sha256', $this->cryptKey, true);
        }

        if (!$this->key) {
            throw new RuntimeException("Could not hash the key.");
        }

        return $this->key;
    }

    private function getIv(): string
    {
        if ($this->iv === null) {
            if (!extension_loaded('openssl')) {
                throw new RuntimeException("openssl extension is not loaded.");
            }

            $iv = openssl_random_pseudo_bytes(16);

            $this->iv = $iv;
        }

        return $this->iv;
    }

    public function encrypt(string $string): string
    {
        $iv = $this->getIv();

        if (!extension_loaded('openssl')) {
            throw new RuntimeException("openssl extension is not loaded.");
        }

        return base64_encode(
            openssl_encrypt($string, 'aes-256-cbc', $this->getKey(), OPENSSL_RAW_DATA, $iv) . $iv
        );
    }

    public function decrypt(string $encryptedString): string
    {
        $encryptedStringDecoded = base64_decode($encryptedString);
        $string = substr($encryptedStringDecoded, 0, strlen($encryptedStringDecoded) - 16);
        $iv = substr($encryptedStringDecoded, -16);

        if (!extension_loaded('openssl')) {
            throw new RuntimeException("openssl extension is not loaded.");
        }

        $value = openssl_decrypt($string, 'aes-256-cbc', $this->getKey(), OPENSSL_RAW_DATA, $iv);

        if ($value === false) {
            throw new RuntimeException("OpenSSL decrypt failure.");
        }

        return trim($value);
    }
}
