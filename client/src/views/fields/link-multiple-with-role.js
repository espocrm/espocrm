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

Espo.define('views/fields/link-multiple-with-role', 'views/fields/link-multiple', function (Dep) {

    return Dep.extend({

        roleType: 'enum',

        columnName: 'role',

        roleFieldIsForeign: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.columnsName = this.name + 'Columns';
            this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});

            this.listenTo(this.model, 'change:' + this.columnsName, function () {
                this.columns = Espo.Utils.cloneDeep(this.model.get(this.columnsName) || {});
            }, this);

            this.roleField = this.getMetadata().get('entityDefs.' + this.model.name + '.fields.' + this.name + '.columns.' + this.columnName);

            if (this.roleFieldIsForeign) {
                this.roleFieldScope = this.foreignScope;
            } else {
                this.roleFieldScope = this.model.name;
            }

            if (this.roleType == 'enum') {
                this.roleList = this.getMetadata().get('entityDefs.' + this.roleFieldScope + '.fields.' + this.roleField + '.options');
            }
        },

        getAttributeList: function () {
            var list = Dep.prototype.getAttributeList.call(this);
            list.push(this.name + 'Columns');
            return list;
        },

        getDetailLinkHtml: function (id, name) {
            name = name || this.nameHash[id];

            var role = (this.columns[id] || {})[this.columnName] || '';
            var roleHtml = '';
            if (role != '') {
                roleHtml = '<span class="text-muted small"> &#187; ' + this.getLanguage().translateOption(role, this.roleField, this.roleFieldScope) + '</span>';
            }
            var lineHtml = '<div>' + '<a href="#' + this.foreignScope + '/view/' + id + '">' + name + '</a> ' + roleHtml + '</div>';
            return lineHtml;
        },

        getValueForDisplay: function () {
            if (this.mode == 'detail' || this.mode == 'list') {
                var names = [];
                this.ids.forEach(function (id) {
                    var lineHtml = this.getDetailLinkHtml(id);

                    names.push(lineHtml);
                }, this);
                return names.join('');
            }
        },

        deleteLink: function (id) {
            this.deleteLinkHtml(id);

            var index = this.ids.indexOf(id);
            if (index > -1) {
                this.ids.splice(index, 1);
            }
            delete this.nameHash[id];
            delete this.columns[id];
            this.trigger('change');
        },

        addLink: function (id, name) {
            if (!~this.ids.indexOf(id)) {
                this.ids.push(id);
                this.nameHash[id] = name;
                this.columns[id] = {};
                this.columns[id][this.columnName] = null;
                this.addLinkHtml(id, name);
            }
            this.trigger('change');
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
        },

        getJQSelect: function (id, roleValue) {
            $role = $('<select class="role form-control input-sm pull-right" data-id="'+id+'">');
            this.roleList.forEach(function (role) {
                var selectedHtml = (role == roleValue) ? 'selected': '';
                option = '<option value="'+role+'" '+selectedHtml+'>' + this.getLanguage().translateOption(role, this.roleField, this.roleFieldScope) + '</option>';
                $role.append(option);
            }, this);

            return $role;
        },

        addLinkHtml: function (id, name) {
            if (this.mode == 'search') {
                return Dep.prototype.addLinkHtml.call(this, id, name);
            }
            var $container = this.$el.find('.link-container');
            var $el = $('<div class="form-inline list-group-item link-with-role">').addClass('link-' + id);

            var nameHtml = '<div>' + name + '&nbsp;' + '</div>';

            var removeHtml = '<a href="javascript:" class="pull-right" data-id="' + id + '" data-action="clearLink"><span class="glyphicon glyphicon-remove"></a>';

            var $role;

            var roleValue = (this.columns[id] || {})[this.columnName];

            roleValue = Handlebars.Utils.escapeExpression(roleValue);

            if (this.roleType == 'enum') {
                $role = this.getJQSelect(id, roleValue);
            } else {
                var label = this.translate(this.roleField, 'fields', this.roleFieldScope);
                $role = $('<input class="role form-control input-sm pull-right" maxlength="50" placeholder="'+label+'" data-id="'+id+'" value="' + (roleValue || '') + '">');
            }

            $left = $('<div class="pull-left">').css({
                'width': '92%',
                'display': 'inline-block'
            });
            $left.append($role);
            $left.append(nameHtml);
            $el.append($left);

            $right = $('<div>').css({
                'width': '8%',
                'display': 'inline-block',
                'vertical-align': 'top'
            });
            $right.append(removeHtml);
            $el.append($right);
            $el.append('<br style="clear: both;" />');

            $container.append($el);

            if (this.mode == 'edit') {
                var fetch = function ($target) {
                    var value = $target.val().toString().trim();
                    var id = $target.data('id');
                    this.columns[id] = this.columns[id] || {};
                    this.columns[id][this.columnName] = value;
                }.bind(this);
                $role.on('change', function (e) {
                    var $target = $(e.currentTarget);
                    fetch($target);
                    this.trigger('change');
                }.bind(this));
                fetch($role);

            }
            return $el;
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);
            data[this.columnsName] = Espo.Utils.cloneDeep(this.columns);
            return data;
        },

    });
});


