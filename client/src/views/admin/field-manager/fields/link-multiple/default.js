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

define('views/admin/field-manager/fields/link-multiple/default', 'views/fields/link-multiple', function (Dep) {

    return Dep.extend({

        data: function () {
            var defaultAttributes = this.model.get('defaultAttributes') || {};

            var nameHash = defaultAttributes[this.options.field + 'Names'] || {};
            var idValues = defaultAttributes[this.options.field + 'Ids'] || [];

            var data = Dep.prototype.data.call(this);

            data.nameHash = nameHash;
            data.idValues = idValues;

            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            this.foreignScope = this.getMetadata().get(['entityDefs', this.options.scope, 'links', this.options.field, 'entity']);
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            var defaultAttributes = {};
            defaultAttributes[this.options.field + 'Ids'] = data[this.idsName];
            defaultAttributes[this.options.field + 'Names'] = data[this.nameHashName];

            if (data[this.idsName] === null || data[this.idsName].length === 0) {
                defaultAttributes = null;
            }

            return {
                defaultAttributes: defaultAttributes
            };
        },

        copyValuesFromModel: function () {
            var defaultAttributes = this.model.get('defaultAttributes') || {};

            var idValues = defaultAttributes[this.options.field + 'Ids'] || [];
            var nameHash = defaultAttributes[this.options.field + 'Names'] || {};

            this.ids = idValues;
            this.nameHash = nameHash;
        },

    });
});
