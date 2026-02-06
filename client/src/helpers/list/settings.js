/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import {inject} from 'di';
import Storage from 'storage';
import Utils from 'utils';

class ListSettingsHelper {

    /**
     * @typedef {Object} ListSettingsHelper~columnWidth
     * @property {number} value A value.
     * @property {'px'|'%'} unit A unit.
     */

    /**
     * @private
     * @type {Storage}
     */
    @inject(Storage)
    storage

    /**
     * @private
     * @type {boolean}
     */
    useStorage

    /**
     * Note: Do not change the signature for the first 3 parameters.
     * @internal
     *
     * @param {string} entityType
     * @param {string} key A key used for storage.
     * @param {string} userId
     * @param {{useStorage?: boolean}} options
     */
    constructor(entityType, key, userId, options = {}) {
        /** @private */
        this.layoutColumnsKey = `${key}-${entityType}-${userId}`;

        /**
         * @private
         * @type {Object.<string, boolean>}
         */
        this.hiddenColumnMapCache = undefined;

        /**
         * @private
         * @type {Object.<string, ListSettingsHelper~columnWidth>}
         */
        this.columnWidthMapCache = undefined;

        /**
         * @private
         * @type {boolean|undefined}
         */
        this.columnResize = undefined;

        /**
         * @private
         * @type {function()[]}
         */
        this.columnWidthChangeFunctions = [];

        this.useStorage = options.useStorage ?? true;
    }

    /**
     * @private
     * @param {string} key
     * @return {*}
     */
    getStored(key) {
        if (this.useStorage) {
            return this.storage.get(key, this.layoutColumnsKey);
        }

        return null;
    }

    /**
     * @private
     * @param {string} key
     * @param {*} value
     */
    store(key, value) {
        if (this.useStorage) {
            this.storage.set(key, this.layoutColumnsKey, value);
        }
    }

    /**
     * @private
     * @param {string} key
     */
    clearStored(key) {
        if (this.useStorage) {
            this.storage.clear(key, this.layoutColumnsKey);
        }
    }

    /**
     * Get a stored hidden column map.
     *
     * @return {Object.<string, boolean>}
     */
    getHiddenColumnMap() {
        if (this.hiddenColumnMapCache) {
            return this.hiddenColumnMapCache;
        }

        this.hiddenColumnMapCache = /** @type {Object} */
            this.getStored('listHiddenColumns') ?? {};

        return this.hiddenColumnMapCache;
    }

    /**
     * Is a column hidden.
     *
     * @param {string} name A name.
     * @param {boolean} [hidden] Is hidden by default.
     * @return {boolean}
     * @since 9.0.0
     */
    isColumnHidden(name, hidden) {
        const hiddenMap = this.getHiddenColumnMap();

        if (hiddenMap[name]) {
            return true;
        }

        if (!hidden) {
            return false;
        }

        if (!(name in hiddenMap)) {
            return true;
        }

        return hiddenMap[name];
    }

    /**
     * Is column resize enabled.
     *
     * @return {boolean}
     * @since 9.0.0
     */
    getColumnResize() {
        if (this.columnResize === undefined) {
            this.columnResize = this.getStored('listColumnResize') ?? false;
        }

        return this.columnResize;
    }

    /**
     * Store column width editable.
     *
     * @param {boolean} columnResize
     */
    storeColumnResize(columnResize) {
        this.columnResize = columnResize;

        this.store('listColumnResize', columnResize);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Clear column width editable.
     */
    clearColumnResize() {
        this.columnResize = undefined;

        this.clearStored('listColumnResize');
    }

    /**
     * Store a hidden column map.
     *
     * @param {Object.<string, boolean>} map
     */
    storeHiddenColumnMap(map) {
        this.hiddenColumnMapCache = Utils.cloneDeep(map);

        this.store('listHiddenColumns', map);
    }

    /**
     * Clear a hidden column map in the storage.
     */
    clearHiddenColumnMap() {
        this.hiddenColumnMapCache = undefined;

        this.clearStored('listHiddenColumns');
    }

    /**
     * Get a stored column width map.
     *
     * @return {Object.<string, ListSettingsHelper~columnWidth>}
     */
    getColumnWidthMap() {
        if (this.columnWidthMapCache) {
            return this.columnWidthMapCache;
        }

        this.columnWidthMapCache = /** @type {Object} */
            this.getStored('listColumnsWidths') ?? {};

        return this.columnWidthMapCache;
    }

    /**
     * Store a column width map.
     *
     * @param {Object.<string, ListSettingsHelper~columnWidth>} map
     */
    storeColumnWidthMap(map) {
        this.columnWidthMapCache = Utils.cloneDeep(map);

        this.store('listColumnsWidths', map);
    }

    /**
     * Clear a column width map in the storage.
     */
    clearColumnWidthMap() {
        this.columnWidthMapCache = undefined;

        this.clearStored('listColumnsWidths');
    }

    /**
     * Set a column width.
     *
     * @param {string} name A column name.
     * @param {ListSettingsHelper~columnWidth} width Width data.
     */
    storeColumnWidth(name, width) {
        if (!this.columnWidthMapCache) {
            this.columnWidthMapCache = {};
        }

        this.columnWidthMapCache[name] = width;

        this.storeColumnWidthMap(this.columnWidthMapCache);

        for (const handler of this.columnWidthChangeFunctions) {
            handler();
        }
    }

    /**
     * Subscribe to a column width change.
     *
     * @param {function()} handler A handler.
     */
    subscribeToColumnWidthChange(handler) {
        this.columnWidthChangeFunctions.push(handler);
    }

    /**
     * Unsubscribe from a column width change.
     *
     * @param {function()} handler A handler.
     */
    unsubscribeFromColumnWidthChange(handler) {
        const index = this.columnWidthChangeFunctions.findIndex(it => handler === it);

        if (!~index) {
            return;
        }

        this.columnWidthChangeFunctions.splice(index, 1);
    }
}

export default ListSettingsHelper;
