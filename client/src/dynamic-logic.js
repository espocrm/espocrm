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

define('dynamic-logic', [], function () {

    let DynamicLogic = function (defs, recordView) {
        this.defs = defs || {};

        this.recordView = recordView;

        this.fieldTypeList = ['visible', 'required', 'readOnly'];
        this.panelTypeList = ['visible', 'styled'];

        this.optionsDirtyMap = {};
        this.originalOptions = {};
    };

    _.extend(DynamicLogic.prototype, {

        process: function () {
            let fields = this.defs.fields || {};

            Object.keys(fields).forEach(field => {
                var item = (fields[field] || {});

                this.fieldTypeList.forEach(type => {
                    if (!(type in item)) {
                        return;
                    }

                    if (!item[type]) {
                        return;
                    }

                    let typeItem = (item[type] || {});

                    if (!typeItem.conditionGroup) {
                        return;
                    }

                    let result = this.checkConditionGroup(typeItem.conditionGroup);

                    let methodName;

                    if (result) {
                        methodName = 'makeField' + Espo.Utils.upperCaseFirst(type) + 'True';
                    }
                    else {
                        methodName = 'makeField' + Espo.Utils.upperCaseFirst(type) + 'False';
                    }

                    this[methodName](field);
                });
            });

            let panels = this.defs.panels || {};

            Object.keys(panels).forEach(panel => {
                this.panelTypeList.forEach(type => {
                    this.processPanel(panel, type);
                });
            });

            let options = this.defs.options || {};

            Object.keys(options).forEach(field => {
                let itemList = options[field] || [];

                let isMet = false;

                for (let i in itemList) {
                    let item = itemList[i];

                    if (this.checkConditionGroup(item.conditionGroup)) {
                        this.setOptionList(field, item.optionList || []);

                        isMet = true;

                        break;
                    }
                }

                if (!isMet) {
                    this.resetOptionList(field);
                }
            });
        },

        processPanel: function (panel, type) {
            let panels = this.defs.panels || {};
            let item = (panels[panel] || {});

            if (!(type in item)) {
                return;
            }

            let typeItem = (item[type] || {});

            if (!typeItem.conditionGroup) {
                return;
            }

            let result = this.checkConditionGroup(typeItem.conditionGroup);

            let methodName;

            if (result) {
                methodName = 'makePanel' + Espo.Utils.upperCaseFirst(type) + 'True';
            }
            else {
                methodName = 'makePanel' + Espo.Utils.upperCaseFirst(type) + 'False';
            }

            this[methodName](panel);
        },

        checkConditionGroup: function (data, type) {
            type = type || 'and';

            let list;

            let result = false;

            if (type === 'and') {
                list =  data || [];

                result = true;

                for (let i in list) {
                    if (!this.checkCondition(list[i])) {
                        result = false;

                        break;
                    }
                }
            }
            else if (type === 'or') {
                list =  data || [];

                for (let i in list) {
                    if (this.checkCondition(list[i])) {
                        result = true;

                        break;
                    }
                }
            }
            else if (type === 'not') {
                if (data) {
                    result = !this.checkCondition(data);
                }
            }

            return result;
        },

        checkCondition: function (defs) {
            defs = defs || {};

            let type = defs.type || 'equals';

            if (~['or', 'and', 'not'].indexOf(type)) {
                return this.checkConditionGroup(defs.value, type);
            }

            let attribute = defs.attribute;
            let value = defs.value;

            if (!attribute) {
                return;
            }

            var setValue = this.recordView.model.get(attribute);

            if (type === 'equals') {
                if (!value) {
                    return;
                }

                return setValue === value;
            }

            if (type === 'notEquals') {
                if (!value) {
                    return;
                }

                return setValue !== value;
            }

            if (type === 'isEmpty') {
                if (Array.isArray(setValue)) {
                    return !setValue.length;
                }

                return setValue === null || (setValue === '') || typeof setValue === 'undefined';
            }

            if (type === 'isNotEmpty') {
                if (Array.isArray(setValue)) {
                    return !!setValue.length;
                }

                return setValue !== null && (setValue !== '') && typeof setValue !== 'undefined';
            }

            if (type === 'isTrue') {
                return !!setValue;
            }

            if (type === 'isFalse') {
                return !setValue;
            }

            if (type === 'contains' || type === 'has') {
                if (!setValue) {
                    return false;
                }

                return !!~setValue.indexOf(value);
            }

            if (type === 'notContains' || type === 'notHas') {
                if (!setValue) {
                    return true;
                }

                return !~setValue.indexOf(value);
            }

            if (type === 'startsWith') {
                if (!setValue) {
                    return false;
                }

                return setValue.indexOf(value) === 0;
            }

            if (type === 'endsWith') {
                if (!setValue) {
                    return false;
                }

                return setValue.indexOf(value) === setValue.length - value.length;
            }

            if (type === 'matches') {
                if (!setValue) {
                    return false;
                }

                let match = /^\/(.*)\/([a-z]*)$/.exec(value);

                if (!match || match.length < 2) {
                    return false;
                }

                return (new RegExp(match[1], match[2])).test(setValue);
            }

            if (type === 'greaterThan') {
                return setValue > value;
            }

            if (type === 'lessThan') {
                return setValue < value;
            }

            if (type === 'greaterThanOrEquals') {
                return setValue >= value;
            }

            if (type === 'lessThanOrEquals') {
                return setValue <= value;
            }

            if (type === 'in') {
                return ~value.indexOf(setValue);
            }

            if (type === 'notIn') {
                return !~value.indexOf(setValue);
            }

            if (type === 'isToday') {
                let dateTime = this.recordView.getDateTime();

                if (!setValue) {
                    return false;
                }

                if (setValue.length > 10) {
                    return dateTime.toMoment(setValue).isSame(dateTime.getNowMoment(), 'day');
                }

                return dateTime.toMomentDate(setValue).isSame(dateTime.getNowMoment(), 'day');
            }

            if (type === 'inFuture') {
                let dateTime = this.recordView.getDateTime();

                if (!setValue) {
                    return false;
                }

                if (setValue.length > 10) {
                    return dateTime.toMoment(setValue).isAfter(dateTime.getNowMoment(), 'day');
                }

                return dateTime.toMomentDate(setValue).isAfter(dateTime.getNowMoment(), 'day');
            }

            if (type === 'inPast') {
                let dateTime = this.recordView.getDateTime();

                if (!setValue) {
                    return false;
                }


                if (setValue.length > 10) {
                    return dateTime.toMoment(setValue).isBefore(dateTime.getNowMoment(), 'day');
                }

                return dateTime.toMomentDate(setValue).isBefore(dateTime.getNowMoment(), 'day');
            }

            return false;
        },

        setOptionList: function (field, optionList) {
            this.recordView.setFieldOptionList(field, optionList);
        },

        resetOptionList: function (field) {
            this.recordView.resetFieldOptionList(field);
        },

        makeFieldVisibleTrue: function (field) {
            this.recordView.showField(field);
        },

        makeFieldVisibleFalse: function (field) {
            this.recordView.hideField(field);
        },

        makeFieldRequiredTrue: function (field) {
            this.recordView.setFieldRequired(field);
        },

        makeFieldRequiredFalse: function (field) {
            this.recordView.setFieldNotRequired(field);
        },

        makeFieldReadOnlyTrue: function (field) {
            this.recordView.setFieldReadOnly(field);
        },

        makeFieldReadOnlyFalse: function (field) {
            this.recordView.setFieldNotReadOnly(field);
        },

        makePanelVisibleTrue: function (panel) {
            this.recordView.showPanel(panel, 'dynamicLogic');
        },

        makePanelVisibleFalse: function (panel) {
            this.recordView.hidePanel(panel, false, 'dynamicLogic');
        },

        makePanelStyledTrue: function (panel) {
            this.recordView.stylePanel(panel, 'dynamicLogic');
        },

        makePanelStyledFalse: function (panel) {
            this.recordView.unstylePanel(panel, false, 'dynamicLogic');
        },

        addPanelVisibleCondition: function (name, item) {
            this.defs.panels = this.defs.panels || {};
            this.defs.panels[name] = this.defs.panels[name] || {};

            this.defs.panels[name].visible = item;

            this.processPanel(name, 'visible');
        },

        addPanelStyledCondition: function (name, item) {
            this.defs.panels = this.defs.panels || {};
            this.defs.panels[name] = this.defs.panels[name] || {};

            this.defs.panels[name].styled = item;

            this.processPanel(name, 'styled');
        },

    });

    return DynamicLogic;
});
