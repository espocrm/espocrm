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

/** @module num-util */

/**
 * A number util.
 */
class NumberUtil {

    /**
     * @param {module:models/settings} config A config.
     * @param {module:models/preferences} preferences Preferences.
     */
    constructor(config, preferences) {
        /**
         * @private
         * @type {module:models/settings}
         */
        this.config = config;

        /**
         * @private
         * @type {module:models/preferences}
         */
        this.preferences = preferences;

        /**
         * A thousand separator.
         *
         * @private
         * @type {string|null}
         */
        this.thousandSeparator = null;

        /**
         * A decimal mark.
         *
         * @private
         * @type {string|null}
         */
        this.decimalMark = null;

        this.config.on('change', () => {
            this.thousandSeparator = null;
            this.decimalMark = null;
        });

        this.preferences.on('change', () => {
            this.thousandSeparator = null;
            this.decimalMark = null;
        });

        /**
         * A max decimal places.
         *
         * @private
         * @type {number}
         */
        this.maxDecimalPlaces = 10;
    }

    /**
     * Format an integer number.
     *
     * @param {number} value A value.
     * @returns {string}
     */
    formatInt(value) {
        if (value === null || value === undefined) {
            return '';
        }

        let stringValue = value.toString();

        stringValue = stringValue.replace(/\B(?=(\d{3})+(?!\d))/g, this.getThousandSeparator());

        return stringValue;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Format a float number.
     *
     * @param {number} value A value.
     * @param {number} [decimalPlaces] Decimal places.
     * @returns {string}
     */
    formatFloat(value, decimalPlaces) {
        if (value === null || value === undefined) {
            return '';
        }

        if (decimalPlaces === 0) {
            value = Math.round(value);
        }
        else if (decimalPlaces) {
            value = Math.round(value * Math.pow(10, decimalPlaces)) / (Math.pow(10, decimalPlaces));
        }
        else {
            value = Math.round(
                value * Math.pow(10, this.maxDecimalPlaces)) / (Math.pow(10, this.maxDecimalPlaces)
            );
        }

        const parts = value.toString().split('.');

        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.getThousandSeparator());

        if (decimalPlaces === 0) {
            return parts[0];
        }

        if (decimalPlaces) {
            let decimalPartLength = 0;

            if (parts.length > 1) {
                decimalPartLength = parts[1].length;
            }
            else {
                parts[1] = '';
            }

            if (decimalPlaces && decimalPartLength < decimalPlaces) {
                const limit = decimalPlaces - decimalPartLength;

                for (let i = 0; i < limit; i++) {
                    parts[1] += '0';
                }
            }
        }

        return parts.join(this.getDecimalMark());
    }

    /**
     * @private
     * @returns {string}
     */
    getThousandSeparator() {
        if (this.thousandSeparator !== null) {
            return this.thousandSeparator;
        }

        let thousandSeparator = '.';

        if (this.preferences.has('thousandSeparator')) {
            thousandSeparator = this.preferences.get('thousandSeparator');
        }
        else if (this.config.has('thousandSeparator')) {
            thousandSeparator = this.config.get('thousandSeparator');
        }

        /**
         * A thousand separator.
         *
         * @private
         * @type {string|null}
         */
        this.thousandSeparator = thousandSeparator;

        return thousandSeparator;
    }

    /**
     * @private
     * @returns {string}
     */
    getDecimalMark() {
        if (this.decimalMark !== null) {
            return this.decimalMark;
        }

        let decimalMark = '.';

        if (this.preferences.has('decimalMark')) {
            decimalMark = this.preferences.get('decimalMark');
        }
        else {
            if (this.config.has('decimalMark')) {
                decimalMark = this.config.get('decimalMark');
            }
        }

        /**
         * A decimal mark.
         *
         * @private
         * @type {string|null}
         */
        this.decimalMark = decimalMark;

        return decimalMark;
    }
}

export default NumberUtil;
