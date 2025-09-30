/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

import {Events} from 'bullbone';

/**
 * @mixes Bull.Events
 */
class RemindersHandler  {

    /**
     * @param {import('views/record/detail').default} view
     */
    constructor(view) {
        this.view = view;
        this.model = view.model;
        this.user = this.view.getUser();

        this.ignoreStatusList = [
            ...(this.view.getMetadata().get(['scopes', this.view.entityType, 'completedStatusList']) || []),
            ...(this.view.getMetadata().get(['scopes', this.view.entityType, 'canceledStatusList']) || []),
        ];
    }

    process() {
        this.control();

        this.listenTo(this.model, 'change', () => {
            if (
                !this.model.hasChanged('assignedUserId') &&
                !this.model.hasChanged('assignedUsersIds') &&
                !this.model.hasChanged('dateEnd') &&
                !this.model.hasChanged('dateEndDate') &&
                !this.model.hasChanged('status')
            ) {
                return;
            }

            this.control();
        });
    }

    control() {
        if (!this.model.attributes.dateEnd && !this.model.attributes.dateEndDate) {
            this.view.hideField('reminders');

            return;
        }

        /** @type {string[]} */
        const assignedUsersIds = this.model.attributes.assignedUsersIds || [];

        if (
            !this.ignoreStatusList.includes(this.model.attributes.status) &&
            (
                this.model.attributes.assignedUserId === this.user.id ||
                assignedUsersIds.includes(this.user.id)
            )
        ) {
            this.view.showField('reminders');

            return;
        }

        this.view.hideField('reminders');
    }
}

Object.assign(RemindersHandler.prototype, Events);

// noinspection JSUnusedGlobalSymbols
export default RemindersHandler;
