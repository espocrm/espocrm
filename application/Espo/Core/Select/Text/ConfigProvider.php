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

namespace Espo\Core\Select\Text;

use Espo\Core\Utils\Config;

class ConfigProvider
{
    private const MIN_LENGTH_FOR_CONTENT_SEARCH = 4;

    public function __construct(private Config $config)
    {}

    /**
     * Full-text search min indexed word length.
     *
     * @internal Do not use.
     * @since 8.3.0
     */
    public function getFullTextSearchMinLength(): ?int
    {
        return $this->config->get('fullTextSearchMinLength');
    }

    public function getMinLengthForContentSearch(): int
    {
        return $this->config->get('textFilterContainsMinLength') ??
            self::MIN_LENGTH_FOR_CONTENT_SEARCH;
    }

    public function useContainsForVarchar(): bool
    {
        return $this->config->get('textFilterUseContainsForVarchar') ?? false;
    }

    public function usePhoneNumberNumericSearch(): bool
    {
        return $this->config->get('phoneNumberNumericSearch') ?? false;
    }
}
