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

    var Acl = function (user, scope) {
        this.user = user || null;
        this.scope = scope;
    }

    _.extend(Acl.prototype, {

        user: null,

        getUser: function () {
            return this.user;
        },

        checkScope: function (data, action, precise, isOwner, inTeam) {
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

            if (action !== null) {
                if (action in data) {
                    var value = data[action];

                    if (value === 'all' || value === true) {
                        return true;
                    }

                    if (action != 'delete' && (value == 'no' || value === false)) {
                        return false;
                    }

                    if (typeof isOwner === 'undefined') {
                        return true;
                    }

                    if (isOwner && action == 'delete' && value === 'no') {
                        return this.checkScope(data, 'edit', precise, isOwner);
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
                        if (inTeam === null) {
                            if (precise) {
                                return null;
                            } else {
                                return true;
                            }
                        } else {
                            return inTeam;
                        }
                    }

                    return false;
                }
            }
            return true;
        },

        checkModel: function (model, data, action, precise) {
            return this.checkScope(data, action, precise, this.checkIsOwner(model), this.checkInTeam(model));
        },

        checkIsOwner: function (model) {
            var result = this.getUser().id === model.get('assignedUserId') || this.getUser().id === model.get('createdById');
            if (!result) {
                if (!model.hasField('assignedUser') && !model.hasField('createdBy')) {
                    return true;
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

    return Acl;
});

