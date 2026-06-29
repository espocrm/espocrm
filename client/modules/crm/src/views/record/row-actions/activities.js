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

import RelationshipRowActionsView from 'views/record/row-actions/relationship';

export default class ActivitiesRowActionsView extends RelationshipRowActionsView {

    setup() {
        super.setup();

        this.options.unlinkDisabled = true;
    }

    getActionList() {
        const actionList = super.getActionList();

        if (this.options.acl.edit) {
            if (this.model.entityType === 'Meeting' || this.model.entityType === 'Call') {
                /** @type {string[]} */
                const options = this.model.getFieldParam('status', 'options') ?? [];

                const notActualStatuses = [
                    ...this.getMetadata().get(`scopes.${this.model.entityType}.completedStatusList`, []),
                    ...this.getMetadata().get(`scopes.${this.model.entityType}.canceledStatusList`, []),
                ];

                if (options.includes('Held') && !notActualStatuses.includes(this.model.attributes.status)) {
                    actionList.push({
                        action: 'setHeld',
                        text: this.translate('Set Held', 'labels', 'Meeting'),
                        data: {
                            id: this.model.id,
                        },
                        groupIndex: 1,
                        iconClass: 'fas fa-check',
                    });
                }

                if (options.includes('Not Held') && !notActualStatuses.includes(this.model.attributes.status)) {
                    actionList.push({
                        action: 'setNotHeld',
                        text: this.translate('Set Not Held', 'labels', 'Meeting'),
                        data: {
                            id: this.model.id,
                        },
                        groupIndex: 1,
                    });
                }
            }
        }

        return actionList;
    }
}
