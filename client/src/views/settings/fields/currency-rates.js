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

define('views/settings/fields/currency-rates', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        editTemplate: 'settings/fields/currency-rates/edit',

        data: function () {
            var baseCurrency = this.model.get('baseCurrency');
            var currencyRates = this.model.get('currencyRates') || {};

            var rateValues = {};

            (this.model.get('currencyList') || []).forEach(currency => {
                if (currency !== baseCurrency) {
                    rateValues[currency] = currencyRates[currency];

                    if (!rateValues[currency]) {
                        if (currencyRates[baseCurrency]) {
                            rateValues[currency] = Math.round(1 / currencyRates[baseCurrency] * 1000) / 1000;
                        }

                        if (!rateValues[currency]) {
                            rateValues[currency] = 1.00
                        }
                    }
                }
            });

            return {
                rateValues: rateValues,
                baseCurrency: baseCurrency,
            };
        },

        setup: function () {
        },

        fetch: function () {
            var data = {};
            var currencyRates = {};

            var baseCurrency = this.model.get('baseCurrency');

            var currencyList = this.model.get('currencyList') || [];

            currencyList.forEach(currency => {
                if (currency !== baseCurrency) {
                    currencyRates[currency] = parseFloat(
                        this.$el.find('input[data-currency="'+currency+'"]').val() || 1);
                }
            });

            delete currencyRates[baseCurrency];

            for (var c in currencyRates) {
                if (!~currencyList.indexOf(c)) {
                    delete currencyRates[c];
                }
            }

            data[this.name] = currencyRates;

            return data;
        },
    });
});
