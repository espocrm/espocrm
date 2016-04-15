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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/fields/address', 'views/fields/base', function (Dep) {

    return Dep.extend({

        type: 'address',

        listTemplate: 'fields/address/detail',

        detailTemplate: 'fields/address/detail',

        editTemplate: 'fields/address/edit',

        editTemplate1: 'fields/address/edit-1',

        editTemplate2: 'fields/address/edit-2',

        editTemplate3: 'fields/address/edit-3',

        editTemplate4: 'fields/address/edit-4',

        searchTemplate: 'fields/address/search',

        data: function () {
            var data = Dep.prototype.data.call(this);
            data.ucName = Espo.Utils.upperCaseFirst(this.name);

            data.postalCodeValue = this.model.get(this.postalCodeField);
            data.streetValue = this.model.get(this.streetField);
            data.cityValue = this.model.get(this.cityField);
            data.stateValue = this.model.get(this.stateField);
            data.countryValue = this.model.get(this.countryField);

            if (this.mode == 'detail' || this.mode == 'list') {
                data.formattedAddress = this.getFormattedAddress();
            }

            data.isEmpty = !(data.postalCodeValue ||
                           data.streetValue ||
                           data.cityValue ||
                           data.stateValue ||
                           data.countryValue);

            return data;
        },

        getFormattedAddress: function () {
            var postalCodeValue = this.model.get(this.postalCodeField);
            var streetValue = this.model.get(this.streetField);
            var cityValue = this.model.get(this.cityField);
            var stateValue = this.model.get(this.stateField);
            var countryValue = this.model.get(this.countryField);

            var isEmpty = !(
                postalCodeValue ||
                streetValue ||
                cityValue ||
                stateValue ||
                countryValue
            );

            if (isEmpty) {
                return this.translate('None');
            }

            var methodName = 'getFormattedAddress' + this.getAddressFormat().toString();

            if (methodName in this) {
                return this[methodName]();
            }
        },

        getFormattedAddress1: function () {
            var postalCodeValue = this.model.get(this.postalCodeField);
            var streetValue = this.model.get(this.streetField);
            var cityValue = this.model.get(this.cityField);
            var stateValue = this.model.get(this.stateField);
            var countryValue = this.model.get(this.countryField);

            var html = '';
            if (streetValue) {
                html += streetValue.replace(/(\r\n|\n|\r)/gm, '<br>');
            }
            if (cityValue || stateValue || postalCodeValue) {
                if (html != '') {
                    html += '<br>'
                }
                if (cityValue) {
                    html += cityValue;
                }
                if (stateValue) {
                    if (cityValue) {
                        html += ', ';
                    }
                    html += stateValue;
                }
                if (postalCodeValue) {
                    if (cityValue || stateValue) {
                        html += ' ';
                    }
                    html += postalCodeValue;
                }
            }
            if (countryValue) {
                if (html != '') {
                    html += '<br>';
                }
                html += countryValue;
            }
            return html;
        },

        getFormattedAddress2: function () {
            var postalCodeValue = this.model.get(this.postalCodeField);
            var streetValue = this.model.get(this.streetField);
            var cityValue = this.model.get(this.cityField);
            var stateValue = this.model.get(this.stateField);
            var countryValue = this.model.get(this.countryField);

            var html = '';
            if (streetValue) {
                html += streetValue.replace(/(\r\n|\n|\r)/gm, '<br>');
            }
            if (cityValue || postalCodeValue) {
                if (html != '') {
                    html += '<br>'
                }
                if (postalCodeValue) {
                    html += postalCodeValue;
                    if (cityValue) {
                        html += ' ';
                    }
                }
                if (cityValue) {
                    html += cityValue;
                }
            }
            if (stateValue || countryValue) {
                if (html != '') {
                    html += '<br>';
                }
                if (stateValue) {
                    html += stateValue;
                    if (countryValue) {
                        html += ' ';
                    }
                }
                if (countryValue) {
                    html += countryValue;
                }
            }
            return html;
        },

        getFormattedAddress3: function () {
            var postalCodeValue = this.model.get(this.postalCodeField);
            var streetValue = this.model.get(this.streetField);
            var cityValue = this.model.get(this.cityField);
            var stateValue = this.model.get(this.stateField);
            var countryValue = this.model.get(this.countryField);

            var html = '';
            if (countryValue) {
                html += countryValue;
            }
            if (cityValue || stateValue || postalCodeValue) {
                if (html != '') {
                    html += '<br>'
                }
                if (postalCodeValue) {
                    html += postalCodeValue;
                }
                if (stateValue) {
                    if (postalCodeValue) {
                        html += ' ';
                    }
                    html += stateValue;
                }
                if (cityValue) {
                    if (postalCodeValue || stateValue) {
                        html += ' ';
                    }
                    html += cityValue;
                }
            }
            if (streetValue) {
                if (html != '') {
                    html += '<br>';
                }
                html += streetValue.replace(/(\r\n|\n|\r)/gm, '<br>');
            }
            return html;
        },

        getFormattedAddress4: function () {
            var postalCodeValue = this.model.get(this.postalCodeField);
            var streetValue = this.model.get(this.streetField);
            var cityValue = this.model.get(this.cityField);
            var stateValue = this.model.get(this.stateField);
            var countryValue = this.model.get(this.countryField);

            var html = '';
            if (streetValue) {
                html += streetValue.replace(/(\r\n|\n|\r)/gm, '<br>');
            }
            if (cityValue) {
                if (html != '') {
                    html += '<br>';
                }
                html += cityValue;
            }
            if (countryValue || stateValue || postalCodeValue) {
                if (html != '') {
                    html += '<br>'
                }
                if (countryValue) {
                    html += countryValue;
                }
                if (stateValue) {
                    if (countryValue) {
                        html += ' - ';
                    }
                    html += stateValue;
                }
                if (postalCodeValue) {
                    if (countryValue || stateValue) {
                        html += ' ';
                    }
                    html += postalCodeValue;
                }
            }

            return html;
        },

        _getTemplateName: function () {
            if (this.mode == 'edit') {
                var prop = 'editTemplate' + this.getAddressFormat().toString();
                if (prop in this) {
                    return this[prop];
                }
            }
            return Dep.prototype._getTemplateName.call(this);
        },

        getAddressFormat: function () {
            return this.getConfig().get('addressFormat') || 1;
        },

        afterRender: function () {
            var self = this;

            if (this.mode == 'edit') {
                this.$street = this.$el.find('[name="' + this.streetField + '"]');
                this.$postalCode = this.$el.find('[name="' + this.postalCodeField + '"]');
                this.$state = this.$el.find('[name="' + this.stateField + '"]');
                this.$city = this.$el.find('[name="' + this.cityField + '"]');
                this.$country = this.$el.find('[name="' + this.countryField + '"]');

                this.$street.on('change', function () {
                    self.trigger('change');
                });
                this.$postalCode.on('change', function () {
                    self.trigger('change');
                });
                this.$state.on('change', function () {
                    self.trigger('change');
                });
                this.$city.on('change', function () {
                    self.trigger('change');
                });
                this.$country.on('change', function () {
                    self.trigger('change');
                });

                this.$street.on('input', function (e) {
                    var numberOfLines = e.currentTarget.value.split('\n').length;
                    var numberOfRows = this.$street.prop('rows');

                    if (numberOfRows < numberOfLines) {
                        this.$street.prop('rows', numberOfLines);
                    } else if (numberOfRows > numberOfLines) {
                        this.$street.prop('rows', numberOfLines);
                    }
                }.bind(this));

                var numberOfLines = this.$street.val().split('\n').length;
                this.$street.prop('rows', numberOfLines);
            }
        },

        init: function () {
            this.postalCodeField = this.options.defs.name + 'PostalCode';
            this.streetField = this.options.defs.name + 'Street';
            this.stateField = this.options.defs.name + 'State';
            this.cityField = this.options.defs.name + 'City';
            this.countryField = this.options.defs.name + 'Country';
            Dep.prototype.init.call(this);
        },

        validateRequired: function () {
            var validate = function (name) {
                if (this.model.isRequired(name)) {
                    if (this.model.get(name) === '') {
                        var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                        this.showValidationMessage(msg, '[name="'+name+'"]');
                        return true;
                    }
                }
            }.bind(this);

            var result = false;
            result = validate(this.postalCodeField) || result;
            result = validate(this.streetField) || result;
            result = validate(this.stateField) || result;
            result = validate(this.cityField) || result;
            result = validate(this.countryField) || result;
            return result;
        },

        isRequired: function () {
            return this.model.getFieldParam(this.postalCodeField, 'required') ||
                   this.model.getFieldParam(this.streetField, 'required') ||
                   this.model.getFieldParam(this.stateField, 'required') ||
                   this.model.getFieldParam(this.cityField, 'required');
                   this.model.getFieldParam(this.countryField, 'required');
        },

        fetch: function () {
            var data = {};
            data[this.postalCodeField] = this.$postalCode.val().toString().trim();
            data[this.streetField] = this.$street.val().toString().trim();
            data[this.stateField] = this.$state.val().toString().trim();
            data[this.cityField] = this.$city.val().toString().trim();
            data[this.countryField] = this.$country.val().toString().trim();
            return data;
        },

        fetchSearch: function () {
            var value = this.$el.find('[name="'+this.name+'"]').val().toString().trim();
            if (value) {
                value += '%';
                var data = {
                    type: 'or',
                    value: [
                        {
                            type: 'like',
                            field: this.postalCodeField,
                            value: value
                        },
                        {
                            type: 'like',
                            field: this.streetField,
                            value: value
                        },
                        {
                            type: 'like',
                            field: this.cityField,
                            value: value
                        },
                        {
                            type: 'like',
                            field: this.stateField,
                            value: value
                        },
                            {
                            type: 'like',
                            field: this.countryField,
                            value: value
                        },
                    ],
                    additionalValue: value
                };
                return data;
            }
            return false;
        },
    });
});

