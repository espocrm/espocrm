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
 ************************************************************************/

Espo.define('views/main', 'view', function (Dep) {

    return Dep.extend({

        el: '#main',

        scope: null,

        name: null,

        menu: null,

        events: {
            'click .action': function (e) {
                var $target = $(e.currentTarget);
                var action = $target.data('action');
                var data = $target.data();
                if (action) {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);
                    if (typeof this[method] == 'function') {
                        e.preventDefault();
                        this[method].call(this, data, e);
                    }
                }
            },
        },

        init: function () {
            this.scope = this.options.scope || this.scope;
            this.menu = {};

            if (this.name && this.scope) {
                this.menu = this.getMetadata().get('clientDefs.' + this.scope + '.menu.' + this.name.charAt(0).toLowerCase() + this.name.slice(1)) || {};
            }

            this.menu = Espo.Utils.cloneDeep(this.menu);

            ['buttons', 'actions', 'dropdown'].forEach(function (type) {
                this.menu[type] = this.menu[type] || [];
            }, this);
        },

        getMenu: function () {
            var menu = {};

            if (this.menu) {
                ['buttons', 'actions', 'dropdown'].forEach(function (type) {
                    (this.menu[type] || []).forEach(function (item) {
                        menu[type] = menu[type] || [];
                        if (Espo.Utils.checkActionAccess(this.getAcl(), this.model || this.scope, item)) {
                            menu[type].push(item);
                        }
                    }, this);
                }, this);
            }

            return menu;
        },

        getHeader: function () {},

        buildHeaderHtml: function (arr) {
            var a = [];
            arr.forEach(function (item) {
                a.push('<div class="pull-left">' + item + '</div>');
            }, this);

            return '<div class="clearfix">' + a.join('<div class="pull-left breadcrumb-separator"> &raquo </div>') + '</div>';
        },

        actionShowModal: function (data) {
            var view = data.view;
            if (!view) {
                return;
            };
            this.createView('modal', view, {
                model: this.model,
                collection: this.collection
            }, function (view) {
                view.render();
                this.listenTo(view, 'after:save', function () {
                    if (this.model) {
                        this.model.fetch();
                    }
                    if (this.collection) {
                        this.collection.fetch();
                    }
                }, this);
            }.bind(this));
        },

        removeMenuItem: function (name) {
            var index = -1;
            var type = false;

            ['actions', 'dropdown', 'buttons'].forEach(function (t) {
                this.menu[t].forEach(function (item, i) {
                    if (item.action == name) {
                        index = i;
                        type = t;
                    }
                }, this);
            }, this);

            if (~index && type) {
                this.menu[type].splice(index, 1);
            }

            if (this.isRendered()) {
                this.getView('header').render();
            }
        },

        actionNavigateToRoot: function (data, e) {
            e.stopPropagation();
            var options = {
                isReturn: true
            };
            this.getRouter().dispatch(this.scope, null, options);
            this.getRouter().navigate('#' + this.scope, {trigger: false});
        }

    });
});


