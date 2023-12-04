/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/fields/int */

import BaseFieldView from 'views/fields/base';
import AutoNumeric from 'autonumeric';

/**
 * An integer field.
 */
class IntFieldView extends BaseFieldView {

    type = 'int'

    listTemplate = 'fields/int/list'
    detailTemplate = 'fields/int/detail'
    editTemplate = 'fields/int/edit'
    searchTemplate = 'fields/int/search'
    validations = ['required', 'int', 'range']

    thousandSeparator = ','

    searchTypeList = [
        'isNotEmpty',
        'isEmpty',
        'equals',
        'notEquals',
        'greaterThan',
        'lessThan',
        'greaterThanOrEquals',
        'lessThanOrEquals',
        'between',
    ]

    /**
     * @type {Object.<string, *>|null}
     * @protected
     */
    autoNumericOptions = null

    /**
     * @type {?AutoNumeric}
     * @protected
     */
    autoNumericInstance = null

    setup() {
        super.setup();

        this.setupMaxLength();

        if (this.getPreferences().has('thousandSeparator')) {
            this.thousandSeparator = this.getPreferences().get('thousandSeparator');
        }
        else if (this.getConfig().has('thousandSeparator')) {
            this.thousandSeparator = this.getConfig().get('thousandSeparator');
        }

        if (this.params.disableFormatting) {
            this.disableFormatting = true;
        }
    }

    setupFinal() {
        super.setupFinal();

        this.setupAutoNumericOptions();
    }

    /**
     * @protected
     */
    setupAutoNumericOptions() {
        let separator = (!this.disableFormatting ? this.thousandSeparator : null) || '';
        let decimalCharacter = '.';

        if (separator === '.') {
            decimalCharacter = ',';
        }

        this.autoNumericOptions = {
            digitGroupSeparator: separator,
            decimalCharacter: decimalCharacter,
            modifyValueOnWheel: false,
            decimalPlaces: 0,
            selectOnFocus: false,
            formulaMode: true,
        };
    }

    afterRender() {
        super.afterRender();

        if (this.mode === this.MODE_EDIT) {
            if (this.autoNumericOptions) {
                this.autoNumericInstance = new AutoNumeric(this.$element.get(0), this.autoNumericOptions);
            }
        }

        if (this.mode === this.MODE_SEARCH) {
            let $searchType = this.$el.find('select.search-type');

            this.handleSearchType($searchType.val());

            this.$el.find('select.search-type').on('change', () => {
                this.trigger('change');
            });

            this.$element.on('input', () => {
                this.trigger('change');
            });

            let $inputAdditional = this.$el.find('input.additional');

            $inputAdditional.on('input', () => {
                this.trigger('change');
            });

            if (this.autoNumericOptions) {
                new AutoNumeric(this.$element.get(0), this.autoNumericOptions);
                new AutoNumeric($inputAdditional.get(0), this.autoNumericOptions);
            }
        }
    }

    data() {
        let data = super.data();

        if (this.model.get(this.name) !== null && typeof this.model.get(this.name) !== 'undefined') {
            data.isNotEmpty = true;
        }

        data.valueIsSet = this.model.has(this.name);

        if (this.isSearchMode()) {
            data.value = this.searchParams.value;

            if (this.getSearchType() === 'between') {
                data.value = this.getSearchParamsData().value1 || this.searchParams.value1;
                data.value2 = this.getSearchParamsData().value2 || this.searchParams.value2;
            }
        }

        if (this.isEditMode()) {
            data.value = this.model.get(this.name);
        }

        return data;
    }

    getValueForDisplay() {
        let value = isNaN(this.model.get(this.name)) ? null : this.model.get(this.name);

        return this.formatNumber(value);
    }

    formatNumber(value) {
        if (this.disableFormatting) {
            return value;
        }

        return this.formatNumberDetail(value);
    }

    formatNumberDetail(value) {
        if (value === null) {
            return '';
        }

        let stringValue = value.toString();

        stringValue = stringValue.replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

        return stringValue;
    }

    setupSearch() {
        this.events['change select.search-type'] = e => {
            this.handleSearchType($(e.currentTarget).val());
        };
    }

    handleSearchType(type) {
        var $additionalInput = this.$el.find('input.additional');

        var $input = this.$el.find('input[data-name="'+this.name+'"]');

        if (type === 'between') {
            $additionalInput.removeClass('hidden');
            $input.removeClass('hidden');
        }
        else if (~['isEmpty', 'isNotEmpty'].indexOf(type)) {
            $additionalInput.addClass('hidden');
            $input.addClass('hidden');
        }
        else {
            $additionalInput.addClass('hidden');
            $input.removeClass('hidden');
        }
    }

    getMaxValue() {
        var maxValue = this.model.getFieldParam(this.name, 'max') || null;

        if (!maxValue && maxValue !== 0) {
            maxValue = null;
        }

        if ('max' in this.params) {
            maxValue = this.params.max;
        }

        return maxValue;
    }

    getMinValue() {
        var minValue = this.model.getFieldParam(this.name, 'min');

        if (!minValue && minValue !== 0) {
            minValue = null;
        }

        if ('min' in this.params) {
            minValue = this.params.min;
        }

        return minValue;
    }

    setupMaxLength() {
        var maxValue = this.getMaxValue();

        if (typeof max !== 'undefined' && max !== null) {
            maxValue = this.formatNumber(maxValue);

            this.params.maxLength = maxValue.toString().length;
        }
    }

    validateInt() {
        let value = this.model.get(this.name);

        if (isNaN(value)) {
            let msg = this.translate('fieldShouldBeInt', 'messages').replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }
    }

    validateRange() {
        let value = this.model.get(this.name);

        if (value === null) {
            return false;
        }

        let minValue = this.getMinValue();
        let maxValue = this.getMaxValue();

        if (minValue !== null && maxValue !== null) {
            if (value < minValue || value > maxValue ) {
                let msg = this.translate('fieldShouldBeBetween', 'messages')
                    .replace('{field}', this.getLabelText())
                    .replace('{min}', minValue)
                    .replace('{max}', maxValue);

                this.showValidationMessage(msg);

                return true;
            }
        }
        else {
            if (minValue !== null) {
                if (value < minValue) {
                    let msg = this.translate('fieldShouldBeGreater', 'messages')
                        .replace('{field}', this.getLabelText())
                        .replace('{value}', minValue);

                    this.showValidationMessage(msg);

                    return true;
                }
            }
            else if (maxValue !== null) {
                if (value > maxValue) {
                    let msg = this.translate('fieldShouldBeLess', 'messages')
                        .replace('{field}', this.getLabelText())
                        .replace('{value}', maxValue);
                    this.showValidationMessage(msg);

                    return true;
                }
            }
        }
    }

    validateRequired() {
        if (this.isRequired()) {
            let value = this.model.get(this.name);

            if (value === null || value === false) {
                let msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        }
    }

    parse(value) {
        value = (value !== '') ? value : null;

        if (value === null) {
            return null;
        }

        value = value
            .split(this.thousandSeparator)
            .join('');

        if (value.indexOf('.') !== -1 || value.indexOf(',') !== -1) {
            return NaN;
        }

        return parseInt(value);
    }

    fetch() {
        let value = this.$element.val();
        value = this.parse(value);

        let data = {};

        data[this.name] = value;

        return data;
    }

    fetchSearch() {
        let value = this.parse(this.$element.val());

        let type = this.fetchSearchType();

        let data;

        if (isNaN(value)) {
            return false;
        }

        if (type === 'between') {
            let valueTo = this.parse(this.$el.find('input.additional').val());

            if (isNaN(valueTo)) {
                return false;
            }

            data = {
                type: type,
                value: [value, valueTo],
                data: {
                    value1: value,
                    value2: valueTo
                }
            };
        }
        else if (type === 'isEmpty') {
            data = {
                type: 'isNull',
                typeFront: 'isEmpty'
            };
        }
        else if (type === 'isNotEmpty') {
            data = {
                type: 'isNotNull',
                typeFront: 'isNotEmpty'
            };
        }
        else {
            data = {
                type: type,
                value: value,
                data: {
                    value1: value
                }
            };
        }

        return data;
    }

    getSearchType() {
        return this.searchParams.typeFront || this.searchParams.type;
    }
}

export default IntFieldView;
