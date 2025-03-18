/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/fields/currency */

import FloatFieldView from 'views/fields/float';
import Select from 'ui/select';

/**
 * A currency field.
 *
 * @extends IntFieldView<module:views/fields/currency~params>
 */
class CurrencyFieldView extends FloatFieldView {

    /**
     * @typedef {Object} module:views/fields/currency~options
     * @property {
     *     module:views/fields/currency~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/currency~params
     * @property {number} [min] A max value.
     * @property {number} [max] A max value.
     * @property {boolean} [required] Required.
     * @property {boolean} [disableFormatting] Disable formatting.
     * @property {number|null} [decimalPlaces] A number of decimal places. @todo
     * @property {boolean} [onlyDefaultCurrency] Only the default currency.
     * @property {boolean} [decimal] Stored as decimal.
     * @property {number} [scale] Scale (for decimal).
     */

    /**
     * @param {
     *     module:views/fields/currency~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'currency'

    editTemplate = 'fields/currency/edit'
    detailTemplate = 'fields/currency/detail'
    // noinspection JSUnusedGlobalSymbols
    detailTemplate1 = 'fields/currency/detail-1'
    // noinspection JSUnusedGlobalSymbols
    detailTemplate2 = 'fields/currency/detail-2'
    // noinspection JSUnusedGlobalSymbols
    detailTemplate3 = 'fields/currency/detail-3'
    listTemplate = 'fields/currency/list'
    // noinspection JSUnusedGlobalSymbols
    listTemplate1 = 'fields/currency/list-1'
    // noinspection JSUnusedGlobalSymbols
    listTemplate2 = 'fields/currency/list-2'
    // noinspection JSUnusedGlobalSymbols
    listTemplate3 = 'fields/currency/list-3'
    detailTemplateNoCurrency = 'fields/currency/detail-no-currency'

    maxDecimalPlaces = 3

    /**
     * @inheritDoc
     * @type {Array<(function (): boolean)|string>}
     */
    validations = [
        'required',
        'number',
        'range',
    ]

    /** @inheritDoc */
    data() {
        const currencyValue = this.model.get(this.currencyFieldName) ||
            this.getPreferences().get('defaultCurrency') ||
            this.getConfig().get('defaultCurrency');

        const multipleCurrencies = !this.isSingleCurrency || currencyValue !== this.defaultCurrency;

        return {
            ...super.data(),
            currencyFieldName: this.currencyFieldName,
            currencyValue: currencyValue,
            currencyList: this.currencyList,
            currencySymbol: this.getMetadata().get(['app', 'currency', 'symbolMap', currencyValue]) || '',
            multipleCurrencies: multipleCurrencies,
            defaultCurrency: this.defaultCurrency,
        };
    }

    /** @inheritDoc */
    setup() {
        super.setup();

        this.currencyFieldName = this.name + 'Currency';
        this.defaultCurrency = this.getConfig().get('defaultCurrency');
        this.currencyList = this.getConfig().get('currencyList') || [this.defaultCurrency];
        this.decimalPlaces = this.getConfig().get('currencyDecimalPlaces');

        if (this.params.onlyDefaultCurrency) {
            this.currencyList = [this.defaultCurrency];
        }

        this.isSingleCurrency = this.currencyList.length <= 1;

        const currencyValue = this.currencyValue = this.model.get(this.currencyFieldName) ||
            this.defaultCurrency;

        if (!this.currencyList.includes(currencyValue)) {
            this.currencyList = Espo.Utils.clone(this.currencyList);
            this.currencyList.push(currencyValue);
        }
    }

    /** @inheritDoc */
    setupAutoNumericOptions() {
        this.autoNumericOptions = {
            digitGroupSeparator: this.thousandSeparator || '',
            decimalCharacter: this.decimalMark,
            modifyValueOnWheel: false,
            selectOnFocus: false,
            decimalPlaces: this.decimalPlaces,
            allowDecimalPadding: true,
            showWarnings: false,
            formulaMode: true,
        };

        if (this.decimalPlaces === null) {
            this.autoNumericOptions.decimalPlaces = this.decimalPlacesRawValue;
            this.autoNumericOptions.decimalPlacesRawValue = this.decimalPlacesRawValue;
            this.autoNumericOptions.allowDecimalPadding = false;
        }
    }

    getCurrencyFormat() {
        return this.getConfig().get('currencyFormat') || 1;
    }

    _getTemplateName() {
        if (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST) {
            let prop;

            if (this.mode === this.MODE_LIST) {
                prop = 'listTemplate' + this.getCurrencyFormat().toString();
            }
            else {
                prop = 'detailTemplate' + this.getCurrencyFormat().toString();
            }

            if (this.options.hideCurrency) {
                prop = 'detailTemplateNoCurrency';
            }

            if (prop in this) {
                return this[prop];
            }
        }

        return super._getTemplateName();
    }

    formatNumber(value) {
        return this.formatNumberDetail(value);
    }

    formatNumberDetail(value) {
        if (value !== null) {
            const currencyDecimalPlaces = this.decimalPlaces;

            if (currencyDecimalPlaces === 0) {
                value = Math.round(value);
            }
            else if (currencyDecimalPlaces) {
                value = Math.round(
                    value * Math.pow(10, currencyDecimalPlaces)) / (Math.pow(10, currencyDecimalPlaces)
                );
            }
            else {
                value = Math.round(
                    value * Math.pow(10, this.maxDecimalPlaces)) / (Math.pow(10, this.maxDecimalPlaces)
                );
            }

            const parts = value.toString().split(".");

            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

            if (currencyDecimalPlaces === 0) {
                return parts[0];
            }
            else if (currencyDecimalPlaces) {
                let decimalPartLength = 0;

                if (parts.length > 1) {
                    decimalPartLength = parts[1].length;
                } else {
                    parts[1] = '';
                }

                if (currencyDecimalPlaces && decimalPartLength < currencyDecimalPlaces) {
                    const limit = currencyDecimalPlaces - decimalPartLength;

                    for (let i = 0; i < limit; i++) {
                        parts[1] += '0';
                    }
                }
            }

            return parts.join(this.decimalMark);
        }

        return '';
    }

    parse(value) {
        value = (value !== '') ? value : null;

        if (value === null) {
            return null;
        }

        value = value.split(this.thousandSeparator).join('');
        value = value.split(this.decimalMark).join('.');

        if (this.params.decimal) {
            // @todo Obtain default scale.
            const scale = this.params.scale || 4;

            const parts = value.split('.');

            const decimalPart = parts[1] || '';

            if (decimalPart.length < scale) {
                value = parts[0] + '.' + decimalPart.padEnd(scale, '0');
            }
        }

        if (!this.params.decimal) {
            value = parseFloat(value);
        }

        return value;
    }

    afterRender() {
        super.afterRender();

        if (this.mode === this.MODE_EDIT) {
            this.$currency = this.$el.find(`[data-name="${this.currencyFieldName}"]`);

            if (this.$currency.length) {
                this.$currency.on('change', () => {
                    this.model.set(this.currencyFieldName, this.$currency.val(), {ui: true});
                });

                Select.init(this.$currency);
            }
        }
    }

    // noinspection JSUnusedGlobalSymbols
    validateNumber() {
        if (!this.params.decimal) {
            return this.validateFloat();
        }

        const value = this.model.get(this.name);

        if (Number.isNaN(Number(value))) {
            const msg = this.translate('fieldShouldBeNumber', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }
    }

    fetch() {
        let value = this.$element.val().trim();

        value = this.parse(value);

        const data = {};

        let currencyValue = this.$currency.length ?
            this.$currency.val() :
            this.defaultCurrency;

        if (value === null) {
            currencyValue = null;
        }

        data[this.name] = value;
        data[this.currencyFieldName] = currencyValue;

        return data;
    }
}

export default CurrencyFieldView;
