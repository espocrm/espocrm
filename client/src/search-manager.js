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

/** @module search-manager */

/**
 * Search data.
 *
 * @typedef {Object} module:search-manager~data
 *
 * @property {string} [presetName] A preset.
 * @property {string} [textFilter] A text filter.
 * @property {string} [primary] A primary filter.
 * @property {Object.<string, boolean>} [bool] Bool filters.
 * @property {Record<module:search-manager~advancedFilter>} [advanced] Advanced filters (field filters).
 * Contains data needed for both the backend and frontend. Keys are field names.
 */

/**
 * A where item. Sent to the backend.
 *
 * @typedef {Object} module:search-manager~whereItem
 *
 * @property {string} type A type.
 * @property {string} [attribute] An attribute (field).
 * @property {module:search-manager~whereItem[]|string|number|boolean|null} [value] A value.
 * @property {boolean} [dateTime] Is a date-time item.
 * @property {string} [timeZone] A time-zone.
 */

/**
 * An advanced filter
 *
 * @typedef {Object} module:search-manager~advancedFilter
 *
 * @property {string} type A type. E.g. `equals`.
 * @property {string} [attribute] An attribute.
 * @property {*} [value] A value.
 * @property {Object.<string, *>} [data] Additional data for UI.
 */

/**
 * A search manager.
 */
class SearchManager {

    /**
     * @type {string|null}
     * @private
     */
    timeZone

    /**
     * @param {module:collection} collection A collection.
     * @param {string|null} type A type. Used for a storage key.
     * @param {module:storage|null} storage A storage.
     * @param {module:date-time|null} dateTime A date-time util.
     * @param {module:search-manager~data|null} [defaultData=null] Default search data.
     * @param {boolean} [emptyOnReset=false] To empty on reset.
     */
    constructor(
        collection,
        type,
        storage,
        dateTime,
        defaultData,
        emptyOnReset
    ) {
        /**
         * @private
         * @type {module:collection}
         */
        this.collection = collection;

        /**
         * An entity type.
         *
         * @public
         * @type {string}
         */
        this.scope = collection.entityType;

        /**
         * @private
         * @type {module:storage|null}
         */
        this.storage = storage;

        /**
         * @private
         * @type {string}
         */
        this.type = type || 'list';

        /**
         * @private
         * @type {module:date-time|null}
         */
        this.dateTime = dateTime;

        /**
         * @private
         * @type {boolean}
         */
        this.emptyOnReset = emptyOnReset;

        /**
         * @private
         * @type {Object}
         */
        this.emptyData = {
            textFilter: '',
            bool: {},
            advanced: {},
            primary: null,
        };

        if (defaultData) {
            this.defaultData = defaultData;

            for (const p in this.emptyData) {
                if (!(p in defaultData)) {
                    defaultData[p] = Espo.Utils.clone(this.emptyData[p]);
                }
            }
        }

        /**
         * @type {module:search-manager~data}
         * @private
         */
        this.data = Espo.Utils.clone(defaultData) || this.emptyData;

        this.sanitizeData();
    }

    /**
     * @private
     */
    sanitizeData() {
        if (!('advanced' in this.data)) {
            this.data.advanced = {};
        }

        if (!('bool' in this.data)) {
            this.data.bool = {};
        }

        if (!('textFilter' in this.data)) {
            this.data.textFilter = '';
        }
    }

    /**
     * Get a where clause. The where clause to be sent to the backend.
     *
     * @returns {module:search-manager~whereItem[]}
     */
    getWhere() {
        const where = [];

        if (this.data.textFilter && this.data.textFilter !== '') {
            where.push({
                type: 'textFilter',
                value: this.data.textFilter
            });
        }

        if (this.data.bool) {
            const o = {
                type: 'bool',
                value: [],
            };

            for (const name in this.data.bool) {
                if (this.data.bool[name]) {
                    o.value.push(name);
                }
            }

            if (o.value.length) {
                where.push(o);
            }
        }

        if (this.data.primary) {
            const o = {
                type: 'primary',
                value: this.data.primary,
            };

            if (o.value.length) {
                where.push(o);
            }
        }

        if (this.data.advanced) {
            for (const name in this.data.advanced) {
                const defs = this.data.advanced[name];

                if (!defs) {
                    continue;
                }

                const part = this.getWherePart(name, defs);

                where.push(part);
            }
        }

        return where;
    }

    /**
     * @private
     */
    getWherePart(name, defs) {
        let attribute = name;

        if (typeof defs !== 'object') {
            console.error('Bad where clause');

            return {};
        }

        if ('where' in defs) {
            return defs.where;
        }

        const type = defs.type;
        let value;

        if (type === 'or' || type === 'and') {
            const a = [];

            value = defs.value || {};

            for (const n in value) {
                a.push(this.getWherePart(n, value[n]));
            }

            return {
                type: type,
                value: a
            };
        }

        if ('field' in defs) { // for backward compatibility
            attribute = defs.field;
        }

        if ('attribute' in defs) {
            attribute = defs.attribute;
        }

        if (defs.dateTime || defs.date) {
            const timeZone = this.timeZone !== undefined ?
                this.timeZone :
                this.dateTime.getTimeZone();

            const data = {
                type: type,
                attribute: attribute,
                value: defs.value,
            };

            if (defs.dateTime) {
                data.dateTime = true;
            }

            if (defs.date) {
                data.date = true;
            }

            if (timeZone) {
                data.timeZone = timeZone;
            }

            return data;
        }

        value = defs.value;

        return {
            type: type,
            attribute: attribute,
            value: value
        };
    }

    /**
     * Load stored data.
     *
     * @returns {module:search-manager}
     */
    loadStored() {
        this.data =
            this.storage.get(this.type + 'Search', this.scope) ||
            Espo.Utils.clone(this.defaultData) ||
            Espo.Utils.clone(this.emptyData);

        this.sanitizeData();

        return this;
    }

    /**
     * Get data.
     *
     * @returns {module:search-manager~data}
     */
    get() {
        return this.data;
    }

    /**
     * Set advanced filters.
     *
     * @param {Object.<string, module:search-manager~advancedFilter>} advanced Advanced filters.
     *   Pairs of field => advancedFilter.
     */
    setAdvanced(advanced) {
        this.data = Espo.Utils.clone(this.data);

        this.data.advanced = advanced;
    }

    /**
     * Set bool filters.
     *
     * @param {Object.<string, boolean>} bool Bool filters.
     */
    setBool(bool) {
        this.data = Espo.Utils.clone(this.data);

        this.data.bool = bool;
    }

    /**
     * Set a primary filter.
     *
     * @param {string} primary A filter.
     */
    setPrimary(primary) {
        this.data = Espo.Utils.clone(this.data);

        this.data.primary = primary;
    }

    /**
     * Set data.
     *
     * @param {module:search-manager~data} data Data.
     */
    set(data) {
        this.data = data;

        if (this.storage) {
            data = Espo.Utils.clone(data);
            delete data['textFilter'];

            this.storage.set(this.type + 'Search', this.scope, data);
        }
    }

    clearPreset() {
        delete this.data.presetName;
    }

    /**
     * Empty data.
     */
    empty() {
        this.data = Espo.Utils.clone(this.emptyData);

        if (this.storage) {
            this.storage.clear(this.type + 'Search', this.scope);
        }
    }

    /**
     * Reset.
     */
    reset() {
        if (this.emptyOnReset) {
            this.empty();

            return;
        }

        this.data = Espo.Utils.clone(this.defaultData) || Espo.Utils.clone(this.emptyData);

        if (this.storage) {
            this.storage.clear(this.type + 'Search', this.scope);
        }
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Set a time zone. Null will not add a time zone.
     *
     * @type {string|null}
     * @internal Is used. Do not remove.
     */
    setTimeZone(timeZone) {
        this.timeZone = timeZone;
    }
}

export default SearchManager;
