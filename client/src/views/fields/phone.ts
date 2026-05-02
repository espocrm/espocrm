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

import VarcharFieldView from 'views/fields/varchar';
import Select from 'ui/select';
import {BaseOptions, BaseParams, BaseViewSchema, FieldValidator} from 'views/fields/base';
import intlTelInput from 'intl-tel-input';

// @ts-ignore
import intlTelInputUtils from 'intl-tel-input-utils';
// @ts-ignore
import intlTelInputGlobals from 'intl-tel-input-globals';

/**
 * Parameters.
 */
export interface PhoneParams extends BaseParams {
    /**
     * Required.
     */
    required?: boolean;
    /**
     * Only primary email address.
     */
    onlyPrimary?: boolean;
}

/**
 * Options.
 */
export interface PhoneOptions extends BaseOptions {}

/**
 * Phone number field.
 */
class PhoneFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends PhoneOptions = PhoneOptions,
    P extends PhoneParams = PhoneParams,
> extends VarcharFieldView<S, O, P> {

    readonly type: string = 'phone'

    protected editTemplate = 'fields/phone/edit'
    protected detailTemplate = 'fields/phone/detail'
    protected listTemplate = 'fields/phone/list'

    protected validations: (FieldValidator | string)[] = [
        'required',
        'phoneData',
    ]

    protected maxExtensionLength: number = 6

    private validationRegExp: RegExp

    protected isNumeric: boolean

    private dataFieldName: string;

    private maxCount: number | null = null;

    private useInternational: boolean

    private intlTelInputMap: Map<HTMLInputElement, intlTelInput.Plugin & any>

    protected allowExtensions: boolean

    private phoneNumberOptedOutByDefault: boolean

    private isOptedOutFieldName: string

    private isInvalidFieldName: string

    private defaultType: string

    private erasedPlaceholder: string

    private itemMaxLength: number

    private preferredCountryList: string[]

    private _codeNames: string[]

    private _itemJustRemoved: boolean

    validateRequired() {
        if (!this.isRequired()) {
            return false;
        }

        if (!this.model.get(this.name)) {
            const msg = this.translate('fieldIsRequired', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, 'div.phone-number-block:nth-child(1) input.phone-number');

            return true;
        }

        return false;
    }

    // noinspection JSUnusedGlobalSymbols
    validatePhoneData() {
        const data = this.model.get(this.dataFieldName) as
            (Record<string, any> & {number: string, type: string})[] | null | undefined;

        if (!data || !data.length) {
            return false;
        }

        const pattern = '^' + this.getMetadata().get('app.regExpPatterns.phoneNumberLoose.pattern') + '$';
        this.validationRegExp = new RegExp(pattern);

        const numberList: string[] = [];
        let notValid = false;

        data.forEach((row, i) => {
            const number = row.phoneNumber;

            if (this.itemValidate(row, i)) {
                notValid = true;
            }

            const numberClean = String(number).replace(/[\s+]/g, '');

            if (numberList.includes(numberClean)) {
                const msg = this.translate('fieldValueDuplicate', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg, 'div.phone-number-block:nth-child(' + (i + 1)
                    .toString() + ') input.phone-number');

                notValid = true;

                return;
            }

            numberList.push(numberClean);
        });

        return notValid;
    }

    protected validateMaxCount(): boolean {
        const maxCount = this.maxCount;

        if (!maxCount) {
            return false;
        }

        const items = this.model.attributes[this.dataFieldName] || [];

        if (items.length <= maxCount) {
            return false;
        }

        const msg = this.translate('fieldExceedsMaxCount', 'messages')
            .replace('{maxCount}', maxCount.toString());

        this.showValidationMessage(msg, 'div.phone-number-block:last-child input.phone-number');

        return true;
    }

    /**
     * @param item A data item.
     * @param i An index.
     * @internal Called in an extension. Do not change the signature.
     */
    protected itemValidate(item: {number: string, type: string}, i: number): boolean {
        const number = item.number;

        const n = (i + 1).toString();
        const selector = `div.phone-number-block:nth-child(${n}) input.phone-number`;

        let notValid = false;

        if (!this.validationRegExp.test(number)) {
            notValid = true;

            const msg = this.translate('fieldPhoneInvalidCharacters', 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, selector);
        }

        if (!this.useInternational) {
            return notValid;
        }

        const element = this.$el.find(selector).get(0) as HTMLInputElement | undefined;

        if (!element) {
            return notValid;
        }

        const intlObj = this.intlTelInputMap.get(element);

        const isPossible = intlObj && intlObj.isPossibleNumber();

        if (intlObj && !isPossible) {
            notValid = true;

            const code = intlObj.getValidationError();

            const key = [
                'fieldPhoneInvalid',
                'fieldPhoneInvalidCode',
                'fieldPhoneTooShort',
                'fieldPhoneTooLong',
            ][code || 0] || 'fieldPhoneInvalid';

            const msg = this.translate(key, 'messages')
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, selector);
        }

        if (
            intlObj &&
            isPossible &&
            this.allowExtensions &&
            intlObj.getExtension() &&
            intlObj.getExtension().length > this.maxExtensionLength
        ) {
            const msg = this.translate('fieldPhoneExtensionTooLong', 'messages')
                .replace('{maxLength}', this.maxExtensionLength.toString())
                .replace('{field}', this.getLabelText());

            this.showValidationMessage(msg, selector);

            notValid = true;
        }

        return notValid;
    }

    protected data(): Record<string, unknown> {
        const number = this.model.get(this.name);
        let phoneNumberData: any;

        if (this.mode === this.MODE_EDIT) {
            phoneNumberData = Espo.Utils.cloneDeep(this.model.get(this.dataFieldName));

            if (this.model.isNew() || !this.model.get(this.name)) {
                if (!phoneNumberData || !phoneNumberData.length) {
                    let optOut: any;

                    if (this.model.isNew()) {
                        optOut = this.phoneNumberOptedOutByDefault && this.model.entityType !== 'User';
                    } else {
                        optOut = this.model.get(this.isOptedOutFieldName)
                    }

                    phoneNumberData = [{
                        phoneNumber: this.model.get(this.name) || '',
                        primary: true,
                        type: this.defaultType,
                        optOut: optOut,
                        invalid: false,
                    }];
                }
            }
        } else {
            phoneNumberData = this.model.get(this.dataFieldName) || false;
        }

        if (phoneNumberData) {
            phoneNumberData = Espo.Utils.cloneDeep(phoneNumberData);

            phoneNumberData.forEach((item: any) => {
                const number = item.phoneNumber || '';

                item.erased = number.indexOf(this.erasedPlaceholder) === 0;

                if (!item.erased) {
                    item.valueForLink = this.formatForLink(number);

                    if (this.isReadMode()) {
                        item.phoneNumber = this.formatNumber(item.phoneNumber);
                    }
                }

                item.lineThrough = item.optOut || item.invalid || this.model.get('doNotCall');
            });
        }

        if ((!phoneNumberData || phoneNumberData.length === 0) && this.model.get(this.name)) {
            const o = {
                phoneNumber: this.formatNumber(number),
                primary: true,
                valueForLink: this.formatForLink(number),
            } as any;

            if (this.isReadMode()) {
                o.phoneNumber = this.formatNumber(o.phoneNumber);
            }

            if (this.mode === 'edit' && this.model.isNew()) {
                o.type = this.defaultType;
            }

            phoneNumberData = [o];
        }

        const data = {
            ...super.data(),
            phoneNumberData: phoneNumberData,
            doNotCall: this.model.get('doNotCall'),
            lineThrough: this.model.get('doNotCall') || this.model.get(this.isOptedOutFieldName),
        } as any;

        if (this.isReadMode()) {
            data.isOptedOut = this.model.get(this.isOptedOutFieldName);
            data.isInvalid = this.model.get(this.isInvalidFieldName);

            if (this.model.get(this.name)) {
                data.isErased = this.model.get(this.name).indexOf(this.erasedPlaceholder) === 0;

                if (!data.isErased) {
                    data.valueForLink = this.formatForLink(this.model.get(this.name));
                }
            }

            data.valueIsSet = this.model.has(this.name);
            data.value = this.formatNumber(data.value);
        }

        data.itemMaxLength = this.itemMaxLength;
        data.onlyPrimary = this.params.onlyPrimary;

        // noinspection JSValidateTypes
        return data;
    }

    private formatForLink(number: string): string {
        if (this.allowExtensions && this.useInternational) {
            if (number.includes(' ext. ')) {
                number = number.replace(' ext. ', ',');
            }
            return number;
        }

        return number.replace(/ /g, '');
    }

    private focusOnLast(cursorAtEnd?: boolean) {
        const $item = this.$el.find('input.form-control').last();

        $item.focus();

        if (cursorAtEnd && $item[0]) {
            // noinspection JSUnresolvedReference
            $item[0].setSelectionRange($item[0].value.length, $item[0].value.length);
        }
    }

    private removePhoneNumber($block: JQuery) {
        if ($block.parent().children().length === 1) {
            $block.find('input.phone-number').val('');
        } else {
            this.removePhoneNumberBlock($block);
        }

        this.trigger('change');
    }

    protected formatNumber(value: string): string {
        if (!value || value === '' || !this.useInternational) {
            return value;
        }

        return intlTelInputUtils.formatNumber(
            value,
            null,
            intlTelInputUtils.numberFormat.INTERNATIONAL
        );
    }

    private addPhoneNumber() {
        const data = Espo.Utils.cloneDeep(this.fetchPhoneNumberData());

        const o = {
            phoneNumber: '',
            primary: !data.length,
            type: this.defaultType,
            optOut: this.phoneNumberOptedOutByDefault,
            invalid: false,
        };

        data.push(o);

        this.model.set(this.dataFieldName, data, {silent: true});

        this.reRender()
            .then(() => this.focusOnLast());
    }

    protected afterRender() {
        super.afterRender();

        this.manageButtonsVisibility();
        this.manageAddButton();

        if (this.mode === this.MODE_EDIT) {
            this.$el.find('select').toArray().forEach((selectElement: HTMLSelectElement) => {
                Select.init(selectElement);
            });
        }
    }

    protected afterRenderEdit() {
        super.afterRenderEdit();

        if (this.useInternational) {
            const inputElements = this.element.querySelectorAll<HTMLInputElement>('input.phone-number');

            inputElements.forEach(inputElement => {
                // noinspection JSUnusedGlobalSymbols
                const obj = intlTelInput(inputElement, {
                    nationalMode: false,
                    autoInsertDialCode: false,
                    separateDialCode: true,
                    showFlags: false,
                    preferredCountries: this.preferredCountryList,
                    localizedCountries: this._codeNames,
                    customPlaceholder: (placeholder: string) => {
                        return placeholder.replace(/[0-9]/g, '0');
                    },
                } as any) as intlTelInput.Plugin & any;

                this.intlTelInputMap.set(inputElement, obj);

                inputElement.addEventListener('blur', () => {
                    if (!obj.isPossibleNumber()) {
                        return;
                    }

                    let number = obj.getNumber();
                    const ext = obj.getExtension();

                    if (this.allowExtensions && ext) {
                        number += ' ext. ' + ext;
                    }

                    obj.setNumber(number);
                });
            });
        }
    }

    private removePhoneNumberBlock($block: JQuery) {
        let changePrimary = false;

        if ($block.find('button[data-property-type="primary"]').hasClass('active')) {
            changePrimary = true;
        }

        $block.remove();

        if (changePrimary) {
            this.$el.find('button[data-property-type="primary"]')
                .first()
                .addClass('active')
                .children()
                .removeClass('text-muted');
        }

        this.manageButtonsVisibility();
        this.manageAddButton();
    }

    private manageAddButton() {
        const $input = this.$el.find('input.phone-number');
        let c = 0;

        $input.each((_i: number, input: HTMLInputElement) => {
            if (input.value !== '') {
                c++;
            }
        });

        if (c === $input.length) {
            this.$el.find('[data-action="addPhoneNumber"]')
                .removeClass('disabled')
                .removeAttr('disabled');

            return;
        }

        this.$el.find('[data-action="addPhoneNumber"]')
            .addClass('disabled')
            .attr('disabled', 'disabled');
    }

    private manageButtonsVisibility() {
        const $primary = this.$el.find('button[data-property-type="primary"]');
        const $remove = this.$el.find('button[data-action="removePhoneNumber"]');
        const $container = this.$el.find('.phone-number-block-container');

        if ($primary.length > 1) {
            $primary.removeClass('hidden');
            $remove.removeClass('hidden');
            $container.addClass('many')

            return;
        }

        $container.removeClass('many')
        $primary.addClass('hidden');
        $remove.addClass('hidden');
    }

    protected setup() {
        this.addActionHandler('switchPhoneProperty', (_e, target) => this.switchPhoneProperty(target));
        this.addActionHandler('removePhoneNumber', (_e, target) => this.removePhoneNumberHandler(target))
        this.addHandler('change', 'input.phone-number', (_e, target) => this.inputChangeHandler(target));
        this.addHandler('keypress', 'input.phone-number', () => this.manageAddButton());
        this.addHandler('paste', 'input.phone-number', () => setTimeout(() => this.manageAddButton(), 10));
        this.addActionHandler('addPhoneNumber', () => this.addPhoneNumber());
        this.addHandler('keydown', 'input.phone-number', (e, target) => {
            this.inputKeydownHandler(e as KeyboardEvent, target as HTMLInputElement);
        });

        this.dataFieldName = this.name + 'Data';
        this.defaultType = this.defaultType ||
            this.getMetadata()
                .get(`entityDefs.${this.model.entityType}.fields.${this.name}.defaultType`);

        this.isOptedOutFieldName = this.name + 'IsOptedOut';
        this.isInvalidFieldName = this.name + 'IsInvalid';

        this.phoneNumberOptedOutByDefault = this.getConfig().get('phoneNumberIsOptedOutByDefault');
        this.useInternational = this.getConfig().get('phoneNumberInternational') || false;
        this.allowExtensions = this.getConfig().get('phoneNumberExtensions') || false;
        this.preferredCountryList = this.getConfig().get('phoneNumberPreferredCountryList') || [];
        this.maxCount = this.getConfig().get('phoneNumberMaxCount');

        if (this.useInternational && !this.isListMode() && !this.isSearchMode()) {
            this._codeNames = intlTelInputGlobals.getCountryData()
                .reduce((map: any, item: any) => {
                    map[item.iso2] = item.iso2.toUpperCase();

                    return map;
                }, {});
        }

        if (this.model.has('doNotCall')) {
            this.listenTo(this.model, 'change:doNotCall', (_m, _v, o) => {
                if (this.mode !== 'detail' && this.mode !== 'list') {
                    return;
                }

                if (!o.ui) {
                    return;
                }

                this.reRender();
            });
        }

        this.erasedPlaceholder = 'ERASED:';
        this.itemMaxLength = this.getMetadata().get(['entityDefs', 'PhoneNumber', 'fields', 'name', 'maxLength']);

        this.intlTelInputMap = new Map();

        this.once('remove', () => {
            for (const obj of this.intlTelInputMap.values()) {
                obj.destroy();
            }

            this.intlTelInputMap.clear();
        });

        this.validations.push(() => this.validateMaxCount());

        this.isNumeric = this.getConfig().get('phoneNumberNumericSearch');
    }

    private fetchPhoneNumberData(): {
        phoneNumber: string;
        primary: boolean;
        type: string;
        optOut: boolean;
        invalid: boolean;
    }[] {
        const $list = this.$el.find('div.phone-number-block');

        if (!$list.length) {
            return [];
        }

        const data: {
            phoneNumber: string;
            primary: boolean;
            type: string;
            optOut: boolean;
            invalid: boolean;
        }[] = [];

        $list.each((_i: number, itemElement: HTMLElement) => {
            const row = {} as any;
            const $d = $(itemElement);

            const inputElement = $d.find('input.phone-number').get(0) as HTMLInputElement;

            if (!inputElement) {
                return;
            }

            row.phoneNumber = inputElement.value.trim();

            if (this.intlTelInputMap.has(inputElement)) {
                row.phoneNumber = this.intlTelInputMap.get(inputElement).getNumber();

                const ext = this.intlTelInputMap.get(inputElement).getExtension() || null;

                if (this.allowExtensions && ext) {
                    row.phoneNumber += ' ext. ' + ext;
                }
            }

            if (row.phoneNumber === '') {
                return;
            }

            row.primary = $d.find('button[data-property-type="primary"]').hasClass('active');
            row.type = $d.find('select[data-property-type="type"]').val();
            row.optOut = $d.find('button[data-property-type="optOut"]').hasClass('active');
            row.invalid = $d.find('button[data-property-type="invalid"]').hasClass('active');

            data.push(row);
        });

        return data;
    }

    fetch(): Record<string, unknown> {
        const data = {} as Record<string, unknown>;

        const addressData = this.fetchPhoneNumberData();

        if (this.params.onlyPrimary) {
            if (addressData.length > 0) {
                data[this.name] = addressData[0].phoneNumber;

                data[this.dataFieldName] = [
                    {
                        phoneNumber: addressData[0].phoneNumber,
                        primary: true,
                    }
                ];
            } else {
                data[this.name] = null;
                data[this.dataFieldName] = null;
            }

            return data;
        }

        data[this.dataFieldName] = addressData;
        data[this.name] = null;
        data[this.isOptedOutFieldName] = false;
        data[this.isInvalidFieldName] = false;

        let primaryIndex = 0;

        addressData.forEach((item, i) => {
            if (item.primary) {
                primaryIndex = i;

                if (item.optOut) {
                    data[this.isOptedOutFieldName] = true;
                }

                if (item.invalid) {
                    data[this.isInvalidFieldName] = true;
                }
            }
        });

        if (addressData.length && primaryIndex > 0) {
            const t = addressData[0];

            addressData[0] = addressData[primaryIndex];
            addressData[primaryIndex] = t;
        }

        if (addressData.length) {
            data[this.name] = addressData[0].phoneNumber;
        } else {
            data[this.isOptedOutFieldName] = null;
            data[this.isInvalidFieldName] = null;
        }

        return data;
    }

    fetchSearch(): Record<string, unknown> | null {
        const type = this.fetchSearchType() || 'startsWith';

        const name = this.isNumeric ?
            this.name + 'Numeric' :
            this.name;

        if (['isEmpty', 'isNotEmpty'].includes(type)) {
            if (type === 'isEmpty') {
                return {
                    type: 'isNull',
                    attribute: name,
                    data: {
                        type: type,
                    },
                };
            }

            return {
                type: 'isNotNull',
                attribute: name,
                data: {
                    type: type,
                },
            };
        }

        let value = this.$element?.val()
            ?.toString()
            ?.trim();

        const originalValue = value;

        if (this.isNumeric && value) {
            value = value.replace(/[^0-9]/g, '');
        }

        if (!value) {
            return null;
        }

        return {
            type: type,
            value: value,
            attribute: name,
            data: {
                type: type,
                value: originalValue,
            },
        };
    }

    protected focusOnInlineEdit() {
        const input = this.element.querySelector<HTMLInputElement>('input.phone-number');

        if (!input) {
            return;
        }

        input.focus({preventScroll: true});
    }

    private switchPhoneProperty(target: HTMLElement) {
        const $target = $(target);
        const $block = $(target).closest('div.phone-number-block');
        const property = $target.data('property-type');
        const $input = $block.find('input.phone-number');

        if (property === 'primary') {
            if (!$target.hasClass('active')) {
                if ($input.val() !== '') {
                    this.$el.find('button.phone-property[data-property-type="primary"]')
                        .removeClass('active').children().addClass('text-muted');

                    $target.addClass('active').children().removeClass('text-muted');
                }
            }

            this.trigger('change');

            return;
        }

        let active = false;

        if ($target.hasClass('active')) {
            $target.removeClass('active').children().addClass('text-muted');
        } else {
            $target.addClass('active').children().removeClass('text-muted');

            active = true;
        }

        if (property === 'optOut') {
            active ?
                $input.addClass('text-strikethrough') :
                $input.removeClass('text-strikethrough');
        }

        if (property === 'invalid') {
            active ?
                $input.addClass('text-danger') :
                $input.removeClass('text-danger');
        }

        this.trigger('change');
    }

    private removePhoneNumberHandler(target: HTMLElement) {
        const $block = $(target).closest('div.phone-number-block');

        this.removePhoneNumber($block);
        this.trigger('change');

        const $last = this.$el.find('.phone-number').last();

        if ($last.length) {
            $last[0].focus({preventScroll: true});
        }
    }

    private inputChangeHandler(target: HTMLElement) {
        const input = target as HTMLInputElement;
        const $block = $(input).closest('div.phone-number-block');

        if (this._itemJustRemoved) {
            return;
        }

        if (input.value === '' && $block.length) {
            this.removePhoneNumber($block);
        } else {
            this.trigger('change');
        }

        this.manageAddButton();
    }

    private inputKeydownHandler(e: KeyboardEvent, target: HTMLInputElement) {
        const key = Espo.Utils.getKeyFromKeyEvent(e);

        const $target = $(target);

        if (key === 'Enter') {
            if (!this.$el.find('[data-action="addPhoneNumber"]').hasClass('disabled')) {
                this.addPhoneNumber();

                e.stopPropagation();
            }

            return;
        }

        if (key === 'Backspace' && $target.val() === '') {
            const $block = $target.closest('div.phone-number-block');

            this._itemJustRemoved = true;
            setTimeout(() => this._itemJustRemoved = false, 100);

            e.stopPropagation();

            this.removePhoneNumber($block);

            setTimeout(() => this.focusOnLast(true), 50);
        }
    }
}

export default PhoneFieldView;
