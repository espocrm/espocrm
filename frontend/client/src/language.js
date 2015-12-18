/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

Espo.define('language', [], function () {

    var Language = function (cache) {
        this.cache = cache || null;
        this.data = {};
    };

    _.extend(Language.prototype, {

        data: null,

        cache: null,

        url: 'I18n',

        has: function (name, category, scope) {
            if (scope in this.data) {
                if (category in this.data[scope]) {
                    if (name in this.data[scope][category]) {
                        return true;
                    }
                }
            }
        },

        get: function (scope, category, name) {
            if (scope in this.data) {
                if (category in this.data[scope]) {
                    if (name in this.data[scope][category]) {
                        return this.data[scope][category][name];
                    }
                }
            }
            if (scope == 'Global') {
                return name;
            }
            return false;
        },

        translate: function (name, category, scope) {
            scope = scope || 'Global';
            category = category || 'labels';
            var res = this.get(scope, category, name);
            if (res === false && scope != 'Global') {
                res = this.get('Global', category, name);
            }
            return res;
        },

        translateOption: function (value, field, scope) {
            var translation = this.translate(field, 'options', scope);
            if (typeof translation != 'object') {
                translation = {};
            }
            return translation[value] || value;
        },

        loadFromCache: function () {

            if (this.cache) {
                var cached = this.cache.get('app', 'language');
                if (cached) {
                    this.data = cached;
                    return true;
                }
            }
            return null;
        },

        clearCache: function () {
            if (this.cache) {
                this.cache.clear('app', 'language');
            }
        },

        storeToCache: function () {
            if (this.cache) {
                this.cache.set('app', 'language', this.data);
            }
        },

        load: function (callback, disableCache) {
            this.once('sync', callback);

            if (!disableCache) {
                if (this.loadFromCache()) {
                    this.trigger('sync');
                    return;
                }
            }

            this.fetch();
        },

        fetch: function (sync) {
            var self = this;
            $.ajax({
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

        sortFieldList: function (scope, fieldList) {
            return fieldList.sort(function (v1, v2) {
                 return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
            }.bind(this));
        },

        sortEntityList: function (entityList, plural) {
            var category = 'scopeNames';
            if (plural) {
                category += 'Plural';
            }
            return entityList.sort(function (v1, v2) {
                 return this.translate(v1, category).localeCompare(this.translate(v2, category));
            }.bind(this));
        }

    }, Backbone.Events);

    return Language;

});


