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

define('views/admin/layouts/list', ['views/admin/layouts/rows'], function (Dep) {

    return Dep.extend({

        dataAttributeList: [
            'name',
            'widthComplex',
            'width',
            'widthPx',
            'link',
            'notSortable',
            'noLabel',
            'align',
            'view',
            'customLabel',
            'label',
            'hidden',
        ],

        dataAttributesDefs: {
            widthComplex: {
                label: 'width',
                type: 'base',
                view: 'views/admin/layouts/fields/width-complex',
                tooltip: 'width',
                notStorable: true,
            },
            link: {
                type: 'bool',
                tooltip: true,
            },
            width: {
                type: 'float',
                min: 0,
                max: 100,
                hidden: true,
            },
            widthPx: {
                type: 'int',
                min: 0,
                max: 720,
                hidden: true,
            },
            notSortable: {
                type: 'bool',
                tooltip: true,
            },
            align: {
                type: 'enum',
                options: ['left', 'right'],
            },
            view: {
                type: 'varchar',
                readOnly: true,
            },
            noLabel: {
                type: 'bool',
                tooltip: true,
            },
            customLabel: {
                type: 'varchar',
                readOnly: true,
            },
            name: {
                type: 'varchar',
                readOnly: true,
            },
            label: {
                type: 'varchar',
                readOnly: true,
            },
            hidden: {
                type: 'bool',
            },
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
            const allFields = [];

            for (const field in model.defs.fields) {
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

            const labelList = [];
            const duplicateLabelList = [];

            for (const i in layout) {
                const label = this.getLanguage().translate(layout[i].name, 'fields', this.scope);

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

            for (const i in allFields) {
                if (!_.contains(this.enabledFieldsList, allFields[i])) {
                    const label = this.getLanguage().translate(allFields[i], 'fields', this.scope);

                    if (~labelList.indexOf(label)) {

                        duplicateLabelList.push(label);
                    }

                    labelList.push(label);

                    const fieldName = allFields[i];

                    const o = {
                        name: fieldName,
                        label: label,
                    };

                    const fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', fieldName, 'type']);

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

            for (const i in this.rowLayout) {
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

        // noinspection JSUnusedLocalSymbols
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

            /** @type {string[]|null} */
            const layoutList = model.getFieldParam(name, 'layoutAvailabilityList');

            let realType = this.realType;

            if (realType === 'listSmall') {
                realType = 'list';
            }

            if (
                layoutList &&
                !layoutList.includes(this.type) &&
                !layoutList.includes(realType)
            ) {
                return false;
            }

            return !model.getFieldParam(name, 'disabled') &&
                !model.getFieldParam(name, 'utility') &&
                !model.getFieldParam(name, 'layoutListDisabled');
        },
    });
});
