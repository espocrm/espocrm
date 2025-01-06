/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import LayoutRowsView from 'views/admin/layouts/rows';

class LayoutListView extends LayoutRowsView {

    dataAttributeList = [
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
    ]

    dataAttributesDefs = {
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
    }

    dataAttributesDynamicLogicDefs = {
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
    }

    editable = true
    languageCategory = 'fields'
    ignoreList = []
    ignoreTypeList = []

    /**
     * @protected
     * @type {number}
     */
    defaultWidth = 16

    setup() {
        super.setup();

        this.wait(
            new Promise(resolve => this.loadLayout(() => resolve()))
        );
    }

    /**
     * @inheritDoc
     */
    loadLayout(callback) {
        this.getModelFactory().create(Espo.Utils.hyphenToUpperCamelCase(this.scope), model => {
            this.getHelper().layoutManager.getOriginal(this.scope, this.type, this.setId, layout => {
                this.readDataFromLayout(model, layout);

                callback();
            });
        });
    }

    readDataFromLayout(model, layout) {
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

        for (const item of layout) {
            const label = this.getLanguage().translate(item.name, 'fields', this.scope);

            if (labelList.includes(label)) {
                duplicateLabelList.push(label);
            }

            labelList.push(label);

            this.enabledFields.push({
                name: item.name,
                labelText: label,
            });

            this.enabledFieldsList.push(item.name);
        }

        for (const field of allFields) {
            if (this.enabledFieldsList.includes(field)) {
                continue;
            }

            const label = this.getLanguage().translate(field, 'fields', this.scope);

            if (labelList.includes(label)) {
                duplicateLabelList.push(label);
            }

            labelList.push(label);

            const fieldName = field;

            const item = {
                name: fieldName,
                labelText: label,
            };

            const fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', fieldName, 'type']);

            this.itemsData[fieldName] = this.itemsData[fieldName] || {};

            if (fieldType && this.getMetadata().get(`fields.${fieldType}.notSortable`)) {
                item.notSortable = true;
                this.itemsData[fieldName].notSortable = true;
            }

            item.width = this.defaultWidth;
            this.itemsData[fieldName].width = this.defaultWidth;

            this.disabledFields.push(item);
        }

        this.enabledFields.forEach(item => {
            if (duplicateLabelList.includes(item.labelText)) {
                item.labelText += ' (' + item.name + ')';
            }
        });

        this.disabledFields.forEach(item => {
            if (duplicateLabelList.includes(item.labelText)) {
                item.labelText += ' (' + item.name + ')';
            }

            //item.width = this.defaultWidth;
        });

        this.rowLayout = layout;

        for (const it of this.rowLayout) {
            let label = this.getLanguage().translate(it.name, 'fields', this.scope);

            this.enabledFields.forEach(item => {
                if (it.name === item.name) {
                    label = item.labelText;
                }
            });

            it.labelText = label;
            this.itemsData[it.name] = Espo.Utils.cloneDeep(it);
        }
    }

    // noinspection JSUnusedLocalSymbols
    checkFieldType(type) {
        return true;
    }

    isFieldEnabled(model, name) {
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

        const layoutIgnoreList = model.getFieldParam(name, 'layoutIgnoreList') || [];

        if (layoutIgnoreList.includes(realType) || layoutIgnoreList.includes(this.type)) {
            return false;
        }

        return !model.getFieldParam(name, 'disabled') &&
            !model.getFieldParam(name, 'utility') &&
            !model.getFieldParam(name, 'layoutListDisabled');
    }
}

export default LayoutListView;
