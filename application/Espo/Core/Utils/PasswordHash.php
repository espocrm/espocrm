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

namespace Espo\Core\Utils;

use Espo\Core\Exceptions\Error;

class PasswordHash
{
    private $config;

    /**
     * SHA-512 salt format.
     */
    private $saltFormat = '$6${0}$';

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Hash a password.
     */
    public function hash(string $password, bool $useMd5 = true): string
    {
        $salt = $this->getSalt();

        if ($useMd5) {
            $password = md5($password);
        }

        $hash = crypt($password, $salt);

        return str_replace($salt, '', $hash);
    }

    /**
     * Get a salt from the config and normalize it.
     */
    protected function getSalt(): string
    {
        $salt = $this->config->get('passwordSalt');

        if (!isset($salt)) {
            throw new Error('Option "passwordSalt" does not exist in config.php');
        }

        return $this->normalizeSalt($salt);
    }

    /**
     * Convert salt in format in accordance to $saltFormat.
     */
    protected function normalizeSalt(string $salt): string
    {
        return str_replace("{0}", $salt, $this->saltFormat);
    }

    /**
     * Generate a new salt.
     */
    public function generateSalt(): string
    {
        return substr(md5(uniqid()), 0, 16);
    }
}
