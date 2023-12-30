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

/** @module views/fields/address */

import BaseFieldView from 'views/fields/base';
import Varchar from 'views/fields/varchar';

/**
 * An address field.
 */
class AddressFieldView extends BaseFieldView {

    type = 'address'

    listTemplate = 'fields/address/detail'
    detailTemplate = 'fields/address/detail'
    editTemplate = 'fields/address/edit'
    editTemplate1 = 'fields/address/edit-1'
    editTemplate2 = 'fields/address/edit-2'
    editTemplate3 = 'fields/address/edit-3'
    editTemplate4 = 'fields/address/edit-4'
    searchTemplate = 'fields/address/search'

    postalCodeField
    streetField
    cityField
    stateField
    countryField

    /** @inheritDoc */
    validations = [
        'required',
        'pattern',
    ]

    /** @inheritDoc */
    events = {
        /** @this AddressFieldView */
        'click [data-action="viewMap"]': function (e) {
            e.preventDefault();
            e.stopPropagation();

            this.viewMapAction();
        },
    }

    data() {
        let data = super.data();

        data.ucName = Espo.Utils.upperCaseFirst(this.name);

        this.addressPartList.forEach(item => {
            data[item + 'Value'] = this.model.get(this[item + 'Field']);
        });

        if (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST) {
            data.formattedAddress = this.getFormattedAddress();

            data.isNone = data.formattedAddress === null;

            if (data.formattedAddress === -1) {
                data.formattedAddress = null;
                data.isLoading = true;
            }

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
    }

    setupSearch() {
        this.searchData.value = this.getSearchParamsData().value || this.searchParams.additionalValue;
    }

    canBeDisplayedOnMap() {
        return !!this.model.get(this.name + 'City') || !!this.model.get(this.name + 'PostalCode');
    }

    getFormattedAddress() {
        let isNotEmpty = false;
        let isSet = false;

        this.addressAttributeList.forEach(attribute => {
            isNotEmpty = isNotEmpty || this.model.get(attribute);
            isSet = isSet || this.model.has(attribute);
        });

        let isEmpty = !isNotEmpty;

        if (isEmpty) {
            if (this.mode === this.MODE_LIST) {
                return '';
            }

            if (!isSet) {
                return -1;
            }

            return null;
        }

        let methodName = 'getFormattedAddress' + this.getAddressFormat().toString();

        if (methodName in this) {
            return this[methodName]();
        }
    }

    getFormattedAddress1() {
        let postalCodeValue = this.model.get(this.postalCodeField);
        let streetValue = this.model.get(this.streetField);
        let cityValue = this.model.get(this.cityField);
        let stateValue = this.model.get(this.stateField);
        let countryValue = this.model.get(this.countryField);

        let html = '';

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
    }

    getFormattedAddress2() {
        let postalCodeValue = this.model.get(this.postalCodeField);
        let streetValue = this.model.get(this.streetField);
        let cityValue = this.model.get(this.cityField);
        let stateValue = this.model.get(this.stateField);
        let countryValue = this.model.get(this.countryField);

        let html = '';

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
    }

    getFormattedAddress3() {
        let postalCodeValue = this.model.get(this.postalCodeField);
        let streetValue = this.model.get(this.streetField);
        let cityValue = this.model.get(this.cityField);
        let stateValue = this.model.get(this.stateField);
        let countryValue = this.model.get(this.countryField);

        let html = '';

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
    }

    getFormattedAddress4() {
        let postalCodeValue = this.model.get(this.postalCodeField);
        let streetValue = this.model.get(this.streetField);
        let cityValue = this.model.get(this.cityField);
        let stateValue = this.model.get(this.stateField);
        let countryValue = this.model.get(this.countryField);

        let html = '';

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
    }

    _getTemplateName() {
        if (this.mode === this.MODE_EDIT) {
            let prop = 'editTemplate' + this.getAddressFormat().toString();

            if (prop in this) {
                return this[prop];
            }
        }

        return super._getTemplateName();
    }

    getAddressFormat() {
        return this.getConfig().get('addressFormat') || 1;
    }

    afterRender() {
        if (this.mode === this.MODE_EDIT) {
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

            let countryList = this.getConfig().get('addressCountryList') || [];

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
                    onSelect: () => {
                        this.trigger('change');

                        this.$country.focus();
                    },
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

            let cityList = this.getConfig().get('addressCityList') || [];

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

                        this.$city.focus();
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

            let stateList = this.getConfig().get('addressStateList') || [];

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

                        this.$state.focus();
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
    }

    controlStreetTextareaHeight(lastHeight) {
        let scrollHeight = this.$street.prop('scrollHeight');
        let clientHeight = this.$street.prop('clientHeight');

        if (typeof lastHeight === 'undefined' && clientHeight === 0) {
            setTimeout(this.controlStreetTextareaHeight.bind(this), 10);

            return;
        }

        if (clientHeight === lastHeight) return;

        if (scrollHeight > clientHeight + 1) {
            let rows = this.$street.prop('rows');
            this.$street.attr('rows', rows + 1);

            this.controlStreetTextareaHeight(clientHeight);
        }

        if (this.$street.val().length === 0) {
            this.$street.attr('rows', 1);
        }
    }

    setup() {
        super.setup();

        let actualAttributePartList = this.getMetadata().get(['fields', this.type, 'actualFields']) || [];

        this.addressAttributeList = [];
        this.addressPartList = [];

        actualAttributePartList.forEach(item => {
            let attribute = this.name + Espo.Utils.upperCaseFirst(item);

            this.addressAttributeList.push(attribute);
            this.addressPartList.push(item);

            this[item + 'Field'] = attribute;

            this[item + 'MaxLength'] =
                this.getMetadata().get(['entityDefs', this.entityType, 'fields', attribute, 'maxLength']);
        });
    }

    validateRequired() {
        let validate = name => {
            if (this.model.isRequired(name)) {
                if (this.model.get(name) === '') {
                    let msg = this.translate('fieldIsRequired', 'messages')
                        .replace('{field}', this.translate(name, 'fields', this.entityType));

                    this.showValidationMessage(msg, '[data-name="'+name+'"]');

                    return true;
                }
            }
        };

        let result = false;

        result = validate(this.postalCodeField) || result;
        result = validate(this.streetField) || result;
        result = validate(this.stateField) || result;
        result = validate(this.cityField) || result;
        result = validate(this.countryField) || result;

        return result;
    }

    isRequired() {
        return this.model.getFieldParam(this.postalCodeField, 'required') ||
            this.model.getFieldParam(this.streetField, 'required') ||
            this.model.getFieldParam(this.stateField, 'required') ||
            this.model.getFieldParam(this.cityField, 'required') ||
            this.model.getFieldParam(this.countryField, 'required');
    }

    validatePattern() {
        let fieldList = [
            this.postalCodeField,
            this.stateField,
            this.cityField,
            this.countryField,
        ];

        let result = false;

        for (let field of fieldList) {
            result = Varchar.prototype.fieldValidatePattern.call(this, field) || result;
        }

        return result;
    }

    fetch() {
        let data = {};

        data[this.postalCodeField] = this.$postalCode.val().toString().trim();
        data[this.streetField] = this.$street.val().toString().trim();
        data[this.stateField] = this.$state.val().toString().trim();
        data[this.cityField] = this.$city.val().toString().trim();
        data[this.countryField] = this.$country.val().toString().trim();

        let attributeList = [
            this.postalCodeField,
            this.streetField,
            this.stateField,
            this.cityField,
            this.countryField,
        ];

        attributeList.forEach(attribute => {
            if (data[attribute] === '') {
                data[attribute] = null;
            }
        });

        return data;
    }

    fetchSearch() {
        let value = this.$el.find('input.main-element')
            .val()
            .toString()
            .trim();

        if (!value) {
            return null;
        }

        return {
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
    }

    viewMapAction() {
        this.createView('mapDialog', 'views/modals/view-map', {
            model: this.model,
            field: this.name,
        }, view => view.render());
    }
}

export default AddressFieldView;
