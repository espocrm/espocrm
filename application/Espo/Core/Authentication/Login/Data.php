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

namespace Espo\Core\Authentication\Login;

use Espo\Core\Authentication\AuthToken\AuthToken;
use SensitiveParameter;

/**
 * Login data to be passed to the 'login' method.
 */
class Data
{
    private ?string $username;
    private ?string $password;
    private ?AuthToken $authToken;

    public function __construct(
        ?string $username,
        #[SensitiveParameter] ?string $password,
        ?AuthToken $authToken = null
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->authToken = $authToken;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getAuthToken(): ?AuthToken
    {
        return $this->authToken;
    }

    public function hasUsername(): bool
    {
        return !is_null($this->username);
    }

    public function hasPassword(): bool
    {
        return !is_null($this->password);
    }

    public function hasAuthToken(): bool
    {
        return !is_null($this->authToken);
    }

    public static function createBuilder(): DataBuilder
    {
        return new DataBuilder();
    }
}
