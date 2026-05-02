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
import MailtoHelper from 'helpers/misc/mailto';
import Autocomplete from 'ui/autocomplete';
import {BaseOptions, BaseParams, BaseViewSchema, FieldValidator} from 'views/fields/base';
import Ui from 'ui';
import Ajax from 'ajax';

/**
 * Parameters.
 */
export interface EmailParams extends BaseParams {
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
export interface EmailOptions extends BaseOptions {}

class EmailFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends EmailOptions = EmailOptions,
    P extends EmailParams = EmailParams,
> extends VarcharFieldView<S, O, P> {

    readonly type: string = 'email'

    protected editTemplate = 'fields/email/edit'
    protected detailTemplate = 'fields/email/detail'
    protected listTemplate = 'fields/email/list'

    protected validations: (FieldValidator | string)[] = [
        'required',
        'emailData',
    ]

    private dataFieldName: string;

    private maxCount: number | null = null;

    private isOptedOutFieldName: string;

    private emailAddressOptedOutByDefault: boolean;

    private isInvalidFieldName: string;

    private erasedPlaceholder: string;

    private itemMaxLength: number;

    private autocompleteMaxCount: number;

    private _itemJustRemoved: boolean;

    protected data(): Record<string, any> {
        let emailAddressData: Record<string, any>[];

        if (this.mode === this.MODE_EDIT) {
            emailAddressData = Espo.Utils.clone(this.model.get(this.dataFieldName));

            if (this.model.isNew() || !this.model.get(this.name)) {
                if (!emailAddressData || !emailAddressData.length) {
                    let optOut: boolean;

                    if (this.model.isNew()) {
                        optOut = this.emailAddressOptedOutByDefault && this.model.entityType !== 'User';
                    } else {
                        optOut = this.model.get(this.isOptedOutFieldName)
                    }

                    emailAddressData = [{
                        emailAddress: this.model.get(this.name) || '',
                        primary: true,
                        optOut: optOut,
                        invalid: false,
                    }];
                }
            }
        } else {
            emailAddressData = this.model.get(this.dataFieldName) || false;
        }

        if ((!emailAddressData || emailAddressData.length === 0) && this.model.get(this.name)) {
            emailAddressData = [{
                emailAddress: this.model.get(this.name),
                primary: true,
                optOut: false,
                invalid: false,
            }];
        }

        if (emailAddressData) {
            emailAddressData = Espo.Utils.cloneDeep(emailAddressData);

            emailAddressData.forEach(item => {
                const address = item.emailAddress || '';

                item.erased = address.indexOf(this.erasedPlaceholder) === 0;
                item.lineThrough = item.optOut || item.invalid;
            });
        }

        const data = {
            ...super.data(),
            emailAddressData: emailAddressData,
        } as Record<string, any>;

        if (this.isReadMode()) {
            data.isOptedOut = this.model.get(this.isOptedOutFieldName);
            data.isInvalid = this.model.get(this.isInvalidFieldName);

            if (this.model.get(this.name)) {
                data.isErased = this.model.get(this.name).indexOf(this.erasedPlaceholder) === 0
            }

            data.valueIsSet = this.model.has(this.name);
        }

        data.itemMaxLength = this.itemMaxLength;
        data.onlyPrimary = this.params.onlyPrimary;

        return data;
    }

    private getAutocompleteMaxCount(): number {
        if (this.autocompleteMaxCount) {
            return this.autocompleteMaxCount;
        }

        return this.getConfig().get('recordsPerPage') as number;
    }

    // noinspection JSUnusedGlobalSymbols
    protected validateEmailData(): boolean {
        const data = this.model.get(this.dataFieldName) as Record<string, unknown>[] | undefined;

        if (!data || !data.length) {
            return false;
        }

        const addressList: string[] = [];

        const regExp = new RegExp(
            /^[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+(?:\.[-!#$%&'*+/=?^_`{|}~A-Za-z0-9]+)*/.source +
            /@([A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?\.)+[A-Za-z0-9][A-Za-z0-9-]*[A-Za-z0-9]/.source
        );

        let notValid = false;

        data.forEach((row, i) => {
            const address = (row.emailAddress || '') as string;
            const addressLowerCase = String(address).toLowerCase();

            if (!regExp.test(addressLowerCase) && address.indexOf(this.erasedPlaceholder) !== 0) {
                const msg = this.translate('fieldShouldBeEmail', 'messages')
                    .replace('{field}', this.getLabelText());

                this.reRender();

                this.showValidationMessage(msg, 'div.email-address-block:nth-child(' + (i + 1)
                    .toString() + ') input');

                notValid = true;

                return false;
            }

            if (addressList.includes(addressLowerCase)) {
                const msg = this.translate('fieldValueDuplicate', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg, 'div.email-address-block:nth-child(' + (i + 1)
                    .toString() + ') input');

                notValid = true;

                return false;
            }

            addressList.push(addressLowerCase);
        });

        if (notValid) {
            return true;
        }

        return false;
    }

    private focusOnLast(cursorAtEnd?: boolean) {
        const $item = this.$el.find('input.form-control').last();

        $item.focus();

        if (cursorAtEnd && $item[0]) {
            // Not supported for email inputs.
            // $item[0].setSelectionRange($item[0].value.length, $item[0].value.length);
        }
    }

    private removeEmailAddress($block: any) {
        if ($block.parent().children().length === 1) {
            $block.find('input.email-address').val('');
        } else {
            this.removeEmailAddressBlock($block);
        }

        this.trigger('change');
    }

    private addEmailAddress() {
        const data = Espo.Utils.cloneDeep(this.fetchEmailAddressData());

        const o = {
            emailAddress: '',
            primary: !data.length,
            optOut: this.emailAddressOptedOutByDefault,
            invalid: false,
            lower: '',
        };

        data.push(o);

        this.model.set(this.dataFieldName, data, {silent: true});

        this.reRender()
            .then(() => this.focusOnLast());
    }

    private removeEmailAddressBlock($block: any) {
        let changePrimary = false;

        if ($block.find('button[data-property-type="primary"]').hasClass('active')) {
            changePrimary = true;
        }

        $block.remove();

        if (changePrimary) {
            this.$el.find('button[data-property-type="primary"]')
                .first().addClass('active').children().removeClass('text-muted');
        }

        this.manageButtonsVisibility();
        this.manageAddButton();
    }

    protected setup() {
        this.addActionHandler('mailTo', (_e, target) => this.mailTo(target.dataset.emailAddress as string));
        this.addActionHandler('switchEmailProperty', (_e, target) => this.switchEmailProperty(target));
        this.addActionHandler('removeEmailAddress', (_e, target) => this.removeEmailAddressHandler(target));
        this.addHandler('change', 'input.email-address', (_e, target) => this.inputChangeHandler(target));
        this.addHandler('keypress', 'input.email-address', () => this.manageAddButton());
        this.addHandler('paste', 'input.email-address', () => setTimeout(() => this.manageAddButton(), 10));
        this.addActionHandler('addEmailAddress', () => this.addEmailAddress());
        this.addHandler('keydown', 'input.email-address', (e, target) => this.inputKeydownHandler(e, target));

        this.dataFieldName = `${this.name}Data`;
        this.isOptedOutFieldName = `${this.name}IsOptedOut`;
        this.isInvalidFieldName = `${this.name}IsInvalid`;

        this.erasedPlaceholder = 'ERASED:';

        this.emailAddressOptedOutByDefault = this.getConfig().get('emailAddressIsOptedOutByDefault');
        this.maxCount = this.getConfig().get('emailAddressMaxCount');

        this.itemMaxLength = this.getMetadata()
            .get(['entityDefs', 'EmailAddress', 'fields', 'name', 'maxLength']) || 255;

        this.validations.push(() => this.validateMaxCount());
    }

    protected afterRender() {
        super.afterRender();

        this.manageButtonsVisibility();
        this.manageAddButton();

        if (this.mode === this.MODE_SEARCH) {
            const autocomplete = new Autocomplete(this.$element?.get(0) as HTMLInputElement, {
                name: this.name,
                autoSelectFirst: true,
                triggerSelectOnValidInput: true,
                focusOnSelect: true,
                minChars: 1,
                forceHide: true,
                handleFocusMode: 1,
                onSelect: (item: any) => {
                    this.$element?.val(item.emailAddress);
                },
                formatResult: (item: any) => {
                    return this.getHelper().escapeString(item.name) + ' &#60;' +
                        this.getHelper().escapeString(item.id) + '&#62;';
                },
                lookupFunction: async (query: string) => {
                    const response: any[] = await Ajax.getRequest('EmailAddress/search', {
                        q: query,
                        maxSize: this.getAutocompleteMaxCount(),
                        entityType: this.entityType,
                    });

                    return response.map(item => {
                        return {
                            id: item.emailAddress,
                            name: item.entityName,
                            emailAddress: item.emailAddress,
                            entityId: item.entityId,
                            entityName: item.entityName,
                            entityType: item.entityType,
                            data: item.emailAddress,
                            value: item.emailAddress,
                        };
                    });
                },
            });

            this.once('render remove', () => autocomplete.dispose());
        }
    }

    validateRequired(): boolean {
        if (this.isRequired()) {
            if (!this.model.get(this.name)) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg, 'div.email-address-block:nth-child(1) input');

                return true;
            }
        }

        return false;
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

        this.showValidationMessage(msg, 'div.email-address-block:last-child input');

        return true;
    }

    private manageAddButton() {
        const $input = this.$el.find('input.email-address');
        let c = 0;

        $input.each((_i: number, input: HTMLInputElement) => {
            if (input.value !== '') {
                c ++;
            }
        });

        if (c === $input.length) {
            this.$el.find('[data-action="addEmailAddress"]')
                .removeClass('disabled')
                .removeAttr('disabled');

            return;
        }

        this.$el.find('[data-action="addEmailAddress"]')
            .addClass('disabled')
            .attr('disabled', 'disabled');
    }

    private manageButtonsVisibility() {
        const $primary = this.$el.find('button[data-property-type="primary"]');
        const $remove = this.$el.find('button[data-action="removeEmailAddress"]');

        if ($primary.length > 1) {
            $primary.removeClass('hidden');
            $remove.removeClass('hidden');
        } else {
            $primary.addClass('hidden');
            $remove.addClass('hidden');
        }
    }

    private mailTo(emailAddress: string) {
        const attributes = {
            status: 'Draft',
            to: emailAddress,
        } as Record<string, any>;

        const scope = this.model.entityType;

        switch (scope) {
            case 'Account':
            case 'Lead':
                attributes.parentType = scope;
                attributes.parentName = this.model.get('name');
                attributes.parentId = this.model.id;

                break;

            case 'Contact':
                if (this.getConfig().get('b2cMode')) {
                    attributes.parentType = 'Contact';
                    attributes.parentName = this.model.get('name');
                    attributes.parentId = this.model.id;
                } else {
                    if (this.model.get('accountId')) {
                        attributes.parentType = 'Account';
                        attributes.parentName = this.model.get('accountName');
                        attributes.parentId = this.model.get('accountId');
                    }
                }

                break;
        }

        const parentModel = this.model.collection?.parentModel;

        if (parentModel?.entityType && this.checkParentTypeAvailability(parentModel.entityType)) {
            attributes.parentType = parentModel.entityType;
            attributes.parentId = parentModel.id;
            attributes.parentName = parentModel.get('name');
        }

        if (!attributes.parentId) {
            if (this.model.entityType && this.checkParentTypeAvailability(this.model.entityType)) {
                attributes.parentType = this.model.entityType;
                attributes.parentId = this.model.id;
                attributes.parentName = this.model.get('name');
            }
        } else {
            if (attributes.parentType && !this.checkParentTypeAvailability(attributes.parentType)) {
                attributes.parentType = null;
                attributes.parentId = null;
                attributes.parentName = null;
            }
        }

        if (this.model.entityType && ['Contact', 'Lead', 'Account'].includes(this.model.entityType)) {
            attributes.nameHash = {};
            attributes.nameHash[emailAddress] = this.model.get('name');
        }

        const helper = new MailtoHelper(this.getConfig(), this.getPreferences(), this.getAcl());

        if (helper.toUse()) {
            document.location.href = helper.composeLink(attributes);

            return;
        }

        const viewName = 'views/modals/compose-email';

        Ui.notifyWait();

        this.createView('quickCreate', viewName, {
            attributes: attributes,
        }).then(view => {
            view.render();

            Ui.notify();
        });
    }

    private checkParentTypeAvailability(parentType: string): boolean {
        const entityTypeList = (this.getMetadata()
            .get(['entityDefs', 'Email', 'fields', 'parent', 'entityList']) ?? []) as string[];

        return entityTypeList.includes(parentType);
    }

    private inputKeydownHandler(e: Event, target: HTMLElement) {
        const key = Espo.Utils.getKeyFromKeyEvent(e as KeyboardEvent);

        const $target = $(target);

        if (key === 'Enter') {
            if (!this.$el.find('[data-action="addEmailAddress"]').hasClass('disabled')) {
                this.addEmailAddress();

                e.stopPropagation();
            }

            return;
        }

        if (key === 'Backspace' && $target.val() === '') {
            const $block = $target.closest('div.email-address-block');

            this._itemJustRemoved = true;
            setTimeout(() => this._itemJustRemoved = false, 100);

            e.stopPropagation();

            this.removeEmailAddress($block);

            setTimeout(() => this.focusOnLast(true), 50);
        }
    }

    private inputChangeHandler(target: HTMLElement) {
        const input = target as HTMLInputElement;

        const $block = $(target).closest('div.email-address-block');

        if (this._itemJustRemoved) {
            return;
        }

        if (input.value === '' && $block.length) {
            this.removeEmailAddress($block);
        } else {
            this.trigger('change');
        }

        this.trigger('change');

        this.manageAddButton();
    }

    private removeEmailAddressHandler(target: HTMLElement) {
        const $block = $(target).closest('div.email-address-block');

        this.removeEmailAddress($block);

        const $last = this.$el.find('.email-address').last();

        if ($last.length) {
            // noinspection JSUnresolvedReference
            $last[0].focus({preventScroll: true});
        }
    }

    private switchEmailProperty(target: HTMLElement) {
        const $target = $(target);
        const $block = $(target).closest('div.email-address-block');
        const property = $target.data('property-type');
        const $input = $block.find('input.email-address');

        if (property === 'primary') {
            if (!$target.hasClass('active')) {
                if ($input.val() !== '') {
                    this.$el.find('button.email-property[data-property-type="primary"]')
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

    /**
     * @return {{
     *     emailAddress: string,
     *     primary: boolean,
     *     optOut: boolean,
     *     invalid: boolean,
     *     lower: string,
     * }[]}
     */
    private fetchEmailAddressData(): {
        emailAddress: string;
        primary: boolean;
        optOut: boolean;
        invalid: boolean;
        lower: string;
    }[] {
        const data: any[] = [];

        const $list = this.$el.find('div.email-address-block');

        if ($list.length) {
            $list.each((_i: number, itemElement: HTMLElement) => {
                const row = {} as any;
                const $d = $(itemElement);

                row.emailAddress = ($d.find('input.email-address').val() as string).trim();

                if (row.emailAddress === '') {
                    return;
                }

                row.primary = $d.find('button[data-property-type="primary"]').hasClass('active');
                row.optOut = $d.find('button[data-property-type="optOut"]').hasClass('active');
                row.invalid = $d.find('button[data-property-type="invalid"]').hasClass('active');
                row.lower = row.emailAddress.toLowerCase();

                data.push(row);
            });
        }

        return data;
    }

    fetch(): Record<string, unknown> {
        const data = {} as any;

        const addressData = this.fetchEmailAddressData();

        if (this.params.onlyPrimary) {
            if (addressData.length > 0) {
                data[this.name] = addressData[0].emailAddress;

                data[this.dataFieldName] = [
                    {
                        emailAddress: addressData[0].emailAddress,
                        lower: addressData[0].lower,
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
            data[this.name] = addressData[0].emailAddress;
        } else {
            data[this.isOptedOutFieldName] = null;
            data[this.isInvalidFieldName] = null;
        }

        return data;
    }

    fetchSearch(): Record<string, unknown> | null {
        const type = this.fetchSearchType();

        if (['isEmpty', 'isNotEmpty'].includes(type)) {
            return {
                type: type === 'isEmpty' ? 'isNull' : 'isNotNull',
                attribute: this.name,
                data: {
                    type: type,
                },
            };
        }

        return super.fetchSearch();
    }
}

export default EmailFieldView;
