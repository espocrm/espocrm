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

import LayoutGridView from 'views/admin/layouts/grid';

class LayoutDetailView extends LayoutGridView {

    dataAttributeList = [
        'name',
        'fullWidth',
        'customLabel',
        'noLabel',
    ]

    panelDataAttributeList = [
        'panelName',
        'dynamicLogicVisible',
        'style',
        'dynamicLogicStyled',
        'tabBreak',
        'tabLabel',
        'hidden',
        'noteText',
        'noteStyle',
    ]

    dataAttributesDefs = {
        fullWidth: {
            type: 'bool',
        },
        name: {
            readOnly: true,
        },
        label: {
            type: 'varchar',
            readOnly: true,
        },
        customLabel: {
            type: 'varchar',
            readOnly: true,
        },
        noLabel: {
            type: 'bool',
            readOnly: true,
        },
    }

    panelDataAttributesDefs = {
        panelName: {
            type: 'varchar',
        },
        style: {
            type: 'enum',
            options: [
                'default',
                'success',
                'danger',
                'warning',
                'info',
                'primary',
            ],
            style: {
                'info': 'info',
                'success': 'success',
                'danger': 'danger',
                'warning': 'warning',
                'primary': 'primary',
            },
            default: 'default',
            translation: 'LayoutManager.options.style',
            tooltip: 'panelStyle',
        },
        dynamicLogicVisible: {
            type: 'base',
            view: 'views/admin/field-manager/fields/dynamic-logic-conditions'
        },
        dynamicLogicStyled: {
            type: 'base',
            view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
            tooltip: 'dynamicLogicStyled',
        },
        hidden: {
            type: 'bool',
            tooltip: 'hiddenPanel',
        },
        tabBreak: {
            type: 'bool',
            tooltip: 'tabBreak',
        },
        tabLabel: {
            type: 'varchar',
        },
        noteText: {
            type: 'text',
            tooltip: 'noteText',
        },
        noteStyle: {
            type: 'enum',
            options: [
                'info',
                'success',
                'danger',
                'warning',
                'primary',
            ],
            style: {
                'info': 'info',
                'success': 'success',
                'danger': 'danger',
                'warning': 'warning',
                'primary': 'primary',
            },
            default: 'info',
            translation: 'LayoutManager.options.style',
        },
    }

    defaultPanelFieldList = [
        'modifiedAt',
        'createdAt',
        'modifiedBy',
        'createdBy',
    ]

    panelDynamicLogicDefs = {
        fields: {
            tabLabel: {
                visible: {
                    conditionGroup: [
                        {
                            attribute: 'tabBreak',
                            type: 'isTrue',
                        }
                    ]
                }
            },
            dynamicLogicStyled: {
                visible: {
                    conditionGroup: [
                        {
                            attribute: 'style',
                            type: 'notEquals',
                            value: 'default'
                        }
                    ]
                }
            },
            noteStyle: {
                visible: {
                    conditionGroup: [
                        {
                            attribute: 'noteText',
                            type: 'isNotEmpty',
                        }
                    ]
                }
            },
        }
    }

    setup() {
        super.setup();

        this.panelDataAttributesDefs = Espo.Utils.cloneDeep(this.panelDataAttributesDefs);

        this.panelDataAttributesDefs.dynamicLogicVisible.scope = this.scope;
        this.panelDataAttributesDefs.dynamicLogicStyled.scope = this.scope;

        this.wait(true);

        this.loadLayout(() => {
            this.setupPanels();
            this.wait(false);
        });
    }

    loadLayout(callback) {
        let layout;
        let model;

        const promiseList = [];

        promiseList.push(
            new Promise(resolve => {
                this.getModelFactory().create(this.scope, (m) => {
                    this.getHelper()
                        .layoutManager
                        .getOriginal(this.scope, this.type, this.setId, (layoutLoaded) => {
                            layout = layoutLoaded;
                            model = m;
                            resolve();
                        });
                });
            })
        );

        if (['detail', 'detailSmall'].includes(this.type)) {
            promiseList.push(
                new Promise(resolve => {
                    this.getHelper().layoutManager.getOriginal(
                        this.scope, 'sidePanels' + Espo.Utils.upperCaseFirst(this.type),
                        this.setId,
                        layoutLoaded => {
                            this.sidePanelsLayout = layoutLoaded;

                            resolve();
                        }
                    );
                })
            );
        }

        promiseList.push(
            new Promise(resolve => {
                if (this.getMetadata().get(['clientDefs', this.scope, 'layoutDefaultSidePanelDisabled'])) {
                    resolve();

                    return;
                }

                if (this.typeDefs.allFields) {
                    resolve();

                    return;
                }

                this.getHelper().layoutManager.getOriginal(
                    this.scope,
                    'defaultSidePanel',
                    this.setId,
                    layoutLoaded => {
                        this.defaultPanelFieldList = Espo.Utils.clone(this.defaultPanelFieldList);

                        layoutLoaded.forEach(item => {
                            let field = item.name;

                            if (!field) {
                                return;
                            }

                            if (field === ':assignedUser') {
                                field = 'assignedUser';
                            }

                            if (!this.defaultPanelFieldList.includes(field)) {
                                this.defaultPanelFieldList.push(field);
                            }
                        });

                        resolve();
                    }
                );
            })
        );

        Promise.all(promiseList).then(() => {
            this.readDataFromLayout(model, layout);

            if (callback) {
                callback();
            }
        });
    }

    readDataFromLayout(model, layout) {
        const allFields = [];

        for (const field in model.defs.fields) {
            if (this.isFieldEnabled(model, field)) {
                allFields.push(field);
            }
        }

        this.enabledFields = [];
        this.disabledFields = [];

        this.panels = layout;

        layout.forEach((panel) => {
            panel.rows.forEach((row) => {
                row.forEach(cell => {
                    this.enabledFields.push(cell.name);
                });
            });
        });

        allFields.sort((v1, v2) => {
            return this.translate(v1, 'fields', this.scope)
                .localeCompare(this.translate(v2, 'fields', this.scope));
        });

        for (const i in allFields) {
            if (!_.contains(this.enabledFields, allFields[i])) {
                this.disabledFields.push(allFields[i]);
            }
        }
    }

    isFieldEnabled(model, name) {
        if (this.hasDefaultPanel()) {
            if (this.defaultPanelFieldList.includes(name)) {
                return false;
            }
        }

        const layoutList = model.getFieldParam(name, 'layoutAvailabilityList');

        let realType = this.realType;

        if (realType === 'detailSmall') {
            realType = 'detail';
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
            !model.getFieldParam(name, 'layoutDetailDisabled');
    }

    hasDefaultPanel() {
        if (this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanel', this.viewType]) === false) {
            return false;
        }

        if (this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanelDisabled'])) {
            return false;
        }

        if (this.sidePanelsLayout) {
            for (const name in this.sidePanelsLayout) {
                if (name === 'default' && this.sidePanelsLayout[name].disabled) {
                    return false;
                }
            }
        }

        return true;
    }

    validate(layout) {
        if (!super.validate(layout)) {
            return false;
        }

        const fieldList = [];

        for (const panel of layout) {
            for (const row of panel.rows) {
                for (const cell of row) {
                    if (cell !== false && cell !== null && cell.name) {
                        fieldList.push(cell.name);
                    }
                }
            }
        }

        let incompatibleFieldList = [];

        let isIncompatible = false;

        for (const field of fieldList) {
            const defs = /** @type {Record} */
                this.getMetadata().get(['entityDefs', this.scope, 'fields', field]) ?? {};

            const targetFieldList = defs.detailLayoutIncompatibleFieldList ?? [];

            targetFieldList.forEach(itemField => {
                if (isIncompatible) {
                    return;
                }

                if (fieldList.includes(itemField)) {
                    isIncompatible = true;

                    incompatibleFieldList = [field].concat(targetFieldList);
                }
            });

            if (isIncompatible) {
                break;
            }
        }

        if (isIncompatible) {
            Espo.Ui.error(
                this.translate('fieldsIncompatible', 'messages', 'LayoutManager')
                    .replace(
                        '{fields}',
                        incompatibleFieldList
                            .map(field => this.translate(field, 'fields', this.scope))
                            .join(', ')
                    )
            );

            return false;
        }

        return true;
    }
}

export default LayoutDetailView;
