<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Mail\Account\Storage;

class Params
{
    private ?string $host = null;

    private ?int $port = null;

    private ?string $username = null;

    private ?string $password = null;

    private ?string $security = null;

    /**
     * @var ?class-string<object>
     */
    private ?string $imapHandlerClassName = null;

    private ?string $id = null;

    private ?string $userId = null;

    private ?string $emailAddress = null;

    /**
     * @param ?class-string<object> $imapHandlerClassName
     */
    public function __construct(
        ?string $host,
        ?int $port,
        ?string $username,
        ?string $password,
        ?string $security,
        ?string $imapHandlerClassName,
        ?string $id,
        ?string $userId,
        ?string $emailAddress
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->security = $security;
        $this->imapHandlerClassName = $imapHandlerClassName;
        $this->id = $id;
        $this->userId = $userId;
        $this->emailAddress = $emailAddress;
    }

    public static function createBuilder(): ParamsBuilder
    {
        return new ParamsBuilder();
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSecurity(): ?string
    {
        return $this->security;
    }

    /**
     * @return ?class-string
     */
    public function getImapHandlerClassName(): ?string
    {
        return $this->imapHandlerClassName;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function withPassword(?string $password): self
    {
        $obj = clone $this;
        $obj->password = $password;

        return $obj;
    }

    /**
     * @param ?class-string $imapHandlerClassName
     */
    public function withImapHandlerClassName(?string $imapHandlerClassName): self
    {
        $obj = clone $this;
        $obj->imapHandlerClassName = $imapHandlerClassName;

        return $obj;
    }
}
