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

/** @module acl-manager */

import Acl from 'acl';
import Utils from 'utils';
import {View as BullView} from 'bullbone';

/**
 * An action.
 *
 * @typedef {'create'|'read'|'edit'|'delete'|'stream'} module:acl-manager~action
 */

/**
 * An access checking class for a specific scope.
 */
class AclManager {

    /** @protected */
    data = null
    fieldLevelList = ['yes', 'no']

    /**
     * @param {module:models/user} user A user.
     * @param {Object} implementationClassMap `acl` implementations.
     * @param {boolean} aclAllowDeleteCreated Allow a user to delete records they created regardless a
     *   role access level.
     */
    constructor(user, implementationClassMap, aclAllowDeleteCreated) {
        this.setEmpty();

        /** @protected */
        this.user = user || null;
        this.implementationClassMap = implementationClassMap || {};
        this.aclAllowDeleteCreated = aclAllowDeleteCreated;
    }

    /**
     * @protected
     */
    setEmpty() {
        this.data = {
            table: {},
            fieldTable:  {},
            fieldTableQuickAccess: {},
        };

        this.implementationHash = {};
        this.forbiddenFieldsCache = {};
        this.implementationClassMap = {};
        this.forbiddenAttributesCache = {};
    }

    /**
     * Get an `acl` implementation.
     *
     * @protected
     * @param {string} scope A scope.
     * @returns {module:acl}
     */
    getImplementation(scope) {
        if (!(scope in this.implementationHash)) {
            let implementationClass = Acl;

            if (scope in this.implementationClassMap) {
                implementationClass = this.implementationClassMap[scope];
            }

            const forbiddenFieldList = this.getScopeForbiddenFieldList(scope);

            const params = {
                aclAllowDeleteCreated: this.aclAllowDeleteCreated,
                teamsFieldIsForbidden: forbiddenFieldList.includes('teams'),
                forbiddenFieldList: forbiddenFieldList,
            };

            this.implementationHash[scope] = new implementationClass(this.getUser(), scope, params, this);
        }

        return this.implementationHash[scope];
    }

    /**
     * @return {import('models/user').default}
     * @protected
     */
    getUser() {
        return this.user;
    }

    /**
     * @internal
     */
    set(data) {
        data = data || {};

        this.data = data;
        this.data.table = this.data.table || {};
        this.data.fieldTable = this.data.fieldTable || {};
        this.data.attributeTable = this.data.attributeTable || {};
    }

    /**
     * @deprecated Use `getPermissionLevel`.
     *
     * @returns {string|null}
     */
    get(name) {
        return this.data[name] || null;
    }

    /**
     * Get a permission level.
     *
     * @param {string} permission A permission name.
     * @returns {'yes'|'all'|'team'|'no'}
     */
    getPermissionLevel(permission) {
        let permissionKey = permission;

        if (permission.slice(-10) !== 'Permission') {
            permissionKey = permission + 'Permission';
        }

        return this.data[permissionKey] || 'no';
    }

    /**
     * Get access level to a scope action.
     *
     * @param {string} scope A scope.
     * @param {module:acl-manager~action} action An action.
     * @returns {'yes'|'all'|'team'|'own'|'no'|null}
     */
    getLevel(scope, action) {
        if (!(scope in this.data.table)) {
            return null;
        }

        const scopeItem = this.data.table[scope];

        if (
            typeof scopeItem !== 'object' ||
            !(action in scopeItem)
        ) {
            return null;
        }

        return scopeItem[action];
    }

    /**
     * Clear access data.
     *
     * @internal
     */
    clear() {
        this.setEmpty();
    }

    /**
     * Check whether a scope has ACL.
     *
     * @param {string} scope A scope.
     * @returns {boolean}
     */
    checkScopeHasAcl(scope) {
        const data = (this.data.table || {})[scope];

        if (typeof data === 'undefined') {
            return false;
        }

        return true;
    }

    /**
     * Check access to a scope.
     *
     * @param {string} scope A scope.
     * @param {module:acl-manager~action|null} [action=null] An action.
     * @param {boolean} [precise=false] Deprecated. Not used.
     * @returns {boolean} True if access allowed.
     */
    checkScope(scope, action, precise) {
        let data = (this.data.table || {})[scope];

        if (typeof data === 'undefined') {
            data = null;
        }

        return this.getImplementation(scope).checkScope(data, action, precise);
    }

    /**
     * Check access to a model.
     *
     * @param {module:model} model A model.
     * @param {module:acl-manager~action|null} [action=null] An action.
     * @param {boolean} [precise=false] To return `null` if not enough data is set in a model.
     *   E.g. the `teams` field is not yet loaded.
     * @returns {boolean|null} True if access allowed, null if not enough data to determine.
     */
    checkModel(model, action, precise) {
        const scope = model.entityType;

        // todo move this to custom acl
        if (action === 'edit') {
            if (!model.isEditable()) {
                return false;
            }
        }

        if (action === 'delete') {
            if (!model.isRemovable()) {
                return false;
            }
        }

        let data = (this.data.table || {})[scope];

        if (typeof data === 'undefined') {
            data = null;
        }

        const impl = this.getImplementation(scope);

        if (action) {
            const methodName = 'checkModel' + Utils.upperCaseFirst(action);

            if (methodName in impl) {
                return impl[methodName](model, data, precise);
            }
        }

        return impl.checkModel(model, data, action, precise);
    }

    /**
     * Check access to a scope or a model.
     *
     * @param {string|module:model} subject What to check. A scope or a model.
     * @param {module:acl-manager~action|null} [action=null] An action.
     * @param {boolean} [precise=false]  To return `null` if not enough data is set in a model.
     *   E.g. the `teams` field is not yet loaded.
     * @returns {boolean|null} True if access allowed, null if not enough data to determine.
     */
    check(subject, action, precise) {
        if (typeof subject === 'string') {
            return this.checkScope(subject, action, precise);
        }

        return this.checkModel(subject, action, precise);
    }

    /**
     * Check if a user is owner to a model.
     *
     * @param {module:model} model A model.
     * @returns {boolean|null} True if owner, null if not clear.
     */
    checkIsOwner(model) {
        return this.getImplementation(model.entityType).checkIsOwner(model);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Check if a user in a team of a model.
     *
     * @param {module:model} model A model.
     * @returns {boolean|null} True if in a team, null if not clear.
     */
    checkInTeam(model) {
        return this.getImplementation(model.entityType).checkInTeam(model);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Check if a record is shared with the user.
     *
     * @param {module:model} model A model.
     * @returns {boolean|null} True if shared, null if not clear.
     */
    checkIsShared(model) {
        return this.getImplementation(model.entityType).checkIsShared(model);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Check an assignment permission to a user.
     *
     * @param {module:models/user} user A user.
     * @returns {boolean} True if access allowed.
     */
    checkAssignmentPermission(user) {
        return this.checkPermission('assignmentPermission', user);
    }

    /**
     * Check a user permission to a user.
     *
     * @param {module:models/user} user A user.
     * @returns {boolean} True if access allowed.
     */
    checkUserPermission(user) {
        return this.checkPermission('userPermission', user);
    }

    /**
     * Check a specific permission to a user.
     *
     * @param {string} permission A permission name.
     * @param {module:models/user} user A user.
     * @returns {boolean|null} True if access allowed. Null if not enough data loaded to know for sure.
     */
    checkPermission(permission, user) {
        if (this.getUser().isAdmin()) {
            return true;
        }

        const level = this.getPermissionLevel(permission);

        if (level === 'no') {
            if (user.id === this.getUser().id) {
                return true;
            }

            return false;
        }

        if (level === 'team') {
            if (!user.has('teamsIds')) {
                return null;
            }

            let result = false;

            const teamsIds = user.get('teamsIds') || [];

            teamsIds.forEach(id => {
                if ((this.getUser().get('teamsIds') || []).includes(id)) {
                    result = true;
                }
            });

            return result;
        }

        if (level === 'all') {
            return true;
        }

        if (level === 'yes') {
            return true;
        }

        return false;
    }

    /**
     * Get a list of forbidden fields for an entity type.
     *
     * @param {string} scope An entity type.
     * @param {'read'|'edit'} [action='read'] An action.
     * @param {'yes'|'no'} [thresholdLevel='no'] A threshold level.
     * @returns {string[]} A forbidden field list.
     */
    getScopeForbiddenFieldList(scope, action, thresholdLevel) {
        action = action || 'read';
        thresholdLevel = thresholdLevel || 'no';

        const key = scope + '_' + action + '_' + thresholdLevel;

        if (key in this.forbiddenFieldsCache) {
            return Utils.clone(this.forbiddenFieldsCache[key]);
        }

        const levelList = this.fieldLevelList.slice(this.fieldLevelList.indexOf(thresholdLevel));

        const fieldTableQuickAccess = this.data.fieldTableQuickAccess || {};
        const scopeData = fieldTableQuickAccess[scope] || {};
        const fieldsData = scopeData.fields || {};
        const actionData = fieldsData[action] || {};

        const fieldList = [];

        levelList.forEach(level => {
            const list = actionData[level] || [];

            list.forEach(field => {
                if (fieldList.includes(field)) {
                    return;
                }

                fieldList.push(field);
            });
        });

        this.forbiddenFieldsCache[key] = fieldList;

        return Utils.clone(fieldList);
    }

    /**
     * Get a list of forbidden attributes for an entity type.
     *
     * @param {string} scope An entity type.
     * @param {'read'|'edit'} [action='read'] An action.
     * @param {'yes'|'no'} [thresholdLevel='no'] A threshold level.
     * @returns {string[]} A forbidden attribute list.
     */
    getScopeForbiddenAttributeList(scope, action, thresholdLevel) {
        action = action || 'read';
        thresholdLevel = thresholdLevel || 'no';

        const key = scope + '_' + action + '_' + thresholdLevel;

        if (key in this.forbiddenAttributesCache) {
            return Utils.clone(this.forbiddenAttributesCache[key]);
        }

        const levelList = this.fieldLevelList.slice(this.fieldLevelList.indexOf(thresholdLevel));

        const fieldTableQuickAccess = this.data.fieldTableQuickAccess || {};
        const scopeData = fieldTableQuickAccess[scope] || {};

        const attributesData = scopeData.attributes || {};
        const actionData = attributesData[action] || {};

        const attributeList = [];

        levelList.forEach(level => {
            const list = actionData[level] || [];

            list.forEach(attribute => {
                if (attributeList.includes(attribute)) {
                    return;
                }

                attributeList.push(attribute);
            });
        });

        this.forbiddenAttributesCache[key] = attributeList;

        return Utils.clone(attributeList);
    }

    /**
     * Check an assignment permission to a team.
     *
     * @param {string} teamId A team ID.
     * @returns {boolean} True if access allowed.
     */
    checkTeamAssignmentPermission(teamId) {
        if (this.getPermissionLevel('assignmentPermission') === 'all') {
            return true;
        }

        return this.getUser().getLinkMultipleIdList('teams').includes(teamId);
    }

    /**
     * Check access to a field.
     * @param {string} scope An entity type.
     * @param {string} field A field.
     * @param {'read'|'edit'} [action='read'] An action.
     * @returns {boolean} True if access allowed.
     */
    checkField(scope, field, action) {
        return !this.getScopeForbiddenFieldList(scope, action).includes(field);
    }
}

AclManager.extend = BullView.extend;

export default AclManager;
