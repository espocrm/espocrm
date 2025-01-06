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

/** @module views/fields/float */

import IntFieldView from 'views/fields/int';

/**
 * A float field.
 *
 * @extends IntFieldView<module:views/fields/float~params>
 */
class FloatFieldView extends IntFieldView {

    /**
     * @typedef {Object} module:views/fields/float~options
     * @property {
     *     module:views/fields/float~params &
     *     module:views/fields/base~params &
     *     Record
     * } [params] Parameters.
     */

    /**
     * @typedef {Object} module:views/fields/float~params
     * @property {number} [min] A max value.
     * @property {number} [max] A max value.
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

    type = 'float'

    editTemplate = 'fields/float/edit'

    decimalMark = '.'
    decimalPlacesRawValue = 10

    /**
     * @inheritDoc
     * @type {Array<(function (): boolean)|string>}
     */
    validations = ['required', 'float', 'range']

    /** @inheritDoc */
    setup() {
        super.setup();

        if (this.getPreferences().has('decimalMark')) {
            this.decimalMark = this.getPreferences().get('decimalMark');
        }
        else if (this.getConfig().has('decimalMark')) {
            this.decimalMark = this.getConfig().get('decimalMark');
        }

        if (!this.decimalMark) {
            this.decimalMark = '.';
        }

        if (this.decimalMark === this.thousandSeparator) {
            this.thousandSeparator = '';
        }
    }

    /** @inheritDoc */
    setupAutoNumericOptions() {
        // noinspection JSValidateTypes
        this.autoNumericOptions = {
            digitGroupSeparator: this.thousandSeparator || '',
            decimalCharacter: this.decimalMark,
            modifyValueOnWheel: false,
            selectOnFocus: false,
            decimalPlaces: this.decimalPlacesRawValue,
            decimalPlacesRawValue: this.decimalPlacesRawValue,
            allowDecimalPadding: false,
            showWarnings: false,
            formulaMode: true,
        };
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

    formatNumberDetail(value) {
        if (value === null) {
            return '';
        }

        const decimalPlaces = this.params.decimalPlaces;

        if (decimalPlaces === 0) {
            value = Math.round(value);
        }
        else if (decimalPlaces) {
            value = Math.round(
                 value * Math.pow(10, decimalPlaces)) / (Math.pow(10, decimalPlaces)
            );
        }

        const parts = value.toString().split(".");

        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

        if (decimalPlaces === 0) {
            return parts[0];
        }
        else if (decimalPlaces) {
            let decimalPartLength = 0;

            if (parts.length > 1) {
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

    setupMaxLength() {}

    validateFloat() {
        const value = this.model.get(this.name);

        if (isNaN(value)) {
            const msg = this.translate('fieldShouldBeFloat', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg);

            return true;
        }
    }

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

        return parseFloat(value);
    }

    fetch() {
        let value = this.$element.val();
        value = this.parse(value);

        const data = {};
        data[this.name] = value;

        return data;
    }
}

export default FloatFieldView;
