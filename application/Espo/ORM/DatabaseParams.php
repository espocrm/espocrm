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

namespace Espo\ORM;

class DatabaseParams
{
    private $platform = null;

    private $host = null;

    private $port = null;

    private $name = null;

    private $username = null;

    private $password = null;

    private $charset = null;

    private $sslCa = null;

    private $sslCert = null;

    private $sslKey = null;

    private $sslCaPath = null;

    private $sslCipher = null;

    private $sslVerifyDisabled = false;

    public static function create(): self
    {
        return new self();
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function getSslCa(): ?string
    {
        return $this->sslCa;
    }

    public function getSslCert(): ?string
    {
        return $this->sslCert;
    }

    public function getSslCaPath(): ?string
    {
        return $this->sslCaPath;
    }

    public function getSslCipher(): ?string
    {
        return $this->sslCipher;
    }

    public function getSslKey(): ?string
    {
        return $this->sslKey;
    }

    public function isSslVerifyDisabled(): bool
    {
        return $this->sslVerifyDisabled;
    }

    public function withPlatform(?string $platform): self
    {
        $obj = clone $this;
        $obj->platform = $platform;

        return $obj;
    }

    public function withHost(?string $host): self
    {
        $obj = clone $this;
        $obj->host = $host;

        return $obj;
    }

    public function withPort(?int $port): self
    {
        $obj = clone $this;
        $obj->port = $port;

        return $obj;
    }

    public function withName(?string $name): self
    {
        $obj = clone $this;
        $obj->name = $name;

        return $obj;
    }

    public function withUsername(?string $username): self
    {
        $obj = clone $this;
        $obj->username = $username;

        return $obj;
    }

    public function withPassword(?string $password): self
    {
        $obj = clone $this;
        $obj->password = $password;

        return $obj;
    }

    public function withCharset(?string $charset): self
    {
        $obj = clone $this;
        $obj->charset = $charset;

        return $obj;
    }

    public function withSslCa(?string $sslCa): self
    {
        $obj = clone $this;
        $obj->sslCa = $sslCa;

        return $obj;
    }

    public function withSslCaPath(?string $sslCaPath): self
    {
        $obj = clone $this;
        $obj->sslCaPath = $sslCaPath;

        return $obj;
    }

    public function withSslCert(?string $sslCert): self
    {
        $obj = clone $this;
        $obj->sslCert = $sslCert;

        return $obj;
    }

    public function withSslCipher(?string $sslCipher): self
    {
        $obj = clone $this;
        $obj->sslCipher = $sslCipher;

        return $obj;
    }

    public function withSslKey(?string $sslKey): self
    {
        $obj = clone $this;
        $obj->sslKey = $sslKey;

        return $obj;
    }

    public function withSslVerifyDisabled(bool $sslVerifyDisabled = true): self
    {
        $obj = clone $this;
        $obj->sslVerifyDisabled = $sslVerifyDisabled;

        return $obj;
    }
}
