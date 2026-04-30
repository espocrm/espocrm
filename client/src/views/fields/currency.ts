// noinspection JSUnusedLocalSymbols

/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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
import {BaseOptions, BaseParams, BaseViewSchema, FieldValidator} from 'views/fields/base';

/**
 * Parameters.
 */
export interface CurrencyParams extends BaseParams {
    /**
     * A min value.
     */
    min?: number;
    /**
     * A max value.
     */
    max?: number;
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Disable formatting.
     */
    disableFormatting?: boolean;
    /**
     * Decimal places.
     */
    decimalPlaces?: number | null;
    /**
     * onlyDefaultCurrency
     */
    onlyDefaultCurrency?: boolean
    /**
     * Stored as decimal.
     */
    decimal?: boolean
    /**
     * Scale (for decimal).
     */
    scale?: number
}

/**
 * Options.
 */
export interface CurrencyOptions extends BaseOptions {
    /**
     * Hide currency.
     */
    hideCurrency?: boolean;
}

/**
 * A currency field.
 */
class CurrencyFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends CurrencyOptions = CurrencyOptions,
    P extends CurrencyParams = CurrencyParams,
> extends FloatFieldView<S, O, P> {

    readonly type: string = 'currency'

    protected editTemplate = 'fields/currency/edit'
    protected detailTemplate = 'fields/currency/detail'
    protected listTemplate = 'fields/currency/list'

    // noinspection JSUnusedGlobalSymbols
    protected detailTemplate1 = 'fields/currency/detail-1'
    // noinspection JSUnusedGlobalSymbols
    protected detailTemplate2 = 'fields/currency/detail-2'
    // noinspection JSUnusedGlobalSymbols
    protected detailTemplate3 = 'fields/currency/detail-3'

    // noinspection JSUnusedGlobalSymbols
    protected listTemplate1 = 'fields/currency/list-1'
    // noinspection JSUnusedGlobalSymbols
    protected listTemplate2 = 'fields/currency/list-2'
    // noinspection JSUnusedGlobalSymbols
    protected listTemplate3 = 'fields/currency/list-3'

    protected detailTemplateNoCurrency = 'fields/currency/detail-no-currency'

    protected maxDecimalPlaces: number = 3

    protected validations: (FieldValidator | string)[] = [
        'required',
        'number',
        'range',
    ]

    /**
     * @since 9.2.6
     */
    protected currencyAttribute: string

    protected currencyFieldName: string
    protected isSingleCurrency: boolean
    protected defaultCurrency: string
    protected currencyList: string[]
    protected decimalPlaces: number

    private $currency: JQuery

    protected data() {
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

    protected setup() {
        super.setup();

        this.currencyFieldName = this.currencyAttribute ?? this.name + 'Currency';
        this.defaultCurrency = this.getConfig().get('defaultCurrency');
        this.currencyList = this.getConfig().get('currencyList') || [this.defaultCurrency];
        this.decimalPlaces = this.getConfig().get('currencyDecimalPlaces');

        if (typeof this.params.decimalPlaces === 'number') {
            this.decimalPlaces = this.params.decimalPlaces;
        }

        if (this.params.onlyDefaultCurrency) {
            this.currencyList = [this.defaultCurrency];
        }

        this.isSingleCurrency = this.currencyList.length <= 1;

        const currencyValue = this.model.get(this.currencyFieldName) ||
            this.defaultCurrency;

        if (!this.currencyList.includes(currencyValue)) {
            this.currencyList = Espo.Utils.clone(this.currencyList);
            this.currencyList.push(currencyValue);
        }
    }

    protected setupAutoNumericOptions() {
        this.autoNumericOptions = {
            digitGroupSeparator: this.thousandSeparator || '',
            decimalCharacter: this.decimalMark,
            modifyValueOnWheel: false,
            selectOnFocus: false,
            decimalPlaces: this.decimalPlaces,
            allowDecimalPadding: true,
            showWarnings: false,
            // @ts-ignore
            formulaMode: true,
        };

        if (this.decimalPlaces === null) {
            this.autoNumericOptions.decimalPlaces = this.decimalPlacesRawValue;
            this.autoNumericOptions.decimalPlacesRawValue = this.decimalPlacesRawValue;
            this.autoNumericOptions.allowDecimalPadding = false;
        }
    }

    protected getCurrencyFormat(): number {
        return this.getConfig().get('currencyFormat') || 1;
    }

    _getTemplateName() {
        if (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST) {
            let prop: string;

            if (this.mode === this.MODE_LIST) {
                prop = 'listTemplate' + this.getCurrencyFormat().toString();
            } else {
                prop = 'detailTemplate' + this.getCurrencyFormat().toString();
            }

            if (this.options.hideCurrency) {
                prop = 'detailTemplateNoCurrency';
            }

            if (prop in this) {
                return (this as any)[prop];
            }
        }

        // @ts-ignore
        return super._getTemplateName();
    }

    protected formatNumber(value: number | null): string | null {
        return this.formatNumberDetail(value);
    }

    protected formatNumberDetail(value: number | null): string {
        if (value === null) {
            return '';
        }

        const currencyDecimalPlaces = this.decimalPlaces;

        if (currencyDecimalPlaces === 0) {
            value = Math.round(value);
        } else if (currencyDecimalPlaces) {
            value = Math.round(
                value * Math.pow(10, currencyDecimalPlaces)) / (Math.pow(10, currencyDecimalPlaces)
            );
        } else {
            value = Math.round(
                value * Math.pow(10, this.maxDecimalPlaces)) / (Math.pow(10, this.maxDecimalPlaces)
            );
        }

        const parts = value.toString().split('.');

        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

        if (currencyDecimalPlaces === 0) {
            return parts[0];
        } else if (currencyDecimalPlaces) {
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

    protected parse(input: string): number | string | null {
        let value = (input !== '') ? input : null;

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

        if (this.params.decimal) {
            return value;
        }

        return parseFloat(value);
    }

    protected afterRender() {
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

        return false;
    }

    fetch(): Record<string, any> {
        const valueString = ((this.$element?.val() ?? '') as string).trim();

        const value = this.parse(valueString);

        const data = {} as Record<string, any>;

        let currencyValue: any = this.$currency.length ?
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
