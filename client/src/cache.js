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

define('cache', [], function () {

    var Cache = function (cacheTimestamp) {
        this.basePrefix = this.prefix;

        if (cacheTimestamp) {
            this.prefix =  this.basePrefix + '-' + cacheTimestamp;
        }

        if (!this.get('app', 'timestamp')) {
            this.storeTimestamp();
        }
    };

    _.extend(Cache.prototype, {

        prefix: 'cache',

        handleActuality: function (cacheTimestamp) {
            let storedTimestamp = this.getCacheTimestamp();

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
        },

        getCacheTimestamp: function () {
            return parseInt(this.get('app', 'cacheTimestamp') || 0);
        },

        storeTimestamp: function () {
            let frontendCacheTimestamp = Date.now();

            this.set('app', 'timestamp', frontendCacheTimestamp);
        },

        composeFullPrefix: function (type) {
            return this.prefix + '-' + type;
        },

        composeKey: function (type, name) {
            return this.composeFullPrefix(type) + '-' + name;
        },

        checkType: function (type) {
            if (typeof type === 'undefined' && toString.call(type) !== '[object String]') {
                throw new TypeError("Bad type \"" + type + "\" passed to Cache().");
            }
        },

        get: function (type, name) {
            this.checkType(type);

            let key = this.composeKey(type, name);

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

                if (stored.length > 9 && stored.substr(0, 9) === '__JSON__:') {
                    let jsonString = stored.substr(9);

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
        },

        set: function (type, name, value) {
            this.checkType(type);

            let key = this.composeKey(type, name);

            if (value instanceof Object || Array.isArray(value)) {
                value = '__JSON__:' + JSON.stringify(value);
            }

            try {
                localStorage.setItem(key, value);
            }
            catch (error) {
                console.log('Local storage limit exceeded.');
            }
        },

        clear: function (type, name) {
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

            let re = new RegExp(reText);

            for (var i in localStorage) {
                if (re.test(i)) {
                    delete localStorage[i];
                }
            }
        },
    });

    return Cache;
});
