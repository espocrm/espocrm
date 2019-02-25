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

Espo.define('views/admin/field-manager/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/field-manager/index',

        scopeList: null,

        scope: null,

        type: null,

        data: function () {
            return {
                scopeList: this.scopeList,
                scope: this.scope,
            };
        },

        events: {
            'click #scopes-menu a.scope-link': function (e) {
                var scope = $(e.currentTarget).data('scope');
                this.openScope(scope);
            },

            'click #fields-content a.field-link': function (e) {
                e.preventDefault();
                var scope = $(e.currentTarget).data('scope');
                var field = $(e.currentTarget).data('field');
                this.openField(scope, field);
            },

            'click a[data-action="addField"]': function (e) {
                var scope = $(e.currentTarget).data('scope');
                var type = $(e.currentTarget).data('type');
                this.createField(scope, type);
            },
        },

        setup: function () {
            this.scopeList = [];

            var scopesAll = Object.keys(this.getMetadata().get('scopes')).sort(function (v1, v2) {
                return this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural'));
            }.bind(this));

            scopesAll.forEach(function (scope) {
                if (this.getMetadata().get('scopes.' + scope + '.entity')) {
                    if (this.getMetadata().get('scopes.' + scope + '.customizable')) {
                        this.scopeList.push(scope);
                    }
                }
            }.bind(this));

            this.scope = this.options.scope || null;
            this.field = this.options.field || null;

            this.on('after:render', function () {
                if (!this.scope) {
                    this.renderDefaultPage();
                } else {
                    if (!this.field) {
                        this.openScope(this.scope);
                    } else {
                        this.openField(this.scope, this.field);
                    }
                }
            });
        },

        openScope: function (scope) {
            this.scope = scope;
            this.field = null;

            this.getRouter().navigate('#Admin/fieldManager/scope=' + scope, {trigger: false});

            this.notify('Loading...');
            this.createView('content', 'views/admin/field-manager/list', {
                el: '#fields-content',
                scope: scope,
            }, function (view) {
                view.render();
                this.notify(false);
                $(window).scrollTop(0);
            }.bind(this));
        },

        openField: function (scope, field) {
            this.scope = scope;
            this.field = field

            this.getRouter().navigate('#Admin/fieldManager/scope=' + scope + '&field=' + field, {trigger: false});

            this.notify('Loading...');
            this.createView('content', 'views/admin/field-manager/edit', {
                el: '#fields-content',
                scope: scope,
                field: field,
            }, function (view) {
                view.render();
                this.notify(false);
                $(window).scrollTop(0);

                this.listenTo(view, 'after:save', function () {
                    this.notify('Saved', 'success');
                }, this);
            }.bind(this));
        },

        createField: function (scope, type) {
            this.scope = scope;
            this.type = type;

            this.getRouter().navigate('#Admin/fieldManager/scope=' + scope + '&type=' + type + '&create=true', {trigger: false});

            this.notify('Loading...');
            this.createView('content', 'Admin.FieldManager.Edit', {
                el: '#fields-content',
                scope: scope,
                type: type,
            }, function (view) {
                view.render();
                this.notify(false);
                $(window).scrollTop(0);

                view.once('after:save', function () {
                    this.openScope(this.scope);
                    this.notify('Created', 'success');
                }, this);
            }.bind(this));
        },

        renderDefaultPage: function () {
            $('#fields-content').html(this.translate('selectEntityType', 'messages', 'Admin'));
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Field Manager', 'labels', 'Admin'));
        },
    });
});


