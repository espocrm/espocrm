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

define('views/fields/currency', ['views/fields/float', 'ui/select'],
function (Dep, /** module:ui/select*/Select) {

    /**
     * @class
     * @name Class
     * @extends module:views/fields/float.Class
     * @memberOf module:views/fields/currency
     */
    return Dep.extend(/** @lends module:views/fields/currency.Class# */{

        type: 'currency',

        editTemplate: 'fields/currency/edit',
        detailTemplate: 'fields/currency/detail',
        detailTemplate1: 'fields/currency/detail-1',
        detailTemplate2: 'fields/currency/detail-2',
        detailTemplate3: 'fields/currency/detail-3',
        listTemplate: 'fields/currency/list',
        listTemplate1: 'fields/currency/list-1',
        listTemplate2: 'fields/currency/list-2',
        listTemplate3: 'fields/currency/list-3',
        detailTemplateNoCurrency: 'fields/currency/detail-no-currency',

        maxDecimalPlaces: 3,

        validations: [
            'required',
            'number',
            'range',
        ],

        /**
         * @inheritDoc
         */
        data: function () {
            let currencyValue = this.model.get(this.currencyFieldName) ||
                this.getPreferences().get('defaultCurrency') ||
                this.getConfig().get('defaultCurrency');

            let multipleCurrencies = !this.isSingleCurrency || currencyValue !== this.defaultCurrency;

            return _.extend({
                currencyFieldName: this.currencyFieldName,
                currencyValue: currencyValue,
                currencyOptions: this.currencyOptions,
                currencyList: this.currencyList,
                currencySymbol: this.getMetadata().get(['app', 'currency', 'symbolMap', currencyValue]) || '',
                multipleCurrencies: multipleCurrencies,
                defaultCurrency: this.defaultCurrency,
            }, Dep.prototype.data.call(this));
        },

        /**
         * @inheritDoc
         */
        setup: function () {
            Dep.prototype.setup.call(this);

            this.currencyFieldName = this.name + 'Currency';
            this.defaultCurrency = this.getConfig().get('defaultCurrency');
            this.currencyList = this.getConfig().get('currencyList') || [this.defaultCurrency];
            this.decimalPlaces = this.getConfig().get('currencyDecimalPlaces');

            if (this.params.onlyDefaultCurrency) {
                this.currencyList = [this.defaultCurrency];
            }

            this.isSingleCurrency = this.currencyList.length <= 1;

            let currencyValue = this.currencyValue = this.model.get(this.currencyFieldName) ||
                this.defaultCurrency;

            if (!~this.currencyList.indexOf(currencyValue)) {
                this.currencyList = Espo.Utils.clone(this.currencyList);
                this.currencyList.push(currencyValue);
            }
        },

        /**
         * @inheritDoc
         */
        setupAutoNumericOptions: function () {
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
        },

        getCurrencyFormat: function () {
            return this.getConfig().get('currencyFormat') || 1;
        },

        _getTemplateName: function () {
            if (this.mode === this.MODE_DETAIL || this.mode === this.MODE_LIST) {
                var prop;

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

            return Dep.prototype._getTemplateName.call(this);
        },

        formatNumber: function (value) {
            return this.formatNumberDetail(value);
        },

        /**
         * @todo Remove. Used in range.
         */
        formatNumberEdit: function (value) {
            let currencyDecimalPlaces = this.decimalPlaces;

            if (value !== null) {
                var parts = value.toString().split(".");

                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

                if (parts.length > 1) {
                    if (
                        currencyDecimalPlaces &&
                        parts[1].length < currencyDecimalPlaces
                    ) {
                        var limit = currencyDecimalPlaces - parts[1].length;

                        for (var i = 0; i < limit; i++) {
                            parts[1] += '0';
                        }
                    }

                    if (
                        this.params.decimal &&
                        currencyDecimalPlaces &&
                        parts[1].length > currencyDecimalPlaces
                    ) {
                        let i = parts[1].length - 1;

                        while (i >= currencyDecimalPlaces) {
                            if (parts[1][i] !== '0') {
                                break;
                            }

                            i--;
                        }

                        parts[1] = parts[1].substring(0, i + 1);
                    }
                }

                return parts.join(this.decimalMark);
            }

            return '';
        },

        formatNumberDetail: function (value) {
            if (value !== null) {
                let currencyDecimalPlaces = this.decimalPlaces;

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

                let parts = value.toString().split(".");

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
                        let limit = currencyDecimalPlaces - decimalPartLength;

                        for (let i = 0; i < limit; i++) {
                            parts[1] += '0';
                        }
                    }
                }

                return parts.join(this.decimalMark);
            }

            return '';
        },

        parse: function (value) {
            value = (value !== '') ? value : null;

            if (value === null) {
                return null;
            }

            value = value.split(this.thousandSeparator).join('');
            value = value.split(this.decimalMark).join('.');

            if (!this.params.decimal) {
                value = parseFloat(value);
            }

            return value;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === this.MODE_EDIT) {
                this.$currency = this.$el.find('[data-name="' + this.currencyFieldName + '"]');

                this.$currency.on('change', () => {
                    this.model.set(this.currencyFieldName, this.$currency.val(), {ui: true});
                });

                Select.init(this.$currency);
            }
        },

        validateNumber: function () {
            if (!this.params.decimal) {
                return this.validateFloat();
            }

            let value = this.model.get(this.name);

            if (Number.isNaN(Number(value))) {
                let msg = this.translate('fieldShouldBeNumber', 'messages').replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        },

        fetch: function () {
            let value = this.$element.val().trim();

            value = this.parse(value);

            let data = {};

            let currencyValue = this.$currency.length ?
                this.$currency.val() :
                this.defaultCurrency;

            if (value === null) {
                currencyValue = null;
            }

            data[this.name] = value;
            data[this.currencyFieldName] = currencyValue;

            return data;
        },
    });
});
