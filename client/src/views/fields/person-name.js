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

Espo.define('views/fields/person-name', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        type: 'personName',

        detailTemplate: 'fields/person-name/detail',

        editTemplate: 'fields/person-name/edit',

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.ucName = Espo.Utils.upperCaseFirst(this.name);
            data.salutationValue = this.model.get(this.salutationField);
            data.firstValue = this.model.get(this.firstField);
            data.lastValue = this.model.get(this.lastField);
            data.salutationOptions = this.model.getFieldParam(this.salutationField, 'options');
            data.firstMaxLength = this.model.getFieldParam(this.firstField, 'maxLength');
            data.lastMaxLength = this.model.getFieldParam(this.lastField, 'maxLength');

            data.valueIsSet = this.model.has(this.firstField) || this.model.has(this.lastField);

            if (this.mode === 'detail') {
                data.isNotEmpty = !!data.firstValue || !!data.lastValue || !!data.salutationValue;
            } else if (this.mode === 'list' || this.mode === 'listLink') {
                data.isNotEmpty = !!data.firstValue || !!data.lastValue;
            }
            return data;
        },

        setup: function () {
            Dep.prototype.setup.call(this);
            var ucName = Espo.Utils.upperCaseFirst(this.name)
            this.salutationField = 'salutation' + ucName;
            this.firstField = 'first' + ucName;
            this.lastField = 'last' + ucName;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.mode == 'edit') {
                this.$salutation = this.$el.find('[data-name="' + this.salutationField + '"]');
                this.$first = this.$el.find('[data-name="' + this.firstField + '"]');
                this.$last = this.$el.find('[data-name="' + this.lastField + '"]');

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

        validateRequired: function () {
            var isRequired = this.isRequired();

            var validate = function (name) {
                if (this.model.isRequired(name)) {
                    if (this.model.get(name) === '') {
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
            return result;
        },

        hasRequiredMarker: function () {
            if (this.isRequired()) return true;
            return this.model.getFieldParam(this.salutationField, 'required') ||
                   this.model.getFieldParam(this.firstField, 'required') ||
                   this.model.getFieldParam(this.lastField, 'required');
        },

        fetch: function (form) {
            var data = {};
            data[this.salutationField] = this.$salutation.val();
            data[this.firstField] = this.$first.val().trim();
            data[this.lastField] = this.$last.val().trim();
            return data;
        }
    });
});
