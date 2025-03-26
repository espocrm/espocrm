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

/**
 * Immutable.
 */
class MetadataParams
{
    private string $method;
    private ?string $credentialsHeader;
    private bool $api;

    public function __construct(
        string $method,
        ?string $credentialsHeader = null,
        bool $api = false
    ) {
        $this->method = $method;
        $this->credentialsHeader = $credentialsHeader;
        $this->api = $api;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromRaw(string $method, array $data): self
    {
        return new self(
            $method,
            $data['credentialsHeader'] ?? null,
            $data['api'] ?? false,
        );
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getCredentialsHeader(): ?string
    {
        return $this->credentialsHeader;
    }

    public function isApi(): bool
    {
        return $this->api;
    }
}
