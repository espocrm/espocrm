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

Espo.define('Views.Header', 'View', function (Dep) {

    return Dep.extend({

        template: 'header',

        data: function () {
            var data = {};
            if ('getHeader' in this.getParentView()) {
                data.header = this.getParentView().getHeader();
            }
            data.scope = this.getParentView().scope;
            data.items = this.getItems();
            return data;
        },
        
        afterRender: function () {
            if (this.model) {    
                this.listenTo(this.model, 'after:save', function () {
                    this.render();
                }.bind(this));
            }
        },

        getItems: function () {
            var name = this.getParentView().name || null;
            var scope = this.getParentView().scope;

            var items = {};

            if (name) {
                var types = ['buttons', 'dropdown'];
                var menu = this.getParentView().getMenu() || {};

                types.forEach(function (type) {
                    var filtered = [];
                    if (menu[type]) {
                        menu[type].forEach(function (item, i) {
                            var hasAccess = true;
                            if (item.acl) {
                                if (this.model && !item.aclScope) {
                                    if (!this.getAcl().checkModel(this.model, item.acl)) {
                                        hasAccess = false;
                                    }
                                } else {
                                    if (!this.getAcl().check(item.aclScope || scope, item.acl)) {
                                        hasAccess = false;
                                    }
                                }
                            }
                            if (hasAccess) {
                                filtered.push(item);
                            }
                        }.bind(this));
                        items[type] = filtered;
                    }
                }.bind(this));

                if (name != 'default') {
                    var defaultMenu = this.getMetadata().get('clientDefs.' + scope + '.menu.default') || {};
                    types.forEach(function (type) {
                        if (defaultMenu[type]) {
                            if (!items[type]) {
                                items[type] = [];
                            }
                            defaultMenu[type].forEach(function (i) {
                                items[type].push(i);
                            });
                        }
                    }.bind(this));
                }
            }
            return items;
        },
    });
});

