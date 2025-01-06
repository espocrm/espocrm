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

import BaseFieldView from 'views/fields/base';
import IntFieldView from 'views/fields/int';
import AutoNumeric from 'autonumeric';

class RangeIntFieldView extends BaseFieldView {

    type = 'rangeInt'

    listTemplate = 'fields/range-int/detail'
    detailTemplate = 'fields/range-int/detail'
    editTemplate = 'fields/range-int/edit'

    validations = ['required', 'int', 'range', 'order']

    // noinspection JSCheckFunctionSignatures
    data() {
        const data = super.data();

        data.ucName = Espo.Utils.upperCaseFirst(this.name);
        data.fromValue = this.model.get(this.fromField);
        data.toValue = this.model.get(this.toField);

        // noinspection JSValidateTypes
        return data;
    }

    init() {
        const ucName = Espo.Utils.upperCaseFirst(this.options.defs.name);

        this.fromField = 'from' + ucName;
        this.toField = 'to' + ucName;

        super.init();
    }

    getValueForDisplay() {
        let fromValue = this.model.get(this.fromField);
        let toValue = this.model.get(this.toField);

        fromValue = isNaN(fromValue) ? null : fromValue;
        toValue = isNaN(toValue) ? null : toValue;

        if (fromValue !== null && toValue !== null) {
            return this.formatNumber(fromValue) + ' &#8211 ' + this.formatNumber(toValue);
        }

        if (fromValue) {
            return '&#62;&#61; ' + this.formatNumber(fromValue);
        }

        if (toValue) {
            return '&#60;&#61; ' + this.formatNumber(toValue);
        }

        return this.translate('None');
    }

    setup() {
        if (this.getPreferences().has('decimalMark')) {
            this.decimalMark = this.getPreferences().get('decimalMark');
        }
        else if (this.getConfig().has('decimalMark')) {
            this.decimalMark = this.getConfig().get('decimalMark');
        }

        if (this.getPreferences().has('thousandSeparator')) {
            this.thousandSeparator = this.getPreferences().get('thousandSeparator');
        } else if (this.getConfig().has('thousandSeparator')) {
            this.thousandSeparator = this.getConfig().get('thousandSeparator');
        }
    }

    setupFinal() {
        super.setupFinal();

        this.setupAutoNumericOptions();
    }

    /**
     * @protected
     */
    setupAutoNumericOptions() {
        const separator = (!this.disableFormatting ? this.thousandSeparator : null) || '';
        let decimalCharacter = '.';

        if (separator === '.') {
            decimalCharacter = ',';
        }

        this.autoNumericOptions = {
            digitGroupSeparator: separator,
            decimalCharacter: decimalCharacter,
            modifyValueOnWheel: false,
            decimalPlaces: 0,
            selectOnFocus: false,
            formulaMode: true,
        };
    }

    afterRender() {
        super.afterRender();

        if (this.mode === this.MODE_EDIT) {
            this.$from = this.$el.find('[data-name="' + this.fromField + '"]');
            this.$to = this.$el.find('[data-name="' + this.toField + '"]');

            this.$from.on('change', () => {
                this.trigger('change');
            });

            this.$to.on('change', () => {
                this.trigger('change');
            });

            if (this.autoNumericOptions) {
                // noinspection JSUnusedGlobalSymbols
                this.autoNumericInstance1 = new AutoNumeric(this.$from.get(0), this.autoNumericOptions);
                // noinspection JSUnusedGlobalSymbols
                this.autoNumericInstance2 = new AutoNumeric(this.$to.get(0), this.autoNumericOptions);
            }
        }
    }

    validateRequired() {
        const validate = (name) => {
            if (this.model.isRequired(name) && this.model.get(name) === null) {
                const msg = this.translate('fieldIsRequired', 'messages')
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

    // noinspection JSUnusedGlobalSymbols
    validateInt() {
        const validate = (name) => {
            if (isNaN(this.model.get(name))) {
                const msg = this.translate('fieldShouldBeInt', 'messages')
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

    // noinspection JSUnusedGlobalSymbols
    validateRange() {
        const validate = (name) => {
            const value = this.model.get(name);

            if (value === null) {
                return false;
            }

            const minValue = this.model.getFieldParam(name, 'min');
            const maxValue = this.model.getFieldParam(name, 'max');

            if (minValue !== null && maxValue !== null) {
                if (value < minValue || value > maxValue) {
                    const msg = this.translate('fieldShouldBeBetween', 'messages')
                        .replace('{field}', this.translate(name, 'fields', this.entityType))
                        .replace('{min}', minValue)
                        .replace('{max}', maxValue);

                    this.showValidationMessage(msg, '[data-name="' + name + '"]');

                    return true;
                }
            } else if (minValue !== null) {
                if (value < minValue) {
                    const msg = this.translate('fieldShouldBeLess', 'messages')
                        .replace('{field}', this.translate(name, 'fields', this.entityType))
                        .replace('{value}', minValue);

                    this.showValidationMessage(msg, '[data-name="' + name + '"]');

                    return true;
                }
            } else if (maxValue !== null) {
                if (value > maxValue) {
                    const msg = this.translate('fieldShouldBeGreater', 'messages')
                        .replace('{field}', this.translate(name, 'fields', this.entityType))
                        .replace('{value}', maxValue);

                    this.showValidationMessage(msg, '[data-name="' + name + '"]');

                    return true;
                }
            }
        };

        let result = false;

        result = validate(this.fromField) || result;
        result = validate(this.toField) || result;

        return result;
    }

    // noinspection JSUnusedGlobalSymbols
    validateOrder() {
        const fromValue = this.model.get(this.fromField);
        const toValue = this.model.get(this.toField);

        if (fromValue !== null && toValue !== null && fromValue > toValue) {
            const msg = this.translate('fieldShouldBeGreater', 'messages')
                .replace('{field}', this.translate(this.toField, 'fields', this.entityType))
                .replace('{value}', this.translate(this.fromField, 'fields', this.entityType));

            this.showValidationMessage(msg, '[data-name="' + this.fromField + '"]');

            return true;
        }
    }

    isRequired() {
        return this.model.getFieldParam(this.fromField, 'required') ||
            this.model.getFieldParam(this.toField, 'required');
    }

    parse(value) {
        return IntFieldView.prototype.parse.call(this, value);
    }

    formatNumber(value) {
        if (this.params.disableFormatting) {
            return value.toString();
        }

        return IntFieldView.prototype.formatNumberDetail.call(this, value);
    }

    fetch() {
        const data = {};

        data[this.fromField] = this.parse(this.$from.val().trim());
        data[this.toField] = this.parse(this.$to.val().trim());

        return data;
    }
}

export default RangeIntFieldView;
