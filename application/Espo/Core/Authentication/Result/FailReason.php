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

namespace Espo\Core\Authentication\Result;

class FailReason
{
    public const DENIED = 'Denied';
    public const CODE_NOT_VERIFIED = 'Code not verified';
    public const NO_USERNAME = 'No username';
    public const NO_PASSWORD = 'No password';
    public const TOKEN_NOT_FOUND = 'Token not found';
    public const USER_NOT_FOUND = 'User not found';
    public const WRONG_CREDENTIALS = 'Wrong credentials';
    public const USER_TOKEN_MISMATCH = 'User and token mismatch';
    public const HASH_NOT_MATCHED = 'Hash not matched';
    public const METHOD_NOT_ALLOWED = 'Not allowed authentication method';
    public const DISCREPANT_DATA = 'Discrepant authentication data';
    public const ANOTHER_USER_NOT_FOUND = 'Another user not found';
    public const ANOTHER_USER_NOT_ALLOWED = 'Another user not allowed';
    public const ERROR = 'Error';
}
