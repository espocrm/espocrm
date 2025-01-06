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

namespace Espo\Core\Acl;

/**
 * Access levels for a user.
 */
interface Table
{
    public const LEVEL_YES = 'yes';
    public const LEVEL_NO = 'no';
    public const LEVEL_ALL = 'all';
    public const LEVEL_TEAM = 'team';
    public const LEVEL_OWN = 'own';

    public const ACTION_READ = 'read';
    public const ACTION_STREAM = 'stream';
    public const ACTION_EDIT = 'edit';
    public const ACTION_DELETE = 'delete';
    public const ACTION_CREATE = 'create';

    /**
     * Get scope data.
     */
    public function getScopeData(string $scope): ScopeData;

    /**
     * Get field data.
     */
    public function getFieldData(string $scope, string $field): FieldData;

    /**
     * Get a permission level.
     *
     * @return self::ACTION_*
     */
    public function getPermissionLevel(string $permission): string;
}
