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

import RangeIntFieldView from 'views/fields/range-int';
import FloatFieldView from 'views/fields/float';

class RangeFloatFieldView extends RangeIntFieldView {

    type = 'rangeFloat'

    validations = ['required', 'float', 'range', 'order']
    decimalPlacesRawValue = 10

    setupAutoNumericOptions() {
        this.autoNumericOptions = {
            digitGroupSeparator: this.thousandSeparator || '',
            decimalCharacter: this.decimalMark,
            modifyValueOnWheel: false,
            selectOnFocus: false,
            decimalPlaces: this.decimalPlacesRawValue,
            decimalPlacesRawValue: this.decimalPlacesRawValue,
            allowDecimalPadding: false,
            showWarnings: false,
            formulaMode: true,
        };
    }

    // noinspection JSUnusedGlobalSymbols
    validateFloat() {
        const validate = (name) => {
            if (isNaN(this.model.get(name))) {
                const msg = this.translate('fieldShouldBeFloat', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg, '[data-name="' + name + '"]');

                return true;
            }
        };

        let result = false;

        result = validate(this.fromField) || result;
        result = validate(this.toField) || result;

        return result;
    }

    parse(value) {
        return FloatFieldView.prototype.parse.call(this, value);
    }

    formatNumber(value) {
        return FloatFieldView.prototype.formatNumberDetail.call(this, value);
    }
}

export default RangeFloatFieldView;

