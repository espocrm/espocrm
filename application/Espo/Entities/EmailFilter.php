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

namespace Espo\Entities;

use Espo\Core\ORM\Entity;

class EmailFilter extends Entity
{
    public const ENTITY_TYPE = 'EmailFilter';

    public const ACTION_SKIP = 'Skip';
    public const ACTION_MOVE_TO_FOLDER = 'Move to Folder';
    public const ACTION_MOVE_TO_GROUP_FOLDER = 'Move to Group Folder';
    public const ACTION_NONE = 'None';

    public const STATUS_ACTIVE = 'Active';

    /**
     * @return self::ACTION_*|null
     */
    public function getAction(): ?string
    {
        return $this->get('action');
    }

    public function getEmailFolderId(): ?string
    {
        return $this->get('emailFolderId');
    }

    public function getGroupEmailFolderId(): ?string
    {
        return $this->get('groupEmailFolderId');
    }

    public function markAsRead(): bool
    {
        return (bool) $this->get('markAsRead');
    }

    public function skipNotification(): bool
    {
        return (bool) $this->get('skipNotification');
    }

    public function isGlobal(): bool
    {
        return (bool) $this->get('isGlobal');
    }

    public function getParentType(): ?string
    {
        return $this->get('parentType');
    }

    public function getParentId(): ?string
    {
        return $this->get('parentId');
    }

    public function getFrom(): ?string
    {
        return $this->get('from');
    }

    public function getTo(): ?string
    {
        return $this->get('to');
    }

    public function getSubject(): ?string
    {
        return $this->get('subject');
    }

    /**
     * @return string[]
     */
    public function getBodyContains(): array
    {
        return $this->get('bodyContains') ?? [];
    }

    /**
     * @return string[]
     */
    public function getBodyContainsAll(): array
    {
        return $this->get('bodyContainsAll') ?? [];
    }
}
