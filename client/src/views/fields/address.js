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

define('views/fields/address', 'views/fields/base', function (Dep) {

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

        events: {
            'click [data-action="viewMap"]': function (e) {
                e.preventDefault();
                e.stopPropagation();

                this.viewMapAction();
            },
        },

        data: function () {
            var data = Dep.prototype.data.call(this);

            data.ucName = Espo.Utils.upperCaseFirst(this.name);

            this.addressPartList.forEach(item => {
                var value = this.model.get(this[item + 'Field']);

                data[item + 'Value'] = value;
            });

            if (this.mode === 'detail' || this.mode === 'list') {
                data.formattedAddress = this.getFormattedAddress();

                if (this.params.viewMap && this.canBeDisplayedOnMap()) {
                    data.viewMap = true;

                    data.viewMapLink = '#AddressMap/view/' +
                        this.model.entityType + '/' +
                        this.model.id + '/' +
                        this.name;
                }
            }

            if (this.isEditMode()) {
                data.stateMaxLength = this.stateMaxLength;
                data.streetMaxLength = this.streetMaxLength;
                data.postalCodeMaxLength = this.postalCodeMaxLength;
                data.cityMaxLength = this.cityMaxLength;
                data.countryMaxLength = this.countryMaxLength;
            }

            return data;
        },

        setupSearch: function () {
            this.searchData.value = this.getSearchParamsData().value || this.searchParams.additionalValue;
        },

        canBeDisplayedOnMap: function () {
            return !!this.model.get(this.name + 'City') || !!this.model.get(this.name + 'PostalCode');
        },

        getFormattedAddress: function () {
            var isNotEmpty = false;
            var isSet = false;

            this.addressAttributeList.forEach(attribute => {
                isNotEmpty = isNotEmpty || this.model.get(attribute);
                isSet = isSet || this.model.has(attribute);
            });

            var isEmpty = !isNotEmpty;

            if (isEmpty) {
                if (this.mode === 'list') {
                    return '';
                }

                if (!isSet) {
                    return this.translate('...');
                }

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
                html += streetValue;
            }

            if (cityValue || stateValue || postalCodeValue) {
                if (html !== '') {
                    html += '\n';
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
                if (html !== '') {
                    html += '\n';
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
                html += streetValue;
            }

            if (cityValue || postalCodeValue) {
                if (html !== '') {
                    html += '\n';
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
                if (html !== '') {
                    html += '\n';
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
                if (html !== '') {
                    html += '\n';
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
                if (html !== '') {
                    html += '\n';
                }

                html += streetValue;
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
                html += streetValue;
            }

            if (cityValue) {
                if (html !== '') {
                    html += '\n';
                }

                html += cityValue;
            }

            if (countryValue || stateValue || postalCodeValue) {
                if (html !== '') {
                    html += '\n';
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
            if (this.mode === 'edit') {
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
            if (this.mode === 'edit') {
                this.$street = this.$el.find('[data-name="' + this.streetField + '"]');
                this.$postalCode = this.$el.find('[data-name="' + this.postalCodeField + '"]');
                this.$state = this.$el.find('[data-name="' + this.stateField + '"]');
                this.$city = this.$el.find('[data-name="' + this.cityField + '"]');
                this.$country = this.$el.find('[data-name="' + this.countryField + '"]');

                this.$street.on('change', () => {
                    this.trigger('change');
                });

                this.$postalCode.on('change', () => {
                    this.trigger('change');
                });

                this.$state.on('change', () => {
                    this.trigger('change');
                });

                this.$city.on('change', () => {
                    this.trigger('change');
                });

                this.$country.on('change', () => {
                    this.trigger('change');
                });

                var countryList = this.getConfig().get('addressCountryList') || [];

                if (countryList.length) {
                    this.$country.autocomplete({
                        minChars: 0,
                        lookup: countryList,
                        maxHeight: 200,
                        formatResult: suggestion => {
                            return this.getHelper().escapeString(suggestion.value);
                        },
                        lookupFilter: (suggestion, query, queryLowerCase) => {
                            if (suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0) {
                                if (suggestion.value.length === queryLowerCase.length) {
                                    return false;
                                }

                                return true;
                            }

                            return false;
                        },
                        onSelect: () => this.trigger('change'),
                    });

                    this.$country.on('focus', () => {
                        if (this.$country.val()) {
                            return;
                        }

                        this.$country.autocomplete('onValueChange');
                    });

                    this.once('render', () => {
                        this.$country.autocomplete('dispose');
                    });

                    this.once('remove', () => {
                        this.$country.autocomplete('dispose');
                    });

                    this.$country.attr('autocomplete', 'espo-country');
                }

                var cityList = this.getConfig().get('addressCityList') || [];

                if (cityList.length) {
                    this.$city.autocomplete({
                        minChars: 0,
                        lookup: cityList,
                        maxHeight: 200,
                        formatResult: (suggestion) => {
                            return this.getHelper().escapeString(suggestion.value);
                        },
                        lookupFilter: (suggestion, query, queryLowerCase) => {
                            if (suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0) {
                                if (suggestion.value.length === queryLowerCase.length) {
                                    return false;
                                }

                                return true;
                            }

                            return false;
                        },
                        onSelect: () => {
                            this.trigger('change');
                        },
                    });

                    this.$city.on('focus', () => {
                        if (this.$city.val()) {
                            return;
                        }

                        this.$city.autocomplete('onValueChange');
                    });

                    this.once('render', () => {
                        this.$city.autocomplete('dispose');
                    });

                    this.once('remove', () => {
                        this.$city.autocomplete('dispose');
                    });

                    this.$city.attr('autocomplete', 'espo-city');
                }

                var stateList = this.getConfig().get('addressStateList') || [];

                if (stateList.length) {
                    this.$state.autocomplete({
                        minChars: 0,
                        lookup: stateList,
                        maxHeight: 200,
                        formatResult: suggestion => {
                            return this.getHelper().escapeString(suggestion.value);
                        },
                        lookupFilter: function (suggestion, query, queryLowerCase) {
                            if (suggestion.value.toLowerCase().indexOf(queryLowerCase) === 0) {
                                if (suggestion.value.length === queryLowerCase.length) {
                                    return false;
                                }

                                return true;
                            }

                            return false;
                        },
                        onSelect: () => {
                            this.trigger('change');
                        },
                    });

                    this.$state.on('focus', () => {
                        if (this.$state.val()) {
                            return;
                        }

                        this.$state.autocomplete('onValueChange');
                    });

                    this.once('render', () => {
                        this.$state.autocomplete('dispose');
                    });

                    this.once('remove', () => {
                        this.$state.autocomplete('dispose');
                    });

                    this.$state.attr('autocomplete', 'espo-state');
                }

                this.controlStreetTextareaHeight();

                this.$street.on('input', () => {
                    this.controlStreetTextareaHeight();
                });
            }
        },

        controlStreetTextareaHeight: function (lastHeight) {
            var scrollHeight = this.$street.prop('scrollHeight');
            var clientHeight = this.$street.prop('clientHeight');

            if (typeof lastHeight === 'undefined' && clientHeight === 0) {
                setTimeout(this.controlStreetTextareaHeight.bind(this), 10);

                return;
            }

            if (clientHeight === lastHeight) return;

            if (scrollHeight > clientHeight + 1) {
                var rows = this.$street.prop('rows');
                this.$street.attr('rows', rows + 1);

                this.controlStreetTextareaHeight(clientHeight);
            }

            if (this.$street.val().length === 0) {
                this.$street.attr('rows', 1);
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            var actualAttributePartList = this.getMetadata().get(['fields', this.type, 'actualFields']) || [];
            this.addressAttributeList = [];
            this.addressPartList = [];

            actualAttributePartList.forEach(item => {
                var attribute = this.name + Espo.Utils.upperCaseFirst(item);

                this.addressAttributeList.push(attribute);
                this.addressPartList.push(item);

                this[item + 'Field'] = attribute;

                this[item + 'MaxLength'] =
                    this.getMetadata().get(['entityDefs', this.model.name, 'fields', attribute, 'maxLength']);
            });
        },

        validateRequired: function () {
            var validate = name => {
                if (this.model.isRequired(name)) {
                    if (this.model.get(name) === '') {
                        var msg = this.translate('fieldIsRequired', 'messages')
                            .replace('{field}', this.translate(name, 'fields', this.model.name));

                        this.showValidationMessage(msg, '[data-name="'+name+'"]');

                        return true;
                    }
                }
            };

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
                this.model.getFieldParam(this.cityField, 'required') ||
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
            var value = this.$el.find('input.main-element')
                .val()
                .toString()
                .trim();

            if (value) {
                var data = {
                    type: 'or',
                    value: [
                        {
                            type: 'like',
                            field: this.postalCodeField,
                            value: value + '%'
                        },
                        {
                            type: 'like',
                            field: this.streetField,
                            value: value + '%'
                        },
                        {
                            type: 'like',
                            field: this.cityField,
                            value: value + '%'
                        },
                        {
                            type: 'like',
                            field: this.stateField,
                            value: value + '%'
                        },
                            {
                            type: 'like',
                            field: this.countryField,
                            value: value + '%'
                        }
                    ],
                    data: {
                        value: value
                    }
                };

                return data;
            }

            return false;
        },

        viewMapAction: function () {
            this.createView('mapDialog', 'views/modals/view-map', {
                model: this.model,
                field: this.name,
            }, view => view.render());
        },

    });
});
