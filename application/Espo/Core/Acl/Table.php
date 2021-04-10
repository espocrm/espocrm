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

namespace Espo\Core\Acl;

use StdClass;

/**
 * Contains access levels for a user.
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
     * Get a full map.
     */
    public function getMap(): StdClass;

    /**
     * Get scope data.
     */
    public function getScopeData(string $scope): ScopeData;

    /**
     * Get a permission level.
     */
    public function getPermissionLevel(string $permission): string;

    /**
     * Get a list of forbidden attributes for a scope and action.
     *
     * @param $scope A scope.
     * $param $action An action.
     * @param $thresholdLevel An attribute will be treated as forbidden if the level is
     * equal to or lower than the threshold.
     * @return array<string>
     */
    public function getScopeForbiddenAttributeList(string $scope, string $action, string $thresholdLevel): array;

    /**
     * Get a list of forbidden fields for a scope and action.
     *
     * @param $scope A scope.
     * $param $action An action.
     * @param $thresholdLevel An attribute will be treated as forbidden if the level is
     * equal to or lower than the threshold.
     * @return array<string>
     */
    public function getScopeForbiddenFieldList(string $scope, string $action, string $thresholdLevel): array;
}
