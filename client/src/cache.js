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

/** @module cache */

/**
 * Cache for source and resource files.
 */
class Cache {

    /**
     * @param {Number} [cacheTimestamp] A cache timestamp.
     */
    constructor(cacheTimestamp) {
        this.basePrefix = this.prefix;

        if (cacheTimestamp) {
            this.prefix =  this.basePrefix + '-' + cacheTimestamp;
        }

        if (!this.get('app', 'timestamp')) {
            this.storeTimestamp();
        }
    }

    /** @private */
    prefix = 'cache'

    /**
     * Handle actuality. Clears cache if not actual.
     *
     * @param {Number} cacheTimestamp A cache timestamp.
     */
    handleActuality(cacheTimestamp) {
        const storedTimestamp = this.getCacheTimestamp();

        if (storedTimestamp) {
            if (storedTimestamp !== cacheTimestamp) {
                this.clear();
                this.set('app', 'cacheTimestamp', cacheTimestamp);
                this.storeTimestamp();
            }

            return;
        }

        this.clear();
        this.set('app', 'cacheTimestamp', cacheTimestamp);
        this.storeTimestamp();
    }

    /**
     * Get a cache timestamp.
     *
     * @returns {number}
     */
    getCacheTimestamp() {
        return parseInt(this.get('app', 'cacheTimestamp') || 0);
    }

    /**
     * @todo Revise whether is needed.
     */
    storeTimestamp() {
        const frontendCacheTimestamp = Date.now();

        this.set('app', 'timestamp', frontendCacheTimestamp);
    }

    /**
     * @private
     * @param {string} type
     * @returns {string}
     */
    composeFullPrefix(type) {
        return this.prefix + '-' + type;
    }

    /**
     * @private
     * @param {string} type
     * @param {string} name
     * @returns {string}
     */
    composeKey(type, name) {
        return this.composeFullPrefix(type) + '-' + name;
    }

    /**
     * @private
     * @param {string} type
     */
    checkType(type) {
        if (typeof type === 'undefined' && toString.call(type) !== '[object String]') {
            throw new TypeError("Bad type \"" + type + "\" passed to Cache().");
        }
    }

    /**
     * Get a stored value.
     *
     * @param {string} type A type/category.
     * @param {string} name A name.
     * @returns {string|null} Null if no stored value.
     */
    get(type, name) {
        this.checkType(type);

        const key = this.composeKey(type, name);

        let stored;

        try {
            stored = localStorage.getItem(key);
        }
        catch (error) {
            console.error(error);

            return null;
        }

        if (stored) {
            let result = stored;

            if (stored.length > 9 && stored.substring(0, 9) === '__JSON__:') {
                const jsonString = stored.slice(9);

                try {
                    result = JSON.parse(jsonString);
                }
                catch (error) {
                    result = stored;
                }
            }

            return result;
        }

        return null;
    }

    /**
     * Store a value.
     *
     * @param {string} type A type/category.
     * @param {string} name A name.
     * @param {any} value A value.
     */
    set(type, name, value) {
        this.checkType(type);

        const key = this.composeKey(type, name);

        if (value instanceof Object || Array.isArray(value)) {
            value = '__JSON__:' + JSON.stringify(value);
        }

        try {
            localStorage.setItem(key, value);
        }
        catch (error) {
            console.log('Local storage limit exceeded.');
        }
    }

    /**
     * Clear a stored value.
     *
     * @param {string} [type] A type/category.
     * @param {string} [name] A name.
     */
    clear(type, name) {
        let reText;

        if (typeof type !== 'undefined') {
            if (typeof name === 'undefined') {
                reText = '^' + this.composeFullPrefix(type);
            }
            else {
                reText = '^' + this.composeKey(type, name);
            }
        }
        else {
            reText = '^' + this.basePrefix + '-';
        }

        const re = new RegExp(reText);

        for (const i in localStorage) {
            if (re.test(i)) {
                delete localStorage[i];
            }
        }
    }
}

export default Cache;
