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

/** @module view-record-helper */

import {Events} from 'bullbone';

/**
 * @mixes Bull.Events
 */
class ViewRecordHelper {

    /**
     * @private
     * @type {{
     *     isChanged: boolean,
     * }}
     */
    state

    /**
     * @param {Object.<string, *>} [defaultFieldStates] Default field states.
     * @param {Object.<string, *>} [defaultPanelStates] Default panel states.
     */
    constructor(defaultFieldStates, defaultPanelStates) {

        /**
         * @private
         * @type {Object}
         */
        this.defaultFieldStates = defaultFieldStates || {};
        /**
         * @private
         * @type {Object}
         */
        this.defaultPanelStates = defaultPanelStates || {};
        /** @private */
        this.fieldStateMap = {};
        /** @private */
        this.panelStateMap = {};
        /** @private */
        this.hiddenFields = {};
        /** @private */
        this.hiddenPanels = {};
        /** @private */
        this.fieldOptionListMap = {};

        this.state = {
            isChanged: false,
        };
    }

    /**
     * Get hidden fields.
     *
     * @returns {Object.<string, boolean>}
     */
    getHiddenFields() {
        return this.hiddenFields;
    }

    /**
     * Get hidden panels.
     *
     * @returns {Object.<string,boolean>}
     */
    getHiddenPanels() {
        return this.hiddenPanels;
    }

    /**
     * Set a field-state parameter.
     *
     * @param {string} field A field name.
     * @param {string|'hidden'} name A parameter.
     * @param {*} value A value.
     */
    setFieldStateParam(field, name, value) {
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

        this.trigger('field-change');
    }

    /**
     * Get a field-state parameter.
     *
     * @param {string} field A field name.
     * @param {string} name A parameter.
     * @returns {*} A value.
     */
    getFieldStateParam(field, name) {
        if (field in this.fieldStateMap) {
            if (name in this.fieldStateMap[field]) {
                return this.fieldStateMap[field][name];
            }
        }

        if (name in this.defaultFieldStates) {
            return this.defaultFieldStates[name];
        }

        return null;
    }

    /**
     * Set a panel-state parameter.
     *
     * @param {string} panel A panel name.
     * @param {string|'hidden'} name A parameter.
     * @param {*} value A value.
     */
    setPanelStateParam(panel, name, value) {
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
    }

    /**
     * Get a panel-state parameter.
     *
     * @param {string} panel A panel name.
     * @param {string|'hidden'} name A parameter.
     * @returns {*} A value.
     */
    getPanelStateParam(panel, name) {
        if (panel in this.panelStateMap) {
            if (name in this.panelStateMap[panel]) {
                return this.panelStateMap[panel][name];
            }
        }

        if (name in this.defaultPanelStates) {
            return this.defaultPanelStates[name];
        }

        return null;
    }

    /**
     * Set a field option list.
     *
     * @param {string} field A field name.
     * @param {string[]} list An option list.
     */
    setFieldOptionList(field, list) {
        this.fieldOptionListMap[field] = list;
    }

    /**
     * Clear a field option list.
     *
     * @param {string} field A field name.
     */
    clearFieldOptionList(field) {
        delete this.fieldOptionListMap[field];
    }

    /**
     * Get a field option list.
     *
     * @param {string} field A field name.
     * @returns {string[]|null} Null if not set.
     */
    getFieldOptionList(field) {
        return this.fieldOptionListMap[field] || null;
    }

    /**
     * Whether a field option list is set.
     *
     * @param {string} field A field name.
     * @returns {boolean}
     */
    hasFieldOptionList(field) {
        return (field in this.fieldOptionListMap);
    }

    /**
     * Is changed.
     *
     * @return {boolean}
     * @since 9.2.0
     */
    isChanged() {
        return this.state.isChanged;
    }

    /**
     * Set is changed.
     *
     * @param {boolean} isChanged
     * @since 9.2.0
     */
    setIsChanged(isChanged) {
        this.state.isChanged = isChanged;
    }
}

Object.assign(ViewRecordHelper.prototype, Events);

export default ViewRecordHelper;
