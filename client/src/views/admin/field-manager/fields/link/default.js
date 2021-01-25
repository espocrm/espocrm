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

Espo.define('views/admin/field-manager/fields/link/default', 'views/fields/link', function (Dep) {

    return Dep.extend({

        data: function () {
            var defaultAttributes = this.model.get('defaultAttributes') || {};
            var nameValue = defaultAttributes[this.options.field + 'Name'] || null;
            var idValue = defaultAttributes[this.options.field + 'Id'] || null;

            var data = Dep.prototype.data.call(this);

            data.nameValue = nameValue;
            data.idValue = idValue;

            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.foreignScope = this.getMetadata().get(['entityDefs', this.options.scope, 'links', this.options.field, 'entity']);
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            var defaultAttributes = {};
            defaultAttributes[this.options.field + 'Id'] = data[this.idName];
            defaultAttributes[this.options.field + 'Name'] = data[this.nameName];

            if (data[this.idName] === null) {
                defaultAttributes = null;
            }

            return {
                defaultAttributes: defaultAttributes
            };
        }

    });

});
