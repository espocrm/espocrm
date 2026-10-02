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

namespace Espo\Tools\Notification;

/**
 * Parameters for fetching notifications. The defaults stand for the notification list view
 * and the notification panel.
 *
 * @internal
 */
readonly class GetParams
{
    /**
     * @param bool $groupingDisabled Do not group notifications. Covers both grouping by a
     *     related record and grouping by an action. Must be set when search parameters carry a
     *     filter defined by a user: grouping by an action keeps only the oldest notification of
     *     an action, which would hide its matching siblings.
     * @param bool $markAsRead Mark fetched notifications as read.
     */
    public function __construct(
        public bool $groupingDisabled = false,
        public bool $markAsRead = true,
    ) {}
}
