/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('storage', [], function () {

    var Storage = function () {
    };

    _.extend(Storage.prototype, {

        _prefix: 'espo',

        _composeFullPrefix: function (type) {
            return this._prefix + '-' + type;
        },

        _composeKey: function (type, name) {
            return this._composeFullPrefix(type) + '-' + name;
        },

        _checkType: function (type) {
            if (typeof type === 'undefined' && toString.call(type) != '[object String]' || type == 'cache') {
                throw new TypeError("Bad type \"" + type + "\" passed to Espo.Storage.");
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
        }
    });

    return Storage;
});
