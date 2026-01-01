/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

/** @module views/fields/decimal */

import IntFieldView from 'views/fields/int';

/**
 * A decimal field.
 *
 * @extends IntFieldView<module:views/fields/decimal~params>
 */
class DecimalFieldView extends IntFieldView {

    /**
     * @typedef {Object} module:views/fields/decimal~options
     * @property {
     *     module:views/fields/decimal~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/decimal~params
     * @property {string} [min] A max value.
     * @property {string} [max] A max value.
     * @property {boolean} [required] Required.
     * @property {boolean} [disableFormatting] Disable formatting.
     * @property {number|null} [decimalPlaces] A number of decimal places.
     */

    /**
     * @param {
     *     module:views/fields/float~options &
     *     module:views/fields/base~options
     * } options Options.
     */
    constructor(options) {
        super(options);
    }

    type = 'decimal'

    editTemplate = 'fields/float/edit'

    decimalMark = '.'
    decimalPlacesRawValue = 10

    /**
     * @inheritDoc
     * @type {Array<(function (): boolean)|string>}
     */
    validations = [
        'required',
        'range',
    ]

    /**
     * @private
     * @type {number|null}
     */
    decimalPlaces

    setup() {
        super.setup();

        if (this.getPreferences().has('decimalMark')) {
            this.decimalMark = this.getPreferences().get('decimalMark');
        } else if (this.getConfig().has('decimalMark')) {
            this.decimalMark = this.getConfig().get('decimalMark');
        }

        if (!this.decimalMark) {
            this.decimalMark = '.';
        }

        if (this.decimalMark === this.thousandSeparator) {
            this.thousandSeparator = '';
        }

        this.decimalPlaces = this.params.decimalPlaces ?? null;
    }

    /**
     * @inheritDoc
     */
    setupAutoNumericOptions() {
        // noinspection JSValidateTypes
        this.autoNumericOptions = {
            digitGroupSeparator: this.thousandSeparator || '',
            decimalCharacter: this.decimalMark,
            modifyValueOnWheel: false,
            selectOnFocus: false,
            decimalPlaces: this.decimalPlaces,
            decimalPlacesRawValue: this.decimalPlacesRawValue,
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

    getValueForDisplay() {
        const value = isNaN(this.model.get(this.name)) ? null : this.model.get(this.name);

        return this.formatNumber(value);
    }

    formatNumber(value) {
        if (this.disableFormatting) {
            return value;
        }

        return this.formatNumberDetail(value);
    }

    /**
     *
     * @param {string|null} value
     * @return {string}
     */
    formatNumberDetail(value) {
        if (value === null) {
            return '';
        }

        const decimalPlaces = this.decimalPlaces;

        const parts = value.toString().split('.');

        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

        if (decimalPlaces === 0) {
            return parts[0];
        }

        if (decimalPlaces) {
            let decimalPartLength = 0;

            if (parts.length > 1) {
                parts[1] = parts[1].replace(/0+$/, '');

                decimalPartLength = parts[1].length;
            } else {
                parts[1] = '';
            }

            if (decimalPlaces && decimalPartLength < decimalPlaces) {
                const limit = decimalPlaces - decimalPartLength;

                for (let i = 0; i < limit; i++) {
                    parts[1] += '0';
                }
            }
        }

        return parts.join(this.decimalMark);
    }

    /**
     * @param {string|null} value
     * @return {string|null}
     */
    parse(value) {
        value = (value !== '') ? value : null;

        if (value === null) {
            return null;
        }

        value = value
            .split(this.thousandSeparator)
            .join('')
            .split(this.decimalMark)
            .join('.');

        return value;
    }

    /**
     * @return {Record}
     */
    fetch() {
        const rawValue = this.mainInputElement?.value ?? null;

        const value = this.parse(rawValue);

        return {[this.name]: value};
    }

    // noinspection JSUnusedGlobalSymbols
    validateRange() {
        const value = this.model.get(this.name);

        if (value === null) {
            return false;
        }

        const minValue = this.params.min;
        const maxValue = this.params.max;

        if (minValue !== null && maxValue !== null) {
            if (Number(value) < Number(minValue) || Number(value) > Number(maxValue)) {
                const msg = this.translate('fieldShouldBeBetween', 'messages')
                    .replace('{field}', this.getLabelText())
                    .replace('{min}', minValue)
                    .replace('{max}', maxValue);

                this.showValidationMessage(msg);

                return true;
            }
        } else {
            if (minValue !== null) {
                if (Number(value) < Number(minValue)) {
                    const msg = this.translate('fieldShouldBeGreater', 'messages')
                        .replace('{field}', this.getLabelText())
                        .replace('{value}', minValue);

                    this.showValidationMessage(msg);

                    return true;
                }
            } else if (maxValue !== null) {
                if (Number(value) > Number(maxValue)) {
                    const msg = this.translate('fieldShouldBeLess', 'messages')
                        .replace('{field}', this.getLabelText())
                        .replace('{value}', maxValue);
                    this.showValidationMessage(msg);

                    return true;
                }
            }
        }

        return false;
    }
}

export default DecimalFieldView;
