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

Espo.define('Views.Record.Panels.Side', 'View', function (Dep) {

    return Dep.extend({

        template: 'record.panels.side',

        fields: null,

        data: function () {
            return {
                fields: this.getFieldList(),
                dates: this.dates,
            };
        },

        mode: 'detail',
        
        actions: null,
        
        readOnly: false,

        setup: function () {                
            this.fields = this.options.fields || this.fields || [];                
            this.dates = ('dates' in this.options) ? this.options.dates : false;
            this.mode = this.options.mode || this.mode;            
            if ('readOnly' in this.options)    {
                this.readOnly = this.options.readOnly;
            }
            this.createFields();                
        },
        
        getFieldList: function () {
            var fields = [];
            this.fields.forEach(function (field) {
                if (field in this.model.defs.fields) {
                    fields.push(field);
                }
            }, this);
            return fields;
        },

        createField: function (field, readOnly) {
            var type = this.model.getFieldType(field) || 'base';
            var viewName = this.model.getFieldParam(field, 'view') || this.getFieldManager().getViewName(type);
            this.createView(field, viewName, {
                model: this.model,
                el: this.options.el + ' .field-' + field,
                defs: {
                    name: field,
                    params: {},
                },
                mode: this.mode,
                readOnly: (typeof readOnly !== 'undefined') ? readOnly : this.readOnly
            });
        },

        createFields: function () {
            this.fields.forEach(function (field) {
                this.createField(field);
            }.bind(this));
        },

        getFields: function () {
            var fields = {};
            this.fields.forEach(function (name) {
                fields[name] = this.getView(name);
            }.bind(this));
            return fields;
        },
        
        getActions: function () {
            return this.actions || [];
        },
    });
});

