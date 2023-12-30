<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Utils\Client\ActionRenderer;

/**
 * @immutable
 */
class Params
{
    /** @var ?array<string, mixed> */
    private ?array $data;
    private bool $initAuth = false;

    /**
     * @param ?array<string, mixed> $data
     */
    public function __construct(
        private string $controller,
        private string $action,
        ?array $data = null
    ) {
        $this->data = $data;
    }

    /**
     * @param ?array<string, mixed> $data
     */
    public static function create(string $controller, string $action, ?array $data = null): self
    {
        return new self($controller, $action, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function withData(array $data): self
    {
        $obj = clone $this;
        $obj->data = $data;

        return $obj;
    }

    public function withInitAuth(bool $initAuth = true): self
    {
        $obj = clone $this;
        $obj->initAuth = $initAuth;

        return $obj;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return ?array<string, mixed>
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    public function initAuth(): bool
    {
        return $this->initAuth;
    }
}
