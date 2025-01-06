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

import ModalView from 'views/modal';
import Model from 'model';
import Helper from 'helpers/mass-action';

class MassConvertCurrencyModalView extends ModalView {

    template = 'modals/mass-convert-currency'

    className = 'dialog dialog-record'

    buttonList = [
        {
            name: 'cancel',
            label: 'Cancel',
        }
    ]

    data() {
        return {};
    }

    setup() {
        this.$header = $('<span>')
            .append(
                $('<span>').text(this.translate(this.options.entityType, 'scopeNamesPlural')),
                ' <span class="chevron-right"></span> ',
                $('<span>').text(this.translate('convertCurrency', 'massActions'))
            )

        this.addButton({
            name: 'convert',
            text: this.translate('Update'),
            style: 'danger'
        }, true);

        const model = this.model = new Model();

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
            selector: '.field[data-name="currency"]',
            mode: 'edit',
            labelText: this.translate('Convert to')
        });

        this.createView('baseCurrency', 'views/fields/enum', {
            model: model,
            params: {
                options: this.getConfig().get('currencyList')
            },
            name: 'baseCurrency',
            selector: '.field[data-name="baseCurrency"]',
            mode: 'detail',
            labelText: this.translate('baseCurrency', 'fields', 'Settings'),
            readOnly: true
        });

        this.createView('currencyRates', 'views/settings/fields/currency-rates', {
            model: model,
            name: 'currencyRates',
            selector: '.field[data-name="currencyRates"]',
            mode: 'edit',
            labelText: this.translate('currencyRates', 'fields', 'Settings')
        });
    }

    /**
     * @param {string} field
     * @return {module:views/fields/base}
     */
    getFieldView(field) {
        return this.getView(field);
    }

    // noinspection JSUnusedGlobalSymbols
    actionConvert() {
        this.disableButton('convert');

        this.getFieldView('currency').fetchToModel();
        this.getFieldView('currencyRates').fetchToModel();

        const currency = this.model.get('currency');
        const currencyRates = this.model.get('currencyRates');

        const hasWhere = !this.options.ids || this.options.ids.length === 0;

        const helper = new Helper(this);

        const idle = hasWhere && helper.checkIsIdle(this.options.totalCount);

        Espo.Ajax.postRequest('MassAction', {
                entityType: this.options.entityType,
                action: 'convertCurrency',
                params: {
                   ids: this.options.ids || null,
                   where: hasWhere ? this.options.where : null,
                   searchParams: hasWhere ? this.options.searchParams : null,
                },
                data: {
                    fieldList: this.options.fieldList || null,
                    currency: currency,
                    targetCurrency: currency,
                    rates: currencyRates,
                },
                idle: idle,
            })
            .then(result => {
                if (result.id) {
                    helper
                        .process(result.id, 'convertCurrency')
                        .then(view => {
                            this.listenToOnce(view, 'close', () => this.close());

                            this.listenToOnce(view, 'success', result => {
                                this.trigger('after:update', {
                                    count: result.count,
                                    idle: true,
                                });
                            });
                        });

                    return;
                }

                this.trigger('after:update', {count: result.count});

                this.close();
            })
            .catch(() => {
                this.enableButton('convert');
            });
    }
}

export default MassConvertCurrencyModalView;
