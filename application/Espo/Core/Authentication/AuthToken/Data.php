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

namespace Espo\Core\Authentication\AuthToken;

use RuntimeException;
use SensitiveParameter;

/**
 * An auth token data. Used for auth token creation.
 *
 * Immutable.
 */
class Data
{
    private string $userId;
    private ?string $portalId = null;
    private ?string $hash = null;
    private ?string $ipAddress = null;
    private bool $createSecret = false;

    private function __construct()
    {}

    /**
     * A user ID.
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * A portal ID.
     */
    public function getPortalId(): ?string
    {
        return $this->portalId;
    }

    /**
     * A hash.
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * An ID address.
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * To create a secret.
     */
    public function toCreateSecret(): bool
    {
        return $this->createSecret;
    }

    /**
     * @param array{
     *     userId: string,
     *     portalId?: ?string,
     *     hash?: ?string,
     *     ipAddress?: ?string,
     *     createSecret?: ?bool,
     * } $data
     */
    public static function create(#[SensitiveParameter] array $data): self
    {
        $obj = new self();

        $userId = $data['userId'] ?? null;

        if (!$userId) {
            throw new RuntimeException("No user ID.");
        }

        $obj->userId = $userId;
        $obj->portalId = $data['portalId'] ?? null;
        $obj->hash = $data['hash'] ?? null;
        $obj->ipAddress = $data['ipAddress'] ?? null;
        $obj->createSecret = $data['createSecret'] ?? false;

        return $obj;
    }
}
