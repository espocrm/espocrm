/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/admin/layouts/detail', 'views/admin/layouts/grid', function (Dep) {

    return Dep.extend({

        dataAttributeList: [
            'name',
            'fullWidth',
            'customLabel',
            'noLabel',
        ],

        panelDataAttributeList: [
            'panelName',
            'dynamicLogicVisible',
            'style',
            'dynamicLogicStyled',
            'hidden',
        ],

        dataAttributesDefs: {
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
        },

        panelDataAttributesDefs: {
            panelName: {
                type: 'varchar',
            },
            style: {
                type: 'enum',
                options: [
                    'default',
                    'success',
                    'danger',
                    'warning'
                ],
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
        },

        defaultPanelFieldList: ['modifiedAt', 'createdAt', 'modifiedBy', 'createdBy'],

        setup: function () {
            Dep.prototype.setup.call(this);

            this.panelDataAttributesDefs = Espo.Utils.cloneDeep(this.panelDataAttributesDefs);

            this.panelDataAttributesDefs.dynamicLogicVisible.scope = this.scope;
            this.panelDataAttributesDefs.dynamicLogicStyled.scope = this.scope;

            this.wait(true);
            this.loadLayout(function () {

                this.setupPanels();
                this.wait(false);
            }.bind(this));
        },

        loadLayout: function (callback) {
            var layout;
            var model;

            var promiseList = [];

            promiseList.push(
                new Promise(function (resolve) {
                    this.getModelFactory().create(this.scope, function (m) {
                        this.getHelper()
                            .layoutManager
                            .getOriginal(this.scope, this.type, this.setId, function (layoutLoaded) {
                                layout = layoutLoaded;
                                model = m;
                                resolve();
                            });
                    }.bind(this));
                }.bind(this))
            );

            if (~['detail', 'detailSmall'].indexOf(this.type)) {
                promiseList.push(
                    new Promise(function (resolve) {
                        this.getHelper().layoutManager.getOriginal(
                            this.scope, 'sidePanels' + Espo.Utils.upperCaseFirst(this.type),
                            this.setId,
                            function (layoutLoaded) {
                                this.sidePanelsLayout = layoutLoaded;
                                resolve();
                            }.bind(this)
                        );
                    }.bind(this))
                );
            }

            promiseList.push(
                new Promise(
                    function (resolve) {
                        if (this.getMetadata().get(['clientDefs', this.scope, 'layoutDefaultSidePanelDisabled'])) {
                            resolve();
                        }

                        this.getHelper().layoutManager.getOriginal(this.scope, 'defaultSidePanel', this.setId,
                            function (layoutLoaded) {
                                this.defaultSidePanelLayout = layoutLoaded;

                                this.defaultPanelFieldList = Espo.Utils.clone(this.defaultPanelFieldList);

                                layoutLoaded.forEach(function (item) {
                                    var field = item.name;

                                    if (!field) {
                                        return;
                                    }

                                    if (field === ':assignedUser') {
                                        field = 'assignedUser';
                                    }

                                    if (!~this.defaultPanelFieldList.indexOf(field)) {
                                        this.defaultPanelFieldList.push(field);
                                    }
                                }, this);

                                resolve();
                            }.bind(this)
                        );
                    }.bind(this)
                )
            );

            Promise.all(promiseList).then(function () {
                this.readDataFromLayout(model, layout);
                if (callback) {
                    callback();
                }
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
            if (this.hasDefaultPanel()) {
                if (this.defaultPanelFieldList.indexOf(name) !== -1) {
                    return false;
                }
            }

            var layoutList = model.getFieldParam(name, 'layoutAvailabilityList');

            if (layoutList && !~layoutList.indexOf(this.type)) {
                return;
            }

            return !model.getFieldParam(name, 'disabled') && !model.getFieldParam(name, 'layoutDetailDisabled');
        },

        hasDefaultPanel: function () {
            if (this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanel', this.viewType]) === false) {
                return false;
            }

            if (this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanelDisabled'])) {
                return false;
            }

            if (this.sidePanelsLayout) {
                for (var name in this.sidePanelsLayout) {
                    if (name === 'default' && this.sidePanelsLayout[name].disabled) {
                        return false;
                    }
                }
            }

            return true;
        },
    });
});
