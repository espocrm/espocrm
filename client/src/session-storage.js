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

Espo.define('session-storage', 'storage', function (Dep) {

    return Dep.extend({

        storageObject: sessionStorage,

        get: function (name) {
            var stored = this.storageObject.getItem(name);
            if (stored) {
                var str = stored;
                if (stored[0] == "{" || stored[0] == "[") {
                    try {
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

        set: function (name, value) {
            if (value instanceof Object) {
                value = JSON.stringify(value);
            }
            this.storageObject.setItem(name, value);
        },

        clear: function (name) {
            for (var i in this.storageObject) {
                if (i === name) {
                    delete this.storageObject[i];
                }
            }
        }

    });
});
