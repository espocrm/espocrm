/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

Espo.define('Views.User.Fields.Teams', 'Views.Fields.LinkMultipleWithRole', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.roleListMap = {};

            this.loadRoleList(function () {
                if (this.mode == 'edit') {
                    if (this.isRendered()) {
                        this.render();
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
                        this.render();
                    }, this);
                }
            }, this);
        },

        loadRoleList: function (callback, context) {
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
                roleHtml = '<span class="text-muted small"> &#187; ' + role + '</span>';
            }
            var lineHtml = '<div>' + '<a href="#' + this.foreignScope + '/view/' + id + '">' + name + '</a> ' + roleHtml + '</div>';
            return lineHtml;
        },

        getJQSelect: function (id, roleValue) {
            var roleList = Espo.Utils.clone((this.roleListMap[id] || []));
            if (roleList.length || roleValue) {
                $role = $('<select class="role form-control input-sm pull-right" data-id="'+id+'">');

                roleList.unshift('');
                roleList.forEach(function (role) {
                    var selectedHtml = (role == roleValue) ? 'selected': '';
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
        }

    });

});
