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

Espo.define('views/fields/autoincrement', 'views/fields/int', function (Dep) {

    return Dep.extend({

        type: 'autoincrement',

        validations: [],

        inlineEditDisabled: true,

        readOnly: true,

        parse: function (value) {
            value = (value !== '') ? value : null;
            if (value !== null) {
                 if (value.indexOf('.') !== -1 || value.indexOf(',') !== -1) {
                     value = NaN;
                 } else {
                     value = parseInt(value);
                 }
            }
            return value;
        },

        fetch: function () {
            return {};
        },
    });
});

