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

/**
 * @since 9.4.0
 */
export default class AssignmentHelper {

    /**
     * @private
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    /**
     * @param {string} entityType
     * @return {boolean}
     */
    hasAssignedUserField(entityType) {
        return this.metadata.get(`entityDefs.${entityType}.fields.assignedUser.type`) === 'link' &&
            !this.metadata.get(`entityDefs.${entityType}.fields.assignedUser.disabled`) &&
            this.metadata.get(`entityDefs.${entityType}.links.assignedUser.entity`) === 'User';
    }

    /**
     * @param {string} entityType
     * @return {boolean}
     */
    hasAssignedUsersField(entityType) {
        return this.metadata.get(`entityDefs.${entityType}.fields.assignedUsers.type`) === 'linkMultiple' &&
            this.metadata.get(`entityDefs.${entityType}.links.assignedUsers.entity`) === 'User';
    }

    /**
     * @param {string} entityType
     * @return {boolean}
     */
    hasCollaboratorsField(entityType) {
        if (!this.metadata.get(`scopes.${entityType}.collaborators`)) {
            return false;
        }

        return this.metadata.get(`entityDefs.${entityType}.fields.collaborators.type`) === 'linkMultiple' &&
            this.metadata.get(`entityDefs.${entityType}.links.collaborators.entity`) === 'User';
    }
}
