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

define('storage', [], function () {

    var Storage = function () {
    };

    _.extend(Storage.prototype, {

        prefix: 'espo',

        storageObject: localStorage,

        composeFullPrefix: function (type) {
            return this.prefix + '-' + type;
        },

        composeKey: function (type, name) {
            return this.composeFullPrefix(type) + '-' + name;
        },

        checkType: function (type) {
            if (typeof type === 'undefined' && toString.call(type) != '[object String]' || type == 'cache') {
                throw new TypeError("Bad type \"" + type + "\" passed to Espo.Storage.");
            }
        },

        has: function (type, name) {
            this.checkType(type);
            var key = this.composeKey(type, name);

            return this.storageObject.getItem(key) !== null;
        },

        get: function (type, name) {
            this.checkType(type);

            var key = this.composeKey(type, name);

            try {
                var stored = this.storageObject.getItem(key);
            } catch (error) {
                console.error(error);
                return null;
            }

            if (stored) {
                var result = stored;

                if (stored.length > 9 && stored.substr(0, 9) === '__JSON__:') {
                    var jsonString = stored.substr(9);
                    try {
                        result = JSON.parse(jsonString);
                    } catch (error) {
                        result = stored;
                    }
                } else if (stored[0] == "{" || stored[0] == "[") { // for backward compatibility
                    try {
                        result = JSON.parse(stored);
                    } catch (error) {
                        result = stored;
                    }
                }
                return result;
            }
            return null;
        },

        set: function (type, name, value) {
            this.checkType(type);

            var key = this.composeKey(type, name);
            if (value instanceof Object || Array.isArray(value) || value === true || value === false) {
                value = '__JSON__:' + JSON.stringify(value);
            }
            try {
                this.storageObject.setItem(key, value);
            } catch (error) {
                console.error(error);
                return null;
            }
        },

        clear: function (type, name) {
            var reText;
            if (typeof type !== 'undefined') {
                if (typeof name === 'undefined') {
                    reText = '^' + this.composeFullPrefix(type);
                } else {
                    reText = '^' + this.composeKey(type, name);
                }
            } else {
                reText = '^' + this.prefix + '-';
            }
            var re = new RegExp(reText);
            for (var i in this.storageObject) {
                if (re.test(i)) {
                    delete this.storageObject[i];
                }
            }
        }
    });

    Storage.extend = Backbone.Router.extend;

    return Storage;
});
