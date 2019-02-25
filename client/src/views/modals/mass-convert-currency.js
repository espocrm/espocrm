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


Espo.define('views/modals/mass-convert-currency', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'modals/mass-convert-currency',

        data: function () {
            return {

            };
        },

        buttonList: [
            {
                name: 'cancel',
                label: 'Cancel'
            }
        ],

        setup: function () {
            this.headerHtml = this.translate(this.options.entityType, 'scopeNamesPlural') + ' &raquo ' + this.translate('convertCurrency', 'massActions');
            this.addButton({
                name: 'convert',
                text: this.translate('Update'),
                style: 'danger'
            }, true);

            var model = this.model = new Model();
            model.set('currency', this.getConfig().get('defaultCurrency'));
            model.set('baseCurrency', this.getConfig().get('baseCurrency'));
            model.set('currencyRates', this.getConfig().get('currencyRates'));
            model.set('currencyList', this.getConfig().get('currencyList'));

            this.createView('currency', 'views/fields/enum', {
                model: model,
                params: {
                    options: this.getConfig().get('currencyList')
                },
                name: 'currency',
                el: this.getSelector() + ' .field[data-name="currency"]',
                mode: 'edit',
                labelText: this.translate('Convert to')
            });

            this.createView('baseCurrency', 'views/fields/enum', {
                model: model,
                params: {
                    options: this.getConfig().get('currencyList')
                },
                name: 'baseCurrency',
                el: this.getSelector() + ' .field[data-name="baseCurrency"]',
                mode: 'detail',
                labelText: this.translate('baseCurrency', 'fields', 'Settings'),
                readOnly: true
            });

            this.createView('currencyRates', 'views/settings/fields/currency-rates', {
                model: model,
                name: 'currencyRates',
                el: this.getSelector() + ' .field[data-name="currencyRates"]',
                mode: 'edit',
                labelText: this.translate('currencyRates', 'fields', 'Settings')
            });
        },

        actionConvert: function () {
            this.disableButton('convert');

            this.getView('currency').fetchToModel();
            this.getView('currencyRates').fetchToModel();

            var currency = this.model.get('currency');
            var currencyRates = this.model.get('currencyRates');

            var hasWhere = !this.options.ids || this.options.ids.length == 0;

            this.ajaxPostRequest(this.options.entityType + '/action/massConvertCurrency', {
                field: this.options.field,
                currency: currency,
                ids: this.options.ids || null,
                where: hasWhere ? this.options.where : null,
                selectData: hasWhere ? this.options.selectData : null,
                byWhere: this.options.byWhere,
                targetCurrency: currency,
                currencyRates: currencyRates,
                baseCurrency: this.getConfig().get('baseCurrency')
            }).then(function (result) {
                this.trigger('after:update', result.count);
                this.close();
            }.bind(this)).fail(function () {
                this.enableButton('convert');
            }.bind(this));
        }

    });
});
