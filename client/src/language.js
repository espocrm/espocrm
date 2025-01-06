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

/** @module language */

import {Events} from 'bullbone';

/**
 * A language.
 *
 * @mixes Bull.Events
 */
class Language {

    /** @private */
    url = 'I18n'

    /**
     * @class
     * @param {module:cache} [cache] A cache.
     */
    constructor(cache) {
        /**
         * @private
         * @type {module:cache|null}
         */
        this.cache = cache || null;

        /**
         * @private
         * @type {Object}
         */
        this.data = {};

        /**
         * A name.
         *
         * @type {string}
         */
        this.name = 'default';
    }

    /**
     * Whether an item is set in language data.
     *
     * @param {string} scope A scope.
     * @param {string} category A category.
     * @param {string} name An item name.
     * @returns {boolean}
     */
    has(name, category, scope) {
        if (scope in this.data) {
            if (category in this.data[scope]) {
                if (name in this.data[scope][category]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get a value set in language data.
     *
     * @param {string} scope A scope.
     * @param {string} category A category.
     * @param {string} name An item name.
     * @returns {*}
     */
    get(scope, category, name) {
        if (scope in this.data) {
            if (category in this.data[scope]) {
                if (name in this.data[scope][category]) {
                    return this.data[scope][category][name];
                }
            }
        }

        if (scope === 'Global') {
            return name;
        }

        return false;
    }

    /**
     * Translate a label.
     *
     * @param {string} name An item name.
     * @param {string|'messages'|'labels'|'fields'|'links'|'scopeNames'|'scopeNamesPlural'} [category='labels'] A category.
     * @param {string} [scope='Global'] A scope.
     * @returns {string}
     */
    translate(name, category, scope) {
        scope = scope || 'Global';
        category = category || 'labels';

        let res = this.get(scope, category, name);

        if (res === false && scope !== 'Global') {
            res = this.get('Global', category, name);
        }

        return res;
    }

    /**
     * Translation an option item value.
     *
     * @param {string} value An option value.
     * @param {string} field A field name.
     * @param {string} [scope='Global'] A scope.
     * @returns {string}
     */
    translateOption(value, field, scope) {
        let translation = this.translate(field, 'options', scope);

        if (typeof translation !== 'object') {
            translation = {};
        }

        return translation[value] || value;
    }

    /**
     * @private
     */
    loadFromCache(loadDefault) {
        let name = this.name;

        if (loadDefault) {
            name = 'default';
        }

        if (this.cache) {
            const cached = this.cache.get('app', 'language-' + name);

            if (cached) {
                this.data = cached;

                return true;
            }
        }

        return null;
    }

    /**
     * Clear a language cache.
     */
    clearCache() {
        if (this.cache) {
            this.cache.clear('app', 'language-' + this.name);
        }
    }

    /**
     * @private
     */
    storeToCache(loadDefault) {
        let name = this.name;

        if (loadDefault) {
            name = 'default';
        }

        if (this.cache) {
            this.cache.set('app', 'language-' + name, this.data);
        }
    }

    /**
     * Load data from cache or backend (if not yet cached).
     *
     * @returns {Promise}
     */
    load() {
        return this._loadInternal();
    }

    /**
     * @private
     * @param {boolean} [disableCache=false]
     * @param {boolean} [loadDefault=false].
     * @returns {Promise}
     */
    _loadInternal(disableCache, loadDefault) {
        if (!disableCache && this.loadFromCache(loadDefault)) {
            this.trigger('sync');

            return Promise.resolve();
        }

        return this.fetch(loadDefault);
    }

    /**
     * Load default-language data from the backend.
     *
     * @returns {Promise}
     */
    loadDefault() {
        return this._loadInternal(false, true);
    }

    /**
     * Load data from the backend.
     *
     * @returns {Promise}
     */
    loadSkipCache() {
        return this._loadInternal(true);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Load default-language data from the backend.
     *
     * @returns {Promise}
     */
    loadDefaultSkipCache() {
        return this._loadInternal(true, true);
    }

    /**
     * @private
     * @param {boolean} loadDefault
     * @returns {Promise}
     */
    fetch(loadDefault) {
        return Espo.Ajax.getRequest(this.url, {default: loadDefault}).then(data => {
            this.data = data;

            this.storeToCache(loadDefault);
            this.trigger('sync');
        });
    }

    /**
     * Sort a field list by a translated name.
     *
     * @param {string} scope An entity type.
     * @param {string[]} fieldList A field list.
     * @returns {string[]}
     */
    sortFieldList(scope, fieldList) {
        return fieldList.sort((v1, v2) => {
            return this.translate(v1, 'fields', scope)
                .localeCompare(this.translate(v2, 'fields', scope));
        });
    }

    /**
     * Sort an entity type list by a translated name.
     *
     * @param {string[]} entityList An entity type list.
     * @param {boolean} [plural=false] Use a plural label.
     * @returns {string[]}
     */
    sortEntityList(entityList, plural) {
        let category = 'scopeNames';

        if (plural) {
            category += 'Plural';
        }

        return entityList.sort((v1, v2) => {
            return this.translate(v1, category)
                .localeCompare(this.translate(v2, category));
        });
    }

    /**
     * Get a value by a path.
     *
     * @param {string[]|string} path A path.
     * @returns {*}
     */
    translatePath(path) {
        if (typeof path === 'string' || path instanceof String) {
            path = path.split('.');
        }

        let pointer = this.data;

        path.forEach(key => {
            if (key in pointer) {
                pointer = pointer[key];
            }
        });

        return pointer;
    }

    /**
     * Do not use.
     *
     * @param {string} [scope]
     * @param {Record} [data]
     * @internal
     */
    setScopeData(scope, data) {
        this.data[scope] = data;
    }
}

Object.assign(Language.prototype, Events);

export default Language;
