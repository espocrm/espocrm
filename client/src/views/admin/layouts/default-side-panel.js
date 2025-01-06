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

class LayoutDefaultSidePanel extends LayoutRowsView {

    dataAttributeList = ['name', 'view', 'customLabel']

    dataAttributesDefs = {
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
    }

    editable = false

    languageCategory = 'fields'

    setup() {
        super.setup();

        this.wait(true);

        this.loadLayout(() => {
            this.wait(false);
        });
    }

    validate() {
        return true;
    }

    loadLayout(callback) {
        this.getModelFactory().create(Espo.Utils.hyphenToUpperCamelCase(this.scope), (model) => {
            this.getHelper().layoutManager.getOriginal(this.scope, this.type, this.setId, (layout) => {
                this.readDataFromLayout(model, layout);

                if (callback) {
                    callback();
                }
            });
        });
    }

    readDataFromLayout(model, layout) {
        const allFields = [];

        for (const field in model.defs.fields) {
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

        const labelList = [];
        const duplicateLabelList = [];

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
                labelText: label,
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

                const fieldName = allFields[i];
                let realName = fieldName;

                if (realName.indexOf(':') === 0)
                    realName = realName.substr(1);

                label = this.getLanguage().translate(realName, 'fields', this.scope);

                if (realName !== fieldName) {
                    label = label + ' *';
                }

                const o = {
                    name: fieldName,
                    labelText: label,
                };

                const fieldType = this.getMetadata().get(['entityDefs', this.scope, 'fields', fieldName, 'type']);

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
                item.labelText += ' (' + item.name + ')';
            }
        });

        this.disabledFields.forEach(item => {
            if (~duplicateLabelList.indexOf(item.label)) {
                item.labelText += ' (' + item.name + ')';
            }
        });

        this.rowLayout = layout;

        for (const i in this.rowLayout) {
            let label = this.getLanguage().translate(this.rowLayout[i].name, 'fields', this.scope);

            this.enabledFields.forEach(item => {
                if (item.name === this.rowLayout[i].name) {
                    label = item.labelText;
                }
            });

            this.rowLayout[i].labelText = label;

            this.itemsData[this.rowLayout[i].name] = Espo.Utils.cloneDeep(this.rowLayout[i]);
        }
    }

    // noinspection JSUnusedLocalSymbols
    checkFieldType(type) {
        return true;
    }

    isFieldEnabled(model, name) {
        if (~['modifiedAt', 'createdAt', 'modifiedBy', 'createdBy'].indexOf(name)) {
            return false;
        }

        const layoutList = model.getFieldParam(name, 'layoutAvailabilityList');

        if (layoutList && !layoutList.includes(this.type)) {
            return false;
        }

        const layoutIgnoreList = model.getFieldParam(name, 'layoutIgnoreList') || [];

        if (layoutIgnoreList.includes(this.type)) {
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
    }
}

export default LayoutDefaultSidePanel;
