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

namespace Espo\Core\Authentication\AuthToken;

use RuntimeException;

/**
 * An auth token data. Used for auth token creation.
 */
class Data
{
    private $userId;

    private $portalId = null;

    private $hash = null;

    private $ipAddress = null;

    private $createSecret = false;

    private function __construct()
    {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getPortalId(): ?string
    {
        return $this->portalId;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function toCreateSecret(): bool
    {
        return $this->createSecret;
    }

    public static function create(array $data): self
    {
        $object = new self();

        $object->userId = $data['userId'] ?? null;
        $object->portalId = $data['portalId'] ?? null;
        $object->hash = $data['hash'] ?? null;
        $object->ipAddress = $data['ipAddress'] ?? null;
        $object->createSecret = $data['createSecret'] ?? false;

        $object->validate();

        return $object;
    }

    private function validate(): void
    {
        // @todo Use typed properties when php 7.4 is a min supported version.
        if (
            isset($this->userId) && !is_string($this->userId) ||
            isset($this->portalId) && !is_string($this->portalId) ||
            isset($this->hash) && !is_string($this->hash) ||
            isset($this->ipAddress) && !is_string($this->ipAddress) ||
            !is_bool($this->createSecret)
        ) {
            throw new RuntimeException("Invalid data.");
        }

        if (!$this->userId) {
            throw new RuntimeException("No user ID.");
        }
    }
}
