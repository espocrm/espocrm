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

import {inject} from 'di';
import Metadata from 'metadata';
import AssignmentHelper from 'helpers/assignment';

// noinspection JSUnusedGlobalSymbols
export default class LockViewSetup {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /**
     * @private
     * @type {import('model').default}
     */
    model

    /**
     * @private
     * @type {import('views/record/detail').default}
     */
    view

    ignoreFieldList = [
        'isLocked',
        'modifiedAt',
        'modifiedBy',
        'streamUpdatedAt',
    ]

    /**
     * @param {import('views/record/detail').default} view
     */
    constructor(view) {
        this.view = view;
        this.model = view.model;
    }

    process() {
        const entityType = this.model.entityType;

        if (this.metadata.get(`scopes.${entityType}.lockable`) !== true) {
            return;
        }

        let wasLocked = false;
        const lockedMap = {};

        const ignoreFieldList = this.getIgnoreFieldList();

        const fieldsDefs = /** @type {Record.<string, Record>} */
            this.metadata.get(`entityDefs.${entityType}.fields`) ?? {};

        const lockableFields = Object.keys(fieldsDefs)
            .filter(field => {
                if (ignoreFieldList.includes(field)) {
                    return false;
                }

                const defs = fieldsDefs[field];

                return !defs.notLockable &&
                    !defs.readOnly &&
                    !defs.readOnlyAfterCreate;
            });

        const controlLocked = () => {
            const isLocked = this.model.attributes.isLocked;

            if (!isLocked && !wasLocked) {
                return;
            }

            if (isLocked) {
                wasLocked = true;
            }

            lockableFields.forEach(field => {
                if (isLocked) {
                    if (
                        this.view.recordHelper &&
                        this.view.recordHelper.getFieldStateParam(field, 'readOnly')
                    ) {
                        return;
                    }

                    lockedMap[field] = true;

                    this.view.setFieldReadOnly(field);

                    return;
                }

                if (!lockedMap[field]) {
                    return;
                }

                delete lockedMap[field];

                this.view.setFieldNotReadOnly(field);
            });
        }

        controlLocked();
        this.view.listenTo(this.model, 'change:isLocked', () => controlLocked());
    }

    /**
     * @private
     * @return {string[]}
     */
    getIgnoreFieldList() {
        const entityType = this.model.entityType;

        const ignoreFieldList = [...this.ignoreFieldList];

        const helper = new AssignmentHelper;

        if (helper.hasCollaboratorsField(entityType)) {
            if (helper.hasAssignedUsersField(entityType)) {
                if (this.model.getFieldParam('assignedUsers', 'notLockable')) {
                    ignoreFieldList.push('collaborators');
                }
            } else if (helper.hasAssignedUserField(entityType)) {
                if (this.model.getFieldParam('assignedUser', 'notLockable')) {
                    ignoreFieldList.push('collaborators');
                }
            }
        }
        return ignoreFieldList;
    }
}
