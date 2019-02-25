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

define('ajax', [], function () {

    var Ajax = Espo.Ajax = {

        request: function (url, type, data, options) {
            var options = options || {};
            options.type = type;
            options.url = url;

            if (data) {
                options.data = data;
            }

            var xhr = $.ajax(options);

            return xhr;

            var obj = {
                then: xhr.then,
                fail: xhr.fail,
                catch: xhr.fail
            };

            return obj;
        },


        postRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }
            return Ajax.request(url, 'POST', data, options);
        },

        patchRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }
            return Ajax.request(url, 'PATCH', data, options);
        },

        putRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }
            return Ajax.request(url, 'PUT', data, options);
        },

        deleteRequest: function (url, data, options) {
            if (data) {
                data = JSON.stringify(data);
            }
            return Ajax.request(url, 'DELETE', data, options);
        },

        getRequest: function (url, data, options) {
            return Ajax.request(url, 'GET', data, options);
        }
    };

    return Ajax;

});
