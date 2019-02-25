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

define('acl', [], function () {

    var Acl = function (user, scope, aclAllowDeleteCreated) {
        this.user = user || null;
        this.scope = scope;
        this.aclAllowDeleteCreated = aclAllowDeleteCreated;
    }

    _.extend(Acl.prototype, {

        user: null,

        getUser: function () {
            return this.user;
        },

        checkScope: function (data, action, precise, entityAccessData) {
            entityAccessData = entityAccessData || {};

            var inTeam = entityAccessData.inTeam;
            var isOwner = entityAccessData.isOwner;

            if (this.getUser().isAdmin()) {
                return true;
            }

            if (data === false) {
                return false;
            }
            if (data === true) {
                return true;
            }
            if (typeof data === 'string') {
                return true;
            }
            if (data === null) {
                return true;
            }

            action = action || null;

            if (action === null) {
                return true
            }
            if (!(action in data)) {
                return false;
            }

            var value = data[action];

            if (value === 'all') {
                return true;
            }

            if (value === 'yes') {
                return true;
            }

            if (value === 'no') {
                return false;
            }

            if (typeof isOwner === 'undefined') {
                return true;
            }

            if (isOwner) {
                if (value === 'own' || value === 'team') {
                    return true;
                }
            }

            var result = false;

            if (value === 'team') {
                result = inTeam;
                if (inTeam === null) {
                    if (precise) {
                        result = null;
                    } else {
                        return true;
                    }
                } else if (inTeam) {
                    return true;
                }
            }

            if (isOwner === null) {
                if (precise) {
                    result = null;
                } else {
                    return true;
                }
            }

            return result;
        },

        checkModel: function (model, data, action, precise) {
            if (this.getUser().isAdmin()) {
                return true;
            }
            var entityAccessData = {
                isOwner: this.checkIsOwner(model),
                inTeam: this.checkInTeam(model)
            };
            return this.checkScope(data, action, precise, entityAccessData);
        },

        checkModelDelete: function (model, data, precise) {
            var result = this.checkModel(model, data, 'delete', precise);

            if (result) {
                return true;
            }

            if (data === false) {
                return false;
            }

            var d = data || {};
            if (d.read === 'no') {
                return false;
            }

            if (model.has('createdById')) {
                if (model.get('createdById') === this.getUser().id && this.aclAllowDeleteCreated) {
                    if (!model.has('assignedUserId')) {
                        return true;
                    } else {
                        if (!model.get('assignedUserId')) {
                            return true;
                        }
                        if (model.get('assignedUserId') === this.getUser().id) {
                            return true;
                        }
                    }
                }
            }

            return result;
        },

        checkIsOwner: function (model) {
            var result = false;

            if (model.hasField('assignedUser')) {
                if (this.getUser().id === model.get('assignedUserId')) {
                    return true;
                } else {
                    if (!model.has('assignedUserId')) {
                        result = null;
                    }
                }
            } else {
                if (model.hasField('createdBy')) {
                    if (this.getUser().id === model.get('createdById')) {
                        return true;
                    } else {
                        if (!model.has('createdById')) {
                            result = null;
                        }
                    }
                }
            }

            if (model.hasField('assignedUsers')) {
                if (!model.has('assignedUsersIds')) {
                    return null;
                }

                if (~(model.get('assignedUsersIds') || []).indexOf(this.getUser().id)) {
                    return true;
                } else {
                    result = false;
                }
            }

            return result;
        },

        checkInTeam: function (model) {
            var userTeamIdList = this.getUser().getTeamIdList();
            if (model.name == 'Team') {
                return (userTeamIdList.indexOf(model.id) != -1);
            } else {
                if (!model.has('teamsIds')) {
                    return null;
                }
                var teamIdList = model.getTeamIdList();
                var inTeam = false;
                userTeamIdList.forEach(function (id) {
                    if (~teamIdList.indexOf(id)) {
                        inTeam = true;
                    }
                });
                return inTeam;
            }
            return false;
        }
    });

    Acl.extend = Backbone.Router.extend;

    return Acl;
});
