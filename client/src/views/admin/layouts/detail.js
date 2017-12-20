/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/admin/layouts/detail', 'views/admin/layouts/grid', function (Dep) {

    return Dep.extend({

        dataAttributeList: ['name', 'fullWidth', 'customLabel', 'noLabel'],

        dataAttributesDefs: {
            fullWidth: {
                type: 'bool'
            },
            name: {
                readOnly: true
            },
            customLabel: {
                type: 'varchar',
                readOnly: true
            },
            noLabel: {
                type: 'bool',
                readOnly: true
            }
        },

        ignoreList: ['modifiedAt', 'createdAt', 'modifiedBy', 'createdBy', 'assignedUser', 'teams'],

        setup: function () {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.loadLayout(function () {

                this.setupPanels();
                this.wait(false);
            }.bind(this));
        },

        loadLayout: function (callback) {
            this.getModelFactory().create(this.scope, function (model) {
                this.getHelper().layoutManager.get(this.scope, this.type, function (layout) {
                    this.readDataFromLayout(model, layout);
                    if (callback) {
                        callback();
                    }
                }.bind(this), false);
            }.bind(this));
        },

        readDataFromLayout: function (model, layout) {
            var allFields = [];
            for (var field in model.defs.fields) {
                if (this.isFieldEnabled(model, field)) {
                    allFields.push(field);
                }
            }

            this.enabledFields = [];
            this.disabledFields = [];

            this.panels = layout;

            layout.forEach(function (panel) {
                panel.rows.forEach(function (row) {
                    row.forEach(function (cell, i) {
                        if (i == this.columnCount) {
                            return;
                        }
                        this.enabledFields.push(cell.name);
                    }.bind(this));
                }.bind(this));
            }.bind(this));

            allFields.sort(function (v1, v2) {
                return this.translate(v1, 'fields', this.scope).localeCompare(this.translate(v2, 'fields', this.scope));
            }.bind(this));


            for (var i in allFields) {
                if (!_.contains(this.enabledFields, allFields[i])) {
                    this.disabledFields.push(allFields[i]);
                }
            }
        },

        isFieldEnabled: function (model, name) {
            if (this.ignoreList.indexOf(name) != -1) {
                return false;
            }
            return !model.getFieldParam(name, 'disabled') && !model.getFieldParam(name, 'layoutDetailDisabled');
        }
    });
});

