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

/** @module helpers/misc/stored-text-search */

export default class {
    /**
     * @param {module:storage} storage
     * @param {string} scope
     * @param {Number} [maxCount]
     */
    constructor(scope, storage, maxCount) {
        this.scope = scope;
        this.storage = storage;
        this.key = 'textSearches';
        this.maxCount = maxCount || 100;
        /** @type {string[]|null} */
        this.list = null;
    }

    /**
     * Match.
     *
     * @param {string} text
     * @param {Number} [limit]
     * @return {string[]}
     */
    match(text, limit) {
        text = text.toLowerCase().trim();

        const list = this.get();
        const matchedList = [];

        for (const item of list) {
            if (item.toLowerCase().startsWith(text)) {
                matchedList.push(item);
            }

            if (limit !== undefined && matchedList.length === limit) {
                break;
            }
        }

        return matchedList;
    }

    /**
     * Get stored text filters.
     *
     * @private
     * @return {string[]}
     */
    get() {
        if (this.list === null) {
            this.list = this.getFromStorage();
        }

        return this.list;
    }

    /**
     * @private
     * @return {string[]}
     */
    getFromStorage() {
        /** @var {string[]} */
        return this.storage.get(this.key, this.scope) || [];
    }

    /**
     * Store a text filter.
     *
     * @param {string} text
     */
    store(text) {
        text = text.trim();

        let list = this.getFromStorage();

        const index = list.indexOf(text);

        if (index !== -1) {
            list.splice(index, 1);
        }

        list.unshift(text);

        if (list.length > this.maxCount) {
            list = list.slice(0, this.maxCount);
        }

        this.list = list;
        this.storage.set(this.key, this.scope, list);
    }

    /**
     * Remove a text filter.
     *
     * @param {string} text
     */
    remove(text) {
        text = text.trim();

        const list = this.getFromStorage();

        const index = list.indexOf(text);

        if (index === -1) {
            return;
        }

        list.splice(index, 1);

        this.list = list;
        this.storage.set(this.key, this.scope, list);
    }
}
