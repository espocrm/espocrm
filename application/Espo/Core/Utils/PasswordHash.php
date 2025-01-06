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
use SensitiveParameter;

use const PASSWORD_BCRYPT;

class PasswordHash
{
    /**
     * Legacy.
     */
    private string $saltFormat = '$6${0}$';

    public function __construct(private Config $config)
    {}

    /**
     * Hash a password.
     */
    public function hash(#[SensitiveParameter] string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify a password against a hash.
     */
    public function verify(
        #[SensitiveParameter] string $password,
        #[SensitiveParameter] string $hash
    ): bool {

        if (password_verify($password, $hash)) {
            return true;
        }

        return $this->legacyVerify($password, $hash);
    }

    private function legacyVerify(
        #[SensitiveParameter] string $password,
        #[SensitiveParameter] string $hash
    ): bool {

        if (!$this->config->get('passwordSalt')) {
            return false;
        }

        return $this->legacyHash($password) === $hash;
    }

    private function legacyHash(#[SensitiveParameter] string $password): string
    {
        $salt = $this->getSalt();

        $hash = crypt(md5($password), $salt);

        return str_replace($salt, '', $hash);
    }

    private function getSalt(): string
    {
        $salt = $this->config->get('passwordSalt');

        if (!isset($salt)) {
            throw new RuntimeException('Option "passwordSalt" does not exist in config.php');
        }

        return $this->normalizeSalt($salt);
    }

    private function normalizeSalt(string $salt): string
    {
        return str_replace("{0}", $salt, $this->saltFormat);
    }
}
