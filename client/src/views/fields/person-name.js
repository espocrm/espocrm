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

define('views/fields/person-name', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        type: 'personName',

        detailTemplate: 'fields/person-name/detail',

        editTemplate: 'fields/person-name/edit',

        editTemplateLastFirst: 'fields/person-name/edit-last-first',

        editTemplateLastFirstMiddle: 'fields/person-name/edit-last-first-middle',

        editTemplateFirstMiddleLast: 'fields/person-name/edit-first-middle-last',

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.ucName = Espo.Utils.upperCaseFirst(this.name);
            data.salutationValue = this.model.get(this.salutationField);
            data.firstValue = this.model.get(this.firstField);
            data.lastValue = this.model.get(this.lastField);
            data.middleValue = this.model.get(this.middleField);
            data.salutationOptions = this.model.getFieldParam(this.salutationField, 'options');

            if (this.mode === 'edit') {
                data.firstMaxLength = this.model.getFieldParam(this.firstField, 'maxLength');
                data.lastMaxLength = this.model.getFieldParam(this.lastField, 'maxLength');
                data.middleMaxLength = this.model.getFieldParam(this.middleField, 'maxLength');
            }

            data.valueIsSet = this.model.has(this.firstField) || this.model.has(this.lastField);

            if (this.mode === 'detail') {
                data.isNotEmpty = !!data.firstValue || !!data.lastValue || !!data.salutationValue || !!data.middleValue;
            } else if (this.mode === 'list' || this.mode === 'listLink') {
                data.isNotEmpty = !!data.firstValue || !!data.lastValue || !!data.middleValue;
            }

            if (data.isNotEmpty && this.mode == 'detail' || this.mode == 'list' || this.mode === 'listLink') {
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
            if (this.mode == 'edit') {
                this.$salutation = this.$el.find('[data-name="' + this.salutationField + '"]');
                this.$first = this.$el.find('[data-name="' + this.firstField + '"]');
                this.$last = this.$el.find('[data-name="' + this.lastField + '"]');

                if (this.formatHasMiddle()) {
                    this.$middle = this.$el.find('[data-name="' + this.middleField + '"]');
                }

                this.$salutation.on('change', function () {
                    this.trigger('change');
                }.bind(this));
                this.$first.on('change', function () {
                    this.trigger('change');
                }.bind(this));
                this.$last.on('change', function () {
                    this.trigger('change');
                }.bind(this));
            }
        },

        getFormattedValue: function () {
            var salutation = this.model.get(this.salutationField);
            var first = this.model.get(this.firstField);
            var last = this.model.get(this.lastField);
            var middle = this.model.get(this.middleField);

            if (salutation) {
                salutation = this.getLanguage().translateOption(salutation, 'salutationName', this.model.entityType);
            }

            var value = '';

            var format = this.getFormat();

            switch (format) {
                case 'lastFirst':
                    if (salutation) value += salutation;
                    if (last) value += ' ' + last;
                    if (first) value += ' ' + first;
                    break;

                case 'lastFirstMiddle':
                    var arr = [];
                    if (salutation) arr.push(salutation);
                    if (last) arr.push(last);
                    if (first) arr.push(first);
                    if (middle) arr.push(middle);
                    value = arr.join(' ');
                    break;

                case 'firstMiddleLast':
                    var arr = [];
                    if (salutation) arr.push(salutation);
                    if (first) arr.push(first);
                    if (middle) arr.push(middle);
                    if (last) arr.push(last);
                    value = arr.join(' ');
                    break;

                default:
                    if (salutation) value += salutation;
                    if (first) value += ' ' + first;
                    if (last) value += ' ' + last;
            }

            value = value.trim();

            return value;
        },

        _getTemplateName: function () {
            if (this.mode == 'edit') {
                var prop = 'editTemplate' + Espo.Utils.upperCaseFirst(this.getFormat().toString());
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
            var format = this.getFormat();

            return format === 'firstMiddleLast' || format === 'lastFirstMiddle';
        },

        validateRequired: function () {
            var isRequired = this.isRequired();

            var validate = function (name) {
                if (this.model.isRequired(name)) {
                    if (!this.model.get(name)) {
                        var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(name, 'fields', this.model.name));
                        this.showValidationMessage(msg, '[data-name="'+name+'"]');
                        return true;
                    }
                }
            }.bind(this);

            if (isRequired) {
                if (!this.model.get(this.firstField) && !this.model.get(this.lastField)) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());
                    this.showValidationMessage(msg, '[data-name="'+this.lastField+'"]');
                    return true;
                }
            }

            var result = false;
            result = validate(this.salutationField) || result;
            result = validate(this.firstField) || result;
            result = validate(this.lastField) || result;
            result = validate(this.middleField) || result;
            return result;
        },

        hasRequiredMarker: function () {
            if (this.isRequired()) return true;
            return this.model.getFieldParam(this.salutationField, 'required') ||
                   this.model.getFieldParam(this.firstField, 'required') ||
                   this.model.getFieldParam(this.middleField, 'required') ||
                   this.model.getFieldParam(this.lastField, 'required');
        },

        fetch: function (form) {
            var data = {};
            data[this.salutationField] = this.$salutation.val() || null;
            data[this.firstField] = this.$first.val().trim() || null;
            data[this.lastField] = this.$last.val().trim() || null;

            if (this.formatHasMiddle()) {
                data[this.middleField] = this.$middle.val().trim() || null;
            }

            return data;
        },
    });
});
