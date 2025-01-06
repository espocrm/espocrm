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

/** @module session-storage */

/**
 * A session storage. Cleared when a page session ends.
 */
class SessionStorage {

    /** @private */
    storageObject = sessionStorage

    /**
     * Get a value.
     *
     * @param {string} name A name.
     * @returns {*} Null if not set.
     */
    get(name) {
        let stored;

        try {
            stored = this.storageObject.getItem(name);
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
     * Set (store) a value.
     *
     * @param {string} name A name.
     * @param {*} value A value.
     */
    set(name, value) {
        if (value === null) {
            this.clear(name);

            return;
        }

        if (
            value instanceof Object ||
            Array.isArray(value) ||
            value === true ||
            value === false ||
            typeof value === 'number'
        ) {
            value = '__JSON__:' + JSON.stringify(value);
        }

        try {
            this.storageObject.setItem(name, value);
        }
        catch (error) {
            console.error(error);
        }
    }

    /**
     * Has a value.
     *
     * @param {string} name A name.
     * @returns {boolean}
     */
    has(name) {
        return this.storageObject.getItem(name) !== null;
    }

    /**
     * Clear a value.
     *
     * @param {string} name A name.
     */
    clear(name) {
        for (const i in this.storageObject) {
            if (i === name) {
                delete this.storageObject[i];
            }
        }
    }
}

export default SessionStorage;
