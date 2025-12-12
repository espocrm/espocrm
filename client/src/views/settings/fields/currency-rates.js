/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

import BaseFieldView from 'views/fields/base';

/**
 * Not used.
 * @todo Remove.
 */
export default class extends BaseFieldView {

    editTemplate = 'settings/fields/currency-rates/edit'

    /**
     * @private
     * @type {string}
     */
    baseCode

    data() {
        const baseCode = this.baseCode;
        const currencyRates = this.model.get('currencyRates') || {};

        const rateValues = {};

        (this.model.get('currencyList') || []).forEach(currency => {
            if (currency !== baseCode) {
                rateValues[currency] = currencyRates[currency];

                if (!rateValues[currency]) {
                    if (currencyRates[baseCode]) {
                        rateValues[currency] = Math.round(1 / currencyRates[baseCode] * 1000) / 1000;
                    }

                    if (!rateValues[currency]) {
                        rateValues[currency] = 1.00
                    }
                }
            }
        });

        return {
            rateValues: rateValues,
            baseCurrency: baseCode,
        };
    }

    setup() {
        const sync = () => {
            this.baseCode = this.model.get('baseCurrency');
        };

        sync();

        this.listenTo(this.model, 'sync', () => {
            sync();
        });
    }

    fetch() {
        return {};
    }
}
