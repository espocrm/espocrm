/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

define('views/record/detail-side', 'views/record/panels-container', function (Dep) {

    return Dep.extend({

        template: 'record/side',

        mode: 'detail',

        readOnly: false,

        inlineEditDisabled: false,

        defaultPanel: true,

        panelList: [],

        defaultPanelDefs: {
            name: 'default',
            label: false,
            view: 'views/record/panels/default-side',
            isForm: true,
            options: {
                fieldList: [
                    {
                        name: ':assignedUser'
                    },
                    {
                        name: 'teams'
                    }
                ]
            }
        },

        init: function () {
            this.panelList = this.options.panelList || this.panelList;
            this.scope = this.entityType = this.options.model.name;

            this.recordHelper = this.options.recordHelper;

            this.panelList = Espo.Utils.clone(this.panelList);

            this.readOnlyLocked = this.options.readOnlyLocked || this.readOnly;
            this.readOnly = this.options.readOnly || this.readOnly;
            this.inlineEditDisabled = this.options.inlineEditDisabled || this.inlineEditDisabled;

            this.recordViewObject = this.options.recordViewObject;
        },

        setupPanels: function () {
        },

        setup: function () {
            this.type = this.mode;
            if ('type' in this.options) {
                this.type = this.options.type;
            }

            if (this.defaultPanel) {
                this.setupDefaultPanel();
            }

            this.setupPanels();

            if (!this.additionalPanelsDisabled) {
                var additionalPanels = this.getMetadata().get(['clientDefs', this.scope, 'sidePanels', this.type]) || [];
                additionalPanels.forEach(function (panel) {
                    this.panelList.push(panel);
                }, this);
            }

            this.panelList = this.panelList.filter(function (p) {
                if (p.aclScope) {
                    if (!this.getAcl().checkScope(p.aclScope)) {
                        return;
                    }
                }
                if (p.accessDataList) {
                    if (!Espo.Utils.checkAccessDataList(p.accessDataList, this.getAcl(), this.getUser())) {
                        return false;
                    }
                }
                return true;
            }, this);

            this.panelList = this.panelList.map(function (p) {
                var item = Espo.Utils.clone(p);
                if (this.recordHelper.getPanelStateParam(p.name, 'hidden') !== null) {
                    item.hidden = this.recordHelper.getPanelStateParam(p.name, 'hidden');
                } else {
                    this.recordHelper.setPanelStateParam(p.name, item.hidden || false);
                }
                return item;
            }, this);

            this.panelList.forEach(function (item) {
                item.actionsViewKey = item.name + 'Actions';
            }, this);

            this.wait(true);
            this.getHelper().layoutManager.get(this.scope, 'sidePanels' + Espo.Utils.upperCaseFirst(this.type), function (layoutData) {
                if (layoutData) {
                    this.alterPanels(layoutData);
                }
                this.setupPanelViews();
                this.wait(false);
            }.bind(this));
        },

        alterPanels: function (layoutData) {
            layoutData = layoutData || {};

            var newList = [];
            this.panelList.forEach(function (item, i) {
                item.index = i;
                if (item.name) {
                    var itemData = layoutData[item.name] || {};
                    if (itemData.disabled) return;
                    for (var i in itemData) {
                        item[i] = itemData[i];
                    }
                }

                newList.push(item);
            }, this);

            newList.sort(function (v1, v2) {
                return v1.index - v2.index;
            });

            this.panelList = newList;

            if (this.recordViewObject && this.recordViewObject.dynamicLogic) {
                var dynamicLogic = this.recordViewObject.dynamicLogic;
                this.panelList.forEach(function (item) {
                    if (item.dynamicLogicVisible) {
                        dynamicLogic.addPanelVisibleCondition(item.name, item.dynamicLogicVisible);

                        if (this.recordHelper.getPanelStateParam(item.name, 'hidden')) {
                            item.hidden = true;
                        }
                    }
                }, this);
            }
        },

        setupDefaultPanel: function () {
            var met = false;
            this.panelList.forEach(function (item) {
                if (item.name === 'default') {
                    met = true;
                }
            }, this);

            if (met) return;

            var defaultPanelDefs = this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanel', this.type]);

            if (defaultPanelDefs === false) return;

            if (this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanelDisabled'])) return;

            defaultPanelDefs = defaultPanelDefs || this.defaultPanelDefs;

            if (!defaultPanelDefs) return;

            defaultPanelDefs = Espo.Utils.cloneDeep(defaultPanelDefs);

            var fieldList = this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanelFieldLists', this.type]);

            if (!fieldList) {
                fieldList = this.getMetadata().get(['clientDefs', this.scope, 'defaultSidePanelFieldList']);
            }

            if (fieldList) {
                defaultPanelDefs.options = defaultPanelDefs.options || {};
                defaultPanelDefs.options.fieldList = fieldList;
            }

            if (defaultPanelDefs.options.fieldList && defaultPanelDefs.options.fieldList.length) {
                defaultPanelDefs.options.fieldList.forEach(function (item, i) {
                    if (typeof item !== 'object') {
                        item = {
                            name: item
                        }
                        defaultPanelDefs.options.fieldList[i] = item;
                    }
                    if (item.name === ':assignedUser') {
                        if (this.model.hasField('assignedUsers')) {
                            item.name = 'assignedUsers';
                            if (!this.model.getFieldParam('assignedUsers', 'view')) {
                                item.view = 'views/fields/assigned-users';
                            }
                        } else if (this.model.hasField('assignedUser')) {
                            item.name = 'assignedUser';
                        } else {
                            defaultPanelDefs.options.fieldList[i] = {};
                        }
                    }
                }, this);
            }

            this.panelList.unshift(defaultPanelDefs);
        },

    });
});
