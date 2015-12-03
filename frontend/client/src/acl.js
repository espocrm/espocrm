/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

Espo.define('acl', [], function () {

    var Acl = function (user) {
        this.data = {
            table: {}
        };
        this.user = user || null;
    }

    _.extend(Acl.prototype, {

        data: null,

        user: null,

        set: function (data) {
            data = data || {};
            this.data = data;
            this.data.table = this.data.table || {};
        },

        get: function (name) {
            if (this.user.isAdmin()) {
                return true;
            }
            return this.data[name] || null;
        },

        check: function (scope, action, isOwner, inTeam) {
            if (this.user.isAdmin()) {
                return true;
            }

            if (scope in this.data.table) {
                if (this.data.table[scope] === false) {
                    return false;
                }
                if (this.data.table[scope] === true) {
                    return true;
                }
                if (typeof this.data.table[scope] === 'string') {
                    return true;
                }

                if (typeof action !== 'undefined') {
                    if (action in this.data.table[scope]) {
                        var value = this.data.table[scope][action];

                        if (value === 'all' || value === true) {
                            return true;
                        }

                        if ((action == 'edit' || action == 'read') && (value == 'no' || value === false)) {
                            return false;
                        }

                        if (typeof isOwner === 'undefined') {
                            return true;
                        }

                        if (isOwner && action == 'delete' && value === 'no') {
                            return this.check(scope, 'edit', isOwner);
                        }

                        if (!value || value === 'no') {
                            return false;
                        }

                        if (isOwner) {
                            if (value === 'own' || value === 'team') {
                                return true;
                            }
                        }

                        if (value === 'team') {
                            return true;
                        }

                        return false;
                    }
                }
                return true;
            }
            return true;
        },

        checkScope: function (scope, action) {
            return this.check(scope, action);
        },

        checkModel: function (model, action) {
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
            if (this.user.isAdmin()) {
                return true;
            }
            if (action == 'edit') {
                if (model.has('isEditable')) {
                    return model.get('isEditable');
                }
            }
            if (action == 'delete') {
                if (model.has('isRemovable')) {
                    return model.get('isRemovable');
                }
            }
            return this.check(model.name, action, this.checkIsOwner(model), this.checkInTeam(model));
        },

        checkIsOwner: function (model) {
            var result = this.user.id === model.get('assignedUserId') || this.user.id === model.get('createdById');
            if (!result) {
                if (!model.hasField('assignedUser') && !model.hasField('createdBy')) {
                    return true;
                }
            }
            return result;
        },

        checkInTeam: function (model) {
            var userTeamIds = this.user.getTeamIds();

            if (model.name == 'Team') {
                return (userTeamIds.indexOf(model.id) != -1);
            } else {
                var teamIds = model.getTeamIds();
                for (var i in userTeamIds) {
                    if (teamIds.indexOf(i) != -1) {
                        return true;
                    }
                }
            }
            return false;
        },

        clear: function () {
            this.data = {
                table: {}
            };
        },

        checkAssignmentPermission: function (user) {
            return this.checkPermission('assignmentPermission', user);
        },

        checkUserPermission: function (user) {
            return this.checkPermission('userPermission', user);
        },

        checkPermission: function (permission, user) {
            var result = false;

            if (this.user.isAdmin()) {
                result = true;
            } else {
                if (this.get(permission) === 'no') {
                    if (user.id == this.user.id) {
                        result = true;
                    }
                } else if (this.get(permission) === 'team') {
                    if (user.has('teamsIds')) {
                        user.get('teamsIds').forEach(function (id) {
                            if (~(this.user.get('teamsIds') || []).indexOf(id)) {
                                result = true;
                            }
                        }, this);
                    }
                } else {
                    result = true;
                }
            }
            return result;

        }
    });

    return Acl;

});

