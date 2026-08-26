<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Authentication\AuthToken;

/**
 * Fetches and stores auth tokens.
 *
 * @template TAuthToken of AuthToken = AuthToken
 */
interface Manager
{
    /**
     * Get an auth token. If it does not exist, then returns NULL.
     *
     * @return ?TAuthToken
     */
    public function get(string $token): ?AuthToken;

    /**
     * Create an auth token and store it.
     *
     * @return TAuthToken
     */
    public function create(Data $data): AuthToken;

    /**
     * Make an auth token inactive (invalid).
     *
     * @param TAuthToken $authToken
     */
    public function inactivate(AuthToken $authToken): void;

    /**
     * Update a last access date. An implementation can be omitted to avoid a writing operation.
     *
     * @param TAuthToken $authToken
     */
    public function renew(AuthToken $authToken): void;

    /**
     * Inactivate concurrent auth tokens for the same user.
     *
     * @param TAuthToken $authToken
     * @since 10.1.0
     */
    public function inactiveOther(AuthToken $authToken): void;
}
