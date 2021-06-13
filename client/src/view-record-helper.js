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

define('view-record-helper', [], function () {

    let ViewRecordHelper = function (defaultFieldStates, defaultPanelStates) {
        if (defaultFieldStates) {
            this.defaultFieldStates = defaultFieldStates;
        }

        if (defaultPanelStates) {
            this.defaultPanelStates = defaultPanelStates;
        }

        this.fieldStateMap = {};
        this.panelStateMap = {};

        this.hiddenFields = {};
        this.hiddenPanels = {};

        this.fieldOptionListMap = {};
    };

    _.extend(ViewRecordHelper.prototype, {

        defaultFieldStates: {},

        defaultPanelStates: {},

        getHiddenFields: function () {
            return this.hiddenFields;
        },

        getHiddenPanels: function () {
            return this.hiddenPanels;
        },

        setFieldStateParam: function (field, name, value) {
            switch (name) {
                case 'hidden':
                    if (value) {
                        this.hiddenFields[field] = true;
                    }
                    else {
                        delete this.hiddenFields[field];
                    }

                    break;
            }

            this.fieldStateMap[field] = this.fieldStateMap[field] || {};
            this.fieldStateMap[field][name] = value;
        },

        getFieldStateParam: function (field, name) {
            if (field in this.fieldStateMap) {
                if (name in this.fieldStateMap[field]) {
                    return this.fieldStateMap[field][name];
                }
            }

            if (name in this.defaultFieldStates) {
                return this.defaultFieldStates[name];
            }

            return null;
        },

        setPanelStateParam: function (panel, name, value) {
            switch (name) {
                case 'hidden':
                    if (value) {
                        this.hiddenPanels[panel] = true;
                    } else {
                        delete this.hiddenPanels[panel];
                    }
                    break;
            }

            this.panelStateMap[panel] = this.panelStateMap[panel] || {};
            this.panelStateMap[panel][name] = value;
        },

        getPanelStateParam: function (panel, name) {
            if (panel in this.panelStateMap) {
                if (name in this.panelStateMap[panel]) {
                    return this.panelStateMap[panel][name];
                }
            }

            if (name in this.defaultPanelStates) {
                return this.defaultPanelStates[name];
            }

            return null;
        },

        setFieldOptionList: function (field, list) {
            this.fieldOptionListMap[field] = list;
        },

        clearFieldOptionList: function (field) {
            delete this.fieldOptionListMap[field];
        },

        getFieldOptionList: function (field) {
            return this.fieldOptionListMap[field] || null;
        },

        hasFieldOptionList: function (field) {
            return (field in this.fieldOptionListMap);
        },
    });

    return ViewRecordHelper;
});
