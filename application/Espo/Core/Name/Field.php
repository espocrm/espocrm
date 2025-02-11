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

namespace Espo\Core\Name;

class Field
{
    public const ID = 'id';
    public const NAME = 'name';
    public const CREATED_BY = 'createdBy';
    public const CREATED_AT = 'createdAt';
    public const MODIFIED_BY = 'modifiedBy';
    public const MODIFIED_AT = 'modifiedAt';
    public const STREAM_UPDATED_AT = 'streamUpdatedAt';
    public const ASSIGNED_USER = 'assignedUser';
    public const ASSIGNED_USERS = 'assignedUsers';
    public const COLLABORATORS = 'collaborators';
    public const TEAMS = 'teams';
    public const PARENT = 'parent';
    public const IS_FOLLOWED = 'isFollowed';
    public const FOLLOWERS = 'followers';
    public const IS_STARRED = 'isStarred';
    public const EMAIL_ADDRESS = 'emailAddress';
    public const PHONE_NUMBER = 'phoneNumber';
    public const VERSION_NUMBER = 'versionNumber';
}
