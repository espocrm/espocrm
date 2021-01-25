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

Espo.define('views/admin/dynamic-logic/fields/field', 'views/fields/multi-enum', function (Dep) {

    return Dep.extend({

        getFieldList: function () {
            var fields = this.getMetadata().get('entityDefs.' + this.options.scope + '.fields');

            var filterList = Object.keys(fields).filter(function (field) {
                var fieldType = fields[field].type || null;
                if (fields[field].disabled) return;
                if (!fieldType) return;

                if (!this.getMetadata().get(['clientDefs', 'DynamicLogic', 'fieldTypes', fieldType])) return;

                return true;
            }, this);

            filterList.push('id');

            filterList.sort(function (v1, v2) {
                return this.translate(v1, 'fields', this.options.scope).localeCompare(this.translate(v2, 'fields', this.options.scope));
            }.bind(this));

            return filterList;
        },

        setupTranslatedOptions: function () {
            this.translatedOptions = {};
            this.params.options.forEach(function (item) {
                var field = item;
                this.translatedOptions[item] = this.translate(field, 'fields', this.options.scope);
            }, this);
        },

        setupOptions: function () {
            Dep.prototype.setupOptions.call(this);

            this.params.options = this.getFieldList();
            this.setupTranslatedOptions();
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.$element && this.$element[0] && this.$element[0].selectize) {
                this.$element[0].selectize.focus();
            }
        }

    });

});

