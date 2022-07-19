/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
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

define('views/admin/layouts/list', ['views/admin/layouts/rows'], function (Dep) {

    return Dep.extend({

        dataAttributeList: [
            'name',
            'width',
            'widthPx',
            'link',
            'notSortable',
            'noLabel',
            'align',
            'view',
            'customLabel',
        ],

        dataAttributesDefs: {
            link: {
                type: 'bool',
                tooltip: true
            },
            width: {
                type: 'float',
                min: 0,
                max: 100,
                tooltip: true
            },
            widthPx: {
                type: 'int',
                min: 0,
                max: 512,
                tooltip: true
            },
            notSortable: {
                type: 'bool',
                tooltip: true
            },
            align: {
                type: 'enum',
                options: ["left", "right"]
            },
            view: {
                type: 'varchar',
                readOnly: true
            },
            noLabel: {
                type: 'bool',
                tooltip: true
            },
            customLabel: {
                type: 'varchar',
                readOnly: true
            },
            name: {
                type: 'varchar',
                readOnly: true
            }
        },

        dataAttributesDynamicLogicDefs: {
            fields: {
                widthPx: {
                    visible: {
                        conditionGroup: [
                            {
                                attribute: 'width',
                                type: 'isEmpty',
                            }
                        ]
                    }
                },
            }
        },

        editable: true,

        languageCategory: 'fields',

        ignoreList: [],

        ignoreTypeList: [],

        setup: function () {
            Dep.prototype.setup.call(this);

            this.wait(true);

            this.loadLayout(() => {
                this.wait(false);
            });
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
                if (this.checkFieldType(model.getFieldParam(field, 'type')) && this.isFieldEnabled(model, field)) {

                    allFields.push(field);
                }
            }

            allFields.sort((v1, v2) => {
                return this.translate(v1, 'fields', this.scope)
                    .localeCompare(this.translate(v2, 'fields', this.scope));
            });

            this.enabledFieldsList = [];

            this.enabledFields = [];
            this.disabledFields = [];

            var labelList = [];
            var duplicateLabelList = [];

            for (let i in layout) {
                let label = this.getLanguage().translate(layout[i].name, 'fields', this.scope);

                if (~labelList.indexOf(label)) {
                    duplicateLabelList.push(label);
                }

                labelList.push(label);

                this.enabledFields.push({
                    name: layout[i].name,
                    label: label,
                });

                this.enabledFieldsList.push(layout[i].name);
            }

            for (let i in allFields) {
                if (!_.contains(this.enabledFieldsList, allFields[i])) {
                    let label = this.getLanguage().translate(allFields[i], 'fields', this.scope);

                    if (~labelList.indexOf(label)) {

                        duplicateLabelList.push(label);
                    }

                    labelList.push(label);

                    let fieldName = allFields[i];

                    let o = {
                        name: fieldName,
                        label: label,
                    };

                    let fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', fieldName, 'type']);

                    if (fieldType) {
                        if (this.getMetadata().get(['fields', fieldType, 'notSortable'])) {
                            o.notSortable = true;

                            this.itemsData[fieldName] = this.itemsData[fieldName] || {};
                            this.itemsData[fieldName].notSortable = true;
                        }
                    }

                    this.disabledFields.push(o);
                }
            }

            this.enabledFields.forEach(item => {
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
                let label = this.getLanguage().translate(this.rowLayout[i].name, 'fields', this.scope);

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
            if (this.ignoreList.indexOf(name) !== -1) {
                return false;
            }

            if (this.ignoreTypeList.indexOf(model.getFieldParam(name, 'type')) !== -1) {
                return false;
            }

            var layoutList = model.getFieldParam(name, 'layoutAvailabilityList');

            if (layoutList && !~layoutList.indexOf(this.type)) {
                return;
            }

            return !model.getFieldParam(name, 'disabled') &&
                !model.getFieldParam(name, 'layoutListDisabled');
        },
    });
});
