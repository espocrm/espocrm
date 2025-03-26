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

use stdClass;

/**
 * Immutable.
 */
class Data
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @noinspection PhpSameParameterValueInspection */
    private function __construct(
        private ?string $message = null,
        private ?string $failReason = null,
        private ?string $token = null,
        private ?string $view = null
    ) {}

    public static function create(): self
    {
        return new self();
    }

    public static function createWithFailReason(string $failReason): self
    {
        return new self(null, $failReason);
    }

    public static function createWithMessage(string $message): self
    {
        return new self($message);
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getFailReason(): ?string
    {
        return $this->failReason;
    }

    public function getData(): stdClass
    {
        return (object) $this->data;
    }

    public function withMessage(?string $message): self
    {
        $obj = clone $this;
        $obj->message = $message;

        return $obj;
    }

    /** @noinspection PhpUnused */
    public function withFailReason(?string $failReason): self
    {
        $obj = clone $this;
        $obj->failReason = $failReason;

        return $obj;
    }

    /** @noinspection PhpUnused */
    public function withToken(?string $token): self
    {
        $obj = clone $this;
        $obj->token = $token;

        return $obj;
    }

    /** @noinspection PhpUnused */
    public function withView(?string $view): self
    {
        $obj = clone $this;
        $obj->view = $view;

        return $obj;
    }

    /**
     * @param mixed $value
     */
    public function withDataItem(string $name, $value): self
    {
        $obj = clone $this;
        $obj->data[$name] = $value;

        return $obj;
    }
}
