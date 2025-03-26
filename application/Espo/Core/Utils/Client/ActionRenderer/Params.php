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

namespace Espo\Core\Utils\Client\ActionRenderer;

use Espo\Core\Utils\Client\Script;

/**
 * Immutable.
 */
class Params
{
    /** @var ?array<string, mixed> */
    private ?array $data;
    private bool $initAuth = false;
    /** @var string[] */
    private array $frameAncestors = [];
    /** @var Script[] */
    private array $scripts = [];
    private ?string $pageTitle = null;
    private ?string $theme = null;

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

    /**
     * @param string[] $frameAncestors
     * @since 9.0.0
     */
    public function withFrameAncestors(array $frameAncestors): self
    {
        $obj = clone $this;
        $obj->frameAncestors = $frameAncestors;

        return $obj;
    }

    /**
     * @param Script[] $scripts
     * @since 9.0.0
     */
    public function withScripts(array $scripts): self
    {
        $obj = clone $this;
        $obj->scripts = $scripts;

        return $obj;
    }

    /**
     * @since 9.1.0
     */
    public function withPageTitle(?string $pageTitle): self
    {
        $obj = clone $this;
        $obj->pageTitle = $pageTitle;

        return $obj;
    }

    /**
     * @since 9.1.0
     */
    public function withTheme(?string $theme): self
    {
        $obj = clone $this;
        $obj->theme = $theme;

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

    /**
     * @return string[]
     * @since 9.0.0
     */
    public function getFrameAncestors(): array
    {
        return $this->frameAncestors;
    }

    /**
     * @return Script[]
     * @since 9.0.0
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * @since 9.1.0
     */
    public function getPageTitle(): ?string
    {
        return $this->pageTitle;
    }

    /**
     * @since 9.1.0
     */
    public function getTheme(): ?string
    {
        return $this->theme;
    }
}
