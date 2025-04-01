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

namespace Espo\Core\Mail\Account\Storage;

use SensitiveParameter;

class ParamsBuilder
{
    private ?string $host = null;
    private ?int $port = null;
    private ?string $username = null;
    private ?string $password = null;
    private ?string $security = null;
    /** @var ?class-string<object> */
    private ?string $imapHandlerClassName = null;
    private ?string $id = null;
    private ?string $userId = null;
    private ?string $emailAddress = null;

    public function build(): Params
    {
        return new Params(
            $this->host,
            $this->port,
            $this->username,
            $this->password,
            $this->security,
            $this->imapHandlerClassName,
            $this->id,
            $this->userId,
            $this->emailAddress
        );
    }

    public function setHost(?string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(?int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setPassword(#[SensitiveParameter] ?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setSecurity(?string $security): self
    {
        $this->security = $security;

        return $this;
    }

    /**
     * @param ?class-string<object> $imapHandlerClassName
     */
    public function setImapHandlerClassName(?string $imapHandlerClassName): self
    {
        $this->imapHandlerClassName = $imapHandlerClassName;

        return $this;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function setEmailAddress(?string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }
}
