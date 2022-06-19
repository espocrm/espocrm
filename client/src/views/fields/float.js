/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/float', ['views/fields/int'], function (Dep) {

    /**
     * A float field.
     *
     * @class
     * @name Class
     * @extends module:views/fields/int.Class
     * @memberOf module:views/fields/float
     */
    return Dep.extend(/** @lends module:views/fields/float.Class# */{

        type: 'float',

        editTemplate: 'fields/float/edit',

        decimalMark: '.',

        validations: ['required', 'float', 'range'],

        /**
         * @inheritDoc
         */
        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.getPreferences().has('decimalMark')) {
                this.decimalMark = this.getPreferences().get('decimalMark');
            }
            else {
                if (this.getConfig().has('decimalMark')) {
                    this.decimalMark = this.getConfig().get('decimalMark');
                }
            }
        },

        getValueForDisplay: function () {
            var value = isNaN(this.model.get(this.name)) ? null : this.model.get(this.name);

            return this.formatNumber(value);
        },

        formatNumber: function (value) {
            if (this.disableFormatting) {
                return value;
            }

            if (this.isReadMode()) {
                return this.formatNumberDetail(value);
            }

            return this.formatNumberEdit(value);
        },

        formatNumberEdit: function (value) {
            if (value === null) {
                return '';
            }

            let parts = value.toString().split(".");

            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

            return parts.join(this.decimalMark);
        },

        formatNumberDetail: function (value) {
            if (value === null) {
                return '';
            }

            var decimalPlaces = this.params.decimalPlaces;

            if (decimalPlaces === 0) {
                value = Math.round(value);
            }
            else if (decimalPlaces) {
                value = Math.round(
                     value * Math.pow(10, decimalPlaces)) / (Math.pow(10, decimalPlaces)
                );
            }

            var parts = value.toString().split(".");

            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

            if (decimalPlaces === 0) {
                return parts[0];
            }
            else if (decimalPlaces) {
                var decimalPartLength = 0;

                if (parts.length > 1) {
                    decimalPartLength = parts[1].length;
                } else {
                    parts[1] = '';
                }

                if (decimalPlaces && decimalPartLength < decimalPlaces) {
                    var limit = decimalPlaces - decimalPartLength;

                    for (var i = 0; i < limit; i++) {
                        parts[1] += '0';
                    }
                }
            }

            return parts.join(this.decimalMark);
        },

        setupMaxLength: function () {
        },

        validateFloat: function () {
            var value = this.model.get(this.name);

            if (isNaN(value)) {
                var msg = this.translate('fieldShouldBeFloat', 'messages').replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        },

        parse: function (value) {
            value = (value !== '') ? value : null;

            if (value !== null) {
                value = value.split(this.thousandSeparator).join('');
                value = value.split(this.decimalMark).join('.');
                value = parseFloat(value);
            }

            return value;
        },

        fetch: function () {
            var value = this.$element.val();

            value = this.parse(value);

            var data = {};

            data[this.name] = value;

            return data;
        },
    });
});
