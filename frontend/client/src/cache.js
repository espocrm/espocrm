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
 ************************************************************************/

Espo.define('Cache', [], function () {

    var Cache = function () {
        if (!this.get('app', 'timestamp')) {
            this.storeTimestamp();
        }
    };

    _.extend(Cache.prototype, {

        _prefix: 'cache',

        handleActuality: function (cacheTimestamp) {
            var stored = parseInt(this.get('app', 'cacheTimestamp'));
            if (stored) {
                if (stored !== cacheTimestamp) {
                    this.clear();
                    this.storeTimestamp();
                }
            } else {
                this.clear();
                this.set('app', 'cacheTimestamp', cacheTimestamp);
                this.storeTimestamp();
            }
        },

        storeTimestamp: function () {
            var frontendCacheTimestamp = Date.now();
            this.set('app', 'timestamp', frontendCacheTimestamp);
        },

        _composeFullPrefix: function (type) {
            return this._prefix + '-' + type;
        },

        _composeKey: function (type, name) {
            return this._composeFullPrefix(type) + '-' + name;
        },

        _checkType: function (type) {
            if (typeof type === 'undefined' && toString.call(type) != '[object String]') {
                throw new TypeError("Bad type \"" + type + "\" passed to Bull.Cacher().");
            }
        },

        get: function (type, name) {
            this._checkType(type);

            var key = this._composeKey(type, name);
            var stored = localStorage.getItem(key);
            if (stored) {
                var str = stored;
                if (stored[0] == "{" || stored[0] == "[") {
                    try    {
                        str = JSON.parse(stored);
                    } catch (error) {
                        str = stored;
                    }
                    stored = str;
                }
                return stored;
            }
            return null;
        },

        set: function (type, name, value) {
            this._checkType(type);
                var key = this._composeKey(type, name);
            if (value instanceof Object) {
                value = JSON.stringify(value);
            }
            localStorage.setItem(key, value);
        },

        clear: function (type, name) {
            var reText;
            if (typeof type !== 'undefined') {
                if (typeof name === 'undefined') {
                    reText = '^' + this._composeFullPrefix(type);
                } else {
                    reText = '^' + this._composeKey(type, name);
                }
            } else {
                reText = '^' + this._prefix + '-';
            }
            var re = new RegExp(reText);
            for (var i in localStorage) {
                if (re.test(i)) {
                    delete localStorage[i];
                }
            }
        },

    });

    return Cache;

});




