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

define('views/admin/dynamic-logic/fields/field', ['views/fields/multi-enum', 'ui/multi-select'],
function (Dep, /** module:ui/multi-select */MultiSelect) {

    return Dep.extend({

        getFieldList: function () {
            let fields = this.getMetadata().get('entityDefs.' + this.options.scope + '.fields');

            let filterList = Object.keys(fields).filter(field => {
                let fieldType = fields[field].type || null;

                if (
                    fields[field].disabled ||
                    fields[field].utility
                ) {
                    return;
                }

                if (!fieldType) {
                    return;
                }

                if (!this.getMetadata().get(['clientDefs', 'DynamicLogic', 'fieldTypes', fieldType])) {
                    return;
                }

                return true;
            });

            filterList.push('id');

            filterList.sort((v1, v2) => {
                return this.translate(v1, 'fields', this.options.scope)
                    .localeCompare(this.translate(v2, 'fields', this.options.scope));
            });

            return filterList;
        },

        setupTranslatedOptions: function () {
            this.translatedOptions = {};

            this.params.options.forEach(item => {
                this.translatedOptions[item] = this.translate(item, 'fields', this.options.scope);
            });
        },

        setupOptions: function () {
            Dep.prototype.setupOptions.call(this);

            this.params.options = this.getFieldList();
            this.setupTranslatedOptions();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.$element) {
                MultiSelect.focus(this.$element);
            }
        },
    });
});

