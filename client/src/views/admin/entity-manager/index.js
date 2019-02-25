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

Espo.define('views/admin/entity-manager/index', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/entity-manager/index',

        scopeDataList: null,

        scope: null,

        data: function () {
            return {
                scopeDataList: this.scopeDataList,
                scope: this.scope,
            };
        },

        events: {
            'click a[data-action="editEntity"]': function (e) {
                var scope = $(e.currentTarget).data('scope');
                this.editEntity(scope);
            },
            'click [data-action="editFormula"]': function (e) {
                var scope = $(e.currentTarget).data('scope');
                this.editFormula(scope);
            },
            'click button[data-action="createEntity"]': function (e) {
                this.createEntity();
            },
            'click [data-action="removeEntity"]': function (e) {
                var scope = $(e.currentTarget).data('scope');
                this.confirm(this.translate('confirmation', 'messages'), function () {
                    this.removeEntity(scope);
                }, this);
            }
        },

        setupScopeData: function () {
            this.scopeDataList = [];

            var scopeList = Object.keys(this.getMetadata().get('scopes')).sort(function (v1, v2) {
                return v1.localeCompare(v2);
            }.bind(this));

            var scopeListSorted = [];

            scopeList.forEach(function (scope) {
                var d = this.getMetadata().get('scopes.' + scope);
                if (d.entity && d.customizable) {
                    scopeListSorted.push(scope);
                }
            }, this);
            scopeList.forEach(function (scope) {
                var d = this.getMetadata().get('scopes.' + scope);
                if (d.entity && !d.customizable) {
                    scopeListSorted.push(scope);
                }
            }, this);

            scopeList = scopeListSorted;

            scopeList.forEach(function (scope) {
                var d = this.getMetadata().get('scopes.' + scope);

                var isRemovable = !!d.isCustom;
                if (d.isNotRemovable) {
                    isRemovable = false;
                }

                this.scopeDataList.push({
                    name: scope,
                    isCustom: d.isCustom,
                    isRemovable: isRemovable,
                    customizable: d.customizable,
                    type: d.type,
                    label: this.getLanguage().translate(scope, 'scopeNames'),
                    layouts: d.layouts
                });

            }, this);
        },

        setup: function () {
            this.scope = this.options.scope || null;

            this.setupScopeData();

        },

        createEntity: function () {
            this.createView('edit', 'views/admin/entity-manager/modals/edit-entity', {}, function (view) {
                view.render();

                this.listenTo(view, 'after:save', function () {
                    this.clearView('edit');
                    this.setupScopeData();
                    this.render();
                }, this);

                this.listenTo(view, 'close', function () {
                    this.clearView('edit');
                }, this);
            }, this);
        },

        editEntity: function (scope) {
            this.createView('edit', 'views/admin/entity-manager/modals/edit-entity', {
                scope: scope
            }, function (view) {
                view.render();

                this.listenTo(view, 'after:save', function () {
                    this.clearView('edit');
                    this.setupScopeData();
                    this.render();
                }, this);

                this.listenTo(view, 'close', function () {
                    this.clearView('edit');
                }, this);
            }, this);
        },

        removeEntity: function (scope) {
            $.ajax({
                url: 'EntityManager/action/removeEntity',
                type: 'POST',
                data: JSON.stringify({
                    name: scope
                })
            }).done(function () {
                this.$el.find('table tr[data-scope="'+scope+'"]').remove();
                this.getMetadata().load(function () {
                    this.getConfig().load(function () {
                        this.setupScopeData();
                        this.render();
                    }.bind(this), true);
                }.bind(this), true);
            }.bind(this));
        },

        editFormula: function (scope) {
            this.createView('edit', 'views/admin/entity-manager/modals/edit-formula', {
                scope: scope
            }, function (view) {
                view.render();

                this.listenTo(view, 'after:save', function () {
                    this.clearView('edit');
                }, this);

                this.listenTo(view, 'close', function () {
                    this.clearView('edit');
                }, this);
            }, this);
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Entity Manager', 'labels', 'Admin'));
        },
    });
});


