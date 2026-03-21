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

namespace Espo\Core\Utils\Security;

use const FILTER_VALIDATE_URL;
use const PHP_URL_HOST;

class UrlCheck
{
    public function __construct(
        private HostCheck $hostCheck,
    ) {}

    public function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Checks whether it's a URL, and it does not follow to an internal host.
     *
     * @since 9.3.4
     */
    public function isUrlAndNotIternal(string $url): bool
    {
        if (!$this->isUrl($url)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host)) {
            return false;
        }

        return $this->hostCheck->isHostAndNotInternal($host);
    }

    /**
     * @deprecated Since 9.3.4. Use `isUrlAndNotIternal`.
     * @todo Remove in 9.5.0.
     */
    public function isNotInternalUrl(string $url): bool
    {
        return $this->isUrlAndNotIternal($url);
    }
}
