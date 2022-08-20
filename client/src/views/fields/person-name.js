/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/person-name', ['views/fields/varchar'], function (Dep) {

    /**
     * @class
     * @name Class
     * @extends module:views/fields/varchar.Class
     * @memberOf module:views/fields/person-name
     */
    return Dep.extend(/** @lends module:views/fields/person-name.Class# */{

        type: 'personName',

        detailTemplate: 'fields/person-name/detail',

        editTemplate: 'fields/person-name/edit',

        editTemplateLastFirst: 'fields/person-name/edit-last-first',

        editTemplateLastFirstMiddle: 'fields/person-name/edit-last-first-middle',

        editTemplateFirstMiddleLast: 'fields/person-name/edit-first-middle-last',

        /**
         * @inheritDoc
         */
        validations: [
            'required',
            'pattern',
        ],

        data: function () {
            var data = Dep.prototype.data.call(this);

            data.ucName = Espo.Utils.upperCaseFirst(this.name);
            data.salutationValue = this.model.get(this.salutationField);
            data.firstValue = this.model.get(this.firstField);
            data.lastValue = this.model.get(this.lastField);
            data.middleValue = this.model.get(this.middleField);
            data.salutationOptions = this.model.getFieldParam(this.salutationField, 'options');

            if (this.isEditMode()) {
                data.firstMaxLength = this.model.getFieldParam(this.firstField, 'maxLength');
                data.lastMaxLength = this.model.getFieldParam(this.lastField, 'maxLength');
                data.middleMaxLength = this.model.getFieldParam(this.middleField, 'maxLength');
            }

            data.valueIsSet = this.model.has(this.firstField) || this.model.has(this.lastField);

            if (this.isDetailMode()) {
                data.isNotEmpty = !!data.firstValue || !!data.lastValue ||
                    !!data.salutationValue || !!data.middleValue;
            }
            else if (this.isListMode()) {
                data.isNotEmpty = !!data.firstValue || !!data.lastValue || !!data.middleValue;
            }

            if (
                data.isNotEmpty && this.isDetailMode() ||
                this.isListMode()
            ) {
                data.formattedValue = this.getFormattedValue();
            }

            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            var ucName = Espo.Utils.upperCaseFirst(this.name)
            this.salutationField = 'salutation' + ucName;
            this.firstField = 'first' + ucName;
            this.lastField = 'last' + ucName;
            this.middleField = 'middle' + ucName;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.isEditMode()) {
                this.$salutation = this.$el.find('[data-name="' + this.salutationField + '"]');
                this.$first = this.$el.find('[data-name="' + this.firstField + '"]');
                this.$last = this.$el.find('[data-name="' + this.lastField + '"]');

                if (this.formatHasMiddle()) {
                    this.$middle = this.$el.find('[data-name="' + this.middleField + '"]');
                }

                this.$salutation.on('change', () => {
                    this.trigger('change');
                });

                this.$first.on('change', () => {
                    this.trigger('change');
                });

                this.$last.on('change', () => {
                    this.trigger('change');
                });
            }
        },

        getFormattedValue: function () {
            let salutation = this.model.get(this.salutationField);
            let first = this.model.get(this.firstField);
            let last = this.model.get(this.lastField);
            let middle = this.model.get(this.middleField);

            if (salutation) {
                salutation = this.getLanguage()
                    .translateOption(salutation, 'salutationName', this.model.entityType);
            }

            return this.formatName({
                salutation: salutation,
                first: first,
                middle: middle,
                last: last,
            });
        },

        _getTemplateName: function () {
            if (this.isEditMode()) {
                let prop = 'editTemplate' + Espo.Utils.upperCaseFirst(this.getFormat().toString());

                if (prop in this) {
                    return this[prop];
                }
            }

            return Dep.prototype._getTemplateName.call(this);
        },

        getFormat: function () {
            this.format = this.format || this.getConfig().get('personNameFormat') || 'firstLast';

            return this.format;
        },

        formatHasMiddle: function () {
            let format = this.getFormat();

            return format === 'firstMiddleLast' || format === 'lastFirstMiddle';
        },

        validateRequired: function () {
            let isRequired = this.isRequired();

            let validate = (name) => {
                if (this.model.isRequired(name)) {
                    if (!this.model.get(name)) {
                        let msg = this.translate('fieldIsRequired', 'messages')
                            .replace('{field}', this.translate(name, 'fields', this.model.name));
                        this.showValidationMessage(msg, '[data-name="'+name+'"]');

                        return true;
                    }
                }
            };

            if (isRequired) {
                if (!this.model.get(this.firstField) && !this.model.get(this.lastField)) {
                    let msg = this.translate('fieldIsRequired', 'messages')
                        .replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg, '[data-name="'+this.lastField+'"]');

                    return true;
                }
            }

            let result = false;

            result = validate(this.salutationField) || result;
            result = validate(this.firstField) || result;
            result = validate(this.lastField) || result;
            result = validate(this.middleField) || result;

            return result;
        },

        validatePattern: function () {
            let result = false;

            result = this.fieldValidatePattern(this.firstField) || result;
            result = this.fieldValidatePattern(this.lastField) || result;
            result = this.fieldValidatePattern(this.middleField) || result;

            return result;
        },

        hasRequiredMarker: function () {
            if (this.isRequired()) {
                return true;
            }

            return this.model.getFieldParam(this.salutationField, 'required') ||
                   this.model.getFieldParam(this.firstField, 'required') ||
                   this.model.getFieldParam(this.middleField, 'required') ||
                   this.model.getFieldParam(this.lastField, 'required');
        },

        fetch: function () {
            let data = {};

            data[this.salutationField] = this.$salutation.val() || null;
            data[this.firstField] = this.$first.val().trim() || null;
            data[this.lastField] = this.$last.val().trim() || null;

            if (this.formatHasMiddle()) {
                data[this.middleField] = this.$middle.val().trim() || null;
            }

            data[this.name] = this.formatName({
                first: data[this.firstField],
                last: data[this.lastField],
                middle: data[this.middleField],
            });

            return data;
        },

        /**
         * @param {{first?: string, last?: string, middle?: string, salutation?: string}}data
         * @return {?string}
         */
        formatName: function (data) {
            let name = '';
            let format = this.getFormat();
            let arr = [];

            arr.push(data.salutation);

            if (format === 'firstLast') {
                arr.push(data.first);
                arr.push(data.last);
            }
            else if (format === 'lastFirst') {
                arr.push(data.last);
                arr.push(data.first);
            }
            else if (format === 'firstMiddleLast') {
                arr.push(data.first);
                arr.push(data.middle);
                arr.push(data.last);
            }
            else if (format === 'lastFirstMiddle') {
                arr.push(data.last);
                arr.push(data.first);
                arr.push(data.middle);
            }
            else {
                arr.push(data.first);
                arr.push(data.last);
            }

            name = arr.filter(item => !!item).join(' ').trim();

            if (name === '') {
                name = null;
            }

            return name;
        },
    });
});
