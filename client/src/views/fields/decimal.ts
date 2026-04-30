/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

/** @module views/fields/decimal */

import IntFieldView from 'views/fields/int';
import {BaseOptions, BaseParams, BaseViewSchema, FieldValidator} from 'views/fields/base';

export interface DecimalParams extends BaseParams {
    /**
     * A min value.
     */
    min?: string;
    /**
     * A max value.
     */
    max?: string;
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Disable formatting.
     */
    disableFormatting?: boolean;
    /**
     * Decimal places.
     */
    decimalPlaces?: number | null;
}

export interface DecimalOptions extends BaseOptions {}

/**
 * A decimal field.
 */
class DecimalFieldView<
    S extends BaseViewSchema,
    O extends DecimalOptions,
    P extends DecimalParams,
> extends IntFieldView<S, O, P, string> {

    readonly type: string = 'decimal'

    protected editTemplate = 'fields/float/edit'

    protected decimalMark = '.'
    protected decimalPlacesRawValue = 10

    private decimalPlaces: number | null

    protected validations: (FieldValidator | string)[] = [
        'required',
        'range',
    ]

    protected setup() {
        super.setup();

        if (this.getPreferences().has('decimalMark')) {
            this.decimalMark = this.getPreferences().get('decimalMark');
        } else if (this.getConfig().has('decimalMark')) {
            this.decimalMark = this.getConfig().get('decimalMark');
        }

        if (!this.decimalMark) {
            this.decimalMark = '.';
        }

        if (this.decimalMark === this.thousandSeparator) {
            this.thousandSeparator = '';
        }

        this.decimalPlaces = this.params.decimalPlaces ?? null;
    }

    protected setupAutoNumericOptions() {
        // noinspection JSValidateTypes
        this.autoNumericOptions = {
            digitGroupSeparator: this.thousandSeparator || '',
            decimalCharacter: this.decimalMark,
            modifyValueOnWheel: false,
            selectOnFocus: false,
            decimalPlaces: this.decimalPlaces ?? undefined,
            decimalPlacesRawValue: this.decimalPlacesRawValue,
            allowDecimalPadding: true,
            showWarnings: false,
            // @ts-ignore
            formulaMode: true,
        };

        if (this.decimalPlaces === null) {
            this.autoNumericOptions.decimalPlaces = this.decimalPlacesRawValue;
            this.autoNumericOptions.decimalPlacesRawValue = this.decimalPlacesRawValue;
            this.autoNumericOptions.allowDecimalPadding = false;
        }
    }

    protected getValueForDisplay(): string | null {
        const value = isNaN(this.model.get(this.name)) ? null : this.model.get(this.name);

        return this.formatNumber(value);
    }

    protected formatNumber(value: string | null): string | null {
        if (this.disableFormatting) {
            return value?.toString() ?? null;
        }

        return this.formatNumberDetail(value);
    }

    protected formatNumberDetail(value: string | null): string {
        if (value === null) {
            return '';
        }

        const decimalPlaces = this.decimalPlaces;

        const parts = value.toString().split('.');

        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);

        if (decimalPlaces === 0) {
            return parts[0];
        }

        if (decimalPlaces) {
            let decimalPartLength = 0;

            if (parts.length > 1) {
                parts[1] = parts[1].replace(/0+$/, '');

                decimalPartLength = parts[1].length;
            } else {
                parts[1] = '';
            }

            if (decimalPlaces && decimalPartLength < decimalPlaces) {
                const limit = decimalPlaces - decimalPartLength;

                for (let i = 0; i < limit; i++) {
                    parts[1] += '0';
                }
            }
        }

        return parts.join(this.decimalMark);
    }

    protected parse(input: string | null): string | null {
        let value = (input !== '') ? input : null;

        if (value === null) {
            return null;
        }

        value = value
            .split(this.thousandSeparator)
            .join('')
            .split(this.decimalMark)
            .join('.');

        return value;
    }

    fetch(): Record<string, any> {
        const rawValue = this.mainInputElement?.value ?? null;

        const value = this.parse(rawValue);

        return {[this.name]: value};
    }

    // noinspection JSUnusedGlobalSymbols
    validateRange() {
        const value = this.model.get(this.name);

        if (value === null) {
            return false;
        }

        const minValue = this.params.min;
        const maxValue = this.params.max;

        if (minValue != null && maxValue != null) {
            if (Number(value) < Number(minValue) || Number(value) > Number(maxValue)) {
                const msg = this.translate('fieldShouldBeBetween', 'messages')
                    .replace('{field}', this.getLabelText())
                    .replace('{min}', minValue.toString())
                    .replace('{max}', maxValue.toString());

                this.showValidationMessage(msg);

                return true;
            }

            return false;
        }

        if (minValue != null) {
            if (Number(value) < Number(minValue)) {
                const msg = this.translate('fieldShouldBeGreater', 'messages')
                    .replace('{field}', this.getLabelText())
                    .replace('{value}', minValue.toString());

                this.showValidationMessage(msg);

                return true;
            }
        } else if (maxValue != null) {
            if (Number(value) > Number(maxValue)) {
                const msg = this.translate('fieldShouldBeLess', 'messages')
                    .replace('{field}', this.getLabelText())
                    .replace('{value}', maxValue.toString());

                this.showValidationMessage(msg);

                return true;
            }
        }

        return false;
    }
}

export default DecimalFieldView;
