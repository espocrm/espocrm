/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

define('metadata', [], function () {

    var Metadata = function (cache) {
        this.cache = cache || null;

        this.data = {};
        this.ajax = $.ajax;
    }

    _.extend(Metadata.prototype, {

        cache: null,

        data: null,

        url: 'Metadata',

        load: function (callback, disableCache, sync) {
            var sync = (typeof sync == 'undefined') ? false: sync;
            this.off('sync');

            if (callback)
                this.once('sync', callback);

            if (!disableCache) {
                 if (this.loadFromCache()) {
                    this.trigger('sync');
                    return;
                }
            }
            return this.fetch(sync);
        },

        fetch: function (sync) {
            var self = this;
            return this.ajax({
                url: this.url,
                type: 'GET',
                dataType: 'JSON',
                async: !(sync || false),
                success: function (data) {
                    self.data = data;
                    self.storeToCache();
                    self.trigger('sync');
                }
            });
        },

        get: function (path, defaultValue) {
            defaultValue = defaultValue || null;
            var arr;
            if (Array && Array.isArray && Array.isArray(path)) {
                arr = path;
            } else {
                arr = path.split('.');
            }

            var pointer = this.data;
            var result = defaultValue;

            for (var i = 0; i < arr.length; i++) {
                var key = arr[i];

                if (!(key in pointer)) {
                    result = defaultValue;
                    break;
                }
                if (arr.length - 1 == i) {
                    result = pointer[key];
                }
                pointer = pointer[key];
            }
            return result;
        },

        loadFromCache: function () {
            if (this.cache) {
                var cached = this.cache.get('app', 'metadata');
                if (cached) {
                    this.data = cached;
                    return true;
                }
            }
            return null;
        },

        storeToCache: function () {
            if (this.cache) {
                this.cache.set('app', 'metadata', this.data);
            }
        },

        getScopeList: function () {
            var scopes = this.get('scopes') || {};
            var scopeList = [];
            for (scope in scopes) {
                var d = scopes[scope];
                if (d.disabled) continue;
                scopeList.push(scope);
            }
            return scopeList;
        },

        getScopeObjectList: function () {
            var scopes = this.get('scopes') || {};
            var scopeList = [];
            for (scope in scopes) {
                var d = scopes[scope];
                if (d.disabled) continue;
                if (!d.object) continue;
                scopeList.push(scope);
            }
            return scopeList;
        },

        getScopeEntityList: function () {
            var scopes = this.get('scopes') || {};
            var scopeList = [];
            for (scope in scopes) {
                var d = scopes[scope];
                if (d.disabled) continue;
                if (!d.entity) continue;
                scopeList.push(scope);
            }
            return scopeList;
        }

    }, Backbone.Events);

    return Metadata;
});
