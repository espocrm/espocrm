/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/admin/layouts/default-side-panel', ['views/admin/layouts/rows'], function (Dep) {

    return Dep.extend({

        dataAttributeList: ['name', 'view', 'customLabel'],

        dataAttributesDefs: {
            view: {
                type: 'varchar',
                readOnly: true
            },
            customLabel: {
                type: 'varchar',
                readOnly: true
            },
            name: {
                type: 'varchar',
                readOnly: true
            },
        },

        editable: false,

        languageCategory: 'fields',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.loadLayout(function () {
                this.wait(false);
            }.bind(this));
        },

        validate: function () {
            return true;
        },

        loadLayout: function (callback) {
            this.getModelFactory().create(Espo.Utils.hyphenToUpperCamelCase(this.scope), (model) => {
                this.getHelper().layoutManager.getOriginal(this.scope, this.type, this.setId, (layout) => {
                    this.readDataFromLayout(model, layout);

                    if (callback) {
                        callback();
                    }
                });
            });
        },

        readDataFromLayout: function (model, layout) {
            var allFields = [];

            for (let field in model.defs.fields) {
                if (
                    this.checkFieldType(model.getFieldParam(field, 'type')) &&
                    this.isFieldEnabled(model, field)
                ) {
                    allFields.push(field);
                }
            }

            allFields.sort((v1, v2) => {
                return this.translate(v1, 'fields', this.scope)
                    .localeCompare(this.translate(v2, 'fields', this.scope));
            });

            if (~allFields.indexOf('assignedUser')) {
                allFields.unshift(':assignedUser');
            }

            this.enabledFieldsList = [];

            this.enabledFields = [];
            this.disabledFields = [];

            var labelList = [];
            var duplicateLabelList = [];

            for (let i = 0; i < layout.length; i++) {
                let item = layout[i];

                if (typeof item !== 'object') {
                    item = {
                        name: item,
                    };
                }

                let realName = item.name;

                if (realName.indexOf(':') === 0)
                    realName = realName.substr(1);

                let label = this.getLanguage().translate(realName, 'fields', this.scope);

                if (realName !== item.name) {
                    label = label + ' *';
                }

                if (~labelList.indexOf(label)) {
                    duplicateLabelList.push(label);
                }

                labelList.push(label);

                this.enabledFields.push({
                    name: item.name,
                    label: label,
                });

                this.enabledFieldsList.push(item.name);
            }

            for (let i = 0; i < allFields.length; i++) {
                if (!_.contains(this.enabledFieldsList, allFields[i])) {
                    let label = this.getLanguage().translate(allFields[i], 'fields', this.scope);

                    if (~labelList.indexOf(label)) {
                        duplicateLabelList.push(label);
                    }

                    labelList.push(label);

                    let fieldName = allFields[i];
                    let realName = fieldName;

                    if (realName.indexOf(':') === 0)
                        realName = realName.substr(1);

                    label = this.getLanguage().translate(realName, 'fields', this.scope);

                    if (realName !== fieldName) {
                        label = label + ' *';
                    }

                    let o = {
                        name: fieldName,
                        label: label,
                    };

                    let fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', fieldName, 'type']);

                    if (fieldType) {
                        if (this.getMetadata().get(['fields', fieldType, 'notSortable'])) {
                            o.notSortable = true;
                        }
                    }

                    this.disabledFields.push(o);
                }
            }

            this.enabledFields.forEach(item =>  {
                if (~duplicateLabelList.indexOf(item.label)) {
                    item.label += ' (' + item.name + ')';
                }
            });

            this.disabledFields.forEach(item => {
                if (~duplicateLabelList.indexOf(item.label)) {
                    item.label += ' (' + item.name + ')';
                }
            });

            this.rowLayout = layout;

            for (let i in this.rowLayout) {
                var label = this.getLanguage().translate(this.rowLayout[i].name, 'fields', this.scope);

                this.enabledFields.forEach(item => {
                    if (item.name === this.rowLayout[i].name) {
                        label = item.label;
                    }
                });

                this.rowLayout[i].label = label;

                this.itemsData[this.rowLayout[i].name] = Espo.Utils.cloneDeep(this.rowLayout[i]);
            }
        },

        checkFieldType: function (type) {
            return true;
        },

        isFieldEnabled: function (model, name) {
            if (~['modifiedAt', 'createdAt', 'modifiedBy', 'createdBy'].indexOf(name)) {
                return false;
            }

            let layoutList = model.getFieldParam(name, 'layoutAvailabilityList');

            if (layoutList && !~layoutList.indexOf(this.type)) {
                return false;
            }

            if (
                model.getFieldParam(name, 'disabled') ||
                model.getFieldParam(name, 'utility')
            ) {
                return false;
            }

            if (model.getFieldParam(name, 'layoutDefaultSidePanelDisabled')) {
                return false;
            }

            if (model.getFieldParam(name, 'layoutDetailDisabled')) {
                return false;
            }

            return true;
        },
    });
});
