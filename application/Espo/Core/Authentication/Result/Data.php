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

namespace Espo\Core\Authentication\Result;

use Espo\Entities\User;

use stdClass;

class Data
{
    private $message = null;

    private $token = null;

    private $view = null;

    private $loggedUser = null;

    private $failReason = null;

    private $data = [];

    private function __construct(
        ?string $message = null,
        ?string $failReason = null,
        ?string $token = null,
        ?string $view = null,
        ?User $loggedUser = null
    ) {
        $this->message = $message;
        $this->failReason = $failReason;
        $this->token = $token;
        $this->view = $view;
        $this->loggedUser = $loggedUser;
    }

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

    public function getLoggedUser(): ?User
    {
        return $this->loggedUser;
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

    public function withFailReason(?string $failReason): self
    {
        $obj = clone $this;
        $obj->failReason = $failReason;

        return $obj;
    }

    public function withToken(?string $token): self
    {
        $obj = clone $this;
        $obj->token = $token;

        return $obj;
    }

    public function withView(?string $view): self
    {
        $obj = clone $this;
        $obj->view = $view;

        return $obj;
    }

    public function withLoggedUser(?User $loggedUser): self
    {
        $obj = clone $this;
        $obj->loggedUser = $loggedUser;

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
