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

Espo.define('Views.Main', 'View', function (Dep) {

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
                        this[method].call(this, data);
                    }
                }
            },
        },

        init: function () {
            this.scope = this.options.scope || this.scope;
            this.menu = {};
            
            if (this.name && this.scope) {                    
                var menu = this.getMetadata().get('clientDefs.' + this.scope + '.menu.' + this.name.charAt(0).toLowerCase() + this.name.slice(1)) || {};
                this.menu = _.clone(menu);
            }
            
            this.menu.buttons = [];
            this.menu.dropdown = [];                
            
            if (menu) {                
                (menu.buttons || []).forEach(function (item) {
                    if (Espo.Utils.checkActionAccess(this.getAcl(), this.model, item)) {
                        this.menu.buttons.push(item);
                    }
                }, this);
            
                (menu.dropdown || []).forEach(function (item) {
                    if (Espo.Utils.checkActionAccess(this.getAcl(), this.model, item)) {
                        this.menu.dropdown.push(item);
                    }
                }, this);
            }
        },

        getMenu: function () {
            return this.menu;
        },

        getHeader: function () {},
    });
});


