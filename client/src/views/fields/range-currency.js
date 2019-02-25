/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/fields/range-currency', 'views/fields/range-float', function (Dep, Float) {

    return Dep.extend({

        type: 'rangeCurrency',

        editTemplate: 'fields/range-currency/edit',

        data: function () {
            return _.extend({
                currencyField: this.currencyField,
                currencyValue: this.model.get(this.fromCurrencyField) || this.getPreferences().get('defaultCurrency') || this.getConfig().get('defaultCurrency'),
                currencyOptions: this.currencyOptions,
                currencyList: this.currencyList
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            var ucName = Espo.Utils.upperCaseFirst(this.name);

            this.fromCurrencyField = 'from' + ucName + 'Currency';
            this.toCurrencyField = 'to' + ucName + 'Currency';

            this.currencyField = this.name + 'Currency';
            this.currencyList = this.getConfig().get('currencyList') || ['USD'];
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);
            if (this.mode == 'edit') {
                this.$currency = this.$el.find('[data-name="' + this.currencyField + '"]');
            }
        },

        getValueForDisplay: function () {
            var fromValue = this.model.get(this.fromField);
            var toValue = this.model.get(this.toField);

            var fromValue = isNaN(fromValue) ? null : fromValue;
            var toValue = isNaN(toValue) ? null : toValue;

            var currencyValue = this.model.get(this.fromCurrencyField) || this.model.get(this.toCurrencyField) ;

            if (fromValue !== null && toValue !== null) {
                return this.formatNumber(fromValue) + ' &#8211 ' + this.formatNumber(toValue) + ' '+currencyValue+'';
            } else if (fromValue) {
                return '&#62;&#61; ' + this.formatNumber(fromValue) + ' '+currencyValue+'';
            } else if (toValue) {
                return '&#60;&#61; ' + this.formatNumber(toValue) + ' '+currencyValue+'';
            } else {
                return this.translate('None');
            }
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            var currencyValue = this.$currency.val();


            if (data[this.fromField] !== null) {
                data[this.fromCurrencyField] = currencyValue;
            } else {
                data[this.fromCurrencyField] = null;
            }
            if (data[this.toField] !== null) {
                data[this.toCurrencyField] = currencyValue;
            } else {
                data[this.toCurrencyField] = null;
            }

            return data;
        }

    });
});

