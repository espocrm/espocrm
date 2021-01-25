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

define('views/user/fields/teams', 'views/fields/link-multiple-with-role', function (Dep) {

    return Dep.extend({

        forceRoles: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.roleListMap = {};

            this.loadRoleList(function () {
                if (this.mode == 'edit') {
                    if (this.isRendered() || this.isBeingRendered()) {
                        this.reRender();
                    }
                }
            }, this);

            this.listenTo(this.model, 'change:teamsIds', function () {
                var toLoad = false;
                this.ids.forEach(function (id) {
                    if (!(id in this.roleListMap)) {
                        toLoad = true;
                    }
                }, this);
                if (toLoad) {
                    this.loadRoleList(function () {
                        this.reRender();
                    }, this);
                }
            }, this);
        },

        loadRoleList: function (callback, context) {
            if (!this.getAcl().checkScope('Team', 'read')) return;

            var ids = this.ids || [];
            if (ids.length == 0) return;

            this.getCollectionFactory().create('Team', function (teams) {
                teams.maxSize = 50;
                teams.where = [
                    {
                        type: 'in',
                        field: 'id',
                        value: ids
                    }
                ];

                this.listenToOnce(teams, 'sync', function () {
                    teams.models.forEach(function (model) {
                        this.roleListMap[model.id] = model.get('positionList') || [];
                    }, this);

                    callback.call(context);
                }, this);

                teams.fetch();
            }, this);
        },

        getDetailLinkHtml: function (id, name) {
            name = name || this.nameHash[id];

            var role = (this.columns[id] || {})[this.columnName] || '';
            var roleHtml = '';
            if (role != '') {
                role = this.getHelper().escapeString(role);
                roleHtml = '<span class="text-muted small"> &#187; ' + role + '</span>';
            }
            var lineHtml = '<div>' + '<a href="#' + this.foreignScope + '/view/' + id + '">' + name + '</a> ' + roleHtml + '</div>';
            return lineHtml;
        },

        getJQSelect: function (id, roleValue) {
            var roleList = Espo.Utils.clone((this.roleListMap[id] || []));

            if (!roleList.length) {
                return;
            };
            if (roleList.length || roleValue) {
                $role = $('<select class="role form-control input-sm pull-right" data-id="'+id+'">');

                roleList.unshift('');
                roleList.forEach(function (role) {
                    var selectedHtml = (role == roleValue) ? 'selected': '';
                    role = this.getHelper().escapeString(role);
                    var label = role;
                    if (role == '') {
                        label = '--' + this.translate('None', 'labels') + '--';
                    }
                    option = '<option value="'+role+'" ' + selectedHtml + '>' + label + '</option>';
                    $role.append(option);
                }, this);
                return $role;
            } else {
                return $('<div class="small pull-right text-muted">').html(roleValue);
            }
        },
    });
});
