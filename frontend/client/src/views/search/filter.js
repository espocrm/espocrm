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

Espo.define('Views.Search.Filter', 'View', function (Dep) {

    return Dep.extend({

        template: 'search.filter',
        
        data: function () {
            return {
                name: this.name,
                scope: this.model.name,
                notRemovable: this.options.notRemovable
            };
        },            
        
        setup: function () {
            var name = this.name = this.options.name;                
            var type = this.model.getFieldType(name);
            
            if (type) {            
                var viewName = this.model.getFieldParam(name, 'view') || 'Fields.' + Espo.Utils.upperCaseFirst(type);
            
                this.createView('field', viewName, {
                    mode: 'search',
                    model: this.model,
                    el: this.options.el + ' .field',
                    defs: {
                        name: name,                        
                    },
                    searchParams: this.options.params,
                });
            }
        },
    });    
});

