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

Espo.define('Views.Stream.Message', 'View', function (Dep) {

    return Dep.extend({
    
        setup: function () {
            var template = this.options.messageTemplate; 
            var data = this.options.messageData;
            
            for (var key in data) {
                var value = data[key] || '';
                
                if (value.indexOf('field:') === 0) {
                    var field = value.substr(6);
                    this.createField(key, field);
                    
                    template = template.replace('{' + key +'}', '{{{' + key +'}}}');                
                } else {
                    template = template.replace('{' + key +'}', value);
                }
            }
            
            this._template = template;        
        },
    
        createField: function (key, name, type, params) {            
            type = type || this.model.getFieldType(name) || 'base';        
            this.createView(key, this.getFieldManager().getViewName(type), {
                model: this.model,
                defs: {
                    name: name,
                    params: params || {}
                },
                mode: 'list'
            });
        },
        

    });
});

