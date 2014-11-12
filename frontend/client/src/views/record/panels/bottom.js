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

Espo.define('Views.Record.Panels.Bottom', 'View', function (Dep) {

    return Dep.extend({
    
        actions: null,
        
        defs: null,            
        
        events: {
            'click .action': function (e) {            
                $el = $(e.currentTarget);
                var action = $el.data('action');
                var method = 'action' + Espo.Utils.upperCaseFirst(action);
                if (typeof this[method] == 'function') {
                    this[method]($el.data('id'));
                }
            }
        },
        
        data: function () {
            return {
                scope: this.scope,
                name: this.panelName,
            };
        },
        
        init: function () {
            this.panelName = this.options.panelName;
            this.defs = this.options.defs || {};
        },
        
        getButtons: function () {
            return [];
        },            
        
        getActions: function () {
            return [];
        },
        
    });
});

