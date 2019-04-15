/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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


/** * Example:
 * Lead: {
 *   edit: 'own',
 *   read: 'team',
 *   delete: 'no',
 * }
 */

define('acl-manager', ['acl'], function (Acl) {

    var AclManager = function (user, implementationClassMap, aclAllowDeleteCreated) {
        this.setEmpty();

        this.user = user || null;
        this.implementationClassMap = implementationClassMap || {};
        this.aclAllowDeleteCreated = aclAllowDeleteCreated;
    }

    _.extend(AclManager.prototype, {

        data: null,

        user: null,

        fieldLevelList: ['yes', 'no'],

        setEmpty: function () {
            this.data = {
                table: {},
                fieldTable:  {},
                fieldTableQuickAccess: {}
            };
            this.implementationHash = {};
            this.forbiddenFieldsCache = {};
            this.implementationClassMap = {};
            this.forbiddenAttributesCache = {};
        },

        getImplementation: function (scope) {
            if (!(scope in this.implementationHash)) {
                var implementationClass = Acl;
                if (scope in this.implementationClassMap) {
                    implementationClass = this.implementationClassMap[scope];
                }
                var obj = new implementationClass(this.getUser(), scope, this.aclAllowDeleteCreated);
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

        get: function (name) {
            return this.data[name] || null;
        },

        getLevel: function (scope, action) {
            if (!(scope in this.data.table)) return;
            if (typeof this.data.table[scope] !== 'object' || !(action in this.data.table[scope])) return;

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
            var data = (this.data.table || {})[scope];
            if (typeof data === 'undefined') {
                data = null;
            }
            return this.getImplementation(scope).checkScope(data, action, precise);
        },

        checkModel: function (model, action, precise) {
            var scope = model.name;

            // todo move this to custom acl
            if (action == 'edit') {
                if (!model.isEditable()) {
                    return false;
                }
            }
            if (action == 'delete') {
                if (!model.isRemovable()) {
                    return false;
                }
            }

            var data = (this.data.table || {})[scope];
            if (typeof data === 'undefined') {
                data = null;
            }

            var impl = this.getImplementation(scope);

            var methodName = 'checkModel' + Espo.Utils.upperCaseFirst(action);
            if (methodName in impl) {
                return impl[methodName](model, data, precise);
            }

            return impl.checkModel(model, data, action, precise);
        },

        check: function (subject, action, precise) {
            if (typeof subject === 'string') {
                return this.checkScope(subject, action, precise);
            } else {
                return this.checkModel(subject, action, precise);
            }
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
            var result = false;

            if (this.getUser().isAdmin()) {
                result = true;
            } else {
                if (this.get(permission) === 'no') {
                    if (user.id == this.getUser().id) {
                        result = true;
                    }
                } else if (this.get(permission) === 'team') {
                    if (user.has('teamsIds')) {
                        user.get('teamsIds').forEach(function (id) {
                            if (~(this.getUser().get('teamsIds') || []).indexOf(id)) {
                                result = true;
                            }
                        }, this);
                    }
                } else {
                    result = true;
                }
            }
            return result;
        },

        getScopeForbiddenFieldList: function (scope, action, thresholdLevel) {
            action = action || 'read';
            thresholdLevel = thresholdLevel || 'no';

            var key = scope + '_' + action + '_' + thresholdLevel;
            if (key in this.forbiddenFieldsCache) {
                return this.forbiddenFieldsCache[key];
            }

            var levelList = this.fieldLevelList.slice(this.fieldLevelList.indexOf(thresholdLevel));

            var fieldTableQuickAccess = this.data.fieldTableQuickAccess || {};
            var scopeData = fieldTableQuickAccess[scope] || {};
            var fieldsData = scopeData.fields || {};
            var actionData = fieldsData[action] || {};

            var fieldList = [];
            levelList.forEach(function (level) {
                var list = actionData[level] || [];
                list.forEach(function (field) {
                    if (~fieldList.indexOf(field)) return;
                    fieldList.push(field);
                }, this);
            }, this);

            this.forbiddenFieldsCache[key] = fieldList;

            return fieldList;
        },

        getScopeForbiddenAttributeList: function (scope, action, thresholdLevel) {
            action = action || 'read';
            thresholdLevel = thresholdLevel || 'no';

            var key = scope + '_' + action + '_' + thresholdLevel;
            if (key in this.forbiddenAttributesCache) {
                return this.forbiddenAttributesCache[key];
            }

            var levelList = this.fieldLevelList.slice(this.fieldLevelList.indexOf(thresholdLevel));

            var fieldTableQuickAccess = this.data.fieldTableQuickAccess || {};
            var scopeData = fieldTableQuickAccess[scope] || {};

            var attributesData = scopeData.attributes || {};
            var actionData = attributesData[action] || {};

            var attributeList = [];
            levelList.forEach(function (level) {
                var list = actionData[level] || [];
                list.forEach(function (attribute) {
                    if (~attributeList.indexOf(attribute)) return;
                    attributeList.push(attribute);
                }, this);
            }, this);

            this.forbiddenAttributesCache[key] = attributeList;

            return attributeList;
        },

        checkTeamAssignmentPermission: function (teamId) {
            if (this.get('assignmentPermission') === 'all') return true;
            return ~this.getUser().getLinkMultipleIdList('teams').indexOf(teamId);
        }

    });

    AclManager.extend = Backbone.Router.extend;

    return AclManager;
});

