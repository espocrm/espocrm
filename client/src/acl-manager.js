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

define('acl-manager', ['acl', 'utils'], function (Acl, Utils) {

    var AclManager = function (user, implementationClassMap, aclAllowDeleteCreated) {
        this.setEmpty();

        this.user = user || null;
        this.implementationClassMap = implementationClassMap || {};
        this.aclAllowDeleteCreated = aclAllowDeleteCreated;
    };

    _.extend(AclManager.prototype, {

        data: null,

        user: null,

        fieldLevelList: ['yes', 'no'],

        setEmpty: function () {
            this.data = {
                table: {},
                fieldTable:  {},
                fieldTableQuickAccess: {},
            };

            this.implementationHash = {};
            this.forbiddenFieldsCache = {};
            this.implementationClassMap = {};
            this.forbiddenAttributesCache = {};
        },

        getImplementation: function (scope) {
            if (!(scope in this.implementationHash)) {
                let implementationClass = Acl;

                if (scope in this.implementationClassMap) {
                    implementationClass = this.implementationClassMap[scope];
                }

                let forbiddenFieldList = this.getScopeForbiddenFieldList(scope);

                let params = {
                    aclAllowDeleteCreated: this.aclAllowDeleteCreated,
                    teamsFieldIsForbidden: !!~forbiddenFieldList.indexOf('teams'),
                    forbiddenFieldList: forbiddenFieldList,
                };

                let obj = new implementationClass(this.getUser(), scope, params);

                this.implementationHash[scope] = obj;
            }

            return this.implementationHash[scope];
        },

        getUser: function () {
            return this.user;
        },

        set: function (data) {
            data = data || {};

            this.data = data;
            this.data.table = this.data.table || {};
            this.data.fieldTable = this.data.fieldTable || {};
            this.data.attributeTable = this.data.attributeTable || {};
        },

        /**
         * @deprecated Use `getPermissionLevel`.
         * @returns string|null
         */
        get: function (name) {
            return this.data[name] || null;
        },

        /**
         * @param string permission
         * @returns string
         */
        getPermissionLevel: function (permission) {
            let permissionKey = permission;

            if (permission.substr(-10) !== 'Permission') {
                permissionKey = permission + 'Permission';
            }

            return this.data[permissionKey] || 'no';
        },

        getLevel: function (scope, action) {
            if (!(scope in this.data.table)) {
                return;
            }

            if (typeof this.data.table[scope] !== 'object' || !(action in this.data.table[scope])) {
                return;
            }

            return this.data.table[scope][action];
        },

        clear: function () {
            this.setEmpty();
        },

        checkScopeHasAcl: function (scope) {
            var data = (this.data.table || {})[scope];

            if (typeof data === 'undefined') {
                return false;
            }

            return true;
        },

        checkScope: function (scope, action, precise) {
            let data = (this.data.table || {})[scope];

            if (typeof data === 'undefined') {
                data = null;
            }

            return this.getImplementation(scope).checkScope(data, action, precise);
        },

        checkModel: function (model, action, precise) {
            var scope = model.name;

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

            let impl = this.getImplementation(scope);

            if (action) {
                let methodName = 'checkModel' + Espo.Utils.upperCaseFirst(action);

                if (methodName in impl) {
                    return impl[methodName](model, data, precise);
                }
            }

            return impl.checkModel(model, data, action, precise);
        },

        check: function (subject, action, precise) {
            if (typeof subject === 'string') {
                return this.checkScope(subject, action, precise);
            }

            return this.checkModel(subject, action, precise);
        },

        checkIsOwner: function (model) {
            return this.getImplementation(model.name).checkIsOwner(model);
        },

        checkInTeam: function (model) {
            return this.getImplementation(model.name).checkInTeam(model);
        },

        checkAssignmentPermission: function (user) {
            return this.checkPermission('assignmentPermission', user);
        },

        checkUserPermission: function (user) {
            return this.checkPermission('userPermission', user);
        },

        checkPermission: function (permission, user) {
            if (this.getUser().isAdmin()) {
                return true;
            }

            let level = this.get(permission);

            if (level === 'no') {
                if (user.id === this.getUser().id) {
                    return true;
                }

                return false;
            }

            if (level === 'team') {
                if (!user.has('teamsIds')) {
                    return false;
                }

                let result = false;

                let teamsIds = user.get('teamsIds') || [];

                teamsIds.forEach(id => {
                    if (~(this.getUser().get('teamsIds') || []).indexOf(id)) {
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
        },

        getScopeForbiddenFieldList: function (scope, action, thresholdLevel) {
            action = action || 'read';
            thresholdLevel = thresholdLevel || 'no';

            let key = scope + '_' + action + '_' + thresholdLevel;

            if (key in this.forbiddenFieldsCache) {
                return Utils.clone(this.forbiddenFieldsCache[key]);
            }

            let levelList = this.fieldLevelList.slice(this.fieldLevelList.indexOf(thresholdLevel));

            let fieldTableQuickAccess = this.data.fieldTableQuickAccess || {};
            let scopeData = fieldTableQuickAccess[scope] || {};
            let fieldsData = scopeData.fields || {};
            let actionData = fieldsData[action] || {};

            let fieldList = [];

            levelList.forEach(level => {
                let list = actionData[level] || [];

                list.forEach(field => {
                    if (~fieldList.indexOf(field)) {
                        return;
                    }

                    fieldList.push(field);
                });
            });

            this.forbiddenFieldsCache[key] = fieldList;

            return Utils.clone(fieldList);
        },

        getScopeForbiddenAttributeList: function (scope, action, thresholdLevel) {
            action = action || 'read';
            thresholdLevel = thresholdLevel || 'no';

            let key = scope + '_' + action + '_' + thresholdLevel;

            if (key in this.forbiddenAttributesCache) {
                return Utils.clone(this.forbiddenAttributesCache[key]);
            }

            let levelList = this.fieldLevelList.slice(this.fieldLevelList.indexOf(thresholdLevel));

            let fieldTableQuickAccess = this.data.fieldTableQuickAccess || {};
            let scopeData = fieldTableQuickAccess[scope] || {};

            let attributesData = scopeData.attributes || {};
            let actionData = attributesData[action] || {};

            let attributeList = [];

            levelList.forEach(level => {
                let list = actionData[level] || [];

                list.forEach(attribute => {
                    if (~attributeList.indexOf(attribute)) {
                        return;
                    }

                    attributeList.push(attribute);
                });
            });

            this.forbiddenAttributesCache[key] = attributeList;

            return Utils.clone(attributeList);
        },

        checkTeamAssignmentPermission: function (teamId) {
            if (this.get('assignmentPermission') === 'all') {
                return true;
            }

            return ~this.getUser().getLinkMultipleIdList('teams').indexOf(teamId);
        },

        checkField: function (scope, field, action) {
            return !~this.getScopeForbiddenFieldList(scope, action).indexOf(field);
        },

    });

    AclManager.extend = Backbone.Router.extend;

    return AclManager;
});

