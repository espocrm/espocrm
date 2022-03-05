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

namespace Espo\Core\Mail;

use RuntimeException;

class SmtpParams
{
    private string $server;

    private int $port;

    private ?string $fromAddress = null;

    private ?string $fromName = null;

    /**
     * @var array<string,mixed>|null
     */
    private ?array $connectionOptions = null;

    private bool $auth = false;

    private ?string $authMechanism = null;

    /**
     * @var ?class-string
     */
    private ?string $authClassName = null;

    private ?string $username = null;

    private ?string $password = null;

    private ?string $security = null;

    /**
     * @var string[]
     */
    private array $paramList = [
        'server',
        'port',
        'fromAddress',
        'fromName',
        'connectionOptions',
        'auth',
        'authMechanism',
        'authClassName',
        'username',
        'password',
        'security',
    ];

    public function __construct(string $server, int $port)
    {
        $this->server = $server;
        $this->port = $port;
    }

    public static function create(string $server, int $port): self
    {
        return new self($server, $port);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $params = [];

        foreach ($this->paramList as $name) {
            if ($this->$name !== null) {
                $params[$name] = $this->$name;
            }
        }

        return $params;
    }

    /**
     * @param array<string,mixed> $params
     */
    public static function fromArray(array $params): self
    {
        $server = $params['server'] ?? null;
        $port = $params['port'] ?? null;
        $auth = $params['auth'] ?? false;

        if ($server === null) {
            throw new RuntimeException("Empty server.");
        }

        if ($port === null) {
            throw new RuntimeException("Empty port.");
        }

        $obj = new self($server, $port);

        $obj->auth = $auth;

        foreach ($obj->paramList as $name) {
            if ($obj->$name !== null) {
                continue;
            }

            if (array_key_exists($name, $params)) {
               $obj->$name = $params[$name];
            }
        }

        return $obj;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getFromAddress(): ?string
    {
        return $this->fromAddress;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getConnectionOptions(): ?array
    {
        return $this->connectionOptions;
    }

    public function useAuth(): bool
    {
        return $this->auth;
    }

    public function getAuthMechanism(): ?string
    {
        return $this->authMechanism;
    }

    /**
     * @return ?class-string
     */
    public function getAuthClassName(): ?string
    {
        return $this->authClassName;
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

    public function withFromAddress(?string $fromAddress): self
    {
        $obj = clone $this;
        $obj->fromAddress = $fromAddress;

        return $obj;
    }

    public function withFromName(?string $fromName): self
    {
        $obj = clone $this;
        $obj->fromName = $fromName;

        return $obj;
    }

    /**
     * @param array<string,mixed>|null $connectionOptions
     */
    public function withConnectionOptions(?array $connectionOptions): self
    {
        $obj = clone $this;
        $obj->connectionOptions = $connectionOptions;

        return $obj;
    }

    public function withAuth(bool $auth = true): self
    {
        $obj = clone $this;
        $obj->auth = $auth;

        return $obj;
    }

    public function withAuthMechanism(?string $authMechanism): self
    {
        $obj = clone $this;
        $obj->authMechanism = $authMechanism;

        return $obj;
    }

    /**
     * @param ?class-string $authClassName
     */
    public function withAuthClassName(?string $authClassName): self
    {
        $obj = clone $this;
        $obj->authClassName = $authClassName;

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

    public function withSecurity(?string $security): self
    {
        $obj = clone $this;
        $obj->security = $security;

        return $obj;
    }
}
