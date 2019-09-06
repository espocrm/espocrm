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

define('language', ['ajax'], function (Ajax) {

    var Language = function (cache) {
        this.cache = cache || null;
        this.data = {};
        this.name = 'default';
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

        loadFromCache: function (loadDefault) {
            var name = this.name;
            if (loadDefault) {
                name = 'default';
            }
            if (this.cache) {
                var cached = this.cache.get('app', 'language-' + name);
                if (cached) {
                    this.data = cached;
                    return true;
                }
            }
            return null;
        },

        clearCache: function () {
            if (this.cache) {
                this.cache.clear('app', 'language-' + this.name);
            }
        },

        storeToCache: function (loadDefault) {
            var name = this.name;
            if (loadDefault) {
                name = 'default';
            }
            if (this.cache) {
                this.cache.set('app', 'language-' + name, this.data);
            }
        },

        load: function (callback, disableCache, loadDefault) {
            if (callback)
                this.once('sync', callback);

            if (!disableCache) {
                if (this.loadFromCache(loadDefault)) {
                    this.trigger('sync');
                    return;
                }
            }

            this.fetch(disableCache, loadDefault);
        },

        fetch: function (disableCache, loadDefault) {
            return Ajax.getRequest(this.url, {default: loadDefault}).then(function (data) {
                this.data = data;
                if (!disableCache) {
                    this.storeToCache(loadDefault);
                }
                this.trigger('sync');
            }.bind(this));
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
