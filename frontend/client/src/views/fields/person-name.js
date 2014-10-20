/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('Views.Fields.PersonName', 'Views.Fields.Varchar', function (Dep) {

    return Dep.extend({

        type: 'personName',

        detailTemplate: 'fields.person-name.detail',

        editTemplate: 'fields.person-name.edit',

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.ucName = Espo.Utils.upperCaseFirst(this.name);
            data.salutationValue = this.model.get(this.salutationField);
            data.firstValue = this.model.get(this.firstField);
            data.lastValue = this.model.get(this.lastField);
            data.salutationOptions = this.model.getFieldParam(this.salutationField, 'options');
            return data;
        },
        
        init: function () {
            var ucName = Espo.Utils.upperCaseFirst(this.options.defs.name)
            this.salutationField = 'salutation' + ucName;
            this.firstField = 'first' + ucName;
            this.lastField = 'last' + ucName;
            Dep.prototype.init.call(this);
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.mode == 'edit') {
                this.$salutation = this.$el.find('[name="' + this.salutationField + '"]');
                this.$first = this.$el.find('[name="' + this.firstField + '"]');
                this.$last = this.$el.find('[name="' + this.lastField + '"]');

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
            var validate = function (name) {
                if (this.model.isRequired(name)) {
                    if (this.model.get(name) === '') {
                        var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                        this.showValidationMessage(msg, 'input[name="'+name+'"]');
                        return true;
                    }
                }
            }.bind(this);

            var result = false;
            result = validate(this.salutationField) || result;
            result = validate(this.firstField) || result;
            result = validate(this.lastField) || result;
            return result;
        },

        isRequired: function () {
            return this.model.getFieldParam(this.salutationField, 'required') ||
                   this.model.getFieldParam(this.firstField, 'required') ||
                   this.model.getFieldParam(this.lastField, 'required');
        },

        fetch: function (form) {
            var data = {};
            data[this.salutationField] = this.$salutation.val();
            data[this.firstField] = this.$first.val();
            data[this.lastField] = this.$last.val();
            return data;
        },
    });
});

