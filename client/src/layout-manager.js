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

define('layout-manager', [], function () {

    let LayoutManager = function (options, userId) {
        options = options || {};

        this.cache = options.cache || null;
        this.applicationId = options.applicationId || 'default-id';
        this.data = {};
        this.ajax = $.ajax;
        this.userId = userId;
    };

    _.extend(LayoutManager.prototype, {

        cache: null,

        data: null,

        getKey: function (scope, type) {
            if (this.userId) {
                return this.applicationId + '-' + this.userId + '-' + scope + '-' + type;
            }

            return this.applicationId + '-' + scope + '-' + type;
        },

        getUrl: function (scope, type, setId) {
            let url = scope + '/layout/' + type;

            if (setId) {
                url += '/' + setId;
            }

            return url;
        },

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

            Espo.Ajax
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

        clearLoadedData: function () {
            this.data = {};
        },

    }, Backbone.Events);

    return LayoutManager;
});
