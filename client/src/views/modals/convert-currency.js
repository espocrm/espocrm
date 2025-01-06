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

import MassConvertCurrencyModalView from 'views/modals/mass-convert-currency';

class ConvertCurrencyModalView extends MassConvertCurrencyModalView {

    setup() {
        super.setup();

        this.headerText = this.translate('convertCurrency', 'massActions');
    }

    actionConvert() {
        this.disableButton('convert');

        this.getFieldView('currency').fetchToModel();
        this.getFieldView('currencyRates').fetchToModel();

        const currency = this.model.get('currency');
        const currencyRates = this.model.get('currencyRates');

        Espo.Ajax
            .postRequest('Action', {
                entityType: this.options.entityType,
                action: 'convertCurrency',
                id: this.options.model.id,
                data: {
                    targetCurrency: currency,
                    rates: currencyRates,
                    fieldList: this.options.fieldList || null,
                },
            })
            .then(attributes => {
                this.trigger('after:update', attributes);

                this.close();
            })
            .catch(() => {
                this.enableButton('convert');
            });
    }
}

export default ConvertCurrencyModalView;
