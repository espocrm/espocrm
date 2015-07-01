/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

Espo.define('views/admin/currency', 'views/settings/record/edit', function (Dep) {

    return Dep.extend({

        layoutName: 'currency',

        setup: function () {
            Dep.prototype.setup.call(this);
        },

        afterRender: function () {
            var currencyListField = this.getFieldView('currencyList');
            var defaultCurrencyField = this.getFieldView('defaultCurrency');
            var baseCurrencyField = this.getFieldView('baseCurrency');

            var currencyRatesField = this.getFieldView('currencyRates');

            if (currencyListField) {
                this.listenTo(currencyListField, 'change', function () {
                    var data = currencyListField.fetch();
                    var options = data.currencyList;
                    if (defaultCurrencyField) {
                        defaultCurrencyField.params.options = options;
                        defaultCurrencyField.render();
                    }
                    if (baseCurrencyField) {
                        baseCurrencyField.params.options = options;
                        baseCurrencyField.render();
                    }
                    if (currencyRatesField) {
                        currencyRatesField.render();
                    }
                }, this);
            }

            if (baseCurrencyField) {
                this.listenTo(baseCurrencyField, 'change', function () {
                    if (currencyRatesField) {
                        currencyRatesField.render();
                    }
                }, this);
            }
        },

    });

});

