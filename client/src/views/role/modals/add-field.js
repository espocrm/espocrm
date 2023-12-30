/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/role/modals/add-field', ['views/modal'], function (Dep) {

    return Dep.extend({

        template: 'role/modals/add-field',

        events: {
            'click a[data-action="addField"]': function (e) {
                this.trigger('add-field', $(e.currentTarget).data().name);
            }
        },

        data: function () {
            var dataList = [];

            this.fieldList.forEach((field, i) => {
                if (i % 4 === 0) {
                    dataList.push([]);
                }

                dataList[dataList.length -1].push(field);
            });

            return {
                dataList: dataList,
                scope: this.scope
            };
        },

        setup: function () {
            this.headerText = this.translate('Add Field');

            var scope = this.scope = this.options.scope;
            var fields = this.getMetadata().get('entityDefs.' + scope + '.fields') || {};
            var fieldList = [];

            Object.keys(fields).forEach(field => {
                var d = fields[field];

                if (field in this.options.ignoreFieldList) {
                    return;
                }

                if (d.disabled) {
                    return;
                }

                if (
                    this.getMetadata()
                        .get(['app', this.options.type, 'mandatory', 'scopeFieldLevel', this.scope, field]) !== null
                ) {
                    return;
                }

                fieldList.push(field);
            });

            this.fieldList = this.getLanguage().sortFieldList(scope, fieldList);
        },
    });
});
