/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('layout-manager', [], function () {

    /**
     * A layout manager.
     *
     * @class
     * @name Class
     * @memberOf module:layout-manager
     *
     * @param {module:cache.Class|null} [cache] A cache.
     * @param {string} [applicationId] An application ID.
     * @param {string} [userId] A user ID.
     */
    let LayoutManager = function (cache, applicationId, userId) {
        /**
         * @private
         * @type {module:cache.Class|null}
         */
        this.cache = cache || null;

        /**
         * @private
         * @type {string}
         */
        this.applicationId = applicationId || 'default-id';

        /**
         * @private
         * @type {string|null}
         */
        this.userId = userId || null;

        /**
         * @private
         * @type {Object}
         */
        this.data = {};

        /**
         * @private
         */
        this.ajax = Espo.Ajax;
    };

    _.extend(LayoutManager.prototype, /** @lends module:layout-manager.Class# */{

        /**
         * Set a user ID. To be used for the cache purpose.
         *
         * @param {string} userId A user ID.
         *
         * @todo Throw an exception if already set.
         */
        setUserId: function (userId) {
            this.userId = userId
        },

        /**
         * @private
         * @param {string} scope
         * @param {string} type
         * @returns {string}
         */
        getKey: function (scope, type) {
            if (this.userId) {
                return this.applicationId + '-' + this.userId + '-' + scope + '-' + type;
            }

            return this.applicationId + '-' + scope + '-' + type;
        },

        /**
         * @private
         * @param {string} scope
         * @param {string} type
         * @param {string} [setId]
         * @returns {string}
         */
        getUrl: function (scope, type, setId) {
            let url = scope + '/layout/' + type;

            if (setId) {
                url += '/' + setId;
            }

            return url;
        },

        /**
         * @callback module:layout-manager~getCallback
         *
         * @param {*} layout A layout.
         */

        /**
         * Get a layout.
         *
         * @param {string} scope A scope (entity type).
         * @param {string} type A layout type (name).
         * @param {module:layout-manager~getCallback} callback
         * @param {boolean} [cache=true] Use cache.
         */
        get: function (scope, type, callback, cache) {
            if (typeof cache === 'undefined') {
                cache = true;
            }

            let key = this.getKey(scope, type);

            if (cache) {
                if (key in this.data) {
                    if (typeof callback === 'function') {
                        callback(this.data[key]);
                    }

                    return;
                }
            }

            if (this.cache && cache) {
                let cached = this.cache.get('app-layout', key);

                if (cached) {
                    if (typeof callback === 'function') {
                        callback(cached);
                    }

                    this.data[key] = cached;

                    return;
                }
            }

            this.ajax
                .getRequest(this.getUrl(scope, type))
                .then(
                    layout => {
                        if (typeof callback === 'function') {
                            callback(layout);
                        }

                        this.data[key] = layout;

                        if (this.cache) {
                            this.cache.set('app-layout', key, layout);
                        }
                    }
                );
        },

        /**
         * Get an original layout.
         *
         * @param {string} scope A scope (entity type).
         * @param {string} type A layout type (name).
         * @param {string} [setId]
         * @param {module:layout-manager~getCallback} callback
         */
        getOriginal: function (scope, type, setId, callback) {
            let url = 'Layout/action/getOriginal?scope='+scope+'&name='+type;

            if (setId) {
                url += '&setId=' + setId;
            }

            Espo.Ajax
                .getRequest(url)
                .then(
                    layout => {
                        if (typeof callback === 'function') {
                            callback(layout);
                        }
                    }
                );
        },

        /**
         * Store and set a layout.
         *
         * @param {string} scope A scope (entity type).
         * @param {string} type A type (name).
         * @param {*} layout A layout.
         * @param {Function} callback A callback.
         * @param {string} [setId] A set ID.
         * @returns {Promise}
         */
        set: function (scope, type, layout, callback, setId) {
            return Espo.Ajax
                .putRequest(this.getUrl(scope, type, setId), layout)
                .then(
                    () => {
                        let key = this.getKey(scope, type);

                        if (this.cache && key) {
                            this.cache.clear('app-layout', key);
                        }

                        delete this.data[key];

                        this.trigger('sync');

                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                );
        },

        /**
         * Reset a layout to default.
         *
         * @param {string} scope A scope (entity type).
         * @param {string} type A type (name).
         * @param {Function} callback A callback.
         * @param {string} [setId] A set ID.
         */
        resetToDefault: function (scope, type, callback, setId) {
            Espo.Ajax
                .postRequest('Layout/action/resetToDefault', {
                    scope: scope,
                    name: type,
                    setId: setId,
                })
                .then(
                    () => {
                        let key = this.getKey(scope, type);

                        if (this.cache) {
                            this.cache.clear('app-layout', key);
                        }

                        delete this.data[key];

                        this.trigger('sync');

                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                );
        },

        /**
         * Clear loaded data.
         */
        clearLoadedData: function () {
            this.data = {};
        },

    }, Backbone.Events);

    return LayoutManager;
});
