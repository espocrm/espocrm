<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Entities;

use Espo\Core\ORM\Entity;
use UnexpectedValueException;

class PasswordChangeRequest extends Entity
{
    public const string ENTITY_TYPE = 'PasswordChangeRequest';

    public const string FIELD_REQUEST_ID = 'requestId';
    private const string FIELD_URL = 'url';

    public const string ATTR_USER_ID = 'userId';

    public function getUrl(): ?string
    {
        return $this->get(self::FIELD_URL);
    }

    public function getRequestId(): string
    {
        return $this->get(self::FIELD_REQUEST_ID);
    }

    public function getUserId(): string
    {
        $userId = $this->get(self::ATTR_USER_ID);

        if ($userId === null) {
            throw new UnexpectedValueException();
        }

        return $userId;
    }

    public function setRequestId(string $requestId): self
    {
        return $this->set(self::FIELD_REQUEST_ID, $requestId);
    }

    public function setUserId(string $userId): self
    {
        return $this->set(self::ATTR_USER_ID, $userId);
    }

    public function setUrl(?string $url): self
    {
        return $this->set(self::FIELD_URL, $url);
    }
}
