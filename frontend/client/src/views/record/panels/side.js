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

Espo.define('Views.Record.Panels.Side', 'View', function (Dep) {

    return Dep.extend({

        template: 'record.panels.side',

        fieldList: null,

        data: function () {
            return {
                fieldList: this.getFieldList(),
                dates: this.dates,
            };
        },

        mode: 'detail',

        actionList: null,

        buttonList: null,

        readOnly: false,

        inlineEditDisabled: false,

        setup: function () {
            this.fieldList = this.options.fieldList || this.fieldList || [];
            this.dates = ('dates' in this.options) ? this.options.dates : false;
            this.mode = this.options.mode || this.mode;
            if (!this.readOnly) {
                if ('readOnly' in this.options) {
                    this.readOnly = this.options.readOnly;
                }
            }
            if (!this.inlineEditDisabled) {
                if ('inlineEditDisabled' in this.options) {
                    this.inlineEditDisabled = this.options.inlineEditDisabled;
                }
            }
            this.createFields();
        },

        getFieldList: function () {
            var fieldList = [];
            this.fieldList.forEach(function (item) {
                var field;
                if (typeof item === 'object') {
                    field = item.name;
                } else {
                   field = item;
                }
                if (field in this.model.defs.fields) {
                    fieldList.push(field);
                }
            }, this);
            return fieldList;
        },

        createField: function (field, readOnly, viewName) {
            var type = this.model.getFieldType(field) || 'base';
            viewName = viewName || this.model.getFieldParam(field, 'view') || this.getFieldManager().getViewName(type);

            var o = {
                model: this.model,
                el: this.options.el + ' .field-' + field,
                defs: {
                    name: field,
                    params: {},
                },
                mode: this.mode
            };
            if (this.readOnly) {
                o.readOnly = true;
            } else {
                if (readOnly !== null) {
                    o.readOnly = readOnly
                }
            }
            if (this.inlineEditDisabled) {
                o.inlineEditDisabled = true;
            }

            this.createView(field, viewName, o);
        },

        createFields: function () {
            this.fieldList.forEach(function (item) {
                var view = null;
                var field;
                var readOnly = null;
                if (typeof item === 'object') {
                    field = item.name;
                    view = item.view;
                    if ('readOnly' in item) {
                        readOnly = item.readOnly;
                    }
                } else {
                   field = item;
                }
                if (!(field in this.model.defs.fields)) {
                    return;
                }
                this.createField(field, readOnly, view);

            }, this);
        },

        getFields: function () {
            var fields = {};

            this.getFieldList().forEach(function (name) {
                fields[name] = this.getView(name);
            }, this);
            return fields;
        },

        getActionList: function () {
            return this.actionList || [];
        },

        getButtonList: function () {
            return this.buttonList || [];
        },

        actionRefresh: function () {
            this.model.fetch();
        }

    });
});

